<?php
/**
 * @desc    PPLNS 矿池挖矿策略 基类
 * 
 * @author  leafan
 */
namespace controller;
use think\Controller;

/**
 * 应用入口控制器
 * @author leafan <leafan@qq.com>
 */
class PPLNSMiningBase extends Controller
{
    protected $coin = 'mona';

    /**
     * nicehash实现类
     * @var class
     */
    public $nicehash;

    /**
     * 矿池实现类
     * @var class
     */
    public $miningpool;

    /**
     * whattomine实现类
     * @var class
     */
    public $whattomine;


    public function __construct($coin)
    {
        parent::__construct();

        $this->coin = $coin;
        $this->whattomine   = new WhatToMineApi($coin);
        $this->nicehash     = new NiceHashApi($coin);
        $this->miningpool   = new MiningPoolApi($coin);
    }

    // 从whattomine中计算算力当前回报价格
    protected function calc_mine_price() {
        $info = $this->whattomine->getCoinInfo();
        if(!$info)  return false;

        $block_time = 60 * 60 * 24 / $info['block_time'];   // 出块时间，转换成多少天出一个块（比如 0.005天）
        $nethash = $info['nethash'] / config('coins.' . $this->coin . '.whattomine.nethash_unit');    // 转换成 对应单位/s

        // 计算每 1TH 或MH 算力回报率是多少 btc，折算成每天回报率，因为nicehash价格是按天计算的
        $price = $block_time * $info['block_reward'] * $info['exchange_rate'] / $nethash;

        // 将结果取最近n个（暂定50）取平均值
        $const_latest_sum_num = 50;
        $lastest_prices = cache($this->coin . '_latest_whattomine_price');  // 取值
        if(!$lastest_prices) {
            // 不存在，就新建一个
            $lastest_prices = array($price);
            cache($this->coin . '_latest_whattomine_price', $lastest_prices);
        } else {
            // 存在，那就取出来，把新的填充到最后，如果超过n个，就把最老的那个删除，然后统计平均值
            array_push($lastest_prices, $price);
            if(count($lastest_prices) > $const_latest_sum_num) {
                array_shift($lastest_prices);
            }
            cache($this->coin . '_latest_whattomine_price', $lastest_prices);

            // 统计平均值..
            $prices_sum = 0;
            foreach($lastest_prices as $k=>$v) {
                $prices_sum += $v;
            }
            $price = $prices_sum/count($lastest_prices);
        }
        //p("in calc_mine_price: coin=" . $this->coin . ", price=$price, lastest_prices=" . json_encode($lastest_prices));
        
        return $price;
    }

