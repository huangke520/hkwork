<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-29
 * Time: 15:41
 */

namespace app\api\model\btjnew;

use app\api\model\CommonBtjNewModel;

class PriceList extends CommonBtjNewModel
{
    protected $autoWriteTimestamp = true;
    protected $tableName = 'btj_bd_pirce_list';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
}