<?php
/**
 * @desc    各个币种都可以继承父类，然后做自己的相应改动
 */
namespace app\nicehash\controller;

use controller\PPLNSMiningBase;

class Etp extends PPLNSMiningBase{
    
    public function __construct()
    {
        parent::__construct('etp');
    }


    /**
     * @brief   固定跑出一个块的x%算力，此函数就是针对指定算力跑多久。同时检查nicehash中的paid_btc是否已经到位了     
     * 
     * @note    重写该函数，爱怎么实现就怎么实现，不影响其他币
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

            // 消耗了一半的btc了，第二阶段，否则表示第三阶段
            if($consumed_btc < $biggest_btc/2) {
                return 2;
            }
            return 3;
        }

        return 0;
    }


    protected function get_pool_info(){
        $result = null;
        $info = $this->miningpool->getPoolStatus();
        if(!$info || !$info['stats'] || !$info['stats']['lastBlockFound'])  
            return $result;

        $result['timesincelast'] = time() - $info['stats']['lastBlockFound'];

        // 直接拿时间当key，简化获取动作
        $result['last_block'] = $this->coin . "_" . $info['stats']['lastBlockFound'];

        return $result;
    }

    public function test(){
        $t = $this->get_pool_info();
        pfweb($t['timesincelast']);
    }
    
}
