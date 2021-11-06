<?php

namespace App\Model\FIT;
use App\Model\FIT\Message;
use App\Utility\AutoSettablePropertiesTrait;

class File
{
  use AutoSettablePropertiesTrait;

  protected $header;
  protected $messages;

  public function __construct($properties)
  {
    $this->setPropertiesFromArray($properties);
  }

  public function getMessages()
  {
    return $this->messages;
  }

  /**
   * Returns the message at the $message_index position from the FIT file
   * @param  int    $message_index
   * @return Message
   */
  public function getMessage(int $message_index): Message
  {
    return $this->messages[$message_index];
  }
}