    // 从nicehash中计算算力当前价格
    protected function calc_nicehash_reward($speed) {
        $orders = $this->nicehash->getCoinOrders();
        
        //p('orders is: ' . json_encode($orders));
        if(!$orders || !$orders['result'] || !$orders['result']['orders'])  return false;

        $orders = $orders['result']['orders'];

        // 按照策略取最低的哪一行价格
        $total_accepted_speed = 0;
        $total_workers = 0;
        foreach($orders as $k=>$v){
            $total_accepted_speed += $v['accepted_speed'];
            $total_workers += $v['workers'];
        }

        $cur_workers = 0;
        $cur_limit_speed = 0;
        $worker_best_list = array();    // 根据worker矿工方式计算出的最优价格列表
        $limit_best_list = array();     // 根据limit_speed方式计算出的最优价格列表
        foreach($orders as $k=>$v){
            $cur_workers += $v['workers'];
	    if($v['limit_speed'] == 0) {
		$v['limit_speed'] = 99;
            }

            if($v['limit_speed'] != 100) {  // 因为常常有一些大单挂一个100到那里，要排除掉
                $cur_limit_speed += $v['limit_speed'];
            }

            if($total_workers - $cur_workers < 100) {
                // 都快到最后N个矿工了，肯定是好价格，加入价格列表
                array_push($worker_best_list, $v['price']);
            }

            if($cur_limit_speed > ($total_accepted_speed/1000 - $speed)) {
                // 1000是单位差异，也就是快到我最后limit的部分了，也应该是个好价格了
                array_push($limit_best_list, $v['price']);
            }
        }

        //p("in calc_nicehash_reward, worker_best_list=" . json_encode($worker_best_list) . ", limit_best_list=" . json_encode($limit_best_list));

        // 取最优价格
        $best_price = 0;
        $uppest_price = config('coins.' . $this->coin . '.policy.uppest_price');
        $price_accuracy = config('coins.' . $this->coin . '.policy.price_accuracy');
        
        if(abs($worker_best_list[0]-$limit_best_list[0]) <= $price_accuracy && $worker_best_list[0]) {
            $best_price = $worker_best_list[0]>$limit_best_list[0] ? $worker_best_list[0]+0.0001 : $limit_best_list[0]+0.0001;
        } else if(abs($worker_best_list[0]-$limit_best_list[0]) <= $price_accuracy*1.5 && $worker_best_list[0] < $uppest_price) {
            $best_price = $worker_best_list[0]>$limit_best_list[0] ? $limit_best_list[0]+0.0001 : $worker_best_list[0]+0.0001;
        } else {
            $best_price = $worker_best_list[0] < $limit_best_list[0] ? $worker_best_list[0]+0.0001 : $limit_best_list[0]+0.0001;
            //$best_price = ($worker_best_list[0]+$limit_best_list[0])/2;

        }
        if($best_price > $uppest_price) {
            p($this->coin . " price is too big. best price: $best_price is bigger than upprice.");
            //exit('nicehash计算出来的价格差距太大，或者超过了设置的最高价格，退出..! worker best price is: ' . $worker_best_list[0] . ', limit best price is: ' . $limit_best_list[0]);
            return false;
        }
        //p('in calc_nicehash_reward, best_price=' . $best_price);

        return $best_price;
    }

    // 计算最近n次nicehash的平均价格，用于计算待消耗的btc
    protected function calc_nicehash_average_price($new_price){
        return ;
    }

    // 计算某个数字的平均值，需要保存下来cache
    // newer: 最新值； $label: 该值label
    protected function calc_average_things($newer, $name){
        // 将结果取最近n个（暂定50）取平均值
        $const_latest_sum_num = 50;
        $label = $this->coin . '_' . $name;
        $result = $newer;

        $lastest = cache($label);  // 取值
        if(!$lastest) {
            // 不存在，就新建一个
            $lastest = array($newer);
            cache($label, $lastest);
        } else {
            // 存在，那就取出来，把新的填充到最后，如果超过n个，就把最老的那个删除，然后统计平均值
            array_push($lastest, $newer);
            if(count($lastest) > $const_latest_sum_num) {
                array_shift($lastest);
            }
            cache($label, $lastest);

            // 统计平均值..
            $sum = 0;
            foreach($lastest as $k=>$v) {
                $sum += $v;
            }
            $result = $sum/count($lastest);
        }

        p("in calc_average_things, newer=$newer, result=$result, latest=" . json_encode($lastest));

        return $result;
    }

    /**
     * @brief   根据全网算力计算 投入的 btc
     *          whattomine计算： 12.65 * 1.55* = 19.6 TH 出一个块（单位：分钟）
     *          挖30%算力消耗btc：   nicehash平均价格/24/60*全网算力(转换为TH)*30%
     *  
     */
    public function calc_can_paid_btc(){
        $info = $this->whattomine->getCoinInfo();
        if(!$info)  return 0;

        $block_time = 60 * 60 * 24 / $info['block_time'];   // 出块时间，转换成多少天出一个块（比如 0.005天）
        $nethash = $info['nethash'] / config('coins.' . $this->coin . '.whattomine.nethash_unit');

        $this->calc_average_things($nethash, "nethash");
    }


