<?php
/**
 * Created by PhpStorm.
 * User: eddielau
 * Date: 16/12/19
 * Time: 上午11:28
 */

namespace Hardywen\Flow;


interface LiuliangwangguanInterface
{
    /**
     * 订购/订购接口 => 充值
     *
     * @author Eddie
     *
     * @return mixed
     */
    public function recharge();

    /**
     * 账户余额查询接口
     *
     * @author Eddie
     *
     * @return mixed
     */
    public function getBalance();

    /**
     * 设置流量 (通过指定 运营商+充值流量大小 映射到对应的产品资源ID)
     *
     * @param $package 流量包大小
     *
     * @return $this
     */
    public function package($package);

    /**
     * 设置手机号
     *
     * @param $mobile
     *
     * @return mixed
     */
    public function mobile($mobile);

}