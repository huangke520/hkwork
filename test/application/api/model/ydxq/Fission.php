<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\model\ydxq;

use think\Model;
use think\Db;
use app\api\model\CommonMiniMallModel;

class Fission extends CommonMiniMallModel
{
    //protected $autoWriteTimestamp = true;
    //protected $updateTime = false;
    protected $tableName = 'ims_yd_fission';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }


}