    // 确认是否有对应币种订单，如果没有，建立后exit，否则返回 order信息
    protected function get_or_new_orders_fromdb(){
        // 找一个nicehash订单并开机，理论上来说，一共只需要一个订单即可
        $orders = db('my_orders')->where("pool_host", '=', config('coins.' . $this->coin . '.nicehash.pool_host'))->select();
        if(!$orders || count($orders) <= 0) {  // 新建订单
            /*
            $this->nicehash->createOrder(0.01, 0.01, config('coins.' . $this->coin . '.policy.amount'),
                config('coins.' . $this->coin . '.nicehash.pool_host'),config('coins.' . $this->coin . '.nicehash.pool_port'),
                config('coins.' . $this->coin . '.nicehash.pool_user'),config('coins.' . $this->coin . '.nicehash.pool_pass')
            );
            
            pfweb('创建订单??原因??重新请求再来吧');
            exit();
            */
            return array();
        }

        return $orders;
    }

    protected function get_pool_info() {
        $result = null;
        $info = $this->miningpool->getPoolStatus();
        if(!$info || !$info['txs'] || !$info['txs'][0] || !$info['txs'][1]) {
            return $result;   
        } 
        
        $block = $info['txs'][0];
        if($block['isCoinBase'] != true) {
            $block = $info['txs'][1]; 
            if($block['isCoinBase'] != true) {
                return $result; // 这一次不算了
            }
        }

        $result['timesincelast'] = time()-$block['blocktime'];
        $result['last_block']    = $block['blocktime']; // 用时间做block吧
	p($result);

        return $result;
    }

    protected function get_pool_info2(){
        $result = null;
        $info = $this->miningpool->getPoolStatus();
        p($info);
        if(!$info || !$info['getpoolstatus'] || !$info['getpoolstatus']['data'])
            return $result;

        $result['timesincelast'] = $info['getpoolstatus']['data']['timesincelast'];
        $result['last_block'] = $this->coin . "_" . $info['getpoolstatus']['data']['lastblock'];

        return $result;
    }
    
    /**
     * @brief   固定跑出一个块的x%算力，此函数就是针对指定算力跑多久。同时检查nicehash中的paid_btc是否已经到位了
     * 
     *          比如针对mona币的计算结论为：
     *          whattomine计算： 12.65 * 1.55* = 19.6 TH 出一个块（单位：分钟）
     *          如果跑20%的话，那需要投入算力：1.2TH。如果跑10分钟，则每分钟算力为 1.2/10 = 0.12TH 速率即可
     * 
     * @note    实际实现，根据nicehash返回的  btc_paid 实现的，而不是根据时长。因为nicehash提供的算力不稳定 
     * 
     * @param   <=0: 不能挖矿； >0:可以挖矿
     */
    protected function run_first_x_shares() {
        $poolinfo = $this->get_pool_info();
        if(!$poolinfo){
            return -99;
        }

        $uptime = config('coins.' . $this->coin . '.policy.timesincelast');
        $timesincelast = $poolinfo['timesincelast'];
        $last_block = $poolinfo['last_block'];

        $orders = $this->get_or_new_orders_fromdb();

        if($timesincelast > $uptime*2) {
            return -1;  // 启动时候矿池已启动好久了，也不挖了
        }
        // 以Last Block Found为key，如果不存在此last_block，则表示挖新块了
        $exist = db('mining_status')->where("last_block='$last_block'")->find();
        if(!$exist) {
            $btc_paid_when_start = 0;
            foreach($orders as  $k=>$v) {
                $btc_paid_when_start += $v['btc_paid'];
            }

            $row = array(
                'last_block'                    => $last_block,
                'elasped_seconds_when_start'    => $timesincelast,
                'btc_paid_when_start'           => $btc_paid_when_start,
            );

            db('mining_status')->insert($row);
            
            return 1;    // 启动挖矿
        } else {
            // 确认当前btc_paid份额，如果跑够了，则停掉。或者通过计算时间*limit_speed。我们按 btc_paid 来
            $now_paid_sums = 0;
            foreach($orders as  $k=>$v) {
                $now_paid_sums += $v['btc_paid'];
            }
            
            // 把消耗的btc记到日志
            $row = array(
                'btc_paid_this_block'           => ($now_paid_sums - $exist['btc_paid_when_start']),
            );
            db('mining_status')->where("last_block='$last_block'")->update($row);
            
            if(time()-strtotime($exist['create_at']) > $uptime) {
                p(  $this->coin . " in last block " . $exist['last_block'] . " is up to time, stop mining. has used btc = " 
                    . ($now_paid_sums - $exist['btc_paid_when_start']) . " btc");
                return -2;   // 时间太长了，不要再挖了
            }

            // 如果消耗的btc过大，说明逻辑正常，也不跑了
            $consumed_btc = $now_paid_sums - $exist['btc_paid_when_start'];
            $biggest_btc = config('coins.' . $this->coin . '.policy.allowed_btc_paid');
            if($consumed_btc > $biggest_btc) {
                p(  $this->coin . " in last block " . $exist['last_block'] . " has used too much btc, stop mining. has used=" . 
                    ($now_paid_sums - $exist['btc_paid_when_start']) . " btc, passed time = " . (time() - strtotime($exist['create_at'])). "s");
                return -3;
            }

            p("run_first_x_shares, timesincelast=$timesincelast, btc_paid_when_start=" 
                . $exist['btc_paid_when_start'] . "last_block=" . $exist['last_block']
                . ", now_paid=$now_paid_sums, has used=" 
                . ($now_paid_sums - $exist['btc_paid_when_start']) . " btc");

            if($consumed_btc < $biggest_btc/3) {
                return 2;
            } else if($consumed_btc < $biggest_btc*2/3) {
                return 3;
            }

            return 4;
        }

        return 0;
    }

