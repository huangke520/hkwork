<?php

// +----------------------------------------------------------------------
// | btj_new的表，通过database_config = 'btj_new'连接到btj_new库
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

class CommonBtjNewModel extends CommonBaseModel
{
    //protected $db_fix_ver;
    protected $database_config;

    protected function initialize()
    {
        parent::initialize();

        $this->database_config = 'db_btj_new';
    }

}
