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
	use yicmf\Http;

	class Server
	{
		// 需要发送的数据
		private $data = [];
		private $action;
		private $token;
		private $app;
		private $open_cloud = true;
		private $project = 'yicmf';


		/**
		 * Request实例
		 * @var \think\Request
		 */
		protected $request;
		// 服务器地址
		private $server_domain = 'http://cloud.yicmf.com/v1/';

		public function __construct()
		{
			$this->app = Container::get('app');
			$this->request = $this->app['request'];
			$this->project = Config::get('cloud.project');
			if (Config::get('cloud.domain')) {
				$this->server_domain = Config::get('cloud.domain');
			}
			if ($this->open_cloud) {
				if (!Cache::has('open_cloud')) {
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HEADER, 1);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
					curl_setopt($ch, CURLOPT_URL, $this->server_domain . 'foreign/connect');
					curl_exec($ch);
					$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					curl_close($ch);
					Cache::set('open_cloud', $httpcode, 3600 * 24);
				} else {
					$httpcode = Cache::get('open_cloud');
				}
				if (200 != $httpcode) {
					$this->open_cloud = false;
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
			if ($this->open_cloud) {
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
				return ['code' => 1, 'message' => '未开启云服务'];
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
				$result = Http::request($this->server_domain . $this->action, $params, 'POST', $headers);
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
				$host = Config::get($this->project . '.host');
				$ip = '';
			} else {
				$host = $this->request->host(true);
				$ip = $this->request->ip();
			}
			$dentity = [
				'version' => Config::get($this->project . '.version'),
				'edition' => Config::get($this->project . '.edition'),
				'build' => Config::get($this->project . '.build'),
				'web_uuid' => Config::get('ucenter.web_uuid'),
				'lang' => Config::get('app.default_lang'),
				'host' => $host,
				'sapi' => PHP_SAPI,
				'account' => Config::get('cloud.account'),
				'password' => Config::get('cloud.password'),
				'project' => $this->project,
				'ip' => $ip
			];
			return base64_encode(json_encode($dentity));
		}
	}