    /**
     * @brief   固定跑出一个块的x%算力，此函数就是针对指定算力跑多久
     *          根据shares来计算
     * @param   <=0: 不能挖矿； >0:可以挖矿
     */
    protected function run_first_x_shares_by_shares() {
        $info = $this->whattomine->getMinedShares();
        if(!$info){
            p('** Error! getMinedShares failed..');
            return false;
        }  

        $uptime = config('coins.' . $this->coin . '.policy.timesincelast');
        $upshares = config('coins.' . $this->coin . '.policy.allowed_run_shares'); // 允许跑的shares

        $timesincelast = time() - $info['blocktime'];
        $last_block = $this->coin . "_" . $info['last_block'];
        $shares = $info['shares'];
        $orders = $this->get_or_new_orders_fromdb();

        if($timesincelast > $uptime*2) {
            return -1;  // 启动时候矿池已启动好久了，也不挖了
        }
        // 以Last Block Found为key，如果不存在此last_block，则表示挖新块了
        $exist = db('mining_status')->where("last_block='$last_block'")->find();
        if(!$exist) {
            $row = array(
                'last_block'                    => $last_block,
                'elasped_seconds_when_start'    => $timesincelast,
                'run_shares_this_block'         => $shares,
            );

            db('mining_status')->insert($row);
            
            return 1;    // 启动挖矿
        } else {
            // 把消耗的btc记到日志
            $row = array(
                'run_shares_this_block' => $shares,
            );
            db('mining_status')->where("last_block='$last_block'")->update($row);
            
            if(time()-strtotime($exist['create_at']) > $uptime) {
                p(  $this->coin . " in last block " . $exist['last_block'] 
                    . " is up to time, stop mining. has used shares = $shares");
                return -2;   // 时间太长了，不要再挖了
            }

            // 如果跑的shares达标，说明逻辑正常，也不跑了
            if($shares > $upshares) {
                p(  $this->coin . " in last block " . $exist['last_block'] . " has reach its shares, stop mining. shares = $shares" . " btc, passed time = " . (time() - strtotime($exist['create_at'])). "s");
                return -3;
            }

            p("run_first_x_shares_by_shares.. timesincelast=$timesincelast"
                . ", last_block=" . $exist['last_block'] . ", shares=$shares");

            // 根据消耗的shares来判断
            if($shares < $upshares/3) {
                return 2;
            } else if($shares < $upshares*2/3) {
                return 3;
            }
            return 4;
        }

        return 0;
    }

