<?php
/**
 * @desc    各个币种都可以继承父类，然后做自己的相应改动
 */
namespace app\nicehash\controller;

use controller\PPLNSMiningBase;

class ETN extends PPLNSMiningBase{
    
    public function __construct()
    {
        parent::__construct('etn');
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

    // 获取 http://etn.superpools.online/ 信息
    protected function get_pool_info(){
        $result = null;
        $info = $this->miningpool->getPoolStatus();
        if(!$info || !$info['pool'] || !$info['pool']['blocks'])  
            return $result;

        $blockinfo = explode(':', $info['pool']['blocks'][0]);
        $result['timesincelast'] = time() - $blockinfo[1];
        $result['last_block'] = $this->coin . "_" . $info['pool']['blocks'][1];

        $blockinfo1 = explode(':', $info['pool']['blocks'][2]);
        
        // 服务器返回时，有时候是第二个大，有时候是第一个大
        if($blockinfo[1] < $blockinfo1[1]){
            $result['timesincelast'] = time() - $blockinfo1[1];
            $result['last_block'] = $this->coin . "_" . $info['pool']['blocks'][3];
        }

        return $result;
    }

    public function test(){
        $t = $this->get_pool_info();
        pfweb($t);
    }
    
}
