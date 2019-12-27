<?php

namespace app\api\controller;

use app\api\model\ydxq\ShopGoods as ShopGoodsModel;
use app\api\model\ydxq\ScanBuyCart as ScanBuyCartModel;
use app\api\model\ydxq\Supplier as SupplierModel;
use app\api\model\ydxq\ScanBuy as ScanBuyModel;

class ScanBuyCart extends BaseController
{
    private $shop_goods_model;
    private $scan_buy_cart_model;
    private $supplier_model;
    private $scan_buy_model;
    public function __construct()
    {
        parent::__construct();
        $this->shop_goods_model = new ShopGoodsModel();
        $this->scan_buy_cart_model = new ScanBuyCartModel();
        $this->supplier_model = new SupplierModel();
        $this->scan_buy_model = new ScanBuyModel();
    }

    //根据商品条码获取商品信息，并加入购物车
    public function scan_barcode(){
        $param = $this->request_param;
        if(!isset($param['goods_code']) || !isset($param['supplier_id']) || !isset($param['openid'])){
            sdk_return('', 0, '参数缺失');
        }
        $supplier_id = intval($param['supplier_id']);//店铺id
        $openid = $param['openid'];//当前登录用户

        //验证店铺是否存在
        $supplier_info = $this->supplier_model->getInfo(['id'=>$supplier_id]);
        if(!$supplier_info){
            sdk_return('', 0, '店铺不存在');
        }

        //获取商品信息
        //$goods_info = $this->shop_goods_model->getInfoByGoodsCode($param['goods_code'], $supplier_id);
        $goods_info = $this->shop_goods_model->getInfo(['goods_code'=>$param['goods_code'], 'sup_id'=>$supplier_id]);
        if(empty($goods_info)){
            sdk_return('', 0, '暂不可购买此商品去看看其他商品吧');
        }

        //判断购物车中是否存在当前商品
        $info = $this->scan_buy_cart_model->getInfo(['goodsid'=>$goods_info['id'], 'supplier_id'=>$supplier_id,'openid'=>$openid]);

        $total = 1;//加入购物车的商品数量
        if($info){
            $total = $info['total'] + 1;
            $save_data = [
                'total'         =>  $total,
                'updatetime'    =>  time()
            ];
            $rst = $this->scan_buy_cart_model->updateInfo($info['id'], $save_data);
            $cart_id = $info['id'];
        }else{
            //加入到购物车
            $insert_data = [
                'openid'            =>  $openid,
                'supplier_id'       =>  $supplier_id,
                'goodsid'           =>  $goods_info['id'],
                'total'             =>  $total,
                'createtime'        =>  time(),
                'updatetime'        =>  time(),
            ];
            $cart_id = $this->scan_buy_cart_model->insertInfo($insert_data);
            $rst = $cart_id;
        }

        if(!$rst){
            sdk_return('', 0, '暂不可购买此商品 去看看其他商品吧~');
        }

        $return = [
            'cart_id'       =>  $cart_id,//购物车id
            'goodsid'       =>  $goods_info['id'],//商品id
            'total'         =>  $total,//购买数量
            'smg_price'     =>  $goods_info['smg_price'],//扫码购价格
            'smg_total'     =>  $goods_info['smg_total'],//剩余库存
            'title'         =>  $goods_info['title'],//商品名称
            'thumb'         =>  imgSrc($goods_info['thumb']),//商品缩略图
        ];

        sdk_return($return, 1, '加入购物车成功');
    }

