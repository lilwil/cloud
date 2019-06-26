<?php

// +----------------------------------------------------------------------
// | Http 工具类
// | 提供一系列的Http方法
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2019 http://www.yicmf.com, All rights reserved.
// +----------------------------------------------------------------------
// | Author: 微尘 <yicmf@qq.com>
// +----------------------------------------------------------------------

namespace yicmf\cloud;

use think\Exception;

class Http
{
    /**
     * 采集远程文件.
     *
     * @param string $remote 远程文件名
     * @param string $local  本地保存文件名
     *
     * @return mixed
     */
    public static function curlDownload($remote, $local)
    {
        $cp = curl_init($remote);
        $fp = fopen($local, 'w');
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_exec($cp);
        curl_close($cp);
        fclose($fp);
    }

    /**
     * 使用 fsockopen 通过 HTTP 协议直接访问(采集)远程文件
     * 如果主机或服务器没有开启 CURL 扩展可考虑使用
     * fsockopen 比 CURL 稍慢,但性能稳定.
     *
     * @static
     *
     * @param string $url  远程URL
     * @param array  $conf 其他配置信息
     *                     int   limit 分段读取字符个数
     *                     string post  post的内容,字符串或数组,key=value&形式
     *                     string cookie 携带cookie访问,该参数是cookie内容
     *                     string ip    如果该参数传入,$url将不被使用,ip访问优先
     *                     int    timeout 采集超时时间
     *                     bool   block 是否阻塞访问,默认为true
     *
     * @return mixed
     */
    public static function fsockopenDownload($url, $conf = [])
    {
        $return = '';
        if (!is_array($conf)) {
            return $return;
        }

        $matches = parse_url($url);
        !isset($matches['host']) && $matches['host'] = '';
        !isset($matches['path']) && $matches['path'] = '';
        !isset($matches['query']) && $matches['query'] = '';
        !isset($matches['port']) && $matches['port'] = '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'].($matches['query'] ? '?'.$matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;

        $conf_arr = [
            'limit'        => 0,
            'post'         => '',
            'cookie'       => '',
            'ip'           => '',
            'timeout'      => 15,
            'block'        => true,
            ];

        foreach (array_merge($conf_arr, $conf) as $k=>$v) {
            ${$k} = $v;
        }

        if ($post) {
            if (is_array($post)) {
                $post = http_build_query($post);
            }
            $out = "POST $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: '.strlen($post)."\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: $cookie\r\n\r\n";
        }
        $fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $block);
            stream_set_timeout($fp, $timeout);
            @fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if (!$status['timed_out']) {
                while (!feof($fp)) {
                    if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                        break;
                    }
                }

