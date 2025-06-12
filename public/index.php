<?php
if(isset($_SERVER['HTTP_X_Retry_Counter']))
    die("ok");

use DriBots\DriBotsHandler;
use Symfony\Component\Yaml\Yaml;

require_once "../vendor/autoload.php";

const CONFIG_FILE_PATH = __DIR__."/./../config.yaml";
if(!isset($_GET['id']))
    return;

ini_set("display_errors", 1);
$config = Yaml::parse(file_get_contents(CONFIG_FILE_PATH));

$botConfig = null;
foreach($config['bots'] as $bot){
    if($bot['id']==$_GET['id']){
        $botConfig = $bot;
        break;
    }
}

unset($bot);
if($botConfig==null)
    return;

$handler = DriBotsHandler::new("bots\\{$botConfig['classname']}");
foreach($botConfig['platforms'] as $platform=>$data){
    include_once __DIR__."/./../system/builders/$platform.php";
}

unset($data);

$handler->handle();
