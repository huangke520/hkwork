<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-20
 * Time: 19:57
 */

namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;

class SupplierSun extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_yd_supplier_sun';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }

}