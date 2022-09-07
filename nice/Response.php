<?php

/**
 * 响应类
 */

namespace nice;

use nice\traits\InstanceTrait;

class Response
{
    use InstanceTrait;

    /**
     * 输出前 - 回调函数
     * @var callable
     */
    private $beforeSendFunc;

    /**
     * http header头
     */
    private $HEADER = [];

    /**
     * 输出类型
     */
    private const CONTENT_TYPE = [
        'html'  =>  'text/html; charset=utf-8',
        'text'  =>  'text/plain; charset=utf-8',
        'jpeg'  =>  'image/jpeg',
        'zip'   =>  'application/zip',
        'pdf'   =>  'application/pdf',
        'mpeg'  =>  'audio/mpeg',
        'css'   =>  'text/css',
        'js'    =>  'text/javascript',
        'json'  =>  'application/json; charset=utf-8',
        'xml'   =>  'text/xml',
    ];

    /**
     * 注册输出前Header头处理回调函数
     * @return bool
     */
    public function header_register_callback($func)
    {
        if (!is_callable($func)) {
            return false;
        }
        return header_register_callback($func);
    }

    /**
     * 添加 Header 头
     * @return self
     */
    public function addHeader($header, $replace = true, $response_code = 200)
    {
        array_push($this->HEADER, [$header, $replace, $response_code]);
        return $this;
    }

    /**
     * 删除某个 Header 头
     * @return self
     */
    public function deleteHeader($name)
    {
        $name and header_remove($name);
        return $this;
    }

    /**
     * 移除所有header头
     * @return self
     */
    public function removeHeader()
    {
        header_remove();
        return $this;
    }

    /**
     * 输出数据
     */
    public function send($content)
    {
        $this->setHeader();
        echo $content;
    }

    /**
     * 设置 Header 头
     * @return self
     */
    private function setHeader()
    {
        if ($this->HEADER) {
            foreach ($this->HEADER as $header) {
                header($header[0], $header[1], $header[2]);
            }
        }
        return $this;
    }

    /**
     * 输出指定 Content-Type
     * @var $is_original 是否使用原始 header 字符串
     * @return self
     */
    public function setContentType($headerStr = 'html', $is_original = false)
    {
        if ($is_original) {
            $this->addHeader('Content-Type: ' . $headerStr);
        } else {
            if (isset(self::CONTENT_TYPE[$headerStr])) {
                $this->addHeader('Content-Type: ' . self::CONTENT_TYPE[$headerStr]);
            }
        }
    }

    /**
     * 设置 404 header头
     * @return self
     */
    public function setHeader404()
    {
        header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 404 Not Found');
        header("Status: 404 Not Found");
        return $this;
    }

    /**
     * 重定向
     */
    public function redirect($url)
    {
        $this->addHeader('Location: ' . $url);
    }
}
