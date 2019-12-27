<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-29
 * Time: 15:41
 */

namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;
use think\Db;

class OrdersGoods extends CommonMiniMallModel
{
    //protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_ewei_shop_order_goods';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }

    public function insertMore($data){
        return Db::connect($this->database_config)->table($this->tableName)->insertAll($data);
    }
}