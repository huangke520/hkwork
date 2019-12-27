<?php

// +----------------------------------------------------------------------
// | 管理后台的表，通过database_config = ''连接到管理后台库
// | 非标准model类的基类，适用于表名带分表的表，通过setTableName传入表名实例化
// +----------------------------------------------------------------------
// | Author: seaboyer <seaboyer@163.com>
// | Date: 2019-08-10
// +----------------------------------------------------------------------

namespace app\api\model;

use think\Model;
//use think\facade\Session;
use think\Db;
use app\api\model\CommonBaseModel;

class CommonManageModel extends CommonBaseModel
{
    //protected $db_fix_ver;
    protected $database_config;

    protected function initialize()
    {
        parent::initialize();

        $this->database_config = '';
    }

}
