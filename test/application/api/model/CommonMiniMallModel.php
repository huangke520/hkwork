<?php

// +----------------------------------------------------------------------
// | mini_mall的表，通过database_config = 'mini_mall'连接到mini_mall库
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

class CommonMiniMallModel extends CommonBaseModel
{
    //protected $db_fix_ver;
    protected $database_config;

    protected function initialize()
    {
        parent::initialize();

        $this->database_config = 'db_mini_mall';
    }

}
