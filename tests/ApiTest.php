<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use tests\TestExtendTrait;

/**
 * @name 接口增删改查和文档测试类
 * assertTrue/assertFalse    断言是否为真值还是假
 * assertEquals　　　　　　　　判断输出是否和预期的相等
 * assertGreaterThan         断言结果是否大于某个值，同样的也有LessThan(小于),GreaterThanOrEqual(大于等于)，
 * lessThanOrEqual           (小于等于).
 * assertContains            判断输入是否包含指定的值
 * assertNull                判断是否为空值
 * assertFileExists          判断文件是否存在
 * assertRegExp              根据正则表达式判断
 * 
 * https://docs.phpunit.de/en/latest/assertions.html
 * 
 */
class ApiTest extends TestCase
{
    /**
     * @name 测试接口文档
     */
    public function testIndex()
    {
        $content = TestExtendTrait::getHtmlData('Api', 'index');
        $this->assertIsString($content);
    }
    /**
     * @name 测试接口列表
     */
    public function testList()
    {
        $content = TestExtendTrait::getJsonData('user', 'list');
        $this->assertIsObject($content);
    }
    /**
     * @name 测试接口详情
     */
    public function testDetail()
    {
        $content = TestExtendTrait::getJsonData('user', 'detail');
        $this->assertIsObject($content);
    }
    /**
     * @name 测试接口编辑
     */
    public function testEdit()
    {
        $content = TestExtendTrait::getJsonData('user', 'edit', ['id' => 1, 'name' => 'Tommy']);
        $this->assertIsObject($content);
    }
    /**
     * @name 测试接口删除
     */
    public function testDelete()
    {
        $content = TestExtendTrait::getJsonData('user', 'delete', ['id' => '1']);
        $this->assertIsObject($content);
    }
    /**
     * @name 测试接口添加
     */
    public function testAdd()
    {
        $content = TestExtendTrait::getJsonData('user', 'add', ['name' => 'Tommy']);
        $this->assertIsObject($content);
    }
}
