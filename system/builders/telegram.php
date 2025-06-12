<?php

use DriBots\Platforms\TelegramPlatform;

if(!isset($handler, $data)) return;
$handler->addPlatform(new TelegramPlatform($data));
