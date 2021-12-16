<?php
/**
 * @desc    各个币种都可以继承父类，然后做自己的相应改动
 */
namespace app\nicehash\controller;

use controller\PPLNSMiningBase;

class DCR extends PPLNSMiningBase{
    
    public function __construct()
    {
        parent::__construct('dcr');
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

        p("in calc_nicehash_reward, worker_best_list=" . json_encode($worker_best_list) . ", limit_best_list=" . json_encode($limit_best_list));

        // 取最优价格
        $best_price = $worker_best_list[0] + 0.0001;
        $uppest_price = config('coins.' . $this->coin . '.policy.uppest_price');
        
        if($best_price > $uppest_price) {
            p($this->coin . " price is too big. best price: $best_price is bigger than upprice.");
            //exit('nicehash计算出来的价格差距太大，或者超过了设置的最高价格，退出..! worker best price is: ' . $worker_best_list[0] . ', limit best price is: ' . $limit_best_list[0]);
            return false;
        }
        //p('in calc_nicehash_reward, best_price=' . $best_price);

        return $best_price;
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

        // 以Last Block Found为key，如果不存在此last_block，则表示挖新块了
        $exist = db('mining_status')->where("last_block='$last_block'")->find();
        if(!$exist) {

            if($timesincelast > $uptime*2) {
                return -1;
            }

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

            // 如果消耗的btc过大
            //////////////////// 优先判断是否btc paid过大，如果是，删除订单 //////////////
            $consumed_btc = $now_paid_sums - $exist['btc_paid_when_start'];
            $biggest_btc = config('coins.' . $this->coin . '.policy.allowed_btc_paid');
            if($consumed_btc > $biggest_btc) {
                p(  $this->coin . " in last block " . $exist['last_block'] . " has used too much btc, stop mining. has used=" . 
                    ($now_paid_sums - $exist['btc_paid_when_start']) . " btc, passed time = " . (time() - strtotime($exist['create_at'])). "s");
                
                ////////// 删除订单 ////////////
                foreach($orders as  $k=>$v) {
                    $this->nicehash->deleteOneOrder($v['order_id']);
                    p('删除订单！！ 订单ID: ' . $v['order_id']);
                }
                
                return -3;
            }
            
            if(time()-strtotime($exist['create_at']) > $uptime) {
                p(  $this->coin . " in last block " . $exist['last_block'] . " is up to time, stop mining. has used btc = " 
                    . ($now_paid_sums - $exist['btc_paid_when_start']) . " btc");
                return -2;   // 时间太长了，不要再挖了
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
    

    public function test(){
        $t = $this->get_pool_info();
        pfweb($t);
    }
    
}
