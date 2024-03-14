<?php

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\helper\Str;

class Api extends BaseController
{
    /**
     * @name api文档生成页面
     *
     *
     * @return html
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function index()
    {
        $content = '
            <style>
                body{font-size: 14px;line-height: 20px;}
                h1{border-bottom: 1px solid #999;line-height: 40px;}
                #body {display: flex;justify-content: flex-start;}
                #menu {width: 20%;min-width: 200px;border-right: 1px solid #999;margin-right: 10px;}
                a{color: #333;text-decoration:none;}
                a:hover{color:#007bff;}
                strong{font-size: 18px;line-height: 30px;}
                table {border-collapse: collapse;border-spacing: 0;} 
                tr:hover {background-color: #f9f9f9;}
                td,th,pre { padding: 4px 12px;border: 1px solid #cbcbcb;}
                th {background-color: #f1f1f1;}
                pre {background: #f1f1f1; padding: 6px 12px; display: inline-block;font-size: 16px;}
                #top{position: fixed;width: 40px;height: 35px;cursor: pointer;background: rgba(120,120,120,0.5);z-index: 9;left: calc( 20% - 70px );bottom: 50px;color: #fff;font-size: 22px;line-height: 35px;text-align: center;}
            </style>
        ';
        $key = 0;
        $menu = '';
        // 获取所有控制器方法并生成菜单和内容
        $controllers = $this->getController();
        foreach ($controllers as $controller) {
            $actions =  $this->getAction($controller);
            if (count($actions)) {
                foreach ($actions as $action) {
                    $key++; // 接口序号
                    $data = $this->get_cc_desc($controller, $action);
                    // 生成接口菜单
                    $menu .= "<div><a href='#{$controller}'>" . $key . '. ' . $data['name'] . '</a></div>';
                    // 生成接口名称
                    $content .= "<strong id='{$controller}'>" . $key . '. ' . $data['name'] . '</strong><br />';
                    // 显示接口的地址
                    $content .= '<pre>/api/' . $controller . '/' . $action . '</pre><br />';
                    // 生成参数表格
                    $content .= ' <table> <tr><th>字段</th><th>类型</th><th>必填</th><th>默认值</th><th>说明</th></tr>';
                    foreach ((array)$data['param'] as $value) {
                        $arr = explode(' ', $value);
                        if (count($arr) < 5) {
                            for ($i = count($arr); $i < 5; $i++) {
                                $arr[] = '';
                            }
                        }
                        // 拼接参数表格内容
                        $content .= "<tr><td>{$arr[0]}</td><td>{$arr[1]}</td><td>{$arr[3]}</td><td>{$arr[4]}</td><td>{$arr[2]}</td></tr>";
                    }
                }
            }
            $content .= '</table><br /><br />';
        }
        $config = config('database');
        // 获取所有表结构并生成菜单和内容
        $tables = Db::query("SELECT TABLE_NAME,ENGINE,TABLE_COLLATION,CREATE_TIME,UPDATE_TIME,TABLE_COMMENT  FROM INFORMATION_SCHEMA.TABLES Where TABLE_SCHEMA = '{$config['connections'][$config['default']]['database']}';");
        foreach ($tables as $table) {
            if (strstr($table['TABLE_NAME'], 'auth')) {
                continue;
            }
            $key++; // 接口序号
            // 生成接口菜单
            $menu .= "<div><a href='#{$table['TABLE_NAME']}'>" . $key . '. ' . $table['TABLE_COMMENT'] . '</a></div>';
            // 生成接口名称
            $content .= "<strong id='{$table['TABLE_NAME']}'>" . $key . '. ' . $table['TABLE_COMMENT'] . '</strong><br />';
            // 生成接口地址
            $content .= '<pre>/api/' . Str::camel(str_replace($config['connections'][$config['default']]['prefix'], '', $table['TABLE_NAME']));
            $content .= '/';
            $content .= 'add | edit | delete | list | detail</pre>';

            // 生成字段表格
            $content .= ' <table> <tr><th>字段</th><th>类型</th><th>必填</th><th>默认值</th><th>说明</th></tr>';
            // 通过表名获取字段信息
            $columns = Db::query("SHOW FULL COLUMNS FROM {$table['TABLE_NAME']};");
            foreach ($columns as $column) {
                if ($column['Key'] == 'PRI') {
                    $column['Null'] = '自增';
                } else {
                    $column['Null'] == 'YES' ? $column['Null'] = '否' : $column['Null'] = '是';
                }
                // 拼接字段表格内容
                $content .= "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td><td>{$column['Null']}</td><td>{$column['Default']}</td><td>{$column['Comment']}</td></tr>";
            }
            $content .= '</table><br /><br />';
        }

        $result = '';
        // 判断是否是调试模式决定是否输出文档
        if (app()->isDebug()) {
            $result = '<h1 id="h1">API 文档</h1>
                <div id="body">
                    <div id="menu">' . $menu . '</div>
                    <div id="content">' . $content . '</div>
                </div>
                <a href="#h1"><div id="top">&#8593</div></a>';
        }
        return $result;
    }

    /**
     * @name 获取所有方法名称
     *
     * @param $controller
     *
     * @return array
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function getAction($controller)
    {
        $content = file_get_contents(app()->getRootPath() . '/app/Controller/' . $controller . '.php');
        preg_match_all("/.*?public.*?function(.*?)\(.*?\)/i", $content, $matches);
        $functions = $matches[1];
        //排除部分方法
        $inherents_functions = array('_before_index', '_after_index', '_initialize', '__construct', 'getActionName', 'isAjax', 'display', 'show', 'fetch', 'buildHtml', 'assign', '__set', 'get', '__get', '__isset', '__call', 'error', 'success', 'ajaxReturn', 'redirect', '__destruct', '_empty', 'index');
        foreach ($functions as $func) {
            $func = trim($func);
            if (!in_array($func, $inherents_functions)) {
                if (strlen($func) > 0)   $customer_functions[] = $func;
            }
        }
        return $customer_functions;
    }

    /**
     * @name 获取所有控制器名称
     *
     *
     * @return array
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function getController()
    {
        $module_path = app()->getRootPath() . '/app/Controller/*.php';
        $ary_files = glob($module_path);
        $files = [];
        foreach ($ary_files as $file) {
            // 排除部分文件和目录
            if (
                is_dir($file) || basename($file) == 'Api.php' ||
                basename($file) == 'Index.php' ||
                basename($file) == 'Install.php'
            ) {
                continue;
            } else {
                $files[] = basename($file, '.php');
            }
        }
        return $files;
    }

    /**
     * @name 获取指定函数的注释内容
     *
     * @param $controller
     * @param $action
     *
     * @return array
     *
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function get_cc_desc($controller, $action)
    {
        $desc = app()->parseClass('controller', $controller);
        $func  = new \ReflectionMethod(new $desc(app()), $action);
        $tmp   = $func->getDocComment();
        preg_match_all('/@name(.*?)\n/', $tmp, $name); // 正则获取方法名称
        preg_match_all('/@param(.*?)\n/', $tmp, $param); // 正则获取方法参数和说明等
        $name   = count($name) == 2 ? trim($name[1][0]) : '';
        $params = [];
        if (count($param) == 2) {
            foreach ($param[1] as $value) {
                $params[] = trim($value);
            }
        }
        return ['name' => $name, 'param' => $params];
    }
}
