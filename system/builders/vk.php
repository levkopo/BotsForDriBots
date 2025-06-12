<?php

use DriBots\Platforms\VKPlatform;

if(!isset($handler, $data)) return;
$handler->addPlatform(new VKPlatform($data['token'], $data['group_id'],
    $data['secret']??null, $data['confirm_code']??null));
