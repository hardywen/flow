<?php
/**
 * Created by PhpStorm.
 * User: eddielau
 * Date: 16/8/18
 * Time: 下午6:28
 */

namespace Hardywen\Flow\Providers;


use Hardywen\Flow\FlowInterface;
use Hardywen\Flow\Helper;

class Yuanhui implements FlowInterface
{
    use Helper;

    /*
     * 奖品资源分类 ProductId (资源编号)
     */
    //const PRODUCT_ACCOUNT_RECHARGE = 10004101; // 帐号直充类
    //const PRODUCT_CODE             = 10104001; // 串码类
    //const PRODUCT_MOBILE_FEE       = 10001001; // 话费充值类
    const PRODUCT_MOBILE_FLOW      = 10508001; // 流量充值类
    //const PRODUCT_MOVIE_TICKET     = 10305004; // 电影票类
    //const PRODUCT_RECHARGE_CARD    = 10202001; // 充值卡密类

    /*
     * API uri
     */
    //// http://xxxxxxxx/API/AccountRec.ashx
    //const AIP_ACCOUNT_RECHARGE = 'AccountRec.ashx'; // 帐号充值

    //// http://xxxxxxxx/API/CardGet.ashx
    //const API_CARD_GET = 'CardGet.ashx'; // 充值卡密提取

    //// http://xxxxxxxx/API/CodeGet.ashx
    //const API_CODE_GET = 'CodeGet.ashx'; // 串码提取

    //// http://xxxxxxxx/API/TicketGet.ashx
    //const API_TICKET_GET = 'TicketGet.ashx'; // 电影票提取

    //// http://xxxxxxxx/API/MobileGet.ashx
    //const API_MOBILE_GET = 'MobileGet.ashx'; // 话费充值

    //// http://xxxxxxxx/API/MobileFlowGet.ashx
    const API_MOBILE_FLOW_GET = 'MobileFlowGet.ashx'; // 流量充值


    /**
     * 服务地址
     *
     * @var
     */
    protected $_server;

    /**
     * 获取 API 校验
     *
     * @var
     */
    protected $_appkey;

    /**
     * 客户/账号
     *
     * @var
     */
    protected $_cid;

    /**
     * 订单流水号
     *
     * @var
     */
    protected $_order_id;

    /**
     * 手机号
     *
     * @var
     */
    protected $_mobile;


    public function __construct($config)
    {
        if (!is_array($config))
            throw new \Exception('请设置好参数并且配置参数必须是数组', 500);

        if (!$config['cid'])
            throw new \Exception('缺少cid参数', 500);

        if (!$config['appkey'])
            throw new \Exception('缺少appkey参数', 500);

        if (!$config['url'])
            throw new \Exception('缺少url参数', 500);


        $this->_server = $config['url'];
        $this->_appkey = $config['appkey'];
        $this->_cid = $config['cid'];
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
        $this->_mobile = $mobile;
        return $this;
    }

    /**
     * Setter - set order_id.
     *
     * @param $order_id
     * @return $this
     */
    public function orderId($order_id)
    {
        $this->_order_id = $order_id;
        return $this;
    }


    /**
     * Setter - set carrier
     *
     * @param \Hardywen\Flow\服务商 $carrier
     */
    public function carrier($carrier)
    {
        // TODO ...
    }

    /**
     * Getter - get carrier
     *
     * @param $mobile
     */
    public function getCarrier($mobile)
    {
        // TODO ...
    }

    /**
     * Setter - set package
     *
     * @param \Hardywen\Flow\流量包大小 $package
     */
    public function package($package)
    {
        // TODO ...
    }


    /**
     * 流量充值
     */
    public function recharge()
    {
        if (!$this->_order_id) {
            throw new \Exception('订单号不能为空', 422);
        }
        if (!$this->_mobile) {
            throw new \Exception('手机号不能为空', 422);
        }

        $params = [
            'cid' => $this->_cid,
            'productid' => self::PRODUCT_MOBILE_FLOW,
            'orderid' => $this->_order_id,
            'mob' => $this->_mobile,
            'timestamps' => $this->__getMsec() // 精确到毫秒
        ];

        /*
         * 签名
         */
        $params['sign'] = $this->__signature($params);

        $url = $this->_server . self::API_MOBILE_FLOW_GET;
        $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($params);

        $response = $this->request($url, $params);

        return $this->_parse($response);
    }

    /**
     * Parse response.
     *
     * @author Eddie
     *
     * @param $response
     * @return array $return
     */
    private function _parse($response)
    {
        $result = json_decode($response);

        if (!$result) return $result;

        $return = [
            'provider' => 'Yuanhui',
            'code'     => $result->Code,
            'msg'      => $result->Msg,
            'success'  => $result->Success
        ];
        if ($result->Code == '1001') {
            $return['success'] = $result->Success;
            $return['order_sn'] = $result->OutOrderId;
        }
        else {
            //$return['msg'] = trans('flow::yuanhui.error.' . $result->Code);
        }

        return $return;
    }


    /**
     * Return signature string.
     *
     * 签名机制 :
     *     请求参数列表中，除sign外其他必填参数均需要参加验签;
     *     请求列表中的所有必填参数的参数值与APPKEY经过按值的字符串格式从小到大排序(字符串格式排序)后, 直接首尾相接连接为一个字符串,
     *     然后用md5指定的加密方式进行加密。
     *
     *
     * @author Eddie
     *
     * @param $params
     * @return string
     */
    private function __signature($params)
    {
        /*
         * 去除 非必选参数
         */
        //unset($params['recallurl']);

        /*
         * Generate signature.
         */
        $signArr = array_values($params);
        sort($signArr, SORT_STRING);

        return strtoupper(md5(implode($signArr) . $this->_appkey));
    }

    /**
     * Get micro-seconds.
     *
     * @author Eddie
     *
     * @return bool|string
     */
    private function __getMsec()
    {
        list($msec, $sec) = explode(' ', microtime());

        return date('YmdHis' . (sprintf('%03d', $msec*1000)), $sec);
    }
}