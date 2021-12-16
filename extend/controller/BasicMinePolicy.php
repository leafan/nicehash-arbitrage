<?php
/**
 * @author  leafan
 * @desc    由策略实现者继承，可以派生出各种策略方法
 * @note    该类会使用 nicehash api以及 whattomine api类，属于封装类
 */
namespace controller;

use controller\WhatToMineApi;
use controller\NiceHashApi;
use controller\MiningPoolApi;

/**
 * 策略类
 * Class BasicMinePolicy
 * @package controller
 */
class BasicMinePolicy
{

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


    /**
     * @desc    初始化，把所有类的配置文件加载好，根据币种来读取
     *          继承类只需要直接使用接口即可，简化操作
     *          暂时没想到其他更好的抽象，先这么处理
     * @return  bool|string
     */
    public function __construct($coin)
    {
        $this->whattomine = new WhatToMineApi($coin);
        $this->nicehash = new NiceHashApi($coin);
        $this->miningpool = new MiningPoolApi($coin);
    }

    
}
