<?php

	// +----------------------------------------------------------------------
	// | 在线云服务
	// +----------------------------------------------------------------------
	// | Copyright (c) 2015-2022 http://www.yicmf.com, All rights reserved.
	// +----------------------------------------------------------------------
	// | Author: 微尘 <yicmf@qq.com>
	// +----------------------------------------------------------------------

	namespace yicmf\cloud;

	use think\facade\Config;
	use think\facade\Cache;
	use think\Exception;
	use think\Container;
	use think\facade\Lang;
	use yicmf\Http;

	class Server
	{
		// 需要发送的数据
		private $data = [];
		private $action;
		private $token;
		private $app;
		/**
		 * Request实例
		 * @var \think\Request
		 */
		protected $request;
		private $config = [
			// open
			'open' => true,
			// appid
			'app_id' => '',
			// 密钥
			'app_key' => '',
			// 使用项目
			'project' => 'yicmf',
			// 默认域名
			'domain' => 'https://cloud.yicmf.com/api/cloud/',
		];

		public function __construct()
		{
			$this->app = Container::get('app');
			$this->request = $this->app['request'];
			$this->config = array_merge($this->config,Config::get('cloud.')); 
			Lang::load(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $this->request->langset() . '.php');
 
			if ($this->config['open']) {
				if (!Cache::has('open_cloud')) {
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HEADER, 1);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
					curl_setopt($ch, CURLOPT_URL, $this->config['domain'] . 'foreign/connect');
					curl_exec($ch);
					$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					Cache::set('open_cloud', $httpcode, 3600 * 24);
				} else {
					$httpcode = Cache::get('open_cloud');
				}
				if (200 != $httpcode) {
					$this->config['open'] = false;
				}
			}
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
			if ($this->config['open']) {
				if (empty($this->data)) {
					$data = null;
				} else {
					$data = $this->data;
					// 重置，以便下一次服务请求
					$this->data = null;
				}
				$this->action = str_replace('.', '/', $action);;
				return $this->run($data);
			} else {
				return ['code' => 1, 'message' => Lang::get('cloud is close!')];
			}
		}


		/**
		 * 请求
		 * @param array $param
		 * @return array
		 */
		private function run($param = [])
		{
			$params = [
				'data' => base64_encode(json_encode($param)),
				'identity' => $this->getIdentity(),
				'signType' => 'base64',
				'format' => 'json',
			];
			try {
				$headers = [
					'Content-Type' => 'application/x-www-form-urlencoded',
					'cloud-token' => think_encrypt(md5($params['data'] . $params['identity']), 'yicmf'),
				];
				$result = Http::request($this->config['domain'] . $this->action, $params, 'POST', $headers);
				if (!isset($result['content'])) {
					throw new Exception($result['message'], $result['code']);
				}
				$result = $result['content'];
			} catch (Exception $e) {
				$result['code'] = $e->getCode();
				$result['message'] = $e->getMessage();
			}
			return $result;
		}


		/**
		 * 会员帐号信息.
		 * @return string
		 */
		private function getIdentity()
		{
			if ('cli' == PHP_SAPI) {
				$host = Config::get($this->config['project'] . '.host');
				$ip = '';
			} else {
				$host = $this->request->host(true);
				$ip = $this->request->ip();
			}
			$dentity = [
				'version' => Config::get($this->config['project'] . '.version'),
				'edition' => Config::get($this->config['project'] . '.edition'),
				'build' => Config::get($this->config['project'] . '.build'),
				'web_uuid' => Config::get('ucenter.web_uuid'),
				'lang' => $this->request->langset(),
				'host' => $host,
				'sapi' => PHP_SAPI,
				'app_id' => $this->config['app_id'],
				'app_key' => $this->config['app_key'],
				'project' => $this->config['project'],
				'ip' => $ip
			];
			return base64_encode(json_encode($dentity));
		}
	}
