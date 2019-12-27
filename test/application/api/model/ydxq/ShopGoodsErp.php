<?php

/**
 * Author: fenglixin@163.com
 * Date: 2019-08-08
 */

namespace app\api\model\ydxq;

use think\Db;
use think\Model;
use app\api\model\CommonMiniMallModel;

class ShopGoodsErp extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;

    protected $tableName = 'ims_shop_goods_erp';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }

}