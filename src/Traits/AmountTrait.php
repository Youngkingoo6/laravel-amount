<?php

namespace Youngkingoo6\LaravelAmount\Traits;

trait AmountTrait
{
    public static $amountTimes = 100;

    public function getMutatedAttributes()
    {
        $attributes = parent::getMutatedAttributes();
        return array_merge($attributes, $this->getAmountFields());
    }

    protected function mutateAttributeForArray($key, $value)
    {
        return (in_array($key, $this->getAmountFields()))
            ? (function_exists('bcdiv') ? bcdiv($value, self::$amountTimes, 2) : $value / self::$amountTimes)
            : parent::mutateAttributeForArray($key, $value);
    }

    public function getAttributeValue($key)
    {
        $cny = strtolower(substr($key, - 4)) === '_cny';
        if($cny){
            $key = substr($key,0, strlen($key)-4);
        }
        $value = parent::getAttributeValue($key);
        if (in_array($key, $this->getAmountFields())) {
            if(function_exists('bcdiv')){
                $value = bcdiv($value, self::$amountTimes,2);
            }else{
                $value = $value / self::$amountTimes;
            }
        }
        if($cny){
            $value = $this->getCny($value);
        }
        return $value;
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->getAmountFields())) {
            if(function_exists('bcmul')){
                $value = (int)bcmul($value, self::$amountTimes);
            }else{
                $value = (int)($value * self::$amountTimes);
            }
        }
        parent::setAttribute($key, $value);
    }

    public function getAmountFields()
    {
        return (property_exists($this, 'amountFields')) ? $this->amountFields : [];
    }

    public function getCny($value)
    {
        $digits = ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
        $radices =['', '拾', '佰', '仟', '万', '亿'];
        $bigRadices = ['', '万', '亿'];
        $decimals = ['角', '分'];
        $cn_dollar = '元';
        $cn_integer = '整';
        $num_arr = explode('.', $value);
        $int_str = $num_arr[0] ?? '';
        $float_str = $num_arr[1] ?? '';
        $outputCharacters = '';
        if ($int_str) {
            $int_len = strlen($int_str);
            $zeroCount = 0;
            for ($i = 0; $i < $int_len; $i++) {
                $p = $int_len - $i - 1;
                $d = substr($int_str, $i, 1);
                $quotient = $p / 4;
                $modulus = $p % 4;
                if ($d == "0") {
                    $zeroCount++;
                }
                else {
                    if ($zeroCount > 0){
                        $outputCharacters += $digits[0];
                    }
                    $zeroCount = 0;
                    $outputCharacters .= $digits[$d] . $radices[$modulus];
                }
                if ($modulus == 0 && $zeroCount < 4) {
                    $outputCharacters .= $bigRadices[$quotient];
                    $zeroCount = 0;
                }
            }
            $outputCharacters .= $cn_dollar;
        }
        if ($float_str) {
            $float_len = strlen($float_str);
            for ($i = 0; $i < $float_len; $i++) {
                $d = substr($float_str, $i, 1);
                if ($d != "0") {
                    $outputCharacters .= $digits[$d] . $decimals[$i];
                }
            }
        }
        if ($outputCharacters == "") {
            $outputCharacters = $digits[0] . $cn_dollar;
        }
        if ($float_str) {
            $outputCharacters .= $cn_integer;
        }
        return $outputCharacters;
    }
}