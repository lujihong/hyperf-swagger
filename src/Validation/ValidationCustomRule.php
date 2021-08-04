<?php

declare(strict_types=1);

namespace Hyperf\Apidoc\Validation;

class ValidationCustomRule
{
    /**
     * 手机号码验证
     * @param $attribute 属性
     * @param $value 属性值
     * @return bool|string 校验错误则返回错误信息, 正确则返回 true
     */
    public function phone($attribute, $value)
    {
        if (!preg_match('/^1[3456789]{1}\d{9}$/', trim($value))) {
            return '格式不正确';
        }

        return true;
    }

    /**
     * 手机号码或座机号码验证
     * @param $attribute 属性
     * @param $value 属性值
     * @return bool|string 校验错误则返回错误信息, 正确则返回 true
     */
    public function telephone($attribute, $value)
    {
        if (!preg_match('/^1[3456789]{1}\d{9}$/', trim($value)) && !preg_match('/^(0[0-9]{2,3}\-)?([2-9][0-9]{6,7})+(\-[0-9]{1,4})?$/', trim($value)) && !preg_match('/^(0[0-9]{2,3})?([2-9][0-9]{6,7})+(\-[0-9]{1,4})?$/', trim($value))) {
            return '只能是有效的11位手机号码，或区号+座机号码+分机号（可带中划线分隔，分机号必须分隔）';
        }

        return true;
    }

    /**
     * 身份证号码验证
     * @param $attribute
     * @param $value
     * @return bool|string
     */
    public function identity_card($attribute, $value)
    {
        $id = strtoupper($value);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = [];
        $errorMsg = '不合法';
        if (!preg_match($regx, $id)) {
            return $errorMsg;
        }

        //检查15位
        if (15 == strlen($id)) {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
            @preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            if (!strtotime($dtm_birth)) {
                return $errorMsg;
            } else {
                return true;
            }
        } else {
            //检查18位
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            //检查生日日期是否正确
            if (!strtotime($dtm_birth)) {
                return $errorMsg;
            } else {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
                $arr_ch = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
                $sign = 0;
                for ($i = 0; $i < 17; $i++) {
                    $b = (int)$id[$i];
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id, 17, 1)) {
                    return $errorMsg;
                } else {
                    return true;
                }
            }
        }
    }

    public function crontab($attribute, $value)
    {
        if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($value))) {
            if (!preg_match('/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i', trim($value))) {
                return '不是合法的crontab配置';
            }
        }

        return true;
    }

    public function class_exist($attribute, $value)
    {
        if (!class_exists((string)$value)) {
            return '类名不存在';
        }

        return true;
    }

    public function number_concat_ws_comma($attribute, $value)
    {
        if (!preg_match('/^\\d+(,\\d+)*$/', $value)) {
            return '不是英文逗号分隔的字符串';
        }

        return true;
    }
}
