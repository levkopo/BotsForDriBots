<?php


namespace bots\utils;


use DriBots\Data\Message;

class CommandHelper {
    private ?string $command = null;
    private string $text;

    private function __construct(Message $message, string $prefix = "/") {
        if(str_starts_with($message->text, $prefix)){
            $commandWithPrefix = explode(" ", $message->text)[0];
            $this->command = substr($commandWithPrefix, strlen($prefix));
            $this->text = substr($message->text, strlen($commandWithPrefix));
        }
    }

    public function on(string $command, callable $on): ?CommandHelper {
        if($this->command==$command) {
            $on($this->text);
            return $this;
        }

        return null;
    }

    public static function new(Message $message, string $prefix = "/"): ?self {
        if(($helper = new self($message, $prefix))&&$helper->command!=null)
            return $helper;

        return null;
    }
}