<?php
/**
 * @desc    矿池类，目前不知道其他矿池咋用，因此此类可能大范围重构
 */
namespace controller;

use service\ToolsService;

/**
 * 基础接口类
 * Class BasicApi
 * @package controller
 */
class MiningPoolApi
{
    /**
     * 矿池api key，暂不用，写到配置里面
     * @var string
     */
    // protected $api_key;

    /**
     * url，各个矿场可能不一样的
     * example: https://mona.suprnova.cc/index.php?page=api&action=getpoolstatus&api_key=xxxxxxx
     * @var string
     */
    protected $url;

    /**
     * baseparam，基础的url参数，暂不用
     * example: https://mona.suprnova.cc/index.php?page=api&action=getpoolstatus&api_key=xxxxxxx
     * @var string
     */
    //protected $baseparam;

    /**
     * 初始化
     * @param   配置文件对应块
     */
    public function __construct($coin)
    {
        $this->url = config('coins.' . $coin . '.miningpool.url');
    }


    /**
     * Get 矿池状态
     * @return bool|array
     */
    public function getPoolStatus()
    {
        static $status = null;
        if(!$status) {
            $status = ToolsService::httpGetForJson($this->url);
        }

        return $status;
    }

}
