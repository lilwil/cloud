<?php

	// +----------------------------------------------------------------------
	// |  YiCmf 云平台下载解压服务
	// +----------------------------------------------------------------------
	// | Copyright (c) 2015-2022 http://www.yicmf.com, All rights reserved.
	// +----------------------------------------------------------------------
	// | Author: 微尘 <yicmf@qq.com>
	// +----------------------------------------------------------------------

	namespace yicmf\cloud;

	use think\facade\Config;

	class Download
	{
		//错误信息
		private $error = '出现未知错误 Download ！';

		/**
		 * 存储文件.
		 * @param string $packageUrl 文件请求地址
		 * @param string $md5 文件哈希
		 * @return bool
		 */
		public function storageFile($packageUrl, $type, $md5 = '')
		{
			try {
				//检查链接可用
				$array = get_headers($packageUrl, 1);
				if (!preg_match('/200/', $array[0])) {
					throw new \Exception('当前下载文件已经失效！' . $packageUrl);
				}
				$tmpdir = $this->getTempFile($packageUrl, $type);
				if (!file_exists($tmpdir)) {
					//发生错误
					if (mkdir($tmpdir, 0777, true) === false) {
						//错误信息
						throw new \Exception("创建临时缓存目录{$tmpdir}失败！");
					}
				}
				//本地文件名
				$locale = $tmpdir . $this->getPackName($packageUrl);
				//文件哈希验证
				if ($this->validFile($locale, $md5)) {
					//直接使用已有安装包
					$package = $locale;
				} else {
					//下载文件包
					$package = $this->download($packageUrl, $locale);
					if ($package === false) {
						throw new \Exception($this->error ?: '下载远程文件失败！');
					}
				}
				$data['code'] = 0;
				$data['message'] = '文件下载完毕！';
			} catch (\Exception $e) {
				$data['code'] = 1;
				$data['message'] = $e->getMessage();
			}
			return $data;
		}

		/**
		 * 解压缩
		 * @param string $package
		 * @param string $newdir
		 * @param boolean $newdir
		 */
		public function extractFileTo($packageUrl, $newdir, $type, $delete_package = false)
		{
			try {
				$tmpdir = $this->getTempFile($packageUrl, $type);
				$package = $tmpdir . $this->getPackName($packageUrl);
				//删除文件包
				$zip = new \ZipArchive;
				$res = $zip->open($package);
				if ($res !== true) {
					throw new \Exception('无法正常解压文件！' . $res);
				}
				//解压到临时目录
				$zip->extractTo($newdir);
				$zip->close();
				// 删除压缩包
				$delete_package && unlink($package);
				$data['code'] = 0;
				$data['message'] = '解压完成移动完毕！';
			} catch (\Exception $e) {
				$data['code'] = 1;
				$data['message'] = $e->getMessage();
			}
			return $data;
		}

		/**递归方式创建文件夹
		 * @param $dir
		 * @return bool
		 */
		public function createFolder($dir)
		{
			return is_dir($dir) or ($this->createFolder(dirname($dir)) and mkdir($dir, 0777));
		}

		/**
		 * 移动文件到指定目录.
		 * @param type $tmpdir 需要移动的文件路径
		 * @param type $newdir 目标路径
		 * @return bool
		 */
		public function movedFile($tmpdir, $newdir)
		{
			try {
				//删除文件包
				$list = $this->rglob($tmpdir, GLOB_BRACE);
				if (empty($list)) {
					throw new \Exception('移动文件到指定目录错误，原因：文件列表为空！');
				}
				//权限检查
				$this->competence($tmpdir, $newdir);
				//批量迁移文件
				foreach ($list as $file) {
					$newd = str_replace($tmpdir, $newdir, $file);
					//目录
					$dirname = dirname($newd);
					if (file_exists($dirname) == false && mkdir($dirname, 0777, true) == false) {
						throw new \Exception("创建文件夹{$dirname}失败！");
					}
					//检查缓存包中的文件如果文件或者文件夹存在，但是不可写提示错误
					if (file_exists($file) && is_writable($file) == false) {
						throw new \Exception("文件或者目录{$file}，不可写！");
					}
					//检查目标文件是否存在，如果文件或者文件夹存在，但是不可写提示错误
					if (file_exists($newd) && is_writable($newd) == false) {
						throw new \Exception("文件或者目录{$newd}，不可写！");
					}
					//检查缓存包对应的文件是否文件夹，如果是，则创建文件夹
					if (is_dir($file)) {
						//文件夹不存在则创建
						if (file_exists($newd) == false && mkdir($newd, 0777, true) == false) {
							throw new \Exception("创建文件夹{$newd}失败！");
						}
					} else {
						//========文件处理！=============
						if (file_exists($newd)) {
							//删除旧文件（winodws 环境需要）
							if (!unlink($newd)) {
								throw new \Exception("无法删除{$newd}文件！");
							}
						}
						//生成新文件，也就是把下载的，生成到新的路径中去
						if (!rename($file, $newd)) {
							throw new \Exception("无法生成{$newd}文件！");
						}
					}
				}
				//删除临时目录
				$dir = new FileOperation();
				$dir->delDir($tmpdir);
				$data['code'] = 0;
				$data['message'] = '应用移动完毕！';
			} catch (\Exception $e) {
				$data['code'] = 1;
				$data['message'] = $e->getMessage();
			}
			return $data;
		}

		/**
		 * 文件权限检查.
		 * @param type $tmpdir 需要移动的文件路径
		 * @param type $newdir 目标路径
		 * @return bool
		 */
		public function competence($tmpdir, $newdir)
		{
			$list = $this->rglob($tmpdir, GLOB_BRACE);
			if (empty($list)) {
				return true;
			}
			//权限检查
			foreach ($list as $file) {
				$newd = str_replace($tmpdir, $newdir, $file);
				//目录
				$dirname = dirname($newd);
				if (file_exists($dirname) == false && mkdir($dirname, 0777, true) == false) {
					throw new \Exception("创建文件夹{$dirname}失败！");
				}
				//检查缓存包中的文件如果文件或者文件夹存在，但是不可写提示错误
				if (file_exists($file) && is_writable($file) == false) {
					throw new \Exception("文件或者目录{$file}，不可写！");
				}
				//检查目标文件是否存在，如果文件或者文件夹存在，但是不可写提示错误
				if (file_exists($newd) && is_writable($newd) == false) {
					throw new \Exception("文件或者目录{$newd}，不可写！");
				}
				//检查缓存包对应的文件是否文件夹，如果是，则创建文件夹
				if (is_dir($file)) {
					//文件夹不存在则创建
					if (file_exists($newd) == false && mkdir($newd, 0777, true) == false) {
						throw new \Exception("创建文件夹{$newd}失败！");
					}
				} else {
					//========文件处理！=============
					if (file_exists($newd)) {
						if (!is_writable($newd)) {
							throw new \Exception("文件 {$newd} 不可写！");
						}
					}
				}
			}
			return true;
		}

		protected function _treeDirectory($dir, $root)
		{
			$files = [];
			$filenames = glob($dir . DIRECTORY_SEPARATOR . '*');
			foreach ($filenames as $file) {
				if (is_dir($file)) {
					$files = array_merge($files, $this->_treeDirectory($file, $root));
				} else {
					$files[] = str_replace($root, '', $file);
					//                 $files[] = str_replace($root, '',dirname($file).DIRECTORY_SEPARATOR.'<span class=text-success>'.basename($file).'</span>');
				}
			}
			return $files;
		}

		/**
		 * 遍历文件目录，返回目录下所有文件列表.
		 * @param type $pattern 路径及表达式
		 * @param type $flags 附加选项
		 * @param type $ignore 需要忽略的文件
		 * @return type
		 */
		public function rglob($pattern, $flags = 0, $ignore = [])
		{
			$files = [];
			//获取子文件
			$filenames = glob($pattern . DIRECTORY_SEPARATOR . '*', $flags);
			foreach ($filenames as $file) {
				if ($ignore && in_array($file, $ignore)) {
					continue;
				}
				if (is_dir($file)) {
					$files = array_merge($files, $this->rglob($file, $flags, $ignore));
				} else {
					$files[] = $file;
				}
			}
			return $files;
		}

		/**
		 * 验证文件哈希.
		 * @param type $file 文件路径
		 * @param type $hash MD5 值
		 * @return type
		 */
		public function validFile($file, $hash)
		{
			return file_exists($file) && md5_file($file) == $hash;
		}

		/**
		 * 下载文件包临时存放路径.
		 * @param type $file 远程地址
		 * @return type
		 */
		public function getPackName($file)
		{
			$pathinfo = pathinfo($file);
			return md5(basename($file)) . '.' . $pathinfo['extension'];
		}

		/**
		 * 获取临时目录路径.
		 * @param string $file 远程地址
		 * @return string
		 */
		public function getTempFile($file, $type)
		{
			$basename = pathinfo($file);
			return Config::get('system_appstore_path') . $type . DIRECTORY_SEPARATOR . time_format(time(), 'Ymd') . DIRECTORY_SEPARATOR;
		}

		/**
		 * 远程保存.
		 * @param type $url 远程地址
		 * @param type $file 保存路径
		 * @param type $timeout 超时时间
		 * @return bool
		 */
		public function download($url, $file = '', $timeout = 60)
		{
			if (empty($url)) {
				throw new \Exception('下载地址为空！');
			}
			//提取文件名
			$filename = pathinfo($url, PATHINFO_BASENAME);
			if ($file && is_dir($file)) {
				//构造存储名称
				$file = $file . $filename;
			} else {
				//提取文件名
				$file = empty($file) ? $filename : $file;
				//提取目录名
				$dir = pathinfo($file, PATHINFO_DIRNAME);
				//目录不存在时创建
				!is_dir($dir) && mkdir($dir, 0755, true);
				$url = str_replace(' ', '%20', $url);
			}
			if (function_exists('curl_init')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				$temp = curl_exec($ch);
				if (!curl_error($ch)) {
					if (empty($temp)) {
						throw new \Exception('下载失败，下载的文件为空！');
					}
					if (file_put_contents($file, $temp)) {
						return $file;
					} else {
						throw new \Exception("保存文件失败！文件:{$file}");
					}
				} else {
					$error = curl_error($ch);
					throw new \Exception('Curl 下载出现错误！' . $error);
				}
			} else {
				//PHP 5.3 兼容
				if (PHP_VERSION >= '5.3') {
					$userAgent = $_SERVER['HTTP_USER_AGENT'];
					$opts = [
						'http' => [
							'method' => 'GET',
							'header' => $userAgent,
							'timeout' => $timeout,],
					];
					$context = stream_context_create($opts);
					$res = copy($url, $file, $context);
				} else {
					$res = copy($url, $file);
				}
				if ($res) {
					return $file;
				}
				throw new \Exception('使用 copy 下载文件失败，请检查防火墙，或者网络不稳定请稍后！');
			}
		}
	}
