<?php

namespace App\Model\FIT;


class DefinitionMessage extends Message
{

  protected $fields_count;
  protected $field_definitions; // for now, associative array of field_name, base_type, and units

  public function __construct()
  {
    $this->type = Message::MESSAGE_TYPE_DEFINITION;
  }

}
