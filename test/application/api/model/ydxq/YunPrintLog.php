<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-12
 * Time: 09:26
 */
namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;

class YunPrintLog extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_shop_print_log';

    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->setTableName($this->tableName);
    }
}

