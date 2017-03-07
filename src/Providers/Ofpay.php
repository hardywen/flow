<?php

namespace Hardywen\Flow\Providers;


use Hardywen\Flow\FlowInterface;
use Hardywen\Flow\Helper;

class Ofpay implements FlowInterface
{
    use Helper;

    /**
     * 流量直充接口（flowOrder.do）
     * 此接口依据用户提供的请求为指定手机充值流量
     */
    const API_FLOW_ORDER = '/flowOrder.do';

    /**
     * 使用范围( 1->省内, 2->全国 )
     */
    const RANGE_PROVINCE = 1;
    const RANGE_NATION   = 2;

    /**
     * 生效时间( 1->当日, 2->次日, 3->次月 )
     */
    const EFFECT_START_AT_CUR_DAY    = 1;
    const EFFECT_START_AT_CUR_MONTH  = 2;
    const EFFECT_START_AT_NEXT_MONTH = 3;

    /**
     * 有效期( 1->当月有效, 2->30天有效, 3->半年有效, 4->3个月有效, 5->2个月有效, 6->6个月有效, 7->20天有效, 8->3日有效, 9->90天有效, 10->7天有效 )
     */
    const EFFECT_TIME_CUR_MONTH = 1;
    const EFFECT_TIME_30_DAYS   = 2;
    const EFFECT_TIME_HALF_YEAR = 3;
    const EFFECT_TIME_3_MONTH   = 4;
    const EFFECT_TIME_2_MONTH   = 5;
    const EFFECT_TIME_6_MONTH   = 6;
    const EFFECT_TIME_20_DAYS   = 7;
    const EFFECT_TIME_3_DAYS    = 8;
    const EFFECT_TIME_90_DAYS   = 9;
    const EFFECT_TIME_7_DAYS    = 10;


    /**
     * API url
     *
     * @author Eddie
     *
     * @var mixed
     */
    protected $api_url;

    /**
     * SP编码
     *
     * @author Eddie
     *
     * @var mixed
     */
    protected $userid;

    /**
     * SP接入密码
     *
     * @author Eddie
     *
     * @var mixed
     */
    protected $userpwd;

    /**
     * 版本
     *
     * @author Eddie
     *
     * @var mixed
     */
    protected $version;

    /**
     * 签名用key; 默认为:"OFCARD", 实际上线时可以修改; 不在接口间进行传送.
     *
     * @author Eddie
     *
     * @var
     */
    protected $key;

    /**
     * 充值手机号
     *
     * @author Eddie
     *
     * @var
     */
    protected $mobile;

    /**
     * 面值
     *
     * @author Eddie
     *
     * @var
     */
    protected $perValue;

    /**
     * 流量值
     *
     * @author Eddie
     *
     * @var
     */
    protected $flowValue;

    /**
     * 订单号
     *
     * @author Eddie
     *
     * @var
     */
    protected $orderId;

    /**
     * 有效期
     *
     * @author Eddie
     *
     * @var
     */
//    protected $effectTime;

    /**
     * 充值回调地址
     *
     * @author Eddie
     *
     * @var
     */
    protected $redirect;

    /**
     * 服务商: YD->移动, LT->联通, DX->电信
     *
     * @author Eddie
     *
     * @var
     */
    protected $carrier;

    /**
     * 流量包
     *
     * @author Eddie
     *
     * @var
     */
    protected $package;

    private $resources;


    /**
     * Ofpay constructor.
     *
     * @author Eddie
     *
     * @param $config
     * @throws \Exception
     */
    public function __construct($config)
    {
        if (!is_array($config))
            throw new \Exception('请设置好参数并且配置参数必须是数组', 500);

        if ($config['test_mode']) {
            $this->api_url = $config['test_api_url'];
            $this->userid = $config['test_userid'];
            $this->userpwd = $config['test_userpws'];
            $this->key = $config['test_key'];
        }
        else {
//            if (!$config['api_url'])
//                throw new \Exception('缺少api_url参数', 500);

            if (!$config['userid'])
                throw new \Exception('缺少userid参数', 500);

            if (!$config['userpws'])
                throw new \Exception('缺少userpws参数', 500);

            if (!$config['version'])
                throw new \Exception('缺少version参数', 500);

            if (!$config['key'])
                throw new \Exception('缺少key参数', 500);


            /// http://AXXXX.api2.ofpay.com
            $this->api_url = 'http://' . $config['userid'] . '.api2.ofpay.com';

            $this->userid = $config['userid'];
            $this->userpwd = $config['userpws'];
            $this->key = $config['key'];
        }

        $this->version = $config['version'];
        $this->resources = $config['resources'];
    }


