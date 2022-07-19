<?php

/**
 * 入口文件
 */
include '../nice.php';

/**
 * 自定义 404 回调函数
 * 比模板文件优先级高
 */
\nice\Router::instance()->setNotFoundFunc(function(){
    return '404';
});

Nice::instance()->config([
    'APP_DIR'   =>  __DIR__ . '/../app',
    'INDEX_FILE' =>  'index.php',
])->onBeforeRun(function () {
    //前置回调
    \nice\Response::instance()->setContentType('html');
})->onAfterRun(function () {
    //后置回调
    $Router = \nice\Router::instance();
    if (!$Router->isMatched()) {
        //未匹配到路由规则
        header(($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 404 Not Found');
        header("Status: 404 Not Found");
        echo $Router->response(). '<br/> Created By NicePHP.';
    } else {
        echo $Router->response() . '<br/> Created By NicePHP.';
    }
})->run();
