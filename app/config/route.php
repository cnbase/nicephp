<?php

/**
 * 路由规则
 * 两种方式:
 * 1. 动态增加
 * 2. 配置文件
 */

use nice\Router;

//动态增加
$Router = Router::instance();
$Router->get('/', function () {
    return 'Nice to meet you !';
}, false);

//配置文件
return [
    /**
     * 请求方法，路径，回调函数，匹配模式[严格/正则匹配]
     * method,pathInfo,function,isRegular
     */
    ['get', '/', function () {
        return 'Nice to meet you !';
    }, false],
    ['get', '/hello', function () {
        return 'Hello world!';
    }, false],
];
