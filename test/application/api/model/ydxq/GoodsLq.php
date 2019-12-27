<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-21
 * Time: 15:23
 */

namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;

class GoodsLq extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_shop_goods_lianqiang';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
}
