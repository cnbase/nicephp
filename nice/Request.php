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
     * 是否 Ajax 请求
     */
    public function isAjax()
    {
        return ($xmlhttprequest = ($this->SERVER['HTTP_X_REQUEST_WITH']??'')) && 'xmlhttprequest' == strtolower($xmlhttprequest) ? true : false;
    }
}
