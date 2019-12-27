<?php
/**
 * Created by seaboyer
 * Author: seaboyer@163.com
 * Date: 2019-08-29
 */

namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;

class Jingpiwang extends CommonMiniMallModel
{
    //protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_jingpiwang_product';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
}