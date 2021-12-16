<?php
/**
 * @author  leafan
 * @desc    所有自定义配置放到这里，此配置文件可以不提交到git
 */

return [
    // mona币
    'mona'      => array(
        'base' => array(
            'coinid' => 148,
        ),
        'nicehash' => array(
            'location' => 0,
            'algo' => 14,
            'pool_host' =>'mona.suprnova.cc',
            'pool_port' =>3000,
            'pool_user' =>'doctot.1',
            'pool_pass' =>'1024',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'https://mona.chainsight.info/api/txs?address=xxxx&pageNum=0',
            // 'url' => 'https://mona.suprnova.cc/index.php?api_key=xxx&page=api&action=getpoolstatus',
            'balance_url' => 'https://mona.suprnova.cc/index.php?page=api&action=getuserbalance&api_key=xxx&id=201833937',
            'shares_url' => 'https://mona.suprnova.cc/index.php?page=api&action=getdashboarddata&api_key=xxxx&id=201833937',
            
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024*1024*1024,
            'suprnova_diff_unit' => 1024,   // suprnova与whattomine矿池间的单位差异
        ),
        'policy' => array(
            'limit_speed' => 1.66,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.01,      // 最少的limit_speed
            'uppest_price' => 0.32,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.01,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.01,     // 每次价格区间误差在多大以内
            'timesincelast' => 410,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了，前面30分钟
            'max_order_num'   => 6,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.0003,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
            'allowed_run_shares' => 11800000,     // 允许跑的shares
        ),

    ),

    // xvg币
    'xvg'      => array(
        'base' => array(
            'coinid' => 217,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 14,
            'pool_host' =>'xvg-lyra.suprnova.cc',
            'pool_port' =>2596,
            'pool_user' =>'doctot.1',
            'pool_pass' =>'2048',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'https://xvg-lyra.suprnova.cc/index.php?page=api&action=getpoolstatus&api_key=xxx',
            'shares_url' => 'https://mona.suprnova.cc/index.php?page=api&action=getdashboarddata&api_key=xxxx&id=2017',
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024*1024*1024,
        ),
        'policy' => array(
            'limit_speed' => 0.21,      // 计划每次购买的 speed，此参数需要经常调试(调快一点，避免每次都是由于时间到了才停，结果好块都没挖到)
            'lowest_limit_speed' => 0.01,      // 最少的limit_speed
            'uppest_price' => 0.041,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.01,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.01,     // 每次价格区间误差在多大以内
            'timesincelast' => 400,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 2,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.00008,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可。注意要小一点，因为nicehash减速有延迟
        ),

    ),
    // vtc 币
    'vtc'      => array(
        'base' => array(
            'coinid' => 5,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 14,
            'pool_host' =>'vtc.suprnova.cc',
            'pool_port' =>5679,
            'pool_user' =>'creat.1',
            'pool_pass' =>'44096',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'https://vtc.suprnova.cc/index.php?page=api&action=getpoolstatus&api_key=xxxx'
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024*1024*1024,
            'suprnova_diff_unit' => 1024,   // suprnova与whattomine矿池间的单位差异
        ),
        'policy' => array(
            'limit_speed' => 0.33,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.01,      // 最少的limit_speed
            'uppest_price' => 0.35,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.01,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.01,     // 每次价格区间误差在多大以内
            'timesincelast' => 900,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了，前面30分钟
            'max_order_num'   => 3,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.0004,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
        ),

    ),

    // zen 币
    'zen'      => array(
        'base' => array(
            'coinid' => 185,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 24,
            'pool_host' =>'zen.suprnova.cc',
            'pool_port' =>3619,
            'pool_user' =>'creat.1',
            'pool_pass' =>'12',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'https://luckpool.net/zen/stats',
            'balance_url' => '',
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024,
            'suprnova_diff_unit' => 1024,   // suprnova与whattomine矿池间的单位差异
        ),
        'policy' => array(
            'limit_speed' => 10.2,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.1,      // 最少的limit_speed
            'uppest_price' => 0.0481,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.001,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.001,     // 每次价格区间误差在多大以内
            'timesincelast' => 2000,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 6,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.0008,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
            'allowed_run_shares' => 25600000,     // 允许跑的shares
        ),

    ),
    
    // zec 币
    'zec'      => array(
        'base' => array(
            'coinid' => 166,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 24,
            'pool_host' =>'zec-eu.suprnova.cc',
            'pool_port' =>2143,
            'pool_user' =>'creat.1',
            'pool_pass' =>'1024',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'https://zec.suprnova.cc/index.php?page=api&action=getpoolstatus&api_key=xxxx'
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024,
            'suprnova_diff_unit' => 1024,   // suprnova与whattomine矿池间的单位差异
        ),
        'policy' => array(
            'limit_speed' => 10.1,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.1,      // 最少的limit_speed
            'uppest_price' => 0.0421,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.001,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.001,     // 每次价格区间误差在多大以内
            'timesincelast' => 1800,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 1,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.003,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
        ),
    ),
    // zcl 币
    'zcl'      => array(
        'base' => array(
            'coinid' => 167,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 24,
            'pool_host' =>'zcl.suprnova.cc',
            'pool_port' =>4043,
            'pool_user' =>'creat.1',
            'pool_pass' =>'8192',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'https://zcl.suprnova.cc/index.php?page=api&action=getpoolstatus&api_key=xxxx',
            'balance_url' => 'https://zcl.suprnova.cc/index.php?page=api&action=getuserbalance&api_key=xxxx&id=200977936',
            'shares_url' => 'https://zcl.suprnova.cc/index.php?page=api&action=getdashboarddata&api_key=xxx&id=200977936',
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024,
            'suprnova_diff_unit' => 1024,   // suprnova与whattomine矿池间的单位差异
        ),
        'policy' => array(
            'limit_speed' => 1.1,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.1,      // 最少的limit_speed
            'uppest_price' => 0.0401,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.001,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.001,     // 每次价格区间误差在多大以内
            'timesincelast' => 300,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 1,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.00012,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
            'allowed_run_shares' => 51200000,     // 允许跑的shares
        ),
    ),
    // eth 币
    'eth'      => array(
        'base' => array(
            'coinid' => 151,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 20,
            'pool_host' =>'eth.coinmine.pl',
            'pool_port' =>4000,
            'pool_user' =>'creat.default',
            'pool_pass' =>'x',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'https://www2.coinmine.pl/eth/index.php?page=api&action=getpoolstatus&api_key=xxxx',
            'balance_url' => 'https://www2.coinmine.pl/eth/index.php?page=api&action=getuserbalance&api_key=xxxx',
            'shares_url' => 'https://www2.coinmine.pl/eth/index.php?page=api&action=getdashboarddata&api_key=xxx',
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024,
            'suprnova_diff_unit' => 1024,   // suprnova与whattomine矿池间的单位差异
        ),
        'policy' => array(
            'limit_speed' => 1.1,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.1,      // 最少的limit_speed
            'uppest_price' => 0.0401,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.001,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.001,     // 每次价格区间误差在多大以内
            'timesincelast' => 300,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 1,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.00012,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
            'allowed_run_shares' => 51200000,     // 允许跑的shares
        ),
    ),

    // etn 币
    'etn'      => array(
        'base' => array(
            'coinid' => 213,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 22,
            'pool_host' =>'etn.superpools.online',
            'pool_port' =>9999,
            'pool_user' =>'xxx+xxx.xxx'
',
            'pool_pass' =>'x',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'http://etn.superpools.online:8117/live_stats',
            'balance_url' => '',
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024*1024,
        ),
        'policy' => array(
            'limit_speed' => 0.33,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.01,      // 最少的limit_speed
            'uppest_price' => 4.8066,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.001,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.001,     // 每次价格区间误差在多大以内
            'timesincelast' => 1500,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 1,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.00188,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
        ),
    ),


    // etp 币
    'etp'      => array(
        'base' => array(
            'coinid' => 209,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 22,
            'pool_host' =>'eth.coinmine.pl',
            'pool_port' =>4000,
            'pool_user' =>'xxx+xxxx.xxx
',
            'pool_pass' =>'x',
        ),
        'miningpool' => array(
            // 这个矿池返回数据有点大，尽量减少调用
            'url' => 'http://sandpool.org/api/stats',  
            'balance_url' => '',
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024*1024,
            'suprnova_diff_unit' => 1024,   // 矿池与whattomine矿池间的单位差异
        ),
        'policy' => array(
            'limit_speed' => 0.1,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.01,      // 最少的limit_speed
            'uppest_price' => 0.0301,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.001,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.001,     // 每次价格区间误差在多大以内
            'timesincelast' => 180,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 1,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.00002,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
        ),
    ),
    // dcr 币
    'dcr'      => array(
        'base' => array(
            'coinid' => 152,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 21,
            'pool_host' =>'dcr.suprnova.cc',
            'pool_port' =>2255,
            'pool_user' =>'anouymous.1',
            'pool_pass' =>'4096',
        ),
        'miningpool' => array(
            // 这个矿池返回数据有点大，尽量减少调用
            'url' => 'https://dcr.suprnova.cc/index.php?page=api&action=getpoolstatus&api_key=xxx',
            'balance_url' => 'https://dcr.suprnova.cc/index.php?page=api&action=getuserbalance&api_key=xx',
            'shares_url' => 'https://dcr.suprnova.cc/index.php?page=api&action=getdashboarddat&api_key=xx',
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024*1024*1024*1024,
        ),
        'policy' => array(
            'limit_speed' => 0.01,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.01,      // 最少的limit_speed
            'uppest_price' => 0.0898,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.001,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.001,     // 每次价格区间误差在多大以内
            'timesincelast' => 30000,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 1,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.006,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
        ),
    ),
    // xmr 币
    'xmr'      => array(
        'base' => array(
            'coinid' => 101,
        ),
        'nicehash' => array(
            'location' => 1,
            'algo' => 34,
            'pool_host' =>'multipooler.com',
            'pool_port' =>7777,
            'pool_user' =>'xxx+xxx.123',
            'pool_pass' =>'x',
        ),
        'miningpool' => array(
            // 如有需要，请直接修改api_key，暂时简单搞，直接get一个完整url
            'url' => 'https://multipooler.com:8119/live_stats',
            'balance_url' => '',
        ),
        'whattomine' => array(
            'nethash_unit' => 1024*1024,
        ),
        'policy' => array(
            'limit_speed' => 3.61,      // 计划每次购买的 speed，此参数需要经常调试
            'lowest_limit_speed' => 0.01,      // 最少的limit_speed
            'uppest_price' => 0.0651,    // 此币种在nicehash上能接受最贵的价格，避免损失
            'price_accuracy' => 0.001,  // workerlist和limit list对比时的价格大小对比，各个币种不一样
            'amount'    => 0.005,       // 创建订单默认购买 0.005 btc
            'price_step'   => 0.001,     // 每次价格区间误差在多大以内
            'timesincelast' => 3600,     // 离挖上一个区块过去了多久，如果已经出块太久了就不挖了
            'max_order_num'   => 3,     // 此币种最多的新建订单可以是多少
            'allowed_btc_paid' => 0.005,     // 跑一个区块允许消耗的btc，根据 全网难度来计算即可
        ),
    ),
        
];
