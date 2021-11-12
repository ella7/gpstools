<?php

namespace App\Service;

use App\Model\FIT\Message;
use App\Model\FIT\DefinitionMessage;
use App\Model\FIT\DataMessage;
use App\Model\FIT\Field;
use App\Model\FIT\FieldDefinition;
use App\Model\FIT\File;
use PhpBinaryReader\BinaryReader;
use PhpBinaryReader\Endian;
use function Symfony\Component\String\u;
use Symfony\Component\Stopwatch\Stopwatch;
use Psr\Log\LoggerAwareInterface;

class FITParser implements LoggerAwareInterface
{

  const MESSAGE_TYPE_DEFINITION = 1;
  const MESSAGE_TYPE_DATA = 0;

  protected $reader;
  protected $file_header;
  protected $local_definitions = [];
  protected $component_buffer = [];
  protected $log;

  protected $message_limit; // max number of records/messages to parse

  // TODO: remove requirement to have a file path from the constructor so this service can be
  // more easily injected into other parts of the code. Make logger injection automagic.
  public function __construct(string $file_path, $message_limit = -1)
  {
    if (!file_exists($file_path)) {
      throw new \Exception('File \''.$file_path.'\' does not exist!');
    }
    $this->reader = new BinaryReader(file_get_contents($file_path));
    $this->message_limit = $message_limit;
  }

  public function parseFile()
  {
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    return new File([
      'header'    => $this->readFileHeader(),
      'messages'  => $this->readRecords()
    ]);
	}

  public function readFileHeader()
  {
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    $this->file_header = [
      'header_size'		     => $this->reader->readUInt8(),		   // FIT_FILE_HDR_SIZE (size of this structure)
			'protocol_version'	 => $this->reader->readUInt8(),		   // FIT_PROTOCOL_VERSION
			'profile_version'	   => $this->reader->readUInt16(),		 // FIT_PROFILE_VERSION
			'data_size'			     => $this->reader->readUInt32(),  	 // Does not include file header or crc.  Little endian format.
			'data_type'			     => $this->reader->readString(4),	   // ".FIT"
    ];

    if($this->file_header['header_size'] === 14) {
      $this->file_header['crc'] = $this->reader->readUInt16();
    }
		return $this->file_header;
  }

  protected function readRecords()
  {
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    $records = [];
    while ($this->continueReadingRecords(count($records))) {
			$records[] = $this->readRecord();
		}
    return $records;
	}

  // added primarily to make the code more readible - simple, but long logic expression
  protected function continueReadingRecords($records_read): bool
  {
    $header_and_data_size = $this->file_header['header_size'] + $this->file_header['data_size'];
    return (
      $this->reader->getPosition() < $header_and_data_size
      && $records_read < $this->message_limit
    );
  }

  public function readRecord()
  {
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    list('local_number' => $local_number, 'message_type' => $message_type) = $this->readRecordHeader();

    if($message_type == self::MESSAGE_TYPE_DEFINITION) {
      $definition = $this->readDefinitionMessage();
      $this->addLocalDefinition($definition, $local_number);
      return $definition;
		}

    if($message_type == self::MESSAGE_TYPE_DATA) {
      $definition = $this->getLocalDefinition($local_number);
      $data = $this->readDataMessage($definition);
      $data->evaluateSubfields();
      $data->addComponents($this->flushComponentBuffer());
      return $data;
		}
  }

  protected function readRecordHeader()
  {
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    $byte = $this->reader->readUInt8();
    $bits = self::bitsFromByte($byte);
    // echo "# Record header byte and bits: #\n";
    // dump($byte, $bits);

    $record_header['compressed_timestamp_header'] = $bits[7];
    if (!$record_header['compressed_timestamp_header']) {
			//normal header
			$record_header = [
				'compressed_timestamp_header'	=> $bits[7],
				'message_type'			          => $bits[6],
				'reserved1'				            => $bits[5],
				'reserved2'				            => $bits[4],
				'local_number'	              => bindec(strrev(substr($bits, 0, 4)))
			];
		}
		else {
			//compressed timestamp header
			$record_header = array(
				'compressed_timestamp_header'	=> $bits[7],
				'message_type'			          => false,
				'local_number'	              => bindec(strrev(substr($bits, 5, 2))),
				'time_offset'			            => bindec(strrev(substr($bits, 0, 5))),
			);
		}

    return $record_header;
  }

  protected function readDefinitionMessage()
  {
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    $definition = [
			'reserved'			=> $this->reader->readUInt8(),
			'architecture'	=> $this->reader->readUInt8(),	//Architecture Type 0 = Little Endian | 1 = Big Endian
		];
    $endian = ($definition['architecture'] === Endian::BIG) ? Endian::BIG : Endian::LITTLE;
		$this->reader->setEndian($endian);
		$definition += [
			'global_number' => $this->reader->readUInt16(),
			'num_fields'	  => $this->reader->readUInt8(),
			'fields'			  => [],
		];

		for ($i = 0; $i < $definition['num_fields']; $i++) {
			$field_properties = [
				'def_num'	  => $this->reader->readUInt8(),
				'size'			=> $this->reader->readUInt8(),
				'base_type' => $this->reader->readUInt8(),
			];

			$definition['fields'][] = FieldDefinition::initFromGlobalProfile(
        $definition['global_number'],
        $field_properties
      );
		}
		return new DefinitionMessage($definition);
  }

