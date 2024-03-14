<?php

namespace app\controller;

use app\BaseController;

class User extends BaseController
{
    public function index()
    {
        return '';
    }
    /**
     * @name 测试方法
     *
     * @param id int 说明1 必填 默认值
     * @param name string 说明2
     * @param index int 说明3
     *
     * @return string 注释
     *
     */
    public function test()
    {
        return 'test';
    }
}
