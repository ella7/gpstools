<?php

namespace App\Model\FIT;
use App\Model\FIT\Message;
use App\Utility\AutoSettablePropertiesTrait;

class File
{
  use AutoSettablePropertiesTrait;

  protected $header;
  protected $messages;

  /**
   * Construct a File model and initialize its properties from the given array.
   *
   * @param array $properties Associative array of property values used to populate the object's fields. */
  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }

  /**
   * Retrieve all messages contained in this FIT file.
   *
   * Returns the messages collection (in file order) as an array of Message objects.
   *
   * @return Message[]|array
   */
  public function getMessages()
  {
    return $this->messages;
  }

  /**
   * Return the Message at the given zero-based index from the FIT file.
   *
   * No bounds checking is performed; supplying an invalid index will result in an error.
   *
   * @param int $message_index Zero-based index of the message to retrieve.
   * @return Message The Message at the specified index.
   */
  public function getMessage(int $message_index): Message
  {
    return $this->messages[$message_index];
  }
}
