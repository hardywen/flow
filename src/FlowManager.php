<?php

namespace Hardywen\Flow;


use Hardywen\Flow\Providers\Liumi;

class FlowManager
{

    public function provider($provider)
    {
        switch (strtolower($provider)) {
            case 'liumi':
                $config = config('flow.liumi');
                return new Liumi($config);
                break;
            default:
                throw new \Exception('找不到相应的provider', 500);
        }
    }

}