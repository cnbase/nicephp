<?php

/**
 * 路由解析
 */

namespace nice;

use nice\traits\InstanceTrait;

class Router
{
    use InstanceTrait;

    /**
     * 网站入口文件路径
     */
    private $INDEX_FILE;

    /**
     * 路由配置
     * key => value
     * ['GET/POST/ANY'][PATH_INFO|*] => ['isRegular'=>true|false,'callback'=>function]
     */
    private $ROUTES = [];

    /**
     * 当前请求 - 请求方式
     * __call: 目前支持 GET,POST,ANY
     */
    private $REQUEST_METHOD;

    /**
     * 当前请求 - pathinfo路径
     */
    private $PATH_INFO;

    /**
     * 匹配成功的 - 路由规则
     */
    private $MATCHED_ROUTE;

    /**
     * 路由解析响应结果
     */
    private $RESPONSE;

    /**
     * 404 回调函数 - 优先级高
     */
    private $NOT_FOUND_FUNC;

    /**
     * 404 模板 - 优先级低
     */
    private $NOT_FOUND_FILE = __DIR__ . '/tpl/NotFound.html.php';

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->REQUEST_METHOD = strtoupper($_SERVER['REQUEST_METHOD']);
        $REQUEST_URI = '/' . ltrim($_SERVER['REQUEST_URI'], '/');
        $this->PATH_INFO = ($pos = strpos($REQUEST_URI, '?')) === FALSE ? $REQUEST_URI : substr($REQUEST_URI, 0, $pos);
    }

    /**
     * 初始化配置
     * @return self
     * $MODULE_NAME 非必需
     */
    public function setting($INDEX_FILE, $APP_DIR, $MODULE_NAME)
    {
        $this->INDEX_FILE = $INDEX_FILE;
        /**
         * 加载项目路由规则
         */
        $appRouteFile = $APP_DIR . '/config/route.php';
        if (file_exists($appRouteFile) && ($tmpRoutes = require_once $appRouteFile)) {
            $this->addRoutes($tmpRoutes);
        }

        /**
         * 加载项目模块系统配置
         */
        $moduleRouteFile = $APP_DIR . '/' . $MODULE_NAME . '/config/route.php';
        if ($MODULE_NAME && file_exists($moduleRouteFile) && ($tmpRoutes = require_once $moduleRouteFile)) {
            $this->addRoutes($tmpRoutes);
        }

        return $this;
    }

    /**
     * 判断是否匹配到路由规则
     * @return bool
     */
    public function isMatched()
    {
        return $this->MATCHED_ROUTE?TRUE:FALSE;
    }

    /**
     * 获取路由解析结果
     */
    public function response()
    {
        return $this->RESPONSE;
    }

    /**
     * 设置 404 回调函数
     * @return self
     */
    public function setNotFoundFunc($func)
    {
        $this->NOT_FOUND_FUNC = $func;
        return $this;
    }


    /**
     * 魔术方法调用
     * 目前支持 GET,POST,ANY
     */
    public function __call($method, $arguments)
    {
        $tmpMethod = strtoupper($method);
        if (in_array($tmpMethod, ['GET', 'POST', 'ANY'])) {
            if (count($arguments) < 2) {
                throw new \ErrorException('Add Route Failed.');
            }
            $this->addRoute($tmpMethod, ...$arguments);
        }
    }

    /**
     * 根据路由配置文件，批量增加路由规则
     * @param $routes [[method,pathInfo,function,isRegular],...]
     */
    public function addRoutes($routes = [])
    {
        if ($routes && is_array($routes)) {
            foreach ($routes as $route) {
                if (count($route) >= 3) {
                    $this->addRoute(...$route);
                }
            }
        }
        return $this;
    }

    /**
     * 新增路由规则
     * @param $method 请求方法
     * @param $pathInfo 匹配路径
     * @param $callback 回调函数
     * @param $isRegular 匹配模式 true正则匹配,false严格匹配
     * @return self
     */
    public function addRoute($method = '', $pathInfo = '', $callback = null, $isRegular = false)
    {
        if ($method && $pathInfo && is_callable($callback)) {
            $method = strtoupper($method);
            $isRegular and $pathInfo = strtolower($pathInfo);
            $this->ROUTES[$method][$pathInfo] = ['isRegular' => $isRegular, 'callback' => $callback];
        }
        return $this;
    }

    /**
     * 路由解析
     */
    public function dispatch()
    {
        $rawPath = $this->PATH_INFO;
        if (strpos($rawPath, '/' . $this->INDEX_FILE) === 0) {
            $lowerPath = strtolower(substr($rawPath, strlen('/' . $this->INDEX_FILE)));
        } else {
            $lowerPath = strtolower($rawPath);
        }
        /**
         * 1. 优先匹配具体 REQUEST_METHOD
         */
        if (array_key_exists($this->REQUEST_METHOD, $this->ROUTES)) {
            foreach ($this->ROUTES[$this->REQUEST_METHOD] as $pathInfo => $route) {
                //严格匹配模式
                if (!$route['isRegular'] && $pathInfo === $lowerPath) {
                    $this->MATCHED_ROUTE = $this->ROUTES[$this->REQUEST_METHOD];
                    return $this->RESPONSE = call_user_func($route['callback']);
                }
                //正则匹配模式
                if ($route['isRegular'] && preg_match($pathInfo, $lowerPath, $matches)) {
                    $this->MATCHED_ROUTE = $this->ROUTES[$this->REQUEST_METHOD];
                    return $this->RESPONSE = call_user_func($route['callback'], $matches);
                }
            }
        }
        /**
         * 2. 查询 ANY 规则
         */
        if (array_key_exists('ANY', $this->ROUTES)) {
            foreach ($this->ROUTES[$this->REQUEST_METHOD] as $pathInfo => $route) {
                if ($pathInfo === '*') {
                    continue;
                }
                //严格匹配模式
                if (!$route['isRegular'] && $pathInfo === $lowerPath) {
                    $this->MATCHED_ROUTE = $this->ROUTES[$this->REQUEST_METHOD];
                    return $this->RESPONSE = call_user_func($route['callback']);
                }
                //正则匹配模式
                if ($route['isRegular'] && preg_match($pathInfo, $lowerPath, $matches)) {
                    $this->MATCHED_ROUTE = $this->ROUTES[$this->REQUEST_METHOD];
                    return $this->RESPONSE = call_user_func($route['callback'], $matches);
                }
            }
            /**
             * 3. 查询 ANY 的 * 规则
             */
            if (isset($this->ROUTES['ANY']['*'])) {
                $this->MATCHED_ROUTE = $this->ROUTES['ANY'];
                return $this->RESPONSE = call_user_func($this->ROUTES['ANY']['*']['callback']);
            }
        }
        /**
         * 4. NOT FOUND
         * 动态回调函数 > 模板文件
         */
        if (is_callable($this->NOT_FOUND_FUNC)) {
            ob_clean();
            return $this->RESPONSE = call_user_func($this->NOT_FOUND_FUNC);
        } else {
            if (file_exists($this->NOT_FOUND_FILE)) {
                ob_clean();
                ob_start();
                include $this->NOT_FOUND_FILE;
                $content = ob_get_contents();
                ob_end_clean();
                return $this->RESPONSE = $content;
            }
        }
    }
}
