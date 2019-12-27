<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\model\ydxq;

use think\Model;
use app\api\model\CommonMiniMallModel;

class YdSupplierGift extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;

    protected $tableName = 'ims_yd_supplier_gift';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
}