    // 将所有订单全部价格执行下降
    protected function decreaseCoinOrdersPrice(){
        $this->update_and_refill_orders();
        $orders = db('my_orders')->where("pool_host", '=', config('coins.' . $this->coin . '.nicehash.pool_host'))->select();

        p("执行降价逻辑，币为: " . $this->coin);
        foreach($orders as $k=>$v) {
            if($v['price'] <= 0.01) {
                continue;   // 太低了没必要了
            }
            $this->nicehash->setOrdersPriceDecrease($v['order_id']);
            sleep(2);
        }
    }

    // 将超过某价格的订单limit全部调到 0.01，避免损失
    protected function decreaseAllOrdersLimit($big_price) {
        $orders = db('my_orders')->where("pool_host", '=', config('coins.' . $this->coin . '.nicehash.pool_host'))
                ->where("price", ">", $big_price)->select();

        $lowest_limit_speed = config('coins.' . $this->coin . '.policy.lowest_limit_speed');
        foreach($orders as $k=>$v) {
            if($v['limit_speed'] > $lowest_limit_speed) {
                // 降低limit为0.01
                p("limit order to $lowest_limit_speed now... order is: " . $v['order_id']);
                $this->nicehash->setOrderLimit($v['order_id'], $lowest_limit_speed);
            }
        }

        return 0;
    }

    // 检查确认是否需要创建订单，还是选择老订单去update price或limit
    protected function checkNeedCreateOrder($nicehash_best_price, $limit_speed, $uppest_price, $price_step) {
        if($nicehash_best_price > $uppest_price) {
            pfweb("待下单价格大于最高设定价格，不挂单！nicehash_best_price=$nicehash_best_price, uppest_price=$uppest_price\n");
            return;
        }

        $exist = db('my_orders')->where("pool_host", '=', config('coins.' . $this->coin . '.nicehash.pool_host'))
            ->where('price', 'between', [$nicehash_best_price, $nicehash_best_price+$price_step])->find();
        if($exist){
            if($exist['limit_speed'] != $limit_speed) {
                // 修改订单，update limit_speed
                $this->nicehash->setOrderLimit($exist['order_id'], $limit_speed);
            }
        } else {
            // 找一个现存订单中小于上面要求的价格最高的那个单，来挂单
            $replace_order = db('my_orders')->where("pool_host", '=', config('coins.' . $this->coin . '.nicehash.pool_host'))
                            ->where('price', '<=', $nicehash_best_price)->order('price desc')->find();
            if($replace_order){
                $this->nicehash->setOrderPrice($replace_order['order_id'], $nicehash_best_price);
                sleep(2);
                $this->nicehash->setOrderLimit($replace_order['order_id'], $limit_speed);
            } else {
                // 新建订单，但如果总订单大于5个了，也不要挂单了，报错
                $count = db('my_orders')->where("pool_host", '=', config('coins.' . $this->coin . '.nicehash.pool_host'))->count();
                if($count >= config('coins.' . $this->coin . '.policy.max_order_num')) {
                    p("\n\n**** 告警！... 已存在订单过多了！: $count\n\n");
                } else {
                    $this->nicehash->createOrder($limit_speed, $nicehash_best_price, config('coins.' . $this->coin . '.policy.amount'),
                        config('coins.' . $this->coin . '.nicehash.pool_host'),config('coins.' . $this->coin . '.nicehash.pool_port'),
                        config('coins.' . $this->coin . '.nicehash.pool_user'),config('coins.' . $this->coin . '.nicehash.pool_pass')
                    );
                }
            }
        }
    }

