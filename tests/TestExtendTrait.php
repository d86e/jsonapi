<?php

namespace tests;

use think\App;

/**
 * @name 测试扩展trait
 * 
 * 
 */
trait TestExtendTrait
{

    protected static function connect($controller, $action, $data = [])
    {
        $app = new App();

        if (!is_array($data)) {
            $data = [];
        }
        $data['controller'] = $controller;
        $data['action'] = $action;
        $app->http->run($app->request->withRoute($data));

        $controller = $app->parseClass('controller', $controller);
        $api = new $controller($app);
        return $api->$action();
    }
    /**
     * @name 请求指定控制器和方法获取Json数据
     * @param $controller 控制器
     * @param $action 方法
     * @param $data 数据
     * 
     * @return Json
     */
    public static function getJsonData($controller, $action, $data = [])
    {
        $result = self::connect($controller, $action, $data);
        if (is_object($result)) {
            $content = $result->getContent();
        } else {
            $content = $result;
        }
        if ($content) {
            $content = (object)json_decode($content, true);
        }
        return $content;
    }
    /**
     * @name 请求指定控制器和方法获取Html数据
     * @param $controller 控制器
     * @param $action 方法
     * @param $data 数据
     * 
     * @return Json
     */
    public static function getHtmlData($controller, $action, $data = [])
    {
        return (string)self::connect($controller, $action, $data);
    }
}
