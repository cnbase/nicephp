<?php
echo json_encode([
    'code'      =>  $errorInfo['code'],
    'message'   =>  $errorInfo['message'],
    'data'      =>  [
        'errorInfo' =>  $errorInfo,
        'traceList' =>  $traceList,
    ]
]);
