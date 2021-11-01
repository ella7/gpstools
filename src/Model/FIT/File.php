<?php

namespace App\Model\FIT;

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
}
