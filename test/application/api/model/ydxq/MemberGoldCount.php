<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\model\ydxq;

use think\Model;
use think\Db;
use app\api\model\CommonMiniMallModel;

class MemberGoldCount extends CommonMiniMallModel
{
    //protected $autoWriteTimestamp = true;
    //protected $updateTime = false;
    protected $tableName = 'ims_member_gold_count';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }


}