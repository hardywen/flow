<?php

namespace Hardywen\Flow;


use Hardywen\Flow\Providers\Liuliangwangguan;
use Hardywen\Flow\Providers\Liumi;
use Hardywen\Flow\Providers\Ofpay;

class FlowManager
{

    public function provider($provider)
    {
        switch (strtolower($provider)) {
            /**
             * 流米
             */
            case 'liumi':
                $config = config('flow.liumi');
                return new Liumi($config);
                break;

            /**
             * 流量网关平台
             */
            case 'liuliangwangguan':
                return new Liuliangwangguan(config('flow.liuliangwangguan'));
                break;

            case 'ofpay':
                return new Ofpay(config('flow.ofpay'));
                break;

            default:
                throw new \Exception('找不到相应的provider', 500);
        }
    }

}