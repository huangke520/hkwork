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

class CustomerBrandCode extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_customer_brand_code';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }

    //获取店铺标记的所有code
    public function getCustomerBrandCodes($where){
        return Db::connect($this->database_config)->field(['count(id) as num', 'brand_id'])->table($this->tableName)->where($where)->group('brand_id')->select();
    }

    //取消标记
    public function deleteMark($where){
        $rst = Db::connect($this->database_config)->table($this->tableName)->where($where)->delete();
        return $rst;
    }
}