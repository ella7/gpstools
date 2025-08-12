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
  /**
   * Construct a FITParser for the given binary FIT file and optional message limit.
   *
   * Reads the file contents into a BinaryReader and sets an optional maximum number of messages to parse.
   *
   * @param string $file_path Path to an existing FIT file. Must exist or an exception is thrown.
   * @param int|null $message_limit Maximum number of records/messages to parse; -1 (default) means no limit.
   * @throws \Exception If the file at $file_path does not exist.
   */
  public function __construct(string $file_path, $message_limit = -1)
  {
    if (!file_exists($file_path)) {
      throw new \Exception('File \''.$file_path.'\' does not exist!');
    }
    $this->reader = new BinaryReader(file_get_contents($file_path));
    $this->message_limit = $message_limit;
  }

  /**
	 * Parse the opened FIT file and return a File model containing header and messages.
	 *
	 * Reads the binary FIT file header and then sequentially reads all records/messages
	 * according to the parser's current state and message limit, returning a File
	 * object with 'header' (array) and 'messages' (array of Message objects).
	 *
	 * @return File The parsed File model containing 'header' and 'messages'.
	 */
	public function parseFile()
  {
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    return new File([
      'header'    => $this->readFileHeader(),
      'messages'  => $this->readRecords()
    ]);
	}

  /**
   * Read and parse the FIT file header from the current reader position.
   *
   * Reads the standard FIT header fields and stores them on $this->file_header.
   * If the header size indicates the presence of a CRC (14), the CRC field is
   * read and included.
   *
   * @return array{
   *   header_size:int,
   *   protocol_version:int,
   *   profile_version:int,
   *   data_size:int,
   *   data_type:string,
   *   crc?:int
   * } Parsed header fields. `crc` is present only when `header_size === 14`.
   */
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

  /**
	 * Read all records from the current FIT data section.
	 *
	 * Iteratively reads records (definition or data messages) until parsing should stop
	 * according to continueReadingRecords(). Each readRecord() result is appended to
	 * the returned list in the order encountered.
	 *
	 * @return array<int, DefinitionMessage|DataMessage> Array of parsed records (definition or data messages).
	 */
	protected function readRecords()
  {
    $this->log->info(__METHOD__, ['position' => $this->reader->getPosition()]);
    $records = [];
    while ($this->continueReadingRecords(count($records))) {
			$records[] = $this->readRecord();
		}
    return $records;
	}

  /**
   * Determine whether parsing should continue reading records.
   *
   * Returns true while the reader position is before the end of the file's header+data region
   * and the parsed record count has not exceeded the configured message limit (or when no limit is set).
   *
   * @param int $records_read Number of records already read.
   * @return bool True if parsing should continue, false otherwise.
   */
  protected function continueReadingRecords($records_read): bool
  {
    $header_and_data_size = $this->file_header['header_size'] + $this->file_header['data_size'];
    return (
      $this->reader->getPosition() < $header_and_data_size
      && ($records_read < $this->message_limit || $this->message_limit === -1)
    );
  }

  /**
   * Read and parse a single FIT record from the current reader position.
   *
   * Returns a DefinitionMessage when the record is a definition; the definition is stored
   * in the parser's local definitions map. Returns a DataMessage when the record is data;
   * the data message is parsed using the corresponding local definition, subfields are
   * evaluated, and any buffered component fields are attached to the returned message.
   *
   * @return \App\Model\DefinitionMessage|\App\Model\DataMessage
   * @throws \Exception If a data record is encountered but no local definition exists for its local number.
   */
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

  /**
   * Read and decode one FIT record header from the current reader position.
   *
   * Reads a single header byte, interprets its bit fields and returns an associative
   * array describing either a normal header or a compressed-timestamp header.
   * This advances the underlying reader by one byte.
   *
   * Returned array keys:
   * - compressed_timestamp_header (bool): true when header is a compressed-timestamp header.
   * For a normal header (compressed_timestamp_header === false):
   * - message_type (int|bool): message type bit (0 = data, 1 = definition).
   * - reserved1 (int): reserved bit.
   * - reserved2 (int): reserved bit.
   * - local_number (int): local message number (0..15).
   * For a compressed-timestamp header (compressed_timestamp_header === true):
   * - message_type (bool): always false for compressed headers.
   * - local_number (int): local message number (0..3).
   * - time_offset (int): 5-bit time offset value.
   *
   * @return array Header fields decoded from the next header byte.
   */
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

  /**
   * Read and parse a FIT definition message from the current reader position.
   *
   * Reads the definition header (reserved byte and architecture), sets the reader endianness
   * according to the architecture (0 = little-endian, 1 = big-endian), then reads the
   * definition's global message number, the number of field definitions, and each field's
   * properties. Each field is converted to a FieldDefinition via FieldDefinition::initFromGlobalProfile.
   *
   * Side effect: updates the reader's endianness to match the definition's architecture.
   *
   * @return DefinitionMessage Parsed definition message containing the global number and field definitions.
   */
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

  /**
   * Read a data record according to a DefinitionMessage and return a populated DataMessage.
   *
   * Iterates the field definitions contained in $definition_message, reads each field's value(s)
   * from the underlying reader, applies any field-level transformations (scale/offset, components)
   * via readFieldData(), and constructs Field objects. Returns a DataMessage containing the
   * resulting fields plus metadata (type, local/global numbers, name and the original definition).
   *
   * @param DefinitionMessage $definition_message Definition message that describes the layout and metadata for the data record to read.
   * @return DataMessage A DataMessage populated with Field objects and referencing the original definition.
   */
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

  /**
   * Apply the field definition's scale and offset to a raw value.
   *
   * If the field definition provides a scale the value is divided by it (casting to float).
   * If the field definition provides an offset it is subtracted from the value.
   *
   * @param mixed $value Raw value read from the stream.
   * @param FieldDefinition $field_definition Definition containing optional scale and offset.
   * @return mixed The adjusted value (float when scaled, otherwise original type). 
   */
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

  /**
   * Register a definition message for a given local number.
   *
   * Sets the definition's local number on the provided DefinitionMessage and stores
   * it in the parser's local definition map so subsequent data messages can use it.
   *
   * @param \App\Model\DefinitionMessage $definition DefinitionMessage to register (modified in-place).
   * @param int $local_number Local definition index used to look up this definition.
   */
  public function addLocalDefinition(&$definition, $local_number)
  {
    $definition->setLocalNumber($local_number);
    $this->local_definitions[$local_number] = $definition;
  }

  /**
   * Retrieve the DefinitionMessage previously registered for a given local number.
   *
   * Returns the definition associated with the provided local definition index.
   *
   * @param int $local_number Local definition index used in record headers.
   * @return \App\Model\DefinitionMessage The corresponding definition message.
   * @throws \Exception If no definition exists for the provided local number.
   */
  protected function getLocalDefinition($local_number)
  {
    if(!array_key_exists($local_number, $this->local_definitions)){
      throw new \Exception("No local definition has been set for local_number: $local_number", 1);
    }
    return $this->local_definitions[$local_number];
  }

  /**
   * Reads one field's raw value(s) from the stream, applies scale/offset, and expands any components.
   *
   * Reads the number of values indicated by the supplied field definition using the parser's reader,
   * applies scale and offset to each non-invalid value, and returns either a single value or an array
   * of values (when the field contains multiple values).
   *
   * If the field definition declares components and the final read value is not the field's invalid
   * value, the reader position is reset to the start of the field and each component is read via
   * readComponent(). Components are appended to the parser's component buffer. Note: component
   * expansion here does not support multi-value fields (the code only checks the last read value).
   *
   * @param object $field_definition Field definition object (provides getNumberOfValues(), getBaseTypeName(),
   *                                getInvalidValue(), hasComponents(), getComponents()). 
   * @return mixed|array The field value (scalar) or an array of values for multi-valued fields.
   */
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
   * Read and expand a component value from the bit stream and append it to the internal component buffer.
   *
   * The given ComponentDefinition is cloned, its value is read as an unsigned bit-packed field
   * (using the definition's bit length), adjusted by the definition's scale/offset, and stored
   * in the parser's component buffer for later attachment to a DataMessage.
   *
   * @param ComponentDefinition $component_def Component field definition used to read and decode the component value.
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

  /**
   * Read a value from the underlying BinaryReader according to a FIT base type name.
   *
   * Given a FIT base type identifier in $type, this selects the appropriate reader
   * method and returns the read value. For 'string' and the default 'byte' case,
   * $size (when provided) controls how many bytes are read; for numeric and floating
   * types the reader's fixed-size methods are used.
   *
   * @param string $type FIT base type name (e.g. 'string', 'sint8', 'uint16', 'float32', 'byte', etc.)
   * @param int|null $size Optional byte length used for variable-size types (strings or raw bytes)
   * @return mixed The value read from the reader; type depends on $type (string, int, float, or raw bytes)
   */
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

  /**
   * Convert a byte value to an 8-character bit string with least-significant bit first.
   *
   * Returns an 8-character string representation of $byte in binary, padded with leading zeros
   * and reversed so the bit at index 0 is the least-significant bit (LSB).
   *
   * @param int $byte Integer byte value (0-255).
   * @return string 8-character LSB-first bit string.
   */
  public static function bitsFromByte($byte)
  {
    $bits = decbin($byte);
    return strrev(str_pad($bits, 8, 0, STR_PAD_LEFT));
  }

  /**
   * Injects a PSR-3 compatible logger into the parser.
   *
   * The provided logger will be used by the FITParser for any logging calls.
   */
  public function setLogger($logger)
  {
    $this->log = $logger;
  }

  /**
   * Emit a warning-level message via the configured logger.
   *
   * Logs a fixed warning ("this is a warning from inside the FitParser").
   * Requires a logger to have been set via setLogger() before calling.
   */
  public function useLogger()
  {
    $this->log->warning('this is a warning from inside the FitParser');
  }

  /**
   * Dump the currently configured logger instance for debugging.
   *
   * Outputs the stored logger object using the global `dump` helper to assist
   * debugging during development.
   *
   * @return void
   */
  public function dumpLogger()
  {
    dump($this->log);
  }

  /**
   * Set the maximum number of records/messages to parse.
   *
   * Use -1 to disable the limit (default).
   *
   * @param int $limit Maximum number of messages to parse, or -1 for no limit.
   */
  public function setMessageLimit($limit)
  {
    $this->message_limit = $limit;
  }

  /**
   * Return and clear the accumulated component buffer.
   *
   * Retrieves the current list of component objects collected while parsing a data record,
   * resets the internal buffer to an empty array, and returns the retrieved components.
   *
   * @return array The components that were buffered (may be empty).
   */
  public function flushComponentBuffer()
  {
    $components = $this->component_buffer;
    $this->component_buffer = [];
    return $components;
  }

  /**
   * Set the current read position in the underlying binary reader.
   *
   * Moves the reader's cursor to the given byte offset within the input stream.
   *
   * @param int $position Zero-based byte offset from the start of the stream.
   */
  public function setPosition(int $position)
  {
    $this->reader->setPosition($position);
  }

}
