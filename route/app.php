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
use think\route\Rule;
use think\Request;

Route::any('api/:controller/:action', ':controller/:action/')
    ->match(function (Rule $rule, Request $request) {
        $rule->allowCrossDomain(); // 跨域
        $path = explode('/', $request->pathinfo()); // 获取路由
        // 判断控制器是否存在
        if (count($path) > 1 && class_exists('app\controller\\' . $path[1])) {
            return true;
        }
        return false;
    });
Route::any('api/:controller/:action/[:table]/[:profile]/[:id]', 'api/:action')
    ->match(function (Rule $rule, Request $request) {
        $rule->allowCrossDomain(); // 跨域
        return true;
    });
