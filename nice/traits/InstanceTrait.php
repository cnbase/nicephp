<?php

/**
 * 单例trait
 */

namespace nice\traits;

trait InstanceTrait
{
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
        return static::$instance;
    }
}