    /**
     * 流量充值
     *
     * @author Eddie
     *
     * @return mixed
     * @throws \Exception
     */
    public function recharge()
    {
        /*
         * Invalid
         */
        if (!$this->orderId) {
            throw new \Exception('订单号不能为空', 422);
        }
        if (!$this->mobile) {
            throw new \Exception('手机号不能为空', 422);
        }
        if (!$this->redirect) {
            throw new \Exception('请设置充值回调地址', 422);
        }
        if (!in_array($this->carrier, ['YD', 'LT', 'DX'])) {
            throw new \Exception('服务商只能是: YD->移动, LT->联通, DX->电信, 这三个', 422);
        }
        if (!$this->package) {
            throw new \Exception('流量包不能为空', 422);
        }


        /**
         * 通过 服务商 和 流量包, 获取对应的: 流量面值和流量值
         */
        $this->getPerFlow();

        /*
         * 参数列表: [ 请求参数 | 是否必填 | 说明 ]
         *
         * userid          | 是 | SP编码(如:A00001)
         * userpws         | 是 | SP接入密码(为账户密码的MD5值)
         * phoneno         | 是 | 充值手机号
         * perValue        | 是 | 面值(请按照流量产品文档中对应商品输入)
         * flowValue       | 是 | 流量值(请按照流量产品文档中对应商品输入)
         * range           | 是 | 使用范围( 1->省内, 2->全国 )
         * effectStartTime | 是 | 生效时间( 1->当日, 2->次日, 3->次月 )
         * effectTime      | 是 | 有效期( 1->当月有效, 2->30天有效, 3->半年有效, 4->3个月有效, 5->2个月有效, 6->6个月有效 )
         * netType         | 否 | 网络制式: 2G、3G、4G (可不传，不传默认4G\3G\2G依次匹配)
         * sporderId       | 是 | SP商家的订单号, 商户传给欧飞的唯一订单编号 (平台订单号)
         * md5Str          | 是 | MD5后字符串
         * retUrl          | 否 | 订单充值成功后返回的URL地址 (回调地址)
         * version         | 是 | 固定值为: 6.0
         */
        $params = [
            'userid'  => $this->userid,
            'userpws' => md5($this->userpwd),
            'phoneno' => $this->mobile,
            'perValue' => $this->perValue,
            'flowValue' => $this->flowValue,
            'range' => self::RANGE_NATION,
            'effectStartTime' => self::EFFECT_START_AT_CUR_DAY,
            //'effectTime' => $this->effectTime ? $this->effectTime : self::EFFECT_TIME_CUR_MONTH,
            'effectTime' => self::EFFECT_TIME_CUR_MONTH,
            'netType' => '',
            'sporderId' => $this->orderId
        ];

        $str = $params['userid']
            . $params['userpws']
            . $params['phoneno']
            . $params['perValue']
            . $params['flowValue']
            . $params['range']
            . $params['effectStartTime']
            . $params['effectTime']
            . ($params['netType'] ? $params['netType'] : '')
            . $params['sporderId'];
        $params['md5Str'] = $this->sign($str);

        $params['retUrl'] = $this->redirect;
        $params['version'] = $this->version;

        $url = $this->api_url . self::API_FLOW_ORDER;
        \Log::info('request: ', ['url' => $url, 'params' => $params]);

        $response = $this->remote($url, $params, 'POST');

        return $this->parse2Json($response);
    }


