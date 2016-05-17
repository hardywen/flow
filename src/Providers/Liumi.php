<?php

namespace Hardywen\Flow\Providers;


use Hardywen\Flow\carrier;
use Hardywen\Flow\FlowInterface;
use Hardywen\Flow\Helper;
use Illuminate\Support\Facades\Cache;

class Liumi implements FlowInterface
{
    use Helper;

    const URL = 'http://yfbapi.liumi.com';

    protected $appKey;

    protected $appSecret;

    protected $carrier;

    protected $package;

    protected $mobile;

    protected $token;

    function __construct($config)
    {
        if (!is_array($config)) {
            throw new \Exception('配置参数必须是数组', 500);
        }

        if (!$config['appKey']) {
            throw new \Exception('缺少appKey参数', 500);
        }

        if (!$config['appSecret']) {
            throw new \Exception('缺少appSecret参数', 500);
        }

        $this->appKey = $config['appKey'];

        $this->appSecret = $config['appSecret'];

        $this->getToken();

    }

    /**
     * 服务商: YD:移动,LT:联通,DX:电信
     * @param $carrier 服务商: YD:移动,LT:联通,DX:电信
     * @return $this
     */
    public function carrier($carrier)
    {
        $this->carrier = $carrier;
        return $this;
    }


    /**
     * 判断手机号的服务商并返回
     * @param $mobile
     * @return carrier 服务商
     */
    public function getCarrier($mobile)
    {
        return $this->mobileValidate($mobile);
    }

    /**
     * 流量
     * @param $package 流量包大小
     * @return $this
     */
    public function package($package)
    {
        $this->package = $package;
        return $this;
    }

    /**
     * 手机号
     * @param $mobile
     * @return mixed
     */
    public function mobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * 充值
     * @return array ['status'=>0|1,'code'=>int, 'msg'=>string]
     * @throws \Exception
     */
    public function recharge()
    {
        if (!in_array($this->carrier, ['YD', 'LT', 'DX'])) {
            throw new \Exception('服务商carrier只能是 YD,LT,DX 这三个', 422);
        }
        if (!$this->package) {
            throw new \Exception('package 不能为空', 422);
        }
        if (!$this->mobile) {
            throw new \Exception('手机号不能为空', 422);
        } else {
            //验证手机号段与服务商是否一致
            if ($this->mobileValidate($this->mobile) != $this->carrier) {
                throw new \Exception('需要充值的流量与手机号服务商不一致', 422);
            }
        }
        $params = [
            'appkey'      => $this->appKey,
            'appsecret'   => '',
            'appver'      => 'Http',
            'apiver'      => '2.0',
            'des'         => 0,
            'extno'       => '',
            'fixtime'     => '',
            'mobile'      => $this->mobile,
            'postpackage' => $this->carrier . $this->package,
            'token'       => $this->token,
        ];

        $params['sign'] = $this->generateSign($params);

        $result = $this->request(self::URL . '/server/placeOrder', $params, 'POST');

        $result = $this->transform($result);

        return $result;

    }

    /**
     * 转成标准返回格式
     * @param $result
     * @return array
     */
    public function transform($result)
    {
        $result = json_decode($result);

        if (!$result) {
            return $result;
        }

        $return = [
            'provider' => 'Liumi',
            'code'     => $result->code
        ];
        if ($result->code == '000') {
            $return['success'] = true;
            $return['order_sn'] = $result->data->orderNO;
        } else {
            $return['success'] = false;
            $return['msg'] = trans('flow::liumi.error.' . $result->code);
        }

        return $return;
    }

    /**
     * 生成签名
     * @param $data
     * @return string
     */
    public function generateSign($data)
    {

        ksort($data);

        $sign = '';

        foreach ($data as $key => $value) {
            if ($value !== '') {
                $sign .= $key . $value;
            }
        }

        $sign = sha1($sign);

        return $sign;
    }

    /**
     * 获取token
     * @return mixed
     * @throws \Exception
     */
    public function getToken()
    {

        $this->token = Cache::get('hardywen.flow.token', null);

        if ($this->token) {
            return $this->token;
        }

        $params = [
            'appkey'    => $this->appKey,
            'appsecret' => md5($this->appSecret)
        ];

        $sign = $this->generateSign($params);

        $params['sign'] = $sign;

        $result = $this->request(self::URL . '/server/getToken', $params, 'POST');
        $result = json_decode($result);

        if (isset($result->data->token)) {
            $this->token = $result->data->token;

            Cache::put('hardywen.flow.token', $this->token, 20);

        } else {
            throw new \Exception("获取token出错:[{$result->code}]", 422);
        }

    }

}