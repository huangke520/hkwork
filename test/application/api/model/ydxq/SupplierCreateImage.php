<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-26
 * Time: 19:53
 */
namespace app\api\model\ydxq;

use think\Model;
use app\api\model\CommonMiniMallModel;

class SupplierCreateImage extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;

    protected $tableName = 'ims_supplier_create_images';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
}