                $stop = false;
                while (!feof($fp) && !$stop) {
                    $data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
                    $return .= $data;
                    if ($limit) {
                        $limit -= strlen($data);
                        $stop = $limit <= 0;
                    }
                }
            }
            @fclose($fp);

            return $return;
        }
    }

    /**
     * 下载文件
     * 可以指定下载显示的文件名，并自动发送相应的Header信息
     * 如果指定了content参数，则下载该参数的内容.
     *
     * @static
     *
     * @param string $filename 下载文件名
     * @param string $showname 下载显示的文件名
     * @param string $content  下载的内容
     * @param int    $expire   下载内容浏览器缓存时间
     *
     * @return void
     */
    public static function download($filename, $showname = '', $content = '', $expire = 180)
    {
        if (is_file($filename)) {
            $length = filesize($filename);
        } elseif (is_file(UPLOAD_PATH.$filename)) {
            $filename = UPLOAD_PATH.$filename;
            $length = filesize($filename);
        } elseif ($content != '') {
            $length = strlen($content);
        } else {
            E($filename.L('下载文件不存在！'));
        }
        if (empty($showname)) {
            $showname = $filename;
        }
        $showname = basename($showname);
// 		if(!empty($filename)) {
// 			$finfo 	= 	new \finfo(FILEINFO_MIME);
// 			$type 	= 	$finfo->file($filename);
// 		}else{
            $type = 'application/octet-stream';
// 		}
        //发送Http Header信息 开始下载
        header('Pragma: public');
        header('Cache-control: max-age='.$expire);
        //header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Expires: '.gmdate('D, d M Y H:i:s', time() + $expire).'GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).'GMT');
        header('Content-Disposition: attachment; filename='.$showname);
        header('Content-Length: '.$length);
        header('Content-type: '.$type);
        header('Content-Encoding: none');
        header('Content-Transfer-Encoding: binary');
        if ($content == '') {
            readfile($filename);
        } else {
            echo $content;
        }
    }

    /**
     * 显示HTTP Header 信息.
     *
     * @return string
     */
    public static function getHeaderInfo($header = '', $echo = true)
    {
        ob_start();
        $headers = getallheaders();
        if (!empty($header)) {
            $info = $headers[$header];
            echo $header.':'.$info."\n";
        } else {
            foreach ($headers as $key=>$val) {
                echo "$key:$val\n";
            }
        }
        $output = ob_get_clean();
        if ($echo) {
            echo nl2br($output);
        } else {
            return $output;
        }
    }

    /**
     * HTTP Protocol defined status codes.
     *
     * @param int $num
     */
    public static function sendHttpStatus($code)
    {
        static $_status = [
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',

            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',

            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',

            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded',
        ];
        if (isset($_status[$code])) {
            header('HTTP/1.1 '.$code.' '.$_status[$code]);
        }
    }

    /**
     * curl支持 检测.
     *
     * @return bool
     */
    public static function create()
    {
        $ch = null;
        if (!function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init();
        if (!is_resource($ch)) {
            return false;
        }

        return $ch;
    }

    /**
     * 高级http请求
     * @param string $url  要获取内容的URL，必须是以http或是https开头
     * @param array|null|string $param 请求的参数
     * @param string $method 当前的请求方式，默认为get
     * @param array $extra 请求附加值
     * @param number $timeout 超时时间
     * @throws Exception
     */
    public static function request($url, $param = null,$method = 'GET', $extra = [], $timeout = 30)
    {
        $urlset = parse_url($url);
        if (empty($urlset['path'])) {
            $urlset['path'] = '/';
        }
        if (! empty($urlset['query'])) {
            $urlset['query'] = "?{$urlset['query']}";
        }
        if (empty($urlset['port'])) {
            $urlset['port'] = $urlset['scheme'] == 'https' ? '443' : '80';
        }
        if (strpos($url, 'https://') !== false && ! extension_loaded('openssl')) {
            if (! extension_loaded("openssl")) {
                throw new Exception('请开启您PHP环境的openssl');
            }
        }
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            $ch = curl_init();
            if (! empty($extra['ip'])) {
                $extra['Host'] = $urlset['host'];
                $urlset['host'] = $extra['ip'];
                unset($extra['ip']);
            }
            curl_setopt($ch, CURLOPT_URL, $urlset['scheme'] . '://' . $urlset['host'] . ($urlset['port'] == '80' ? '' : ':' . $urlset['port']) . $urlset['path'] . (isset($urlset['query'])?$urlset['query']:''));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            // 如果你想CURL报告每一件意外的事情，设置这个选项为一个非零值。
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            @curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            if ($param) {
                if (is_array($param)) {
                    $filepost = false;
                    foreach ($param as $name => &$value) {
                        if (version_compare(phpversion(), '5.6') >= 0 && substr($value, 0, 1) == '@') {
                            $value = new \CURLFile(ltrim($value, '@'));
                        }
                        if ((is_string($value) && substr($value, 0, 1) == '@') || (class_exists('CURLFile') && $value instanceof \CURLFile)) {
                            $filepost = true;
                        }
                    }
                    if (! $filepost) {
                        $param = http_build_query($param);
                    }
                }
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
            }
            if (isset($proxy)) {
                // 设置代理服务器
                    curl_setopt($ch, CURLOPT_PROXY, 'host:port');
                    $proxytype = 'CURLPROXY_' . strtoupper('scheme');
                    if (! empty($urls['scheme']) && defined($proxytype)) {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, constant($proxytype));
                    } else {
                        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
                    }
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'auth');
            }
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
            if (defined('CURL_SSLVERSION_TLSv1')) {
                curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            }
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:9.0.1) Gecko/20100101 Firefox/9.0.1');
            // 传递一个连接中需要的用户名和密码，格式为："[username]:[password]"。