    //购物车列表
    public function cart_lists(){
        $param = $this->request_param;
        if(!isset($param['supplier_id']) || !isset($param['openid'])){
            sdk_return('', 0, '参数缺失');
        }
        $supplier_id = intval($param['supplier_id']);//店铺id
        $openid = $param['openid'];//当前登录用户

        //验证店铺是否存在
        $supplier_info = $this->supplier_model->getInfo(['id'=>$supplier_id]);
        if(!$supplier_info){
            sdk_return('', 0, '店铺不存在');
        }

        //更新本店已失效 以及 其他店铺扫码记录的扫码状态
        $this->scan_buy_model->updateScanRecordStatus($openid, $supplier_id);
        //获取当前用户是否已经定位店铺
        $record = $this->scan_buy_model->getInfo(['openid'=>$openid, 'supplier_id'=>$supplier_id, 'status'=>1]);
        if(!$record){
            //清空当前店铺购物车中的商品
            $this->scan_buy_cart_model->clearSuppCart($openid, $supplier_id);
            sdk_return('', 0, '店铺定位已超时，请重新扫码定位!');
        }

        //定位店铺剩余时间
        $surplus_time = $record['invalidtime'] - time();

        //获取购物车列表
        $carts = $this->scan_buy_cart_model->getAllListPro(['openid'=>$openid, 'supplier_id'=>$supplier_id], ['id','total','goodsid']);
        $goodsids = array_column($carts, 'goodsid');
        if(!count($goodsids)){
            sdk_return(['surplus_time'=>$surplus_time, 'goods'=>[]], 1, '购物车为空!');
        }
        //var_dump($carts);
        //获取商品列表
        $goods_where = [
            ['id', 'in', $goodsids],
            ['deleted', '=', 0]
        ];
        $goods = $this->shop_goods_model->getAllListPro($goods_where, ['id','title','smg_price','thumb','smg_total']);
        $tmps = [];
        foreach ($goods as $k => $v){
            $v['thumb'] = imgSrc($v['thumb']);
            $tmps[ $v['id'] ] = $v;
        }

        //商品总数，商品总价格
        $cart_goods_num = $cart_goods_price = 0;
        foreach ($carts as $k => $v){
            if(isset($tmps[ $v['goodsid'] ])){
                $carts[ $k ]['title'] = $tmps[ $v['goodsid'] ]['title'];//商品名
                $carts[ $k ]['smg_price'] = $tmps[ $v['goodsid'] ]['smg_price'];//价格
                $carts[ $k ]['thumb'] = $tmps[ $v['goodsid'] ]['thumb'];//缩略图

                $cart_goods_num += $v['total'];//购物车商品总数量
                $cart_goods_price += $v['total'] * $tmps[ $v['goodsid'] ]['smg_price'];
            }else{
                unset( $carts[$k] );
            }
        }

        $return = [
            'surplus_time'      =>  $surplus_time,//剩余失效时间
            'cart_goods_num'    =>  $cart_goods_num,//购物车商品总数量
            'cart_goods_price'  =>  $cart_goods_price,//购物车商品总价格
            'is_give_gold'      =>  1,//赠送金币
            'give_gold_num'     =>  25,//赠送的金币数量
            'give_gold_num_max' =>  50,//每天最多赠送金币数量
            'goods'             =>  $carts,//购物车车商品列表
        ];

        sdk_return($return, 1, 'success');
    }

    //增减购物车商品数量
    public function update_total(){
        $param = $this->request_param;
        if(!isset($param['cart_id']) || !isset($param['openid']) || !isset($param['total'])){
            sdk_return('', 0, '参数缺失');
        }
        $cart_id = intval($param['cart_id']);//购物车id
        $total = intval($param['total']);//购物车
        $openid = $param['openid'];//当前登录用户

        //验证购物车信息
        $cart_info = $this->scan_buy_cart_model->getInfo(['id'=>$cart_id, 'openid'=>$openid]);
        if(!$cart_info){
            sdk_return('', 0, '购物车信息错误！');
        }

        //修改购物车商品数量
        $rst = $this->scan_buy_cart_model->updateInfo($cart_id, ['total'=>$total]);
        if(!$rst){
            sdk_return('', 0, '网络错误，请稍后重试！');
        }

        sdk_return('', 1, 'success');
    }

    //删除购物车商品
    public function remove(){
        $param = $this->request_param;
        if(!isset($param['cart_id']) || !isset($param['openid']) || !isset($param['supplier_id'])){
            sdk_return('', 0, '参数缺失');
        }
        $cart_id = intval($param['cart_id']);//购物车id
        $openid = $param['openid'];//当前登录用户
        $supplier_id = $param['supplier_id'];//店铺id

        //验证购物车信息
        $cart_info = $this->scan_buy_cart_model->getInfo(['id'=>$cart_id, 'openid'=>$openid]);
        if(!$cart_info){
            sdk_return('', 0, '购物车信息错误！');
        }

        $rst = $this->scan_buy_cart_model->clearCart(['id'=>$cart_id]);

        if(!$rst){
            sdk_return('', 0, '服务器错误！');
        }

        $return = [];
        //获取最新的商品信息
        $cart_info = $this->scan_buy_cart_model->getInfoPro(['supplier_id'=>$supplier_id, 'openid'=>$openid], ['id as cart_id','goodsid','total'], ['id'=>'desc']);
        if($cart_info){
            $goods_info = $this->shop_goods_model->getInfo(['id'=>$cart_info['goodsid']]);
            $cart_info['title'] = $goods_info['title'];
            $cart_info['thumb'] = imgSrc($goods_info['thumb']);
            $cart_info['smg_price'] = $goods_info['smg_price'];
            $cart_info['smg_total'] = $goods_info['smg_total'];
            $return[] = $cart_info;
        }

        sdk_return(['goods_info'=>$return], 1, '删除成功！');
    }
}