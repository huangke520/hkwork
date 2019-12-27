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

class GoodsCodeInfo extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_goods_code_info';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }

    //根据品牌名获取到所有的code
    public function getCustomCodeByBrand($where, $limit_start, $page_num){
        $codes = Db::connect($this->database_config)->field(['code','count(id) as custom_repeat_num','brand','goods_name'])->table($this->tableName)->where($where)->group('code')->order(['custom_repeat_num'=>'desc','code'=>'desc'])->limit($limit_start, $page_num)->select();
        return $codes;
    }

    public function getCustomCodeByBrandCount($where){
        $codes = Db::connect($this->database_config)->field(['code','brand'])->table($this->tableName)->where($where)->group('code')->select();
        return $codes;
    }
    
}