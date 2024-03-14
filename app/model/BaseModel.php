<?php

namespace app\model;

use think\Model;
use think\facade\Session;

/**
 * 模型基础类
 * 
 * @author Tommy thf85@qq.com 2024-03-09
 */
class BaseModel extends Model
{
    /**
     * @name 权限处理方法
     * @access protected
     * @comment 权限表      auth,
     *          读取规则表   readRule,
     *          写入规则表   writeRule,
     *          更新规则表   updateRule,
     *          删除规则表   deleteRule
     *          权限表 记录某个表的读写更新删除权限类型,分别有三种类型:
     *              0. 仅操作自己的数据
     *              1. 规则表为空时可以操作所有数据
     *              2. 规则表为空时不可操作数据
     *          需要符合规则表中的条件才能操作数据
     *          规则表 记录某个用户对某条数据是否有操作权限,0不可操作,1可操作
     * @param $model 当前请求的模型对象
     * @param $tableType 表操作类型,读、写、更新、删除
     * 
     * @return Object 返回处理后的模型对象
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function auth($model, $tableType)
    {
        $auth = $this->getRule($tableType);
        $tab = $auth['auth'];
        $rule = $auth['rule'];
        if ($tab) {
            // 表权限为空,则不进行权限控制
            if (
                ($tableType == 'read' && $tab['read'] == 1) ||
                ($tableType == 'update' && $tab['update'] == 1) ||
                ($tableType == 'delete' && $tab['del'] == 1)
            ) {
                $rule = !is_null($rule) ? $rule->where('type', 0)->select()->toArray() : [];
                if (count($rule)) {
                    return $this->fieldData($rule, $model, 'NOT IN'); // not in
                }
            }
            // 表权限为空,则进行权限控制
            elseif (
                ($tableType == 'read' && $tab['read'] == 2) ||
                ($tableType == 'update' && $tab['update'] == 2) ||
                ($tableType == 'delete' && $tab['del'] == 2)
            ) {
                $rule = !is_null($rule) ? $rule->where('type', 1)->select()->toArray() : [];
                if (count($rule)) {
                    return $this->fieldData($rule, $model, 'IN'); // in
                } else {
                    // 没权限,不给查询数据
                    return $model->where(['id' => 0]);
                }
            } else {
                // 默认Session内存储的用户ID索引为user_id
                return $model->where('user_id', Session::get('user_id'));
            }
        }
        return $model;
    }

    /**
     * @name 获取权限规则
     * @access protected
     * @param $tableType 规则表,读、写、更新、删除不同表
     * 
     * @return Array
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function getRule($tableType)
    {
        $auth = self::anonymousModel('auth');
        // 查询表是否设置了权限控制
        $tab = $auth->where(['name' => strtolower(self::getName())])->find();
        $tab = !is_null($tab) ? $tab->toArray() : [];
        if (count($tab)) {
            if ($tableType == 'read') {
                $authRule = self::anonymousModel('authReadRule');
            } elseif ($tableType == 'write') {
                $authRule = self::anonymousModel('authWriteRule');
            } elseif ($tableType == 'update') {
                $authRule = self::anonymousModel('authUpdateRule');
            } elseif ($tableType == 'delete') {
                $authRule = self::anonymousModel('authDeleteRule');
            }
            // 查询每条数据权限规则,仅返回模型对象进一步处理
            $rule = $authRule->where(['name' => strtolower(self::getName()), 'user_id' => Session::get('user_id')]);
            return ['auth' => $tab, 'rule' => $rule];
        }
        return ['auth' => '', 'rule' => ''];
    }

    /**
     * @name 过滤规则权限数据
     * @access protected
     * @param $rule 规则
     * @param $model 模型
     * @param $type IN 或 NOT IN
     * 
     * @return Object
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    protected function fieldData($rule, $model, $type)
    {
        $list = [];
        foreach ($rule as $item) {
            // 取出数据的字段
            $list[] = $item['data_id'];
        }
        return $model->where('id', $type, $list);
    }

    /**
     * @name 添加
     * @access public
     * @param $data 添加数据
     * 
     * @return Boolean
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function add(array $data)
    {
        if (empty($data)) {
            return false;
        }
        $auth = $this->getRule('write');
        $tab = $auth['auth'];
        $rule = $auth['rule'];
        $rule = $rule != '' ? $rule->select()->toArray() : [];
        $result = false;
        if ($tab) {
            // 写入权限单独控制,规则为空访问所有数据
            if ($tab['write'] == 1) {

                if (count($rule)) {
                    if ($rule[0]['type'] == 1) {
                        $result = self::create($data);
                    }
                } else {
                    $result = self::create($data);
                }
            }
            // 规则为空不可访问数据
            elseif ($tab['write'] == 2) {
                if (count($rule) && $rule[0]['type'] == 1) {
                    $result = self::create($data);
                }
            }
        } else {
            $result = self::create($data);
        }
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @name 编辑
     * @access public
     * @param $data 编辑数据
     * 
     * @return Boolean
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function edit(array $data)
    {
        if (empty($data)) {
            return false;
        }
        $model = $this->auth(self::where(1), 'update');
        $result = false;
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $result = $model->where('id', $id)->update($data);
        }
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @name 删除
     * @access public
     * @param $id 删除条件
     * 
     * @return Boolean
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function del(string $id)
    {
        if (empty($id)) {
            return false;
        }
        $model = $this->auth(self::where(1), 'delete');
        $result = $model->where('id', $id)->delete();

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @name 查询列表
     * @access public
     * @param $where 查询条件
     * @param $field 查询字段
     * @param $order 排序
     * @param $page 分页
     * 
     * @return Object
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function list($where, $field = '*', $order = ['id' => 'desc'], $page = ['list_rows' => 50, 'page' => 1])
    {
        if (empty($field)) {
            $field = '*';
        }
        if (empty($page)) {
            $page = ['list_rows' => 50, 'page' => 1];
        }
        if (empty($order)) {
            $order = ['id' => 'desc'];
        }

        if (empty($where) || !count($where)) {
            return $this->auth(self::where(1)->field($field)->order($order), 'read')->paginate($page);
        } else {
            return $this->auth(self::where($where)->field($field)->order($order), 'read')->paginate($page);
        }
    }

    /**
     * @name 查询详情
     * @access public
     * @param $where 查询条件
     * @param $field 查询字段
     * 
     * @return Object
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public function detail(array $where, $field = '*')
    {
        if (empty($where)) {
            return false;
        }
        if (empty($field)) {
            $field = '*';
        }
        return $this->auth(self::where($where)->field($field), 'read')->find();
    }

    /**
     * @name 实列化匿名类模型
     * @access public
     * @param $tableName 数据表名
     * 
     * @return Object
     * 
     * @author Tommy thf85@qq.com 2024-03-09
     */
    public static function anonymousModel($tableName)
    {
        return new class($tableName) extends BaseModel
        {
            protected $name = '';
            public function __construct($name)
            {
                $this->name = $name;
            }
            // 重写父类toArray方法是因为匿名类模型无法使用toArray方法正确返回数据
            public function toArray(): array
            {
                $data = (array) $this;
                return $data["\0*\0name"];
            }
        };
    }
}
