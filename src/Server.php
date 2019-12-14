<?php

    // +----------------------------------------------------------------------
    // | 在线云服务
    // +----------------------------------------------------------------------
    // | Copyright (c) 2015-2019 http://www.yicmf.com, All rights reserved.
    // +----------------------------------------------------------------------
    // | Author: 微尘 <yicmf@qq.com>
    // +----------------------------------------------------------------------

    namespace yicmf\cloud;

    use think\facade\Config;
    use think\facade\Cache;
    use think\Exception;
    use think\Container;
    use yicmf\Http;

    class Server
    {
        // 需要发送的数据
        private $data = [];
        private $action;
        private $token;
        private $app;
        private $open = true;

        /**
         * Request实例
         * @var \think\Request
         */
        protected $request;
        // 服务器地址
        private $domain = 'http://cloud.yicmf.com/v1/';

        public function __construct()
        {
            $this->app = Container::get('app');
            $this->open = Config::get('cloud.open');
            $this->domain = Config::get('cloud.domain');
            $this->request = $this->app['request'];
            // 用户获取token
            $this->competence();
        }

        /**
         * 需要发送的数据.
         * @param $data
         * @return $this
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/3/15 12:25
         */
        public function data($data)
        {
            $this->data = $data;

            return $this;
        }

        /**
         * 执行对应命令.
         * @param string $action 例如 version.detection
         * @return array
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/3/15 12:24
         */
        public function action($action)
        {
            if ( $this->open ) {
                if ( empty($this->data) ) {
                    $data = null;
                } else {
                    $data = $this->data;
                    // 重置，以便下一次服务请求
                    $this->data = null;
                }
                $this->action = str_replace('.', '/', $action);;
                return $this->run($data);
            } else {
                return ['code' => 1, '未开启云服务'];
            }
        }

        /**
         * 用户获取token.
         */
        private function competence()
        {
            $key = $this->getTokenKey();
            if ( !Cache::has($key) ) {
                $token = $this->action('foreign.token');
                if (isset($token['code']) &&  0 === $token['code'] ) {
                    Cache::set($key, $token['token']);
                } else {
                    Cache::set($key, 0);
                }
            }
            $this->token = Cache::get($key);
        }

        /**
         * 请求
         * @param array $param
         * @return array
         * @author  : 微尘 <yicmf@qq.com>
         * @datetime: 2019/1/25 18:11
         */
        private function run($param = [])
        {
            $params = [
                'data' => base64_encode(json_encode($param)),
                'identity' => $this->getIdentity(),
                'token' => $this->token
            ];
            try {
                $result = Http::post($this->domain . $this->action, $params);
                if ( !isset($result['content']) ) {
                    throw new Exception($result['message'], $result['code']);
                }
                $result = $result['content'];
            } catch ( Exception $e ) {
                $result['code'] = $e->getCode();
                $result['message'] = $e->getMessage();
            }
            return $result;
        }

        /**
         * 获取token Key 每一个小时更新一次
         * @return string
         */
        public function getTokenKey()
        {
            return md5(date('Y-m-d H') . 'appstore_token');
        }

        public function clearToken()
        {
            return Cache::rm(md5(date('Y-m-d H') . 'appstore_token'));
        }

        /**
         * 会员帐号信息.
         * @return string
         */
        private function getIdentity()
        {
            $dentity = [
                'version' => Config::get('yiget.version'),
                'edition' => Config::get('yiget.edition'),
                'build' => Config::get('yiget.build'),
                'account' => Config::get('setting.appstore_account'),
                'data_auth_key' => Config::get('ucenter.data_auth_key'),
                'web_uuid' => Config::get('ucenter.web_uuid'),
                'password' => Config::get('setting.appstore_password'),
                'ip' => $this->request->ip(),
                'domain' => $this->request->domain(),
                'lang' => $this->request->langset()
            ];

            return base64_encode(json_encode($dentity));
        }
    }
