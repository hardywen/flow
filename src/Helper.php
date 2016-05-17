<?php

namespace Hardywen\Flow;


trait Helper
{

    /**
     * Curl 请求
     * @param $url
     * @param $data
     * @param string $method
     * @param array $headers
     * @return mixed
     */
    public function request($url, $data, $method = 'GET', $headers = [])
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //以下两行作用:忽略https证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $method = strtoupper($method);

        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(array("Content-Type: application/json"), $headers));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $content = curl_exec($ch);

        curl_close($ch);

        return $content;
    }

    public function mobileValidate($mobile)
    {
        //移动手机号段
        $YD = starts_with($mobile, config('flow.YD', [
            '134',
            '135',
            '136',
            '137',
            '138',
            '139',
            '150',
            '151',
            '152',
            '157',
            '158',
            '159',
            '1705',
            '178',
            '182',
            '183',
            '184',
            '187',
            '188',
            '147'
        ]));

        if ($YD) {
            return 'YD';
        }

        $LT = starts_with($mobile, config('flow.LT', [
            '130',
            '131',
            '132',
            '155',
            '156',
            '1709',
            '176',
            '185',
            '186',
            '145'
        ]));

        if ($LT) {
            return 'LT';
        }

        $DX = starts_with($mobile, config('flow.DX', [
            '133',
            '153',
            '1700',
            '177',
            '180',
            '181',
            '189'
        ]));

        if ($DX) {
            return 'DX';
        }

        return null;
    }

}