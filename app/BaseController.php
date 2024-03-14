<?php

declare(strict_types=1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use app\model\BaseModel;
use PhpParser\Node\Stmt\TryCatch;
use think\facade\Event;
use think\exception\ErrorException;

/**
 * 控制器基础类
 * 
 * @author Tommy thf85@qq.com 2024-03-09
 */
abstract class BaseController
{
    /**
     * @name Request实例
     * @var \think\Request
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected $request;

    /**
     * @name 应用实例
     * @var \think\App
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected $app;

    /**
     * @name 是否批量验证
     * @var bool
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected $batchValidate = false;

    /**
     * @name 控制器中间件
     * @var array
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected $middleware = [];

    /**
     * @name 构造方法
     * @access public
     * @param  App  $app  应用对象
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    /** 
     * @name 初始化
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function initialize()
    {
        // 触发请求事件
        Event::trigger('RequestApi', $this->request->param());
    }

    /**
     * @name 设置参数
     */
    public function setParam($param)
    {
        $this->request->withPost($param);
    }
    /**
     * @name    验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        // 验证参数, 如果为数组则做为验证规则
        if (is_array($validate)) {
            // 实例化官方验证类并设置验证规则
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            try {
                // 实例化验证类
                $v = new $class();
                if (!empty($scene)) {
                    // 场景验证
                    $v->scene($scene);
                }
            } catch (\Throwable $th) {
                // 验证器不存在
                return true;
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * @name 统一取得数据模型
     * 
     * @return Object
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function getModel()
    {
        $param = $this->request->param();
        $modelName = ucfirst($param['controller']);
        $modelFile = app()->getRootPath() . "app/model/{$modelName}.php";

        // 模型文件存在
        if (file_exists($modelFile)) {
            $namespace = $this->app->parseClass('model', $modelName);
            // 实例化模型类
            $model = new $namespace();
        } else {
            // 匿名模型类
            $model = BaseModel::anonymousModel($modelName);
        }
        return $model;
    }

    /**
     * @name 处理请求参数
     * 
     * @return Array
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function getParam()
    {
        $param = $this->request->param();
        $order = isset($param['order']) ? $param['order'] : '';
        $page = isset($param['page']) ? $param['page'] : '';
        $field = isset($param['field']) ? $param['field'] : '';
        unset($param['controller'], $param['action'], $param['order'], $param['page'], $param['field']);
        return ['param' => $param, 'order' => $order, 'page'  => $page, 'field' => $field];
    }

    /**
     * @name 统一取得返回结果
     * 
     * @param $result 结果数据集
     * 
     * @return json
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function getResult($result)
    {

        if (!is_null($result) && !is_bool($result)) {
            if (is_array($result)) {
                return json($result);
            } else {
                return json($result->toArray());
            }
        } else if (is_bool($result)) {
            return json($result);
        }
        return json('', 404);
    }

    /**
     * @name 执行业务操作
     * 
     * @return json
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function operate()
    {
        $param = $this->request->param();
        if (!isset($param['controller']) || !isset($param['action'])) {
            return json('', 404);
        } else {
            $controller = $param['controller'];
            $action = $param['action'];
            $data = $this->getParam();
            try {
                // 验证数据
                $this->validate($data['param'], $controller . '.' . $action);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return $e->getError();
            }
            $model = $this->getModel();
            switch ($action) {
                case "delete":
                    $result = $model->del($data['param']['id']);
                    break;
                case "list":
                    $result = $model->list($data['param'], $data['field'], $data['order'], $data['page']);
                    break;
                case "detail":
                    $result = $model->detail($data['param'], $data['field']);
                    break;
                case "add":
                    $result = $model->add($data['param']);
                    break;
                default:
                    $result = $model->edit($data['param']);
            }
        }
        try {
            return $this->getResult($result);
        } catch (\Throwable $th) {
            return json($th, 404);
        }
    }

    /**
     * @name 请求新增一条数据
     *
     * @return json
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function add()
    {
        return $this->operate();
    }

    /**
     * @name 请求编辑一条或多条数据
     *
     * @return json
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function edit()
    {
        return $this->operate();
    }

    /**
     * @name 请求删除一条数据
     *
     * @return json
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function delete()
    {
        return $this->operate();
    }

    /**
     * @name 请求多条数据
     *
     * @return json
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function list()
    {
        return $this->operate();
    }

    /**
     * @name 请求单条数据
     *
     * @return json
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function detail()
    {
        return $this->operate();
    }
}
