<?php
/**
 * Created by PhpStorm.
 * User: eddielau
 * Date: 16/12/19
 * Time: 上午11:23
 */

namespace Hardywen\Flow\Providers;


use Hardywen\Flow\LiuliangwangguanInterface;

class Liuliangwangguan implements LiuliangwangguanInterface
{
    /**
     * 订单类型 [ 1=>订购, 2=>退购 ]
     */
    const ORDER_TYPE_ORDER = 1;
    const ORDER_TYPE_BACK  = 2;

    /**
     * 生效方式 [ 1=>立即生效, 2=>下半月生效, 3=>下月生效 ]
     */
    const EFFECT_TYPE_NOW = 1;
    const EFFECT_TYPE_NEXT_HALF_MONTH = 2;
    const EFFECT_TYPE_NEXT_MONTH = 3;

    /**
     * 版本
     */
    const VERSION = 1;

    const TIMESTAMP_FORMATER = 'YmdHis';


    const LAN_ERROR = 'flow::liuliangwangguan.error.';

    /**
     * API
     */
    const API_RECHARGE = '/order.do';
    const API_GET_BALANCE = '/query/balance.do';


    protected $secretKey;

    protected $domain;

    /**
     * 客户接入代码(平台分配)
     *
     * @var
     */
    protected $custInteId;

    /**
     * 随机串(客户侧产生,生成签名使用)
     *
     * @var
     */
    protected $echoStr;

    /**
     * 时间戳(格式:YYYYMMDDHHMMSS)
     *
     * @var bool|string
     */
    protected $timestamp;

    /**
     * 版本(填写:1)
     *
     * @var int
     */
    protected $version;

    /**
     * 手机号
     *
     * @var
     */
    protected $mobile;

    /**
     * 流量包编码
     *
     * @var
     */
    protected $packCode;

    /**
     * 订单号
     *
     * @var
     */
    protected $orderId;

    /**
     * 订单类型(1->订购, 2->退购)
     *
     * @var
     */
    protected $orderType;

    /**
     * 生效方式(1->立即生效, 2->下半月生效, 3->下月生效)
     *
     * @var
     */
    protected $effectType;

    /**
     * 运营商
     *
     * @var
     */
    private $carrier;

    /**
     * 流量包大小
     *
     * @var
     */
    private $package;

    /**
     * 资源列表
     *
     * @var
     */
    protected $resources;


    public function __construct($config)
    {
        if (!is_array($config))
            throw new \Exception('请设置好参数并且配置参数必须是数组', 500);

        if (!$config['custInteId']) {
            throw new \Exception('缺少custInteId参数', 500);
        }
        else {
            $this->custInteId = $config['custInteId'];
        }

        if (!$config['secretKey']) {
            throw new \Exception('缺少secretKey参数', 500);
        }
        else {
            $this->secretKey = $config['secretKey'];
        }

        if (!$config['domain']) {
            throw new \Exception('缺少domain参数', 500);
        }
        else {
            $this->domain = $config['domain'];
        }

        if (!$config['resources']) {
            throw new \Exception('缺少resources数', 500);
        }
        else {
            $this->resources = $config['resources'];
        }

        /*
         * 设置版本.
         */
        $this->version = self::VERSION;

        /*
         * 设置时间戳.
         */
        $this->timestamp = date(self::TIMESTAMP_FORMATER);

        /*
         * 生成随机串
         */
        $this->echoStr();
    }

    /**
     * 设置手机号
     *
     * @author Eddie
     *
     * @param $mobile
     * @return $this
     */
    public function mobile($mobile)
    {
        $this->mobile = $mobile;
        return $this;
    }

    /**
     * 设置充值流量
     *
     * @author Eddie
     *
     * @param \Hardywen\Flow\流量包大小 $package
     * @return $this
     * @throws \Exception
     */
    public function package($package)
    {
        $this->carrier = substr($package, 0, 2);
        $this->package = substr($package, 2);

        if (!array_key_exists($this->carrier, $this->resources)) {
            throw new \Exception('服务商carrier只能是 YD,LT,DX 这三个!', 500);
        }

        //// 反转 - 对应运营商的流量资源包数组 ( array(流量包 => 资源ID) )
        $carrierPackages = array_flip($this->resources[$this->carrier]);
        if (!array_key_exists($this->package, $carrierPackages)) {
            throw new \Exception('不存在对应的流量包!', 500);
        }

        $this->packCode = $carrierPackages[$this->package];

        return $this;
    }

    /**
     * 设置订单ID
     *
     * @author Eddie
     *
     * @param $orderId
     * @return $this
     */
    public function orderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * 设置订单类型
     *
     * @author Eddie
     *
     * @param null $orderType
     * @return $this
     */
    public function orderType($orderType = null)
    {
        if (in_array($orderType, [self::ORDER_TYPE_ORDER, self::ORDER_TYPE_BACK])) {
            $this->orderType = $orderType;
        }
        else { // 默认
            $this->orderType = self::ORDER_TYPE_ORDER;
        }
        return $this;
    }

