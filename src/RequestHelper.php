<?php

namespace Hardywen\Flow;


trait RequestHelper
{
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
}