<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-29
 * Time: 15:41
 */

namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;

class GoodsFormal extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_goods_formal_img';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
}