    /**
     * Setter - set mobile.
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
     * Setter - set order_id.
     *
     * @author Eddie
     *
     * @param $orderId
     * @return $this
     */
    public function order_id($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * Setter - set redirect.
     *
     * @author Eddie
     *
     * @param $redirect
     * @return $this
     */
    public function redirect($redirect)
    {
        $this->redirect = $redirect;
        return $this;
    }

    /**
     * Setter - set effect_time.
     *
     * @author Eddie
     *
     * @param $effectTime
     * @return $this
     */
//    public function effect_time($effectTime)
//    {
//        if (in_array($effectTime, [
//            self::EFFECT_TIME_CUR_MONTH,
//            self::EFFECT_TIME_30_DAYS,
//            self::EFFECT_TIME_HALF_YEAR,
//            self::EFFECT_TIME_3_MONTH,
//            self::EFFECT_TIME_2_MONTH,
//            self::EFFECT_TIME_6_MONTH,
//            self::EFFECT_TIME_20_DAYS,
//            self::EFFECT_TIME_3_DAYS,
//            self::EFFECT_TIME_90_DAYS,
//            self::EFFECT_TIME_7_DAYS,
//        ])) {
//            $this->effectTime = $effectTime;
//        }
//        else {
//            $this->effectTime = self::EFFECT_TIME_CUR_MONTH;
//        }
//        return $this;
//    }

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
     *
     * @param $mobile
     * @return carrier 服务商
     */
    public function getCarrier($mobile)
    {
        /// TODO

        return '';
    }

    /**
     * 流量包
     *
     * @param $package
     * @return $this
     */
    public function package($package)
    {
        $this->package = $package;
        return $this;
    }


    /**
     * 通过流量包获取对应的面值和流量值
     *
     * @author Eddie
     *
     * @void
     */
    protected function getPerFlow()
    {
        $packages = $this->resources[$this->carrier];
        foreach ($packages as $per => $flow) {
            if ($this->transferPackage($this->package) == $flow) {
                $this->flowValue = $flow;
                $this->perValue = $per;
                break;
            }
        }
    }

    /**
     * Sign
     *
     * @author Eddie
     *
     * @param $params
     * @return string
     */
    protected function sign($params)
    {
        /**
         * md5Str检验码的计算方:
         *
         * netType 为空的话，不参与MD5验证，不为空的话参与MD5验证;
         * 包体= userid + userpws + phoneno + perValue + flowValue + range + effectStartTime + effectTime + netType + sporderId
         *
         * 1: 对: “包体+KeyStr” 这个串进行md5 的32位值. 结果大写
         * 2: KeyStr 默认为 OFCARD, 实际上线时可以修改。
         * 3: KeyStr 不在接口间进行传送。
         */
        //dd($params);

        if (is_array($params)) {
            $params = implode('', $params);
        }
        return strtoupper(md5($params . $this->key));
    }

    /**
     * Parse API data , and convert to JSON.
     *
     * @author Eddie
     *
     * @param $response
     * @return mixed
     */
    protected function parse2Json($response)
    {
        $data = simplexml_load_string($response);

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }


    protected function transferPackage($packageNum)
    {
        if ($packageNum == '1024') {
            return '1G';
        }

        return $packageNum.'M';
    }

    /**
     * 发送curl请求
     *
     * @author Eddie
     *
     * @param $url
     * @param array $params
     * @param string $method
     * @param array $headers
     * @return mixed
     */
    protected function remote($url, $params = [], $method = 'GET', $headers = [])
    {
        /*
         * Open connection, and set options.
         */
        $ch = curl_init();
        if (strtoupper($method) == 'GET') { // >>>>> GET request.
            curl_setopt($ch, CURLOPT_HEADER, 0);
            if ($params) { // has parameters.
                $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($params);
            }
        }
        else { // >>>>> POST request.
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        /*
         * Execute.
         */
        $result = curl_exec($ch);

        /*
         * Close connection.
         */
        curl_close($ch);

        /*
         * Return.
         */
        return $result;
    }

}