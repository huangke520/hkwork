<?php
/**
 * 数据清洗接口
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-20
 * Time: 17:59
 */

namespace app\api\controller;

use app\api\controller\BaseController;
use think\Db;

use app\api\model\ydxq\SupplierSun;
use app\api\model\ydxq\GoodsProduct;
use think\facade\Debug;


class DataCollation extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // 处理指定店铺数据
    public function get_sup_goods()
    {
        ini_set("max_execution_time", "3000");
        $supplierSunModel = new SupplierSun();
        $goodsProductModel = new GoodsProduct();
        Debug::remark('begin');
        $sun_goods_res = $supplierSunModel->querySql("select id,goods_code from ims_ewei_shop_goods where id in (select goods_id from ims_yd_supplier_goods where supplier_id = 461 and status = 1)");
        if (!empty($sun_goods_res)) {
            foreach ($sun_goods_res as $k=>$v) {
                if (!empty($v['goods_code'])) {
                    $goodsProInfo = null;
                    $goodsProInfo = $goodsProductModel->getInfoPro([['barcode','like',"%".$v['goods_code']."%"]]);
                    if (!empty($goodsProInfo)) {
                        $temp = null;
                        $temp['goods_id'] = $v['id'];
                        $temp['sup_id'] = 461;
                        $temp['bar_code'] = $v['goods_code'];
                        $temp['sku_id'] = $goodsProInfo['id'];
                        $temp['item_id'] = $goodsProInfo['itemId'];
                        $supplierSunModel->insertInfo($temp);
                    }
                }
            }
            Debug::remark('end');
            echo Debug::getRangeTime('begin','end').'s';
        }

        return sdk_return('',0,'data is empty');

    }
}



