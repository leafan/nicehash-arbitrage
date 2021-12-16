<?php
/**
 * @desc    各个币种都可以继承父类去玩
 */
namespace app\nicehash\controller;

use think\Controller;
use controller\PPLNSMiningBase;

class Mining {
    // 定时执行函数，避免影响业务逻辑
    public function timer_for_all_coins() {
        $curtime = time();
        $lasttime = cache('decrease_time');
        if($curtime-$lasttime <= 180) {
            exit('未到执行时间, 差值为:' . ($curtime-$lasttime));
        }
        
        cache('decrease_time', $curtime);

        $coins = array('mona','dcr','etn','xmr');
        foreach($coins as $k=>$coin) {
            pfweb("timer_for_all_coins, coin is: $coin");
            $timer = new PPLNSMiningBase($coin);
            $timer->routine_things();
        }
        
        return;
    }

    // 计算当前币的消耗
    public function calc_coin_consumed($coin) {
        $calc = new PPLNSMiningBase($coin);
        $calc->calc_coin_consumed();
    }

    public function mona() {
        $run = new PPLNSMiningBase('mona');
        $run->run();
    }

    public function xvg() {
        $run = new PPLNSMiningBase('xvg');
        $run->run();
    }
    
    public function vtc() {
        $run = new PPLNSMiningBase('vtc');
        $run->run();
    }

    public function zen() {
        $run = new PPLNSMiningBase('zen');
        $run->run();
    }

    public function zec() {
        $run = new PPLNSMiningBase('zec');
        $run->run();
    }

    public function dcr() {
        $run = new PPLNSMiningBase('dcr');
        $run->run();
    }
}
