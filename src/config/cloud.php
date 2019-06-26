<?php

// +----------------------------------------------------------------------
// | 用户中心模块配置文件
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2019 http://www.yicmf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 微尘 <yicmf@qq.com>
// +----------------------------------------------------------------------

return [
        // 超级管理员id,拥有全部权限,只要用户id在这个角色组里的,就跳出认证.可以设置多个值,如[1,2,3]
        'user_administrator' => [1],
        // 系统数据加密设置
        'data_auth_key' => '2SG<zyP!vH5WD(`sZa>%}^NnhE+8j.-3', // 默认数据加密KEY
        // 最大缓存用户数
        'user_max_cache' => 1000,
        //网站唯一标识
        'web_uuid' => '34D12905-9255-28D3-C285-80F6B3D01BD2',
        // 模糊查询的字段
        'search_like' => [
                'remark',
                'title',
                ],
        // 时间区间查询的字段
        'search_between_time' => [
        'create_time',
        'update_time',
        ],
        // 提现手续费
        'cash_poundage'   =>  '0.2',
        // 最低提现金额
        'cash_floor'        => '1',
        // 提现最大次数
        'cash_times'        => '5',
        // 平台抽成
        'take_a_percentage'        => '30',
        // 首单最高
        'first_cash'        => '1',
        // 二单限制置顶任务
        'second_cash'        => '1',
        // 余额兑换金币的数量
        'balance_to_coin'                => '1000',
        // 注册赠送余额
        'register_give'                => '8',
        // 余额兑换金币起步数量
        'balance_to_coin_start'                => '5',
        // 开启的支付方式
        'open_payment'        => ['alipay','wechat'],
        // 支付宝APPID
        'alipay_appid'        => '2016092300577489',
        // 支付宝商户私钥
        'alipay_merchant_private_key'        => 'MIIEowIBAAKCAQEAvyvjcauSO8syYBcTyzgXLnrbhTsOfj7chfukjsz9lKMHBhL4wZiw7XDW5zRQCHW5YN/VxBkH7bXfPrZ/j18E0lAxor+vhEoZOJNB7hSzzrmqYIwcEcm2e0lDlUPd/Hh6TU94ZIMsInGNcDo6CRGoklHipxogi5T6KNUPjOJF0xY7ONmIsEdDCzNpcJyYt/TgHwPD/Q5lUzAhNrz8tfNC6m6rRpakTdeF93QO+dS5zySvXzcZpTqIyTxE+iuzfY5FCww8ClJJmK9e/vBCSf+2ETp2B9MhrLx6Wo8s2d6I3hgbYSm2wVTVh8d08XMbyMe9HjrqipyH83w+ZqCVojON8wIDAQABAoIBAGFXHZaRgAJGMr5OwdtmEheuovwx1+1cYLkwKtgzdKMsZ7UmD9ezwdME88gCEQZduyiikJwrCqh1RNkP39/GyBO2la+C3wIDINh30shBblTCoQhMDzbXeL1JzsnAJtYZGl0nK+wyBlT7cMNGQqq/fRAT9c6UNSIdl0sXQbXAuUEDXW8dSsvrnE/FH2UEeENxigwgtzrCeWqpAMp6LTJuqg+rh1kvizhqfmeS1/m8rPr4ejQch1ethXnH36Kv/cHxYH+3yyJnLDCH8BuxfP7gV6TGze1vDwwtTxK+0RrWtWJyYN8OGvLyObStKxzIpGKVRs3Vs6O0wrP+SIwM5Rw3X5ECgYEA5ZEDnzh+/OpOCsbb/Hn7dDllyps/zj+SKBypNF+cjqg5FXXZGXoPtUcfywb9d7Pwcj6glgg+9psv3ZJyEjALQ2npoBsYFNW9sc0rXisS40daLwVY9y4D/OkzuCp2Zg8XIrzh/IAWQA4rANIZJTieHBGmqT873WW21CbTLzt7Ig0CgYEA1S8YUVTfo8W3VNuWf7w4EkPVVknnmRvKr+dgTRyqOnJvlvnaZkusg3N0Ez69ACoLhkNxIIqErwPURJZbESeXFk22X0gXphp5v/JQI9aGpkjxw10lwoFThc3sU6OEbU5Spd6zBaCngrAqIjS1/2BDlDqmkD1GMjosm9Xseeytb/8CgYAdXxVojLDqqQu9Iz5IKk86ypE0f/KE7+tCSJB5i/Ya5nkPPtm9Abn6xpPRxR4u743HAC5Jo9pycN6J6c/Adfcq8+UZP/4vxD0V/5sZ3Eb5X0qxk2yTi4alGC0u1ff6DNo37pS3Wqf+IBHuc/MVqQ3Jp5R8OQPuyrG+qQ0CEQkrDQKBgQCFl66ogQRoj+U0Mytbvqpwn7uYCFYu92CL3PXXPOhcGgxd0xMO2csw0O/jg4RTDwYLzEWfO86sEj06AfLtjB69JlPr37SaLaswIvwfiTb2C24dnEimW/7oMcQwIZ9CFDvsn+MV2rg+SIEO0HNgpoWS5TIt34gWv6fmvCclvLBvJwKBgC5inLIXpK/sRkejxkGmNJ9jRk3R+U4qgBGHdQ16X5Cuo3ScU/ur7g0HDQtTS41uA/pOWev4PUshfHZdRXhagsJ3iU+ZZB5tuQkPz/089pbnqg9ZCDIiyIsznkKyM93L/O8chP3kF2y7ustfWMU+MmKniuAhE+/Z/yYJlnKJCyGi',
        // 支付宝支付宝公钥
        'alipay_public_key'        => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAr2yu+53HkK6dQgjMUq37E2kQQHEQky3CwPzQF5etvPqaW1WjXz7I/bkg6ySEMOdTtHESw/E+rJFj1BFZKlGXAZ/kBUN46w8HL6v8CO+Pq6P5QvmgXT0Alm1Rl58gRm2Km9VWNm6ugsugxA0jb1SXCrydtvb+BEXT34R13UpQFIyZ7udoQ9LD/oKO/6Th1Y2osaRpMmZ6c51GNH50nIRpasPYuy/U0Pri0vLnNCcomwXqs/YCgiZ61HyeuWrm/02o5VLGaefrNu7TEwLDd3krVD8NrrqqwPKNMk7KlGaEKtSv38mMvE0W8mRTZ2XDQLnh4VzXi4pirwPRv7QH4GNPcwIDAQAB',
        // 在线时长缺省值
        'online_time_range'   =>  '600',

        // PC微信扫码登录
        'wechat_pc_login'   =>  '1',
        // appid
        'wechat_pc_appid'   =>  'wx3238944cc95fe6c8',
        // appkey
        'wechat_pc_appkey'   =>  '56440b7c3310b52330e028f0c1c3db71',
        // 微信公众号登录
        'wechat_login'   =>  '1',
        // QQ互联登录
        'qq_login'   =>  '1',
        // QQ互联登录appid
        'qq_appid'   =>  '101541393',
        // appkey
        'qq_appkey'   =>  'dcd88733e4da50a20785e4527dd62a0e',
        // QQ回调地址
        'qq_callback'   =>  '{$qq_callback}',

];