    // 更新订单信息到数据库，如果需要补充btc，执行 refill
    protected function update_and_refill_orders(){
        $orders = $this->nicehash->getMyOrders();
        if(!$orders || empty($orders['result']))
            return null;

        if(empty($orders['result']['orders'])) {
            // 说明数据返回正常，删除数据
            db('my_orders')->where("pool_host", '=', config('coins.' . $this->coin . '.nicehash.pool_host'))->delete();
        }
        
        // 先删除对应数据，因为可能有删除订单。最好的是一起比较，不存在就删除，但数据量小，简单处理
        $db_orders = db('my_orders')->where("pool_host", '=', config('coins.' . $this->coin . '.nicehash.pool_host'))->select();
    
        // 更新或存数据库
        $new_orders = array();
        foreach($orders['result']['orders'] as $k=>$v) {
            $new_orders[$v['id']] = 1;

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
            if($row['btc_avail'] < 0.002 && $row['pool_host'] == config('coins.' . $this->coin . '.nicehash.pool_host')) {
                $this->nicehash->refillOrder($v['id'], config('coins.' . $this->coin . '.policy.amount'));
            }
        }

        
        foreach($db_orders as $k=>$v) {
            if(!isset($new_orders[$v['order_id']])) {
                // 此订单不存在了，delete
                db('my_orders')->where(array("order_id"=>$v['order_id']))->delete();
            }
        }
        
    }

    // 针对此币的统计工作
    public function calc_coin_consumed(){
        $orders =   db('my_orders')->where("pool_host", '=', 
                    config('coins.' . $this->coin . '.nicehash.pool_host'))->select();
        
        $paid_sums = 0;
        //p($orders);
        foreach($orders as $k=>$v) {
            $paid_sums += $v['btc_paid'];
        }

        // 计算whattomine，将btc的消耗转换为对应币种
        $info = $this->whattomine->getCoinInfo();
        if(!$info)  return false;
        
        $consumed_coin = $paid_sums / $info['exchange_rate'];
        $mined_coin = $this->whattomine->getMinedCoinNo();
        exit("截止目前 " . $this->coin . " 盈利为: " . ($mined_coin-$consumed_coin) . 
            "，其中，挖出 $mined_coin, 消耗$consumed_coin");
    }

    // 执行定时执行程序，外部调用，一般几分钟执行一次
    public function routine_things() {
        // $this->statistics();
        $this->decreaseCoinOrdersPrice();
    }

    // 执行策略
    public function run() {
        $limit_speed = config('coins.' . $this->coin . '.policy.limit_speed');
        $price_step = config('coins.' . $this->coin . '.policy.price_step');
        $uppest_price = config('coins.' . $this->coin . '.policy.uppest_price');
        
        $this->update_and_refill_orders();  // 更新订单信息到数据库，如果需要补充btc，执行 refill
        
        $whattomine_price = $this->calc_mine_price();
        $nicehash_best_price = $this->calc_nicehash_reward($limit_speed);
        if(!$whattomine_price || !$nicehash_best_price) {
            p($this->coin . " whattomine_price=$whattomine_price, nicehash_best_price=$nicehash_best_price");
            goto decrease_limit;
        }

        p($this->coin . " whattomine_price=$whattomine_price, nicehash_best_price=$nicehash_best_price, whattomine_price/nicehash_bestprice=" 
            . round(abs($whattomine_price-$nicehash_best_price)/$whattomine_price*100) . "%\n");

        // 编写策略代码...
        //$mine_policy = $this->run_first_x_shares_by_shares();
        $mine_policy = $this->run_first_x_shares();
        pfweb("**" . $this->coin . " 币 本次计算得出结果为策略：$mine_policy\n");

        if(     ($mine_policy>0 && $mine_policy<=2 && $whattomine_price>$nicehash_best_price*0.9)
            ||  ($mine_policy==3 && $whattomine_price>$nicehash_best_price)
            ||  ($mine_policy>3  && $whattomine_price>$nicehash_best_price*1.1)) {
            pfweb($this->coin . ' 进入策略执行ing...');
            $this->checkNeedCreateOrder($nicehash_best_price, $limit_speed, $uppest_price, $price_step);
            $this->decreaseAllOrdersLimit($nicehash_best_price+$price_step);
            return;
        }

    decrease_limit:
        pfweb($this->coin . ' 没有执行策略');
        $this->decreaseAllOrdersLimit(0.01);
    }

}