  protected function readDataMessage($definition_message)
  {
    // TODO: Handle endianness
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    foreach ($definition_message->getFields() as $field_definition) {
      $field_data = $this->readFieldData($field_definition);
      $fields[] = new Field([
        'name'      => $field_definition->getName(),
        'value'     => $field_data,
        'units'     => $field_definition->getUnits(),
        'def_num'   => $field_definition->getNumber()
      ]);
    }
    // TODO: change Field constructor so you just hav to pass the field_definition and the 'value' OR create a factory-like function
    // TODO: add DataMessageBuilder or something to DefinitionMessage - construct a DataMessage from a DefinitionMessage and raw fields data
    return new DataMessage([
      'type' => Message::MESSAGE_TYPE_DATA,
      'local_number' => $definition_message->getLocalNumber(),
      'global_number' => $definition_message->getGlobalNumber(),
      'name' => $definition_message->getName(),
      'fields' => $fields,
      'definition' => $definition_message
    ]);
  }

  protected static function applyScaleAndOffset($value, $field_definition)
  {
    if($field_definition->getScale()){
      $value = (float) $value/$field_definition->getScale();
    }
    if($field_definition->getOffset()){
      $value = $value - $field_definition->getOffset();
    }
    return $value;
  }

  public function addLocalDefinition(&$definition, $local_number)
  {
    $definition->setLocalNumber($local_number);
    $this->local_definitions[$local_number] = $definition;
  }

  protected function getLocalDefinition($local_number)
  {
    if(!array_key_exists($local_number, $this->local_definitions)){
      throw new \Exception("No local definition has been set for local_number: $local_number", 1);
    }
    return $this->local_definitions[$local_number];
  }

  protected function readFieldData($field_definition)
  {
    $starting_position = $this->reader->getPosition();

    // read each value for fields containing multiple values
    for ($i=0; $i < $field_definition->getNumberOfValues(); $i++) {
      $value = $this->readBytesAs($field_definition->getBaseTypeName());
      if($value !== $field_definition->getInvalidValue()){
        $value = self::applyScaleAndOffset($value, $field_definition);
      }
      $values[] = $value;
    }

    // expand components
    if($field_definition->hasComponents() && $value !== $field_definition->getInvalidValue()){ // this will not work for multivalue fields
      $this->reader->setPosition($starting_position);
      foreach ($field_definition->getComponents() as $component_def) {
        $this->readComponent($component_def);
      }
    }

    return (count($values) > 1) ? $values : $value;
  }

  /**
   * I'm unhappy with the current flow. All other similar functions read the
   * element in question, and then return an object representation of what was
   * read. Because expanded components are a bit different, it doesn't make
   * sense to return them here (I don't think). I think they'll need to get
   * put in some expanded component buffer and then added to the message later.
   * To be clear, the specific problem is that they are being read before the
   * DataMessage has been created.
   *
   * @param  ComponentDefinition  $component_def
   */
  protected function readComponent($component_def)
  {
    $component = clone $component_def;
    $component->setValue(
      $this->applyScaleAndOffset(
        $this->reader->readUBits($component_def->getBits()),
        $component_def
      )
    );
    $this->component_buffer[] = $component;
  }

  protected function readBytesAs($type, $size = null)
  {
    switch($type) {
			case 'string'	: return $this->reader->readString8($size); break;
			case 'sint8'	: return $this->reader->readInt8(); break;
			case 'enum'		:
			case 'uint8z'	:
			case 'uint8'	: return $this->reader->readUInt8(); break;
			case 'sint16'	: return $this->reader->readInt16(); break;
			case 'uint16z':
			case 'uint16'	: return $this->reader->readUInt16(); break;
			case 'sint32'	: return $this->reader->readInt32(); break;
			case 'uint32z':
			case 'uint32'	: return $this->reader->readUInt32(); break;
			case 'float32': return $this->reader->readFloat(); break;
			case 'float64': return $this->reader->readDouble(); break;
			case 'byte'		:
			default			  : return $this->reader->read($size);
		}
  }

  public static function bitsFromByte($byte)
  {
    $bits = decbin($byte);
    return strrev(str_pad($bits, 8, 0, STR_PAD_LEFT));
  }

  public function setLogger($logger)
  {
    $this->log = $logger;
  }

  public function useLogger()
  {
    $this->log->warning('this is a warning from inside the FitParser');
  }

  public function dumpLogger()
  {
    dump($this->log);
  }

  public function setMessageLimit($limit)
  {
    $this->message_limit = $limit;
  }

  public function flushComponentBuffer()
  {
    $components = $this->component_buffer;
    $this->component_buffer = [];
    return $components;
  }

  public function setPosition(int $position)
  {
    $this->reader->setPosition($position);
  }

}
