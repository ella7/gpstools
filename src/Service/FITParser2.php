<?php

namespace App\Service;

use App\Model\FIT\Message;
use App\Model\FIT\DefinitionMessage;
use App\Model\FIT\DataMessage;
use App\Model\FIT\Field;
use App\Model\FIT\FieldDefinition;
use App\Model\FIT\GlobalProfile;
use PhpBinaryReader\BinaryReader;
use PhpBinaryReader\Endian;
use function Symfony\Component\String\u;
use Symfony\Component\Stopwatch\Stopwatch;

class FITParser2 {

  const MESSAGE_TYPE_DEFINITION = 1;
  const MESSAGE_TYPE_DATA = 0;

  protected $reader;
  protected $file_header;
  protected $local_definitions;

  public function __construct(string $file_path)
  {
    if (!file_exists($file_path)) {
      throw new \Exception('File \''.$file_path.'\' does not exist!');
    }

    $this->reader = new BinaryReader(
      file_get_contents($file_path)
    );
  }

  public function parseFile()
  {
		$this->readFileHeader();
	  $this->readRecords();
	}

  protected function readFileHeader()
  {
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
		while ($this->reader->getPosition() - $this->file_header['header_size'] < $this->file_header['data_size']) {
			$this->readRecord();
		}
	}

  protected function readRecord()
  {
    $position = $this->reader->getPosition();
    $record_header = $this->readRecordHeader();
    $local_message_type = $record_header['local_message_type'];
    echo "### Reading Record: ###\n";
    echo "# Looking for record header at offset: $position\n";
    dump($record_header);
    echo "### ### ### ### ### ###\n";

    if($record_header['message_type'] == self::MESSAGE_TYPE_DEFINITION) {
			$definition = $this->readDefinitionMessage();
      dump($definition);
      exit;
      $this->local_definitions[$local_message_type] = $definition;
      return $definition;
		}

    if($record_header['message_type'] == self::MESSAGE_TYPE_DATA) {
      $definition = $this->local_definitions[$local_message_type];
      $data = $this->readDataMessage($definition);
      // dump($data);
      return $data;
      exit();
		}
  }

  protected function readRecordHeader()
  {
    $byte = $this->reader->readUInt8();
    $bits = self::bitsFromByte($byte);
//    echo "# Record header byte and bits: #\n";
//    dump($byte, $bits);

    $record_header['compressed_timestamp_header'] = $bits[7];
    if (!$record_header['compressed_timestamp_header']) {
			//normal header
			$record_header = [
				'compressed_timestamp_header'	=> $bits[7],
				'message_type'			          => $bits[6],
				'reserved1'				            => $bits[5],
				'reserved2'				            => $bits[4],
				'local_message_type'	        => bindec(strrev(substr($bits, 0, 4)))
			];
		}
		else {
			//compressed timestamp header
			$record_header = array(
				'compressed_timestamp_header'	=> $bits[7],
				'message_type'			          => false,
				'local_message_type'	        => bindec(strrev(substr($bits, 5, 2))),
				'time_offset'			            => bindec(strrev(substr($bits, 0, 5))),
			);
		}

    return $record_header;
  }

  protected function readDefinitionMessage()
  {
    $definition = [
			'reserved'			=> $this->reader->readUInt8(),
			'architecture'	=> $this->reader->readUInt8(),	//Architecture Type 0: Little Endian 1: Big Endian
		];
		$big_endian = $definition['architecture'] === 1;
		$definition += [
			'global_message_number' => $this->reader->readUInt16($definition['architecture']),
			'num_fields'		        => $this->reader->readUInt8(),
			'fields'			          => [],
		];

		for ($i = 0; $i < $definition['num_fields']; $i++) {
			$field = [
				'field_number'	          => $this->reader->readUInt8(),
				'size'				            => $this->reader->readUInt8(),
				'base_type'			          => $this->reader->readUInt8(),
			];
			$definition['fields'][] = $field;
		}
    dump($definition);
		return new DefinitionMessage($definition);
  }

  protected function readDataMessage($definition_message)
  {
    echo "this is where we are \n";
    dump($definition_message);
    foreach ($definition_message->getFields() as $field_definition) {
      $field_data = $this->readFieldData($field_definition);
    }
  }

  protected function readFieldData($field_definition)
  {
    dump($field_definition);
    exit;
    /*
    switch($field_def['base_type']['base_type_definition']['name']) {
					case 'string'	: $value = $this->reader->readString8($field_def['size']); break;
					case 'sint8'	: $value = $this->reader->readInt8(); break;
					case 'enum'		:
					case 'uint8z'	:
					case 'uint8'	: $value = $this->reader->readUInt8(); break;
					case 'sint16'	: $value = $big_endian ? $this->reader->readInt16BE() : $this->reader->readInt16LE(); break;
					case 'uint16z'	:
					case 'uint16'	: $value = $big_endian ? $this->reader->readUInt16BE() : $this->reader->readUInt16LE(); break;
					case 'sint32'	: $value = $big_endian ? $this->reader->readInt32BE() : $this->reader->readInt32LE(); break;
					case 'uint32z'	:
					case 'uint32'	: $value = $big_endian ? $this->reader->readUInt32BE() : $this->reader->readUInt32LE(); break;
					case 'float32'	: $value = $big_endian ? $this->reader->readFloatBE() : $this->reader->readFloatLE(); break;
					case 'float64'	: $value = $big_endian ? $this->reader->readDoubleBE() : $this->reader->readDoubleLE(); break;
					case 'byte'		:
					default			: $value = $this->reader->read($field_def['size']);
				}
      */
  }

  public static function bitsFromByte($byte)
  {
    $bits = decbin($byte);
    return strrev(str_pad($bits, 8, 0, STR_PAD_LEFT));
  }

}
