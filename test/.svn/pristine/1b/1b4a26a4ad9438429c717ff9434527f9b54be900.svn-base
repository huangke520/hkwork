<?php

namespace app\api\controller;

use app\api\model\ydxq\ScanBuy as ScanBuyModel;
use app\api\model\ydxq\ScanBuyCart as ScanBuyCartModel;
use app\api\model\ydxq\Supplier;

class ScanBuy extends BaseController
{
    private $scan_buy_model;
    private $scan_buy_cart_model;
    private $supplier_model;
    public function __construct()
    {
        parent::__construct();
        $this->scan_buy_model = new ScanBuyModel();
        $this->scan_buy_cart_model = new ScanBuyCartModel();
        $this->supplier_model = new Supplier();
    }

    //增加一条扫描店铺码记录
    public function add_scansupplier_record(){
        $now_timetamp = time();
        $param = $this->request_param;
        if(!isset($param['openid']) || !isset($param['supplier_id'])){
            sdk_return('', 0, '参数缺失');
        }

        $openid = $param['openid'];//用户openid
        $supplier_id = intval($param['supplier_id']);//店铺id

        //验证店铺是否存在
        $supplier_info = $this->supplier_model->getInfo(['id'=>$supplier_id]);
        if(!$supplier_info){
            sdk_return('', 0, '店铺不存在');
        }

        //更新本店已失效 以及 其他店铺扫码记录的扫码状态
        $this->scan_buy_model->updateScanRecordStatus($openid, $supplier_id);
        //清空当前用户在其他店铺的购物车商品
        $this->scan_buy_cart_model->clearCartOtherSup($openid, $supplier_id);

        //获取当前用户是否已经定位店铺
        $record = $this->scan_buy_model->getInfo(['openid'=>$openid, 'supplier_id'=>$supplier_id, 'status'=>1]);

        $surplus_time = 15 * 60;//剩余时间戳

        //如果存在，更新时间
        if($record){
            //更新剩余时间
            $this->scan_buy_model->updateInfo($record['id'], ['invalidtime'=>$now_timetamp + $surplus_time]);
            $return = [
                'id'            =>  $record['id'],
                'type'          =>  2,//重复扫描
                'surplus_time'  =>  $surplus_time
            ];
            sdk_return($return, 1, '已定位店铺，请扫码购物');
        }

        //清空当前店铺购物车中的商品
        $this->scan_buy_cart_model->clearSuppCart($openid, $supplier_id);

        //插入新的店铺数据
        $map = [
            'openid'        =>  $openid,//访问用户
            'supplier_id'   =>  $supplier_id,//店铺id
            'creattime'     =>  $now_timetamp,//创建时间
            'invalidtime'   =>  $now_timetamp + $surplus_time,//失效时间
            'status'        =>  1,
        ];
        $id = $this->scan_buy_model->insertInfo($map);
        $return = [
            'id'            =>  $id,
            'type'          =>  1,
            'surplus_time'  =>  $surplus_time
        ];
        sdk_return($return, 1, '成功定位店铺');
    }

    //获取当前用户是否已经定位店铺
    public function get_scan_status(){
        $param = $this->request_param;
        if(!isset($param['openid'])){
            sdk_return('', 0, '参数缺失');
        }

        //更新  已失效 店铺扫码记录的扫码状态
        $this->scan_buy_model->updateAllScanRecordStatus($param['openid']);

        //获取当前用户是否已经定位店铺
        $where = [
            ['openid', '=', $param['openid']],
            ['status', '=', 1]
        ];
        $scan_info = $this->scan_buy_model->getInfo($where);
        if($scan_info){
            $return = [
                'is_scan'       =>  1,
                'supplier_id'   =>  $scan_info['supplier_id'],
            ];
            sdk_return($return, 1, '已定位店铺');
        }else{
            $return = [
                'is_scan'       =>  0,
            ];
            sdk_return($return, 1, '未定位店铺，需重新定位');
        }
    }

}