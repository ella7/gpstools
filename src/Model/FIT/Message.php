<?php

namespace App\Model\FIT;


class Message
{

  const MESSAGE_TYPE_DATA = 'Data';
  const MESSAGE_TYPE_DEFINITION = 'Definition';

  protected $type;
  protected $local_number;
  protected $message;

}
