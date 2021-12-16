<?php
/**
 * @desc    调用whattomineapi，写成一个类，是为了未来可能有内置属性（当前需求没有）
 */
namespace controller;

use service\ToolsService;

/**
 * 基础接口类
 * Class BasicApi
 * @package controller
 */
class WhatToMineApi
{
    /**
     * 币种ID，如 148 = mona
     * @var string
     */
    protected $coinId;

    /**
     * 币种名称，如 mona
     * @var string
     */
    protected $coin;

    /**
     * baseurl，目前是不变量，不当配置了
     * @var string
     */
    protected $baseurl = "https://www.whattomine.com/coins/";


    /**
     * 初始化
     */
    public function __construct($coin)
    {
        $this->coinId = config('coins.' . $coin . '.base.coinid');
        $this->coin = $coin;
    }

    /**
     * Get币种详情
     * @return bool|array
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function getCoinInfo()
    {
        static $info = null;
        if(!$info) {
            $url = $this->baseurl . $this->coinId . ".json";    // 注意，是json..
            $info = ToolsService::httpGetForJson($url);
        }

        return $info;
    }

    // 获取挖矿的币数
    public function getMinedCoinNo()
    {
        static $coinno = 0;
        if(!$coinno) {
            $url = config('coins.' . $this->coin . '.miningpool.balance_url');
            $data = ToolsService::httpGetForJson($url);
            $data = $data['getuserbalance']['data'];
            $coinno = $data['confirmed'] + $data['unconfirmed'] + $data['orphaned'];
        }

        return $coinno;
    }

    /**
     * Get 本块我的shares
     * @return bool|array
     * @throws \Exception
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function getMinedShares()
    {
        static $result = null;
        if(!$result) {
            $url = config('coins.' . $this->coin . '.miningpool.shares_url');
            $data = ToolsService::httpGetForJson($url);
            //pfweb(json_encode($data));
            if( !$data || !$data['getdashboarddata'] || !$data['getdashboarddata']['data']
                || !$data['getdashboarddata']['data']['personal'] 
                || !$data['getdashboarddata']['data']['personal']['shares']
                || !$data['getdashboarddata']['data']['pool']
                || !$data['getdashboarddata']['data']['pool']['blocks']
                || !$data['getdashboarddata']['data']['pool']['blocks'][0]) {
                    return null;
            }

            $data = $data['getdashboarddata']['data'];
            $result['shares'] = $data['personal']['shares']['valid'] + $data['personal']['shares']['unpaid'];
            $result['last_block'] = $data['pool']['blocks'][0]['height'];
            $result['blocktime'] = $data['pool']['blocks'][0]['time'];
        }
        //p($result);
        return $result;
    }
}
