<?php

/**
 * 简单模板类
 */

namespace nice;

use nice\traits\InstanceTrait;

class Template
{
    use InstanceTrait;

    /**
     * 所有模板
     * @var array 'tplAlias' => ['path'=>'','content'=>'']
     */
    private $TPL = [];

    /**
     * 基础模板别名
     */
    private $BASE_TPL_ALIAS = '';

    /**
     * 所有模板变量
     * @var array 'varName' => $val
     */
    private $TPL_VARS = [];

    /**
     * 设置基础模板别名
     * @return self
     */
    public function setBaseTplAlias($tplAlias = '')
    {
        if ($tplAlias) {
            $this->BASE_TPL_ALIAS = $tplAlias;
        }
        return $this;
    }

    /**
     * 添加文件模板
     * @return self
     */
    public function setTplPath($tplAlias, $tplPath)
    {
        if (!file_exists($tplPath)) {
            throw new \ErrorException("{$tplAlias} file does not exist.");
        }
        $this->TPL[$tplAlias] = ['path' => $tplPath, 'content' => ''];
        return $this;
    }

    /**
     * 添加内容模板
     * @return self
     */
    public function setTplContent($tplAlias, $tplContent)
    {
        $this->TPL[$tplAlias] = ['path' => '', 'content' => $tplContent];
        return $this;
    }

    /**
     * 设置模板变量
     * @return self
     */
    public function setVar($varName, $value)
    {
        $this->TPL_VARS[$varName] = $value;
        return $this;
    }

    /**
     * 获取模板变量
     */
    public function getVar($varName, $default = null)
    {
        return $this->TPL_VARS[$varName] ?? $default;
    }

    /**
     * 根据模板别名渲染模板
     */
    private function loadTpl($tplAlias)
    {
        $tpl = $this->TPL[$tplAlias] ?? null;
        if (!$tpl) {
            throw new \ErrorException("{$tplAlias} tpl alias does not exist.");
        }
        if (!$this->TPL[$tplAlias]['path'] && !$this->TPL[$tplAlias]['content']) {
            throw new \ErrorException("{$tplAlias} file or content is empty.");
        }
        if ($this->TPL[$tplAlias]['path'] && file_exists($this->TPL[$tplAlias])) {
            include $this->TPL[$tplAlias];
        }
        if ($this->TPL[$tplAlias]['content']) {
            echo $this->TPL[$tplAlias]['content'];
        }
    }

    /**
     * 渲染并输出模板
     */
    public function display()
    {
        if ($this->BASE_TPL_ALIAS) {
            $this->loadTpl($this->BASE_TPL_ALIAS);
        }
    }

    /**
     * 获取模板渲染后的内容
     */
    public function fetch()
    {
        ob_start();
        $this->display();
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
