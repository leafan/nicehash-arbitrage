<?php
/**
 * @desc    调用nicehash api
 */


namespace controller;

use service\ToolsService;

/**
 * 基础接口类
 * Class BasicApi
 * @package controller
 */
class NiceHashApi
{

    /**
     * location
     * @var int
     */
    protected $location;

    /**
     * algorithm id
     * @var int
     */
    protected $algo;

    /**
     * appid，用户的id
     * @var string
     */
    private $id;
    

    /**
     * nicehash user api key. important!
     * @var string
     */
    private $secret;


    /**
     * baseurl, it's a constant val now.
     * @var string
     */
    protected $baseurl = 'https://api.nicehash.com/api?';

    /**
     * 缓存数据orders
     * @var string
     */
    protected $myorders = null;

    /**
     * 缓存数据orders
     * @var string
     */
    protected $allorders = null;


    /**
     * 构造函数，执行初始化，代替掉init函数
     */
    public function __construct($coin)
    {
        $this->id = config('nicehash.app_id');
        $this->secret = config('nicehash.app_secret');
        
        // 注意中间的 . 号，不要漏掉了
        $this->location = config('coins.' . $coin . '.nicehash.location');
        $this->algo = config('coins.' . $coin . '.nicehash.algo');
    }

    /**
     * 填充http_build_query基础信息，不需要key构造
     */
    protected function build_public_url($method) {
        $params = array();
        $params['method']      = $method;

        $params['algo']        = $this->algo;
        $params['location']    = $this->location;

        $url = $this->baseurl . http_build_query($params);
        //p($url);

        return $url;
    }

    /**
     * 填充http_build_query基础信息，需要api key的场景
     */
    protected function build_private_url($method, $options=null, $my=false) {
        $params = array();
        $params['method']      = $method;
        if($my) {
            // nicehash的怪用法，多一个空参
            $params['my'] = '';
        }

        $params['id']          = $this->id;
        $params['key']         = $this->secret;
        $params['location']    = $this->location;
        $params['algo']        = $this->algo;

        if($options) {
            $params += $options;
        }

        // url中加一个my空参数
        $url = $this->baseurl . http_build_query($params);
        //p($url);

        return $url;
    }


    /**
     * 获取当前订单列表
     * @return bool|array
     */
    public function getCoinOrders()
    {
        if(!$this->allorders) {
            $url = $this->build_public_url('orders.get');
            $this->allorders = ToolsService::httpGetForJson($url);
        }

        return $this->allorders;
    }

    /**
     * @desc    获取我的当前订单详情
     * @return bool|array
     */
    public function getMyOrders()
    {
        if(!$this->myorders) {
            $url = $this->build_private_url('orders.get', null, true);
            $this->myorders = ToolsService::httpGetForJson($url);
        }

        return $this->myorders;
    }

    /**
     * 已废弃
     * @desc    获取我的当前订单详情，由于id是写在配置的，因此无需传参
     *          相当于实现python中 orders_info 函数
     *          TODO: 此函数属于业务，应该放到 mona.php 中实现，同时实现一个Model类
     * @return bool|array
     */
    public function getAndUpdateMyOrderInfo()
    {
        $orders = $this->getMyOrders();
        if(!$orders || !$orders['result'] || !$orders['result']['orders'])
            return null;
        
        // 存数据库
        foreach($orders['result']['orders'] as $k=>$v) {
            $row = array(
                // 复制到对应字段上
                "order_id" => $v['id'],
                'workers' => $v['workers'],
                'price' => $v['price'],
                'pool_host' => $v['pool_host'],
                'pool_user' => $v['pool_user'],
                'limit_speed' => $v['limit_speed'],
                'accepted_speed' => $v['accepted_speed'],
                'btc_paid' => $v['btc_paid'],
                'btc_avail' => $v['btc_avail']
            );
            $exist = db('my_orders')->where(array("order_id"=>$v['id']))->find();
            if(!$exist) {
                db('my_orders')->insert($row);  // 先不判断返回值了
            } else {
                db('my_orders')->where(array("order_id"=>$v['id']))->update($row);
            }

            // 补给btc不够的订单
            // 这些属于业务逻辑，不应该写到api中，到时候再拆，不太理解逻辑，先实现...
            if($row['btc_avail'] < 0.002) {
                $this->refillOrder($v['id'], 0.005);  // TODO! 0.005 做成配置？
            }
        }
        
        return $orders;
    }

    /**
     * create 订单
     * @return bool|array
     */
    public function createOrder($limit, $price, $amount, $pool_host, $pool_port, $pool_user, $pool_pass)
    {
        //exit('测试阶段，不要在挂单啦！');
        $options = array(
            'limit' => $limit,
            'price' => $price,
            'amount' => $amount,
            'pool_host' => $pool_host,
            'pool_port' => $pool_port,
            'pool_user' => $pool_user,
            'pool_pass' => $pool_pass,
        );

        $url = $this->build_private_url('orders.create', $options);
        $ret = ToolsService::httpGetForJson($url);
        p('挂单，参数为: ' . json_encode($options) . '， 结果为: ' . json_encode($ret));

        return $ret;
    }

    /**
     * 设置订单limit
     * @return bool|array
     */
    public function setOrderLimit($orderId, $limit)
    {
        $options = array(
            'order' => $orderId,
            'limit' => $limit
        );

        $url = $this->build_private_url('orders.set.limit', $options);

        $ret = ToolsService::httpGetForJson($url);
        p('设置order的购买量Limit，参数为: ' . json_encode($options) . '， 结果为: ' . json_encode($ret));

        return $ret;
    }

    /**
     * 设置订单价格，由于nicehash只能增加价格，所以价格需要比订单本身高
     * @return bool|array
     */
    public function setOrderPrice($orderId, $price)
    {
        $options = array(
            'order' => $orderId,
            'price' => $price
        );

        $url = $this->build_private_url('orders.set.price', $options);

        $ret = ToolsService::httpGetForJson($url);
        p('设置order  价格，参数为: ' . json_encode($options) . '， 结果为: ' . json_encode($ret));

        return $ret;
    }

    /**
     * 设置订单 decrease
     * @return bool|array
     */
    public function setOrdersPriceDecrease($orderId)
    {
        $options = array(
            'order' => $orderId,
        );

        $url = $this->build_private_url('orders.set.price.decrease', $options);

        $ret = ToolsService::httpGetForJson($url);
        p('设置order 降价，参数为: ' . json_encode($options) . '， 结果为: ' . json_encode($ret));

        return $ret;
    }

    /**
     * @desc    删除订单
     * @note    TODO, !尚未测试
     * @return bool|array
     */
    public function deleteOneOrder($orderId)
    {
        $options = array(
            'order' => $orderId,
        );

        $url = $this->build_private_url('orders.remove', $options);

        $ret = ToolsService::httpGetForJson($url);
        p('删除order，参数为: ' . json_encode($options) . '， 结果为: ' . json_encode($ret));

        return $ret;
    }

    /**
     * @desc    给订单充值btc
     * @param   $orderId: 订单id; $amount: 充值金额（btc）
     * @note    TODO, !尚未测试
     * @return bool|array
     */
    public function refillOrder($orderId, $amount)
    {
        $options = array(
            'order' => $orderId,
            'amount' => $amount
        );

        $url = $this->build_private_url('orders.refill', $options);

        $ret = ToolsService::httpGetForJson($url);
        p('refillorder，参数为: ' . json_encode($options) . '， 结果为: ' . json_encode($ret));

        return $ret;
    }

}
