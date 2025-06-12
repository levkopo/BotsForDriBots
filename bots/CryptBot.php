<?php

namespace bots;

use bots\utils\MCrypt;
use bots\utils\Serializer;
use DriBots\Bot;
use DriBots\Data\Attachment;
use DriBots\Data\InlineQuery;
use DriBots\Data\InlineQueryResult;
use DriBots\Data\Message;
use DriBots\Platforms\BasePlatformProvider;
use Exception;
use JetBrains\PhpStorm\ArrayShape;

class CryptBot extends Bot {
    const CRYPT_VERSION = 2;
    private string $key = "rHdHpd0Tk2BU1ic3";

    /** States */
    const UNKNOWN_CRYPT = 1;
    const INVALID_DATA = 2;

    public function onNewMessage(Message $message): void {
        if($message->chatId!=$message->ownerId) return;

        $response = $this->decrypt($message->text);
        if($response===self::UNKNOWN_CRYPT){
            if($message->text=="" && $message->attachment==null){
                $this->platformProvider->sendMessage($message->chatId, "❗ В данный момент мы не можем зашифровать это вложение");
            } else {
                $this->platformProvider->sendMessage($message->chatId, "✔ Зашифровано:");
                $this->platformProvider->sendMessage($message->chatId, $this->encrypt($message->text, $message->attachment));
            }
        }else if($response===self::INVALID_DATA){
            $this->platformProvider->sendMessage($message->chatId, "❗ Шифр сломан или он зашифрован другим ключом");
        }else{
            $this->platformProvider->sendMessage($message->chatId, "✔ Шифр расшифрован:");
            $this->platformProvider->sendMessage($message->chatId, $response['text'], $response['attachment']??null);
        }
    }

    public function onInlineQuery(InlineQuery $inlineQuery): void {
        $response = $this->decrypt($inlineQuery->query);
        if(is_array($response)){
            $this->platformProvider->answerToQuery($inlineQuery, new InlineQueryResult(
                "Расшифровать", $response['text']
            ));
        }else $this->platformProvider->answerToQuery($inlineQuery, new InlineQueryResult(
            "Зашифровать", $this->encrypt($inlineQuery->query)
        ));
    }

    public function encrypt(string $text, Attachment $attachment = null): string {
        $data = [
            "t"=> $text,
            "p"=> $this->platform->getName(),
            "v"=> self::CRYPT_VERSION,
            "a"=> $attachment?->getFileId()
        ];

//        if($attachment instanceof PhotoAttachment) {
//            $data['ph'] = $attachment->path;
//            return $attachment->path;
//        }

        $crypt = new MCrypt($this->key);
        return "MCrypt".$crypt->encrypt(Serializer::encode($data)).";";
    }

    #[ArrayShape([
        "text"=>"string",
        "attachment"=>"Attachment"
    ])]
    public function decrypt(string $text): int|array {
        if(preg_match("/VK C[0O] FF EE (.*) VK C[0O] FF EE/", $text, $output)||
            preg_match("/II (.*) II/", $text, $output)){
            try {
                $data = str_replace([
                    "PP",
                    "AP ID 0G",
                    "AP ID OG",
                    "II",
                    " "
                ], "", $output[1]);
                $data = hex2bin($data);

                $key = "stupidUsersMustD";
                return ["text"=>openssl_decrypt($data,
                    'AES-128-ECB', $key)];
            }catch (Exception){
                return self::INVALID_DATA;
            }
        }else if(preg_match("/VT[0O]ST[3E]RS (.*) VT[O0]ST[3E]RS/", $text, $output)){
            if($response = $this->isBase64($output[1])) {
                return ["text"=>$response];
            }else return self::INVALID_DATA;
        }else if($response = $this->isBase64($text)) {
            return ["text"=>$response];
        }else if(preg_match('/MCrypt(.*)/', $text, $output)
            ||preg_match('/MCrypt(.+?);/U', $text, $output)){
            try {
                $crypt = new MCrypt($this->key);
                $data = Serializer::decode($crypt->decrypt(is_array($output[1])? $output[1][0] : $output[1]));
                if(!isset($data["v"])||$data["v"]!=self::CRYPT_VERSION) return self::INVALID_DATA;

                return [
                    "text" => $data['t'] ?? "",
                    "attachment" => $this->getAttachment($this->getPlatformProvider($data['p']),$data["a"]??null)
                ];
            }catch (Exception){}

            return self::INVALID_DATA;
        }

        return self::UNKNOWN_CRYPT;
    }

    public function getAttachment(BasePlatformProvider $provider, ?string $fileId): ?Attachment {
        if($fileId==null) return null;
        if($file = $provider->getAttachmentFromFileId($fileId))
            return $file->save(md5($fileId));

        return null;
    }

    public function isBase64(string $data): string|false {
        $decoded_data = base64_decode($data, true);
        $encoded_data = base64_encode($decoded_data);
        if ($encoded_data != $data) return false;
        else if (!ctype_print($decoded_data)) return false;

        return $decoded_data;
    }
}