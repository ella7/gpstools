<?php

namespace App\Model\FIT;

class DataMessage extends Message
{

  protected $definition;

  public function __construct(DefinitionMessage $definition)
  {
    $this->type = Message::MESSAGE_TYPE_DATA;
    $this->definition = $definition;
  }

}
