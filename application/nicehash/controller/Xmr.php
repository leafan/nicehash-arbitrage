<?php
/**
 * @desc    各个币种都可以继承父类，然后做自己的相应改动
 */
namespace app\nicehash\controller;

use controller\PPLNSMiningBase;

class XMR extends PPLNSMiningBase{
    
    public function __construct()
    {
        parent::__construct('xmr');
    }

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

            if($total_workers - $cur_workers < 5000) {
                // 都快到最后N个矿工了，肯定是好价格，加入价格列表
                array_push($worker_best_list, $v['price']);
            }
	    //p($cur_limit_speed/1000);
	    //p($total_accepted_speed * 1000);
            if(($cur_limit_speed/1000) > ($total_accepted_speed * 1000 - $speed)) {
                // 1000是单位差异，也就是快到我最后limit的部分了，也应该是个好价格了
                array_push($limit_best_list, $v['price']);
            }
        }

        //p("in calc_nicehash_reward, worker_best_list=" . json_encode($worker_best_list) . ", limit_best_list=" . json_encode($limit_best_list));

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