//             curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
//             curl_setopt($ch, CURLOPT_USERNAME, "username");
//             curl_setopt($ch, CURLOPT_USERPWD, 'username:password');
            if (! empty($extra) && is_array($extra)) {
                $headers = [];
                foreach ($extra as $opt => $value) {
                    if (strpos($opt, 'CURLOPT_') !== false) {
                        curl_setopt($ch, constant($opt), $value);
                    } elseif (is_numeric($opt)) {
                        curl_setopt($ch, $opt, $value);
                    } else {
                        $headers[] = "{$opt}: {$value}";
                    }
                }
                if (! empty($headers)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }
            }
            $data = curl_exec($ch);
            $status = curl_getinfo($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);
            if ($errno || empty($data)) {
                throw new Exception($error);
            } else {
                return self::responseParse($data);
            }
        }
        $fdata = "{$method} {$urlset['path']}{$urlset['query']} HTTP/1.1\r\n";
        $fdata .= "Host: {$urlset['host']}\r\n";
        if (function_exists('gzdecode')) {
            $fdata .= "Accept-Encoding: gzip, deflate\r\n";
        }
        $fdata .= "Connection: close\r\n";
        if (! empty($extra) && is_array($extra)) {
            foreach ($extra as $opt => $value) {
                if (strpos($opt, 'CURLOPT_') !== false) {
                    $fdata .= "{$opt}: {$value}\r\n";
                }
            }
        }
        $body = '';
        if ($param) {
            if (is_array($param)) {
                $body = http_build_query($param);
            } else {
                $body = urlencode($param);
            }
            $fdata .= 'Content-Length: ' . strlen($body) . "\r\n\r\n{$body}";
        } else {
            $fdata .= "\r\n";
        }
        if ($urlset['scheme'] == 'https') {
            $fp = fsockopen('ssl://' . $urlset['host'], $urlset['port'], $errno, $error);
        } else {
            $fp = fsockopen($urlset['host'], $urlset['port'], $errno, $error);
        }
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $timeout);
        if (! $fp) {
            throw new Exception($error);
        } else {
            fwrite($fp, $fdata);
            $content = '';
            while (! feof($fp))
                $content .= fgets($fp, 512);
                fclose($fp);
                return self::responseParse($content, true);
        }
    }

    protected static function responseParse($data, $chunked = false)
    {
        $rlt = [];
//         $headermeta = explode('HTTP/', $data);
//         dump($headermeta);
//         if (count($headermeta) > 2) {
//             $data = 'HTTP/' . array_pop($headermeta);
//         }
//         halt($data);
        $pos = strpos($data, "\r\n\r\n");
        $split1[0] = substr($data, 0, $pos);
        $split1[1] = substr($data, $pos + 4, strlen($data));
        $split2 = explode("\r\n", $split1[0], 2);
        preg_match('/^(\S+) (\S+) (.*)$/', $split2[0], $matches);
        $rlt['code'] = $matches[2];
        $rlt['status'] = $matches[3];
        if ($pos === false) {
            // 非ajax返回;
            $rlt['content'] = $split1[1];
            return $rlt;
        }else {
            $rlt['responseline'] = $split2[0];
            $header = explode("\r\n", $split2[1]);
            $isgzip = false;
            $ischunk = false;
            foreach ($header as $v) {
                $pos = strpos($v, ':');
                $key = substr($v, 0, $pos);
                $key = strtolower($key);
                $value = trim(substr($v, $pos + 1));
                if (isset($rlt['headers'][$key]) && is_array($rlt['headers'][$key])) {
                    $rlt['headers'][$key][] = $value;
                } elseif (isset($rlt['headers'][$key])) {
                    $temp = $rlt['headers'][$key];
                    unset($rlt['headers'][$key]);
                    $rlt['headers'][$key][] = $temp;
                    $rlt['headers'][$key][] = $value;
                } else {
                    $rlt['headers'][$key] = $value;
                }
                if (! $isgzip && strtolower($key) == 'content-encoding' && strtolower($value) == 'gzip') {
                    $isgzip = true;
                }
                if (! $ischunk && strtolower($key) == 'transfer-encoding' && strtolower($value) == 'chunked') {
                    $ischunk = true;
                }
            }
            if ($chunked && $ischunk) {
                $rlt['content'] = self::responseParseUnchunk($split1[1]);
            } elseif (isset($rlt['headers']['content-type']) && strpos($rlt['headers']['content-type'], 'application/json') !== false) {
                $rlt['content'] = json_decode($split1[1],true);
            }else {
                $rlt['content'] = $split1[1];
            }
            if ($isgzip && function_exists('gzdecode')) {
                $rlt['content'] = gzdecode($rlt['content']);
            }
            if ($rlt['code'] == '100') {
                return self::responseParse($rlt['content']);
            }else {
                return $rlt;
            }
        }
    }
    
    protected static function responseParseUnchunk($str = null)
    {
        if (! is_string($str) or strlen($str) < 1) {
            return false;
        }
        $eol = "\r\n";
        $add = strlen($eol);
        $tmp = $str;
        $str = '';
        do {
            $tmp = ltrim($tmp);
            $pos = strpos($tmp, $eol);
            if ($pos === false) {
                return false;
            }
            $len = hexdec(substr($tmp, 0, $pos));
            if (! is_numeric($len) or $len < 0) {
                return false;
            }
            $str .= substr($tmp, ($pos + $add), $len);
            $tmp = substr($tmp, ($len + $pos + $add));
            $check = trim($tmp);
        } while (! empty($check));
        unset($tmp);
        return $str;
    }

    /**
     * 发送GET请求
     * 
     * @param unknown $url            
     */
    public static function get($url,$param = [])
    {
        return self::request($url,$param,'GET');
    }

    /**
     * 发送POST请求
     * @param string $url  要获取内容的URL，必须是以http或是https开头
     * @param array|string $post 数组格式，要POST请求的数据
     */
    public static function post($url, $param = [])
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        return self::request($url, $param,'POST', $headers);
    }
    
    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     *
     * @param string $url    请求URL
     * @param array  $params 请求参数
     * @param string $method 请求方法GET/POST
     *
     * @return array $data   响应数据
     */
    public static function http($url, $params = [], $method = 'GET', $header = [], $multi = false)
    {

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        $opts = [
                CURLOPT_TIMEOUT        => 30, //设置curl超时秒数
                CURLOPT_RETURNTRANSFER => 1,
        ];
        //是否显示头部信息
// 		if ($username != '') {
        $useragent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";      
        $header = array('Accept-Language: zh-cn','Connection: Keep-Alive','Cache-Control: no-cache'); 
        
        //HEADER信息   
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);      
        //USER_AGENT   
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent); 
//         curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
//         curl_setopt($ch, CURLOPT_USERPWD, 'username:password');  
// 		curl_setopt($ch, CURLOPT_USERPWD, 'user:123123');
// 		}
        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($params)) {
                    $opts[CURLOPT_URL] = $url.'?'.http_build_query($params);
                } else {
                    $opts[CURLOPT_URL] = $url;
                }
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new \Exception('不支持的请求方式！');
        }

        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \Exception('请求发生错误：'.$error);
        }

        return  $data;
    }
}//类定义结束
