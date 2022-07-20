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
})->onAfterRun(function ($Response,$content,$isMatched) {
    //后置回调
    if (!$isMatched) {
        //未匹配到路由规则
        $Response->removeHeader()->setHeader404();
        echo $content. '<br/> Created By NicePHP.';
    } else {
        echo $content . '<br/> Created By NicePHP.';
    }
})->run();