    /**
     * 设置生效方式
     *
     * @author Eddie
     *
     * @param null $effectType
     * @return $this
     */
    public function effectType($effectType = null)
    {
        if (in_array($effectType, [
            self::EFFECT_TYPE_NOW,
            self::EFFECT_TYPE_NEXT_HALF_MONTH,
            self::EFFECT_TYPE_NEXT_MONTH
        ])) {
            $this->effectType = $effectType;
        }
        else { // 默认
            $this->effectType = self::EFFECT_TYPE_NOW;
        }
        return $this;
    }

    /**
     * 生成随机串
     *
     * @author Eddie
     */
    public function echoStr()
    {
        $len = 20;
        $chars = 'abcdefghijklmnopqrstuvwxyz'
            .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
            .'0123456789';

        for ($i = 0; $i < $len; $i++) {
            $idx = mt_rand(0, strlen($chars) - 1);
            $this->echoStr .= $chars[$idx];
        }

        return $this;
    }


    /**
     * 订购/退订接口
     *
     * @author Eddie
     *
     * @param null $mobile
     * @param null $package
     * @param null $orderId
     * @return mixed
     * @throws \Exception
     */
    public function recharge($mobile = null, $package = null, $orderId = null)
    {
        if (!$this->mobile) {
            if (!$mobile) {
                throw new \Exception('手机号不能为空', 422);
            }
            $this->mobile($mobile);
        }
        if (!$this->packCode) {
            if (!$package) {
                throw new \Exception('流量包不能为空', 422);
            }
            $this->package($package);
        }
        if (!$this->orderId) {
            if (!$orderId) {
                throw new \Exception('订单号不能为空', 422);
            }
            $this->orderId($orderId);
        }

        //// Set default 'orderType' (1=>订购), when 'orderType' is null or empty.
        if (!$this->orderType) {
            $this->orderType();
        }
        //// Set default 'effectType' (1=>立即生效), when 'effectType' is null or empty.
        if (!$this->effectType) {
            $this->effectType();
        }

        //// API地址
        $url = $this->domain . self::API_RECHARGE;

        //// 签名
        $sign = $this->buildSign($this->orderId);

        //// 生成XML
        $xml = '<?xml version="1.0" encoding="utf-8"?><request><head>'
            ."<custInteId>{$this->custInteId}</custInteId>"
            ."<echo>{$this->echoStr}</echo>"
            ."<orderId>{$this->orderId}</orderId>"
            ."<timestamp>{$this->timestamp}</timestamp>"
            ."<orderType>{$this->orderType}</orderType>"
            ."<version>{$this->version}</version>"
            ."<chargeSign>{$sign}</chargeSign>"
            .'</head><body>';

        $xml .= '<item>'
            ."<packCode>{$this->packCode}</packCode>"
            ."<mobile>{$this->mobile}</mobile>"
            ."<effectType>{$this->effectType}</effectType>"
            .'</item>';

        $xml .= '</body></request>';

        return $this->remote($url, $xml);
    }

    /**
     * 账户余额查询接口
     *
     * @author Eddie
     */
    public function getBalance()
    {
        //// API地址
        $url = $this->domain . self::API_GET_BALANCE;

        //// 签名
        $sign = $this->buildSign();

        //// 生成XML
        $xml = '<?xml version="1.0" encoding="utf-8"?><request><head>'
            ."<custInteId>{$this->custInteId}</custInteId>"
            ."<echo>{$this->echoStr}</echo>"
            ."<timestamp>{$this->timestamp}</timestamp>"
            ."<version>{$this->version}</version>"
            ."<chargeSign>{$sign}</chargeSign>"
            .'</head></request>';

        return $this->remote($url, $xml);
    }

    /**
     * 发送 Curl 请求
     *
     * @author Eddie
     *
     * @param $url
     * @param $data
     * @param string $method
     * @param array $headers
     * @return mixed
     */
    public function remote($url, $data, $method = 'POST', $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        //以下两行作用:忽略https证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (strtoupper($method) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(["Content-Type: application/xml"], $headers));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        curl_close($ch);

        /*
         * Return.
         */
        return $this->parseResponse($response);
    }


    /**
     * 解析返回结果
     *
     * @author Eddie
     *
     * @param $response
     * @return array
     */
    public function parseResponse($response)
    {
        $result = [
            'provider' => '流量网关',
            'msg'      => '',
            'success'  => false
        ];

        /*
         * Transfer.
         */
        $response = simplexml_load_string($response);
        $response = json_decode(json_encode($response), true);

        $result = array_merge($result, $response);
        if (isset($response['result']) && $response['result'] === '0000') {
            $result['success'] = true;
        }
        else {
            $result['msg'] = trans(self::LAN_ERROR . $response['result']);
        }

        return $result;
    }

    /**
     * 生成签名
     *
     * @author Eddie
     *
     * @param null $orderId
     * @return string
     */
    public function buildSign($orderId = null)
    {
        /*
         * 根据调用接口, 生成签名的字符串组合规则亦有不同;
         *
         * 订购/退订接口: custInteId + orderId + secretKey + echo + timestamp
         *
         * 账户余额查询接口: custInteId + secretKey + echo + timestamp
         */
        if ($orderId) {
            $str = $this->custInteId . $orderId . $this->secretKey . $this->echoStr . $this->timestamp;
        }
        else {
            $str = $this->custInteId . $this->secretKey . $this->echoStr . $this->timestamp;
        }
        return base64_encode(md5($str, true));
    }
}