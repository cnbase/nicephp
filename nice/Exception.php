<?php

/**
 * 异常处理
 */

namespace nice;

use nice\traits\InstanceTrait;

class Exception
{
    use InstanceTrait;

    /**
     * 错误信息
     */
    private $error = [
        'show'      =>  true, //是否输出
        'htmlTpl'   =>  __DIR__ . '/tpl/Exception.html.php', //HTML模板
        'jsonTpl'   =>  __DIR__ . '/tpl/Exception.json.php', //JSON模板
    ];

    /**
     * 错误日志
     */
    private $trace = [
        'write' =>  true, //是否记录
        'path'  =>  __DIR__ . '/../runtime/trace', //日志目录
    ];

    /**
     * 初始化配置
     * @return self
     */
    public function setting($error = [], $trace = [])
    {
        try {
            if (isset($error['show'])) {
                $this->setShowError($error['show'] ? true : false);
            }
            if (isset($error['htmlTpl'])) {
                $this->setErrorHtmlTpl($error['htmlTpl']);
            }
            if (isset($error['jsonTpl'])) {
                $this->setErrorJsonTpl($error['jsonTpl']);
            }
            if (isset($trace['write'])) {
                $this->setWriteTrace($trace['write'] ? true : false);
            }
            if (isset($trace['path'])) {
                $this->setTracePath($trace['path']);
            }
            return $this;
        } catch (\Throwable $e) {
            exit($e->getMessage());
        }
    }

    /**
     * 开启/关闭 输出错误信息
     * @return self
     */
    public function setShowError($showError)
    {
        $this->error['show'] = $showError;
        return $this;
    }

    /**
     * 错误信息 - HTML模板路径
     * @return self
     */
    public function setErrorHtmlTpl($htmlTpl)
    {
        if (!is_file($htmlTpl)) {
            throw new \ErrorException('Error htmlTpl file not found.');
        }
        $this->error['htmlTpl'] = $htmlTpl;
        return $this;
    }

    /**
     * 错误信息 - JSON模板路径
     * @return self
     */
    public function setErrorJsonTpl($jsonTpl)
    {
        if (!is_file($jsonTpl)) {
            throw new \ErrorException('Error jsonTpl file not found.');
        }
        $this->error['jsonTpl'] = $jsonTpl;
        return $this;
    }

    /**
     * 开启/关闭 记录错误日志
     * @return self
     */
    public function setWriteTrace($writeTrace)
    {
        $this->trace['write'] = $writeTrace;
        return $this;
    }

    /**
     * 设置错误日志路径
     * @return self
     */
    public function setTracePath($tracePath)
    {
        if (!is_dir($tracePath)) {
            throw new \ErrorException('Trace directory not found.');
        }
        if (!is_writable($tracePath)) {
            throw new \ErrorException('Trace directory no permission to write.');
        }
        $this->trace['path'] = $tracePath;
        return $this;
    }

    /**
     * 处理 Exception 异常
     */
    public function handleException(\Throwable $Exception)
    {
        $errorInfo = [
            'code'      =>  $Exception->getCode(),
            'message'   =>  $Exception->getMessage(),
            'file'      =>  $Exception->getFile(),
            'line'      =>  $Exception->getLine(),
        ];
        $traceList = $Exception->getTrace();
        $this->writeTraceLog('handleException', $errorInfo, $traceList);
        $this->renderError($errorInfo, $traceList);
    }

    /**
     * 处理 Error 错误
     */
    public function handleError($code, $message, $file, $line)
    {
        $errorInfo = [
            'code'      =>  $code,
            'message'   =>  $message,
            'file'      =>  $file,
            'line'      =>  $line,
        ];
        $traceList = debug_backtrace();
        $this->writeTraceLog('handleError', $errorInfo, $traceList);
        $this->renderError($errorInfo, $traceList);
    }

    /**
     * 记录错误日志
     */
    private function writeTraceLog($handleFuncName, $errorInfo = [], $traceList = [])
    {
        if ($this->trace['write'] && $this->trace['path']) {
            $content = "===============Trace Begin===============\n";
            $content .= "Date: " . date('Y/m/d H:i:s') . "\n" . $errorInfo['message'] . "\n";
            $content .= $errorInfo['file'] . " (" . $errorInfo['line'] . ")";
            foreach ($traceList as $trace) {
                $function = $trace['function'];
                if (isset($trace['type'])) {
                    $function = $trace['type'] . $function;
                }
                if (isset($trace['class'])) {
                    $function = $trace['class'] . $function;
                }
                if ($function == get_class($this) . '->' . $handleFuncName) {
                    //自身不记录
                    continue;
                }
                $content .= isset($trace['file']) ? "\n" . $trace['file'] . " (" . $trace['line'] . ")\n" : "";
                $content .= "Function: " . $trace['function'] . "\n";
                $content .= "----------Args Json Begin----------\n";
                $content .= json_encode($trace['args']) . "\n";
                $content .= "----------Args Json End----------\n";
            }
            $content .= "===============Trace End===============\n\n";
            file_put_contents($this->trace['path'] . '/trace_' . date('Ymd') . '.log', $content, FILE_APPEND);
        }
    }

    /**
     * 渲染并输出错误信息
     */
    private function renderError($errorInfo = [], $traceList = [])
    {
        if ($this->error['show']) {
            ob_clean();
            ob_start();
            if ($this->isAjax()) {
                if ($this->error['jsonTpl']) {
                    include $this->error['jsonTpl'];
                }
            } else {
                if ($this->error['htmlTpl']) {
                    include $this->error['htmlTpl'];
                }
            }
            ob_end_flush();
        }
    }

    /**
     * 是否 Ajax 请求
     */
    private function isAjax()
    {
        return ($xmlhttprequest = ($_SERVER['HTTP_X_REQUEST_WITH']??'')) && 'xmlhttprequest' == strtolower($xmlhttprequest) ? true : false;
    }
}
