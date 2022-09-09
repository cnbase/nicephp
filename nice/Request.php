<?php

/**
 * 请求类
 */

namespace nice;

use nice\traits\InstanceTrait;

class Request
{
    use InstanceTrait;

    /**
     * 请求方法
     */
    private $METHOD;

    /**
     * 服务器和执行环境
     */
    private $SERVER;

    public function __construct()
    {
        $this->METHOD = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->SERVER = $_SERVER;
    }

    /**
     * 获取值
     */
    private function server($k, $default = '')
    {
        return $this->SERVER[$k] ?? $default;
    }

    /**
     * 本次请求时间
     */
    public function requestTime($float = false)
    {
        return $float ? $this->server('REQUEST_TIME_FLOAT') : $this->server('REQUEST_TIME');
    }

    /**
     * 判断 Ajax 请求
     */
    public function isAjax()
    {
        return ($xmlhttprequest = $this->server('HTTP_X_REQUEST_WITH')) && 'xmlhttprequest' == strtolower($xmlhttprequest) ? true : false;
    }

    /**
     * 判断 GET 请求
     */
    public function isGet()
    {
        return $this->METHOD == 'GET';
    }

    /**
     * 判断 POST 请求
     */
    public function isPost()
    {
        return $this->METHOD == 'POST';
    }

    /**
     * 判断 cli
     */
    public function isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * 判断 CGI
     */
    public function isCgi()
    {
        return strpos(PHP_SAPI, 'cgi') === 0;
    }

    /**
     * 判断 SSL
     */
    public function isSsl()
    {
        if ($this->server('HTTPS') && ('1' == $this->server('HTTPS') || 'on' == strtolower($this->server('HTTPS')))) {
            return true;
        } elseif ('https' == $this->server('REQUEST_SCHEME')) {
            return true;
        } elseif ('443' == $this->server('SERVER_PORT')) {
            return true;
        } elseif ('https' == $this->server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        }
        return false;
    }

    /**
     * 判断手机访问
     */
    public function isMobile()
    {
        if ($this->server('HTTP_VIA') && stristr($this->server('HTTP_VIA'), "wap")) {
            return true;
        } elseif ($this->server('HTTP_ACCEPT') && strpos(strtoupper($this->server('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        } elseif ($this->server('HTTP_X_WAP_PROFILE') || $this->server('HTTP_PROFILE')) {
            return true;
        } elseif ($this->server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $this->server('HTTP_USER_AGENT'))) {
            return true;
        }
        return false;
    }

    /**
     * 获取 IP
     * $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * $adv 是否进行高级模式获取（有可能被伪装）
     */
    public function ip($type = 0, $adv = true)
    {
        $type   = $type ? 1 : 0;
        static $ip = null;

        if (null !== $ip) {
            return $ip[$type];
        }

        $httpAgentIp = 'HTTP_X_REAL_IP'; //IP代理标识

        if ($this->server($httpAgentIp)) {
            $ip = $this->server($httpAgentIp);
        } elseif ($adv) {
            if ($this->server('HTTP_X_FORWARDED_FOR')) {
                $arr = explode(',', $this->server('HTTP_X_FORWARDED_FOR'));
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif ($this->server('HTTP_CLIENT_IP')) {
                $ip = $this->server('HTTP_CLIENT_IP');
            } elseif ($this->server('REMOTE_ADDR')) {
                $ip = $this->server('REMOTE_ADDR');
            }
        } elseif ($this->server('REMOTE_ADDR')) {
            $ip = $this->server('REMOTE_ADDR');
        }

        // IP地址类型
        $ip_mode = (strpos($ip, ':') === false) ? 'ipv4' : 'ipv6';

        // IP地址合法验证
        if (filter_var($ip, FILTER_VALIDATE_IP) !== $ip) {
            $ip = ('ipv4' === $ip_mode) ? '0.0.0.0' : '::';
        }

        // 如果是ipv4地址，则直接使用ip2long返回int类型ip；如果是ipv6地址，暂时不支持，直接返回0
        $long_ip = ('ipv4' === $ip_mode) ? sprintf("%u", ip2long($ip)) : 0;

        $ip = [$ip, $long_ip];

        return $ip[$type];
    }
    
    /**
     * 获取UA
     */
    public function ua()
    {
        return $this->server('HTTP_USER_AGENT');
    }

    /**
     * 获取过滤后的提交值
     */
    private function _get($request_method = '', $name = null, $default = null, $filter = '')
    {
        switch ($request_method) {
            case 'GET':
                $data = $_GET;
                break;
            case 'POST':
                $data = $_POST;
                break;
            case 'REQUEST':
                $data = $_REQUEST;
                break;
            default:
                $data = [];
        }
        if ($name === null) {
            return $data;
        }
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                $data = $default;
                break;
            }
        }
        if ($filter && is_callable($filter)) {
            if (is_array($data)) {
                $data = array_map($filter, $data);
            } else {
                $data = call_user_func($filter, $data);
            }
        }
        return $data;
    }

    /**
     * 获取 GET 参数
     */
    public function get($name = null, $default = null, $filter = '')
    {
        return $this->_get('GET', $name, $default, $filter);
    }

    /**
     * 获取 POST 参数
     */
    public function post($name = null, $default = null, $filter = '')
    {
        return $this->_get('POST', $name, $default, $filter);
    }

    /**
     * 获取 REQUEST 参数
     */
    public function request($name = null, $default = null, $filter = '')
    {
        return $this->_get('REQUEST', $name, $default, $filter);
    }
}
