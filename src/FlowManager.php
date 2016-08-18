<?php

namespace Hardywen\Flow;


use Hardywen\Flow\Providers\Liumi;
use Hardywen\Flow\Providers\Yuanhui;

class FlowManager
{

    public function provider($provider)
    {
        switch (strtolower($provider)) {
            case 'liumi':
                $config = config('flow.liumi');
                return new Liumi($config);
                break;

            case 'yuanhui':
                $config = config('flow.yuanhui');
                return new Yuanhui($config);

            default:
                throw new \Exception('找不到相应的provider', 500);
        }
    }

}