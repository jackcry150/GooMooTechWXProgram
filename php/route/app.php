<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP8!';
});

Route::get('hello/:name', 'index/hello');

Route::rule('api/xhs/oauth/callback', 'api/Xhs/oauthCallback', 'GET|POST');
Route::rule('api/xhs/oauth/refresh', 'api/Xhs/refreshToken', 'GET|POST');

Route::rule('api/xhs/order/bind', 'api/Xhs/bindOrder', 'POST');
Route::rule('api/xhs/order/status', 'api/Xhs/bindStatus', 'GET');
Route::rule('api/xhs/order/sync', 'api/Xhs/syncOrders', 'GET|POST');
Route::rule('api/xhs/order/review', 'api/Xhs/reviewBind', 'GET|POST');
