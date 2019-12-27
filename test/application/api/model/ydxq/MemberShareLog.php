<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-10-15
 */

namespace app\api\model\ydxq;

use think\Model;
use think\Db;
use app\api\model\CommonMiniMallModel;

class MemberShareLog extends CommonMiniMallModel
{
    //protected $autoWriteTimestamp = true;
    //protected $updateTime = false;
    protected $tableName = 'ims_member_share_log';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }


}