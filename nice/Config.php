<?php

/**
 * 配置类相关操作
 */

namespace nice;

use nice\traits\InstanceTrait;

class Config
{
    use InstanceTrait;

    /**
     * 框架默认配置
     */
    private $config = [
        'timeZone'  =>  'Aisa/Shanghai',
        'error'     =>  [
            'show'  =>  true,
            'htmlTpl'  =>  __DIR__ . '/tpl/NiceException.html.php',
            'jsonTpl'  =>  __DIR__ . '/tpl/NiceException.json.php',
        ],
        'trace'     =>  [
            'write' =>  true,
            'path'  =>  __DIR__ . '/../runtime/trace',
        ],
    ];

    /**
     * 项目配置
     * @return self
     * $MODULE_NAME 非必需
     */
    public function setting($APP_DIR, $MODULE_NAME)
    {
        /**
         * 加载项目配置
         */
        $appConfigFile = $APP_DIR . '/config/config.php';
        if (file_exists($appConfigFile) && ($tmpConfig = require_once $appConfigFile)) {
            $this->config = array_merge($this->config, $tmpConfig);
        }

        /**
         * 加载项目模块系统配置
         */
        $moduleConfigFile = $APP_DIR . '/' . $MODULE_NAME . '/config/config.php';
        if ($MODULE_NAME && file_exists($moduleConfigFile) && ($tmpConfig = require_once $moduleConfigFile)) {
            $this->config = array_merge($this->config, $tmpConfig);
        }

        return $this;
    }

    /**
     * 获取配置项
     */
    public function get($name, $defaultVal = null)
    {
        return $this->config[$name] ?? $defaultVal;
    }
}
