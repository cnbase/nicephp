<?php

/**
 * 框架核心文件
 */

use nice\traits\InstanceTrait;
use nice\Config;
use nice\Exception;
use nice\Response;
use nice\Router;

class Nice
{
    /**
     * 版本号
     */
    static public $version = '1.0.0';

    /**
     * 项目目录
     * 该目录应包含: 模块系统-index (含控制器,模型处理等类文件), 配置目录-config (含路由文件router.php,配置文件config.php,数据库配置database.php等配置文件)
     */
    private $APP_DIR = __DIR__ . '/app';

    /**
     * 项目模块系统目录名 - 非必需
     * 如：PC端、移动端、中后台、前台等
     */
    private $MODULE_NAME = '';

    /**
     * 网站入口文件路径
     * 注: 相对于网站根目录, 用于匹配路由规则
     */
    private $INDEX_FILE = 'index.php';

    /**
     * 前置回调函数
     * 路由解析前执行, 如请求参数的前置处理等场景
     * @var callable
     */
    private $beforeRunFunc;

    /**
     * 后置回调函数
     * 路由解析完成执行，如响应数据的统一格式处理等场景
     * @var callable
     */
    private $afterRunFunc;

    /**
     * @return static
     */
    static protected $instance;

    /**
     * 单例模式
     * @return static
     */
    static public function instance(...$option)
    {
        !(static::$instance instanceof static) && (static::$instance = new static(...$option));
        return static::$instance = new static(...$option);
    }

    /**
     * 设置基础配置项
     * @return self
     */
    public function config($config = [])
    {
        isset($config['APP_DIR']) and $this->APP_DIR = $config['APP_DIR'];
        isset($config['MODULE_NAME']) and $this->MODULE_NAME = $config['MODULE_NAME'];
        isset($config['INDEX_FILE']) and $this->INDEX_FILE = $config['INDEX_FILE'];
        return $this;
    }

    /**
     * 设置前置回调函数
     * @return self
     */
    public function onBeforeRun($func)
    {
        if (!is_callable($func)) {
            throw new \ErrorException('BeforeRunFunc (function) not callable.');
        }
        $this->beforeRunFunc = $func;
        return $this;
    }

    /**
     * 设置后置回调函数
     * @return self
     */
    public function onAfterRun($func)
    {
        if (!is_callable($func)) {
            throw new \ErrorException('AfterRunFunc (function) not callable.');
        }
        $this->afterRunFunc = $func;
        return $this;
    }

    /**
     * 启动框架
     */
    public function run()
    {
        /**
         * 2. 注册项目类自动加载机制
         */
        spl_autoload_register([$this, '_autoload']);
        /**
         * 3. 加载项目配置 + 配置时区
         */
        $Config = Config::instance()->setting($this->APP_DIR, $this->MODULE_NAME);
        ($timeZone = $Config->get('timeZone')) and date_default_timezone_set($timeZone);
        /**
         * 注册框架自定义异常处理类
         */
        $Exception = Exception::instance();
        $Exception->setting($Config->get['error'], $Config->get('trace'));
        set_exception_handler([$Exception, 'handleException']);
        set_error_handler([$Exception, 'handleError']);
        /**
         * ===打开输出缓冲区===
         */
        ob_start();
        /**
         * 前置函数处理
         */
        is_callable($this->beforeRunFunc) and call_user_func($this->beforeRunFunc);
        /**
         * 路由解析
         * 1. 加载路由配置
         * 2. 执行
         */
        $Router = Router::instance()->setting($this->INDEX_FILE, $this->APP_DIR, $this->MODULE_NAME);
        $Router->dispatch();
        /**
         * 后置函数处理 && 页面数据输出
         */
        Response::instance()->setting($this->afterRunFunc)->send($Router->response(),$Router->isMatched());
        /**
         * ===冲刷输出缓冲区===
         */
        ob_end_flush();
    }

    /**
     * 项目类文件自动加载
     */
    private function _autoload($className)
    {
        $classPath = str_replace('\\', '/', $className);
        if (($classFile = $this->APP_DIR . '/' . $classPath . '.php') && file_exists($classFile)) {
            require_once $classFile;
            return;
        }
    }
}

/**
 * 注册框架类自动加载机制
 */
function nice_autoload($className)
{
    $classPath = str_replace('\\', '/', $className);
    if (strpos($classPath, 'nice/') === 0 && ($classFile = __DIR__ . '/nice/' . substr($classPath, 5) . '.php') && file_exists($classFile)) {
        require_once $classFile;
        return;
    }
}
spl_autoload_register('nice_autoload');
