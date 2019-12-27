<?php

// +----------------------------------------------------------------------
// | 所有数据库类基类
// +----------------------------------------------------------------------
// | Author: seaboyer <seaboyer@163.com>
// | Date: 2019-08-10
// +----------------------------------------------------------------------

namespace app\api\model;

use think\Model;
//use think\facade\Session;
use think\Db;

class CommonBaseModel extends Model
{
    //protected $db_fix_ver;
    protected $database_config;

    protected function initialize()
    {
        parent::initialize();

        $this->database_config = '';
    }

    /**
     * @cc 设置model的表名
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function setTableName($table_name)
    {
        $this->table = $table_name;
    }

    /**
     * @cc 插入记录
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function insertInfo($param)
    {
    	return Db::connect($this->database_config)->table($this->table)->insertGetId($param);
    }


    /**
     * @cc 更新记录
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function updateInfo($id, $param)
    {
    	$where = array('id' => $id);
    	return Db::connect($this->database_config)->table($this->table)->where($where)->update($param);
    }

    /**
     * @cc 更新记录，增强方法
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function updateInfoPro($where, $param)
    {
        return Db::connect($this->database_config)->table($this->table)->where($where)->update($param);
    }

    /**
     * @cc 删除记录
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function deleteInfo($id)
    {
    	$where = array('id' => $id);
        $param['status'] = 9;
        return Db::connect($this->database_config)->table($this->table)->where($where)->update($param);
        //return $this->where($where)->delete();
    }

    /**
     * @cc 记录总数
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function getCount($where = [])
    {
        return Db::connect($this->database_config)->table($this->table)->where($where)->count();
    }

    /**
     * @cc 一条记录
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function getInfo($where = [])
    {
        return Db::connect($this->database_config)->table($this->table)->where($where)->find();
    }

    /** 一条，增强方法
     * @param array $where
     * @param array $field
     * @param array $order
     * @return array|null|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfoPro($where = [], $field = [], $order = [])
    {
        return Db::connect($this->database_config)->table($this->table)->field($field)->order($order)->where($where)->find();
    }

    /**
     * @cc 所有记录
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function getAllList($where = [])
    {
    	return Db::connect($this->database_config)->table($this->table)->where($where)->select();
    }

    /** 所有，增强方法
     * @param array $where
     * @param array $field
     * @param array $order
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllListPro($where = [], $field = [], $order = [])
    {
        return Db::connect($this->database_config)->table($this->table)->field($field)->order($order)->where($where)->select();
    }

    /**
     * @cc 分页列表
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function getPageList($where = [], $pagesize = 15)
    {
		$config = [
            //'type'     => 'Bootstrap',
            //'var_page' => 'page',
            //使用jqery 无刷新分页
            //'path'=>'javascript:AjaxPage([PAGE]);',
            //第一种方法，使用数组方式传入参数
            //'query' => ['keyword'=>$keyword],
            //第二种方法，使用函数助手传入参数
            'query' => request()->param(),
        ];

        //return Db::table($this->table)->where($where)->paginate($pagesize, false, $config);
        return Db::connect($this->database_config)->table($this->table)->where($where)->paginate($pagesize, false, $config);
    }

    /** 分页，增强方法
     * @param array $where
     * @param int $page
     * @param array $field
     * @param array $order
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPageListPro($where = [], $pagesize = 15, $field = [], $order = [])
    {
        $config = [
            //'type'     => 'Bootstrap',
            //'var_page' => 'page',
            //使用jqery 无刷新分页
            //'path'=>'javascript:AjaxPage([PAGE]);',
            //第一种方法，使用数组方式传入参数
            //'query' => ['keyword'=>$keyword],
            //第二种方法，使用函数助手传入参数
            'query' => request()->param(),
        ];

        return Db::connect($this->database_config)->table($this->table)->field($field)->order($order)->where($where)->paginate($pagesize, false, $config);
    }

    /** 分页，增强方法，返回数组
     * @param array $where
     * @param int $page
     * @param array $field
     * @param array $order
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPageListArr($where = [], $pagesize = 15, $field = [], $order = [])
    {
        $config = [
            //'type'     => 'Bootstrap',
            //'var_page' => 'page',
            //使用jqery 无刷新分页
            //'path'=>'javascript:AjaxPage([PAGE]);',
            //第一种方法，使用数组方式传入参数
            //'query' => ['keyword'=>$keyword],
            //第二种方法，使用函数助手传入参数
            'query' => request()->param(),
        ];

        $data = Db::connect($this->database_config)->table($this->table)->field($field)->order($order)->where($where)->paginate($pagesize, false, $config)->toArray();
        return $data['data'];
    }

    /**
     * @cc 普通查询语句
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function querySql($sql)
    {
        $res = null;
		if (!empty($sql)) {
            $res = Db::connect($this->database_config)->query($sql);
        }
        return $res;
    }

    /**
     * @cc 普通执行语句
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function executeSql($sql)
    {
        $res = null;
        if (!empty($sql)) {
            $res = Db::connect($this->database_config)->execute($sql);
        }
        return $res;
    }

    /**
     * @cc 字段增减值
     *
     * @author seaboyer@163.com
     * @date 2019-07-28
     * @version 1.0
     */
    public function incField($where, $op_field, $op_value = 1, $op_inc = 1)
    {
        $op = 'INC';
        if(empty($op_inc)){
            $op = 'DEC';
        }
        Db::connect($this->database_config)->table($this->table)->where($where)->inc($op_field, $op_value, $op)->update();
    }

}
