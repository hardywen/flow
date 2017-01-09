<?php

namespace Hardywen\Flow;


use Hardywen\Flow\Providers\Liuliangwangguan;
use Hardywen\Flow\Providers\Liumi;

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

            default:
                throw new \Exception('找不到相应的provider', 500);
        }
    }

}