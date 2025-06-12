<?php


namespace bots\utils;


class Serializer {
    public static function encode(array $array, string $pie = "-}"): string {
        if(is_int(sizeof($array))){
            $new_array = [];
            foreach (array_keys($array)as$key){
                $new_array[] = trim($key);

                if(is_array($array[$key])){
                    $array[$key] = self::encode($array[$key], "//{");
                }

                $array[$key] = str_replace($pie, "", $array[$key]);
                $new_array[] = trim($array[$key]);
            }
            return implode($pie, $new_array);
        }

        return "";
    }

    public static function decode(string $string, string $pie = "-}"): array {
        $output = array();
        $arr = explode($pie, $string);
        for ($i=0;$i+1 < sizeof($arr);$i+=2){
            $output[$arr[$i]] = str_contains($arr[$i+1], "//{")?
                self::decode($arr[$i+1], "//{"): trim($arr[$i+1]);
        }

        return $output;
    }
}