<?php

namespace app\validate;

use think\Validate;

class User extends Validate
{
    /** 
     * @name 验证规则
     */
    protected $rule =   [
        'name'  => 'max:25',
        'age'   => 'number|between:1,120',
        'email' => 'email',
    ];

    /** 
     * @name 验证提示
     */
    protected $message  =   [
        'name.require' => '名称必须',
        'name.max'     => '名称最多不能超过25个字符',
        'age.number'   => '年龄必须是数字',
        'age.between'  => '年龄只能在1-120之间',
        'email'        => '邮箱格式错误',
    ];

    /** 
     * @name 验证场景
     */
    protected $scene = [
        'edit'  =>  ['name', 'age'],
        'list'  =>  ['email']
    ];
}
