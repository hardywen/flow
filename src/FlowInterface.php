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
     * 判断手机号的服务商并返回
     * @param $mobile
     * @return carrier 服务商
     */
    public function getCarrier($mobile);

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
     * @return array ['success'=>boolean,'order_sn'=>string,'provider'=>string,'code'=>int, 'msg'=>string]
     */
    public function recharge();

}