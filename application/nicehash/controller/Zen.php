<?php
/**
 * @desc    各个币种都可以继承父类，然后做自己的相应改动
 */
namespace app\nicehash\controller;

use controller\PPLNSMiningBase;

class Zen extends PPLNSMiningBase{
    
    public function __construct()
    {
        parent::__construct('mona');
    }

    /**
     * @brief   此函数为 luckpool 准备，暂时不用了
    protected function get_pool_info(){
        $result = null;
        $info = $this->miningpool->getPoolStatus();
        if(!$info || !$info['poolStats'] || !$info['poolStats']['lastBlock'])  
            return $result;

        $info = explode(':', $info['poolStats']['lastBlock']);

        $result['timesincelast'] = time() - $info[4]/1000;
        $result['last_block'] = $this->coin . "_" . $info[2];

        return $result;
    }
    */

    /*
    // 获取 http://etn.superpools.online/ 信息
    protected function get_pool_info(){
        $result = null;
        $info = $this->miningpool->getPoolStatus();
        if(!$info || !$info['pool'] || !$info['pool']['blocks'])  
            return $result;

        $blockinfo = explode(':', $info['pool']['blocks'][0]);
        $result['timesincelast'] = time() - $blockinfo[1];
        $result['last_block'] = $this->coin . "_" . $info['pool']['blocks'][1];

        return $result;
    }
    */

    public function test(){
        $t = $this->get_pool_info();
        pfweb($t['timesincelast']);
    }
    
}
