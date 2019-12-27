<?php
/**
 * Created by seaboyer
 * Author: seaboyer@163.com
 * Date: 2019-08-29
 */

namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;

class OrderAppendCoupon extends CommonMiniMallModel
{
    //protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_ewei_order_append_coupon';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
}