<?php

namespace Hardywen\Flow;


interface FlowInterface
{
    /**
     * 服务商: YD:移动,LT:联通,DX:电信
     * @param $carrier 服务商: YD:移动,LT:联通,DX:电信
     * @return $this
     */
    public function carrier($carrier);

    /**
     * 流量
     * @param $package 流量包大小
     * @return $this
     */
    public function package($package);

    /**
     * 手机号
     * @param $mobile
     * @return mixed
     */
    public function mobile($mobile);

    /**
     * 充值,应该返回标准格式
     * @return array ['success'=>boolean,'provider'=>string,'code'=>int, 'msg'=>string]
     */
    public function recharge();

}