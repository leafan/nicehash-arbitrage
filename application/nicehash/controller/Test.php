<?php
namespace app\nicehash\controller;

use think\Controller;
use controller\WhatToMineApi;
use controller\NiceHashApi;
use controller\MiningPoolApi;
use controller\BasicMinePolicy;
use controller\PPLNSMiningBase;

/**
 * 应用入口控制器
 * @author Anyon <zoujingli@qq.com>
 */
class Test extends Controller
{
    public function whattomine()
    {
        $whattomine = new WhatToMineApi('mona');

        var_dump($whattomine->getMinedShares());
        //var_dump($whattomine->getCoinInfo());
    }

    public function nicehash()
    {
        $nicehash = new NiceHashApi('zen');

        var_dump($nicehash->getMyOrders());
        //var_dump($nicehash->setOrdersPriceDecrease());
        var_dump($nicehash->getAndUpdateMyOrderInfo());
        //var_dump($nicehash->setOrderLimit('1', '0.1'));
        
    }

    public function miningpool()
    {
        $miningpool = new MiningPoolApi('mona');

        var_dump( $miningpool->getPoolStatus() );
    }

    public function minepolicy()
    {
        $minepolicy = new BasicMinePolicy('mona');

        var_dump($minepolicy->nicehash->getCoinOrders());
    }

    public function mining(){
        $run = new PPLNSMiningBase('mona');
        $run->calc_can_paid_btc();
    }

}
