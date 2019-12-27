<?php
/**
 * b2b数据清洗接口(货比三价拆分到bb_几个表)
 * Created by seaboyer
 * Author: seaboyer@163.com
 * Date: 2019-08-29
 */

namespace app\api\controller;

use app\api\controller\BaseController;
use think\Db;

//use app\api\model\ydhl\SupplierSun;
use app\api\model\ydhl\ParityProduct;
use think\facade\Debug;


class DataSplit extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // 处理指定店铺数据
    public function get_sup_goods()
    {
        ini_set("max_execution_time", "3000");
        $ParityProductModel = new ParityProduct();
        Debug::remark('begin');
        $list_ParityProduct = $ParityProductModel->querySql("select * bsj_parity_product where id >= 1)");
        foreach ($list_ParityProduct as $one) {
            if (!empty($one['itemId'])) {
                $itemInfo = null;
                //$itemInfo = $goodsProductModel->getInfoPro([['barcode','like',"%".$v['goods_code']."%"]]);
                if (!empty($itemInfo)) {
                    $temp = null;
                    $temp['goods_id'] = $v['id'];
                    $temp['sup_id'] = 461;
                    $temp['bar_code'] = $v['goods_code'];
                    $temp['sku_id'] = $itemInfo['id'];
                    $temp['item_id'] = $itemInfo['itemId'];
                    $supplierSunModel->insertInfo($temp);
                }
            }
        }
        Debug::remark('end');
        echo Debug::getRangeTime('begin','end').'s';
    }
}



