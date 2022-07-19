<?php

/**
 * Session操作类
 */

namespace nice;

use nice\traits\InstanceTrait;

class Session操作类
{
    use InstanceTrait;

    /**
     * 前缀
     */
    private $PREFIX = '_NICEPHP_';

    /**
     * 设置前缀
     * @return self
     */
    public function setPrefix($prefix = NULL)
    {
        $prefix and $this->PREFIX = $prefix;
        return $this;
    }

    /**
     * 启用Session
     */
    public function start($options = [])
    {
        if (PHP_SESSION_ACTIVE != session_status() && !session_start($options ?: NULL)) {
            throw new \ErrorException('Session unable started.');
        }
    }

    /**
     * 获取 Session 值
     */
    public function get($name, $prefix = NULL)
    {
        $this->start();
        if (is_null($prefix)) {
            $prefix = $this->PREFIX;
        }
        return $_SESSION[$prefix][$name] ?? NULL;
    }

    /**
     * 获取全部
     */
    public function getAll($prefix = NULL)
    {
        $this->start();
        return $_SESSION[$prefix ?: $this->PREFIX] ?? NULL;
    }

    /**
     * 设置 Session 值
     */
    public function set($name, $value = NULL, $prefix = NULL)
    {
        $this->start();
        !$prefix and $prefix = $this->PREFIX;
        if (!$name) {
            throw new \ErrorException('Name not allow false.');
        }
        $_SESSION[$prefix][$name] = $value;
    }

    /**
     * 删除某个值
     */
    public function delete($name, $prefix = NULL)
    {
        $this->start();
        !$prefix and $prefix = $this->PREFIX;
        unset($_SESSION[$prefix][$name]);
    }

    /**
     * 删除所有
     */
    public function deleteAll($prefix = NULL)
    {
        $this->start();
        !$prefix and $prefix = $this->PREFIX;
        unset($_SESSION[$prefix]);
    }

    /**
     * 销毁
     */
    public function destroy($destroyCookie = FALSE)
    {
        if ($_SESSION) {
            /**
             * !!! don't use `unset($_SESSION)`,look php Doc 'session_unset()'
             * 警告 请不要使用unset($_SESSION)来释放整个$_SESSION， 因为它将会禁用通过全局$_SESSION去注册会话变量
             */
            $_SESSION = [];
        }
        if ($destroyCookie) {
            // 如果要清理的更彻底，那么同时删除会话 cookie
            // 注意：这样不但销毁了会话中的数据，还同时销毁了会话本身
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
        }
        session_unset(); //清除内存$_SESSION的值，不删除会话文件及会话ID
        return session_destroy(); //销毁会话数据，删除会话文件及会话ID，但内存$_SESSION变量依然保留
    }
}
