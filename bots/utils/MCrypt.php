<?php
namespace bots\utils;

class MCrypt {
    private array $key;
    private int $cbc = 1;

    public function __construct(string $key) {
        $this->key_setup($key);
    }

    public function encrypt(string $text):string{
        $n = strlen($text);
        $lng = $n%8 != 0 ? $n+(8-($n%8)): 0;

        $text = str_pad($text, $lng, ' ');
        $text = $this->_str2long($text);
        $cipher = array();

        if($this->cbc == 1) {
            $cipher[0][0] = time();
            $cipher[0][1] = (double)microtime()*1000000;
        }

        $a = 1;
        for($i = 0; $i<count($text); $i+=2) {
            if($this->cbc == 1) {
                $text[$i] ^= $cipher[$a-1][0];
                $text[$i+1] ^= $cipher[$a-1][1];
            }

            $cipher[] = $this->blockEncrypt($text[$i],$text[$i+1]);
            $a++;
        }

        $output = "";
        for($i = 0; $i<count($cipher); $i++) {
            $output .= $this->_long2str($cipher[$i][0]);
            $output .= $this->_long2str($cipher[$i][1]);
        }

        return base64_encode($output);
    }

    public function decrypt(string $text):string{
        $plain = array();
        $cipher = $this->_str2long(base64_decode($text));

        for($i = $this->cbc == 1?2:0; $i<count($cipher); $i+=2) {
            $return = $this->blockDecrypt($cipher[$i],$cipher[$i+1]);
            if($this->cbc == 1)
                $plain[] = array($return[0]^$cipher[$i-2],$return[1]^$cipher[$i-1]);
            else
                $plain[] = $return;
        }

        $output = "";
        for($i = 0; $i<count($plain); $i++) {
            $output .= $this->_long2str($plain[$i][0]);
            $output .= $this->_long2str($plain[$i][1]);
        }

        return $output;
    }

    function key_setup($key):void{
        if(is_array($key))
            $this->key = $key;
        else if(isset($key) && !empty($key))
            $this->key = $this->_str2long(str_pad($key, 16, $key));
        else $this->key = array(0,0,0,0);
    }

    function check_implementation():bool{
        $YToy = new MCrypt("");
        $vectors = array(
            array(array(0x00000000,0x00000000,0x00000000,0x00000000), array(0x41414141,0x41414141), array(0xed23375a,0x821a8c2d)),
            array(array(0x00010203,0x04050607,0x08090a0b,0x0c0d0e0f), array(0x41424344,0x45464748), array(0x497df3d0,0x72612cb5)),
        );

        $correct = true;
        foreach($vectors AS $vector) {
            $key = $vector[0];
            $plain = $vector[1];
            $cipher = $vector[2];
            $YToy->key_setup($key);
            $return = $YToy->blockEncrypt($plain[0], $plain[1]);
            if((int)$return[0] != (int)$cipher[0] || (int)$return[1] != (int)$cipher[1])
                $correct = false;
        }
        return $correct;
    }

    private function blockEncrypt($y, $z):array{
        $sum=0;
        $delta=0x9e3779b9;

        for ($i=0; $i<32; $i++) {
            $y = $this->_add($y,
                $this->_add($z << 4 ^ $this->_rshift($z, 5), $z) ^ $this->_add($sum, $this->key[$sum & 3]));
            $sum = $this->_add($sum, $delta);
            $z = $this->_add($z, $this->_add($y << 4 ^ $this->_rshift($y, 5), $y) ^ $this->_add($sum, $this->key[$this->_rshift($sum, 11) & 3]));
        }

        return array($y,$z);
    }

    private function blockDecrypt($y, $z):array{
        $delta=0x9e3779b9;
        $sum=0xC6EF3720;

        for ($i=0; $i<32; $i++) {
            $z = $this->_add($z, -($this->_add($y << 4 ^ $this->_rshift($y, 5), $y) ^
                    $this->_add($sum, $this->key[$this->_rshift($sum, 11) & 3])));
            $sum = $this->_add($sum, -$delta);
            $y = $this->_add($y, -($this->_add($z << 4 ^ $this->_rshift($z, 5), $z) ^
                    $this->_add($sum, $this->key[$sum & 3])));
        }

        return [$y, $z];
    }

    private function _rshift($integer, $n):int {
        if (0xffffffff < $integer || -0xffffffff > $integer) {
            $integer = fmod($integer, 0xffffffff + 1);
        }

        if (0x7fffffff < $integer){
            $integer -= 0xffffffff + 1.0;
        }else if (-0x80000000 > $integer) {
            $integer += 0xffffffff + 1.0;
        }

        if(0 > $integer){
            $integer &= 0x7fffffff;
            $integer >>= $n;
            $integer |= 1 << (31 - $n);
        }else{
            $integer >>= $n;
        }

        return $integer;
    }

    private function _add($i1, $i2):float{
        $result = 0.0;
        foreach (func_get_args() as $value) {
            if (0.0 > $value) {
                $value -= 1.0 + 0xffffffff;
            }
            $result += $value;
        }

        if (0xffffffff < $result || -0xffffffff > $result) {
            $result = fmod($result, 0xffffffff + 1);
        }

        if (0x7fffffff < $result) {
            $result -= 0xffffffff + 1.0;
        } elseif (-0x80000000 > $result) {
            $result += 0xffffffff + 1.0;
        }

        return $result;
    }

    private function _str2long($data):array{
        $tmp = unpack('N*', $data);
        $data_long = array();
        $j = 0;
        foreach ($tmp as $value) $data_long[$j++] = $value;
        return $data_long;
    }

    private function _long2str($l):string{
        return pack('N', $l);
    }
}