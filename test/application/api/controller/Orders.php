<?php

namespace app\api\controller;

use app\api\model\ydxq\ScanBuyCart as ScanBuyCartModel;
use app\api\model\ydxq\Supplier;
use app\api\model\ydxq\ShopGoods as ShopGoodsModel;
use app\api\model\ydxq\Orders as OrdersModel;
use app\api\model\ydxq\OrdersGoods as OrdersGoodsModel;

use Think\Db;
use think\Exception;

class Orders extends BaseController
{
    private $scan_buy_cart_model;
    private $supplier_model;
    private $shop_goods_model;
    private $orders_model;
    private $orders_goods_model;
    private $order_cancel_time;

    public function __construct()
    {
        parent::__construct();
        $this->scan_buy_cart_model = new ScanBuyCartModel();
        $this->supplier_model = new Supplier();
        $this->shop_goods_model = new ShopGoodsModel();
        $this->orders_model = new OrdersModel();
        $this->orders_goods_model = new OrdersGoodsModel();
        $this->order_cancel_time = (60*60)*2;//取消订单的时间范围
//        $this->order_cancel_time = (60*2);//取消订单的时间范围
    }

    //创建一条订单
    public function create_order(){
        $param = $this->request_param;
        if(!isset($param['openid']) || !isset($param['goods']) || !isset($param['supplier_id'])){
            sdk_return('', 0, '参数缺失');
        }
        $openid = $param['openid'];//当前登录用户opendi
        $delivery_type = isset($param['delivery_type']) ? intval($param['delivery_type']) : 2;//配送方式  0：自提;1:商家配送;2:扫码购线下拿货
        $supplier_id = intval($param['supplier_id']);//所在店铺id
        //$goods = json_decode($param['goods'], true);//购物车商品信息
        $goods = $param['goods'];
        if(!is_array($goods) || !count($goods)){
            sdk_return('', 0, '无效的参数goods');
        }

        $goods_id = array_column($goods, 'goodsid');
        if(empty($goods_id)){
            sdk_return('', 0, '无效的参数goods');
        }

        //获取数据库商品信息
        $db_goods = $this->shop_goods_model->getAllListPro([['id', 'in', $goods_id]], ['id','title', 'smg_price', 'smg_total']);
        $tmp_goods = [];
        foreach ($db_goods as $k => $v){
            $tmp_goods[ $v['id'] ] = $v;
        }

        //获取当前用户加入购物车中的所有当前店铺商品
        $carts = $this->scan_buy_cart_model->getAllList(['openid'=>$openid, 'supplier_id'=>$supplier_id]);
        $tmp_carts = [];
        foreach ($carts as $k => $v){
            $tmp_carts[ $v['goodsid'] ] = $v;
        }

        //生成订单号
        $order_sn = $this->createNO( 'SMG');
        $orders = [
            'uniacid'           =>  4,
            'supplier_id'       =>  $supplier_id,//店铺id
            'delivery_type'     =>  $delivery_type,//配送方式
            'openid'            =>  $openid,//当前登录用户的openid
            'ordersn'           =>  $order_sn,//订单号
            'addressid'         =>  isset($param['addressid']) ? intval($param['addressid']) : '',
            'status'            =>  0,
            'remark'            =>  isset($param['remark']) ? trim($param['remark']) : '',
            'createtime'        =>  time(),
            'paytype'           =>  0,
        ];
        //验证商品信息
        $order_price = 0;//订单金额
        foreach ($goods as $k => $v){
            //验证数据库商品信息
            if(!isset($tmp_goods[ $v['goodsid'] ])){
                sdk_return('', 0, '无效的商品id:'. $v['goodsid']);
            }
            $goods_info = $tmp_goods[ $v['goodsid'] ];

            //验证购物车商品信息
            if(!isset($tmp_carts[ $v['goodsid'] ])){
                sdk_return('', 0, '购物车中不存在商品:'.$goods_info['title']);
            }
            $cart_info = $tmp_carts[ $v['goodsid'] ];

            //验证库存
            if($cart_info['total'] > $goods_info['smg_total']){
                sdk_return('', 0, '商品' . $goods_info['title'] . '库存不足');
            }

            $goods_price = $goods_info['smg_price'] * $cart_info['total'];

            //获取订单金额，此处购物车商品数量取自数据库中的数量
            $order_price += $goods_price;

            //生成订单商品表数据
            $order_goods[] = [
                'goodsid'       =>  $v['goodsid'],//商品id
                'price'         =>  $goods_price,//当前商品价格
                'total'         =>  $cart_info['total'],//当前商品购买数量
                'createtime'    =>  time(),
                'openid'        =>  $openid,
                'realprice'     =>  $goods_price,
                'oldprice'      =>  $goods_price
            ];
        }

        $orders['price'] = $orders['goodsprice'] = $order_price;//订单金额

        Db::startTrans();//开启事务

        //数据插入到订单主表
        $order_id = $this->orders_model->insertInfo($orders);

        foreach ($order_goods as $k => $v){
            $order_goods[ $k ]['orderid'] = $order_id;//订单id
        }

        //数据插入到订单商品表
        $orders_goods_rst = $this->orders_goods_model->insertMore($order_goods);

        //清空购物车
        $clear_rst = $this->scan_buy_cart_model->clearSuppCart($openid, $supplier_id);

        if(!$order_id || !$orders_goods_rst || !$clear_rst){
            Db::rollback();//事务回滚
            sdk_return('', 0, '服务器错误，请稍后重试');
        }

        Db::commit();//事务提交

        sdk_return(['orderid'=>$order_id, 'ordersn'=>$order_sn], 1, '创建订单成功');
    }

    //生成订单号
    public function createNO($prefix){
        $billno = date('YmdHis') . $this->random(6, true);

        while (1) {
            $count = $this->orders_model->getCount(['ordersn'=>$billno]);
            if ($count <= 0) {
                break;
            }
            $billno = date('YmdHis') . $this->random(6, true);
        }

        return $prefix . $billno;
    }

    public function random($length, $numeric = false) {
        $seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
        if ($numeric) {
            $hash = '';
        } else {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }
        $max = strlen($seed) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }

    //订单详情接口
    public function detail(){
        $param = $this->request_param;
        if(!isset($param['openid']) || !isset($param['order_id'])){
            sdk_return('', 0, '参数缺失');
        }

        $order_id = intval($param['order_id']);
        $openid = $param['openid'];

        //获取订单详情
        $order_info = $this->orders_model->getInfoPro(['id'=>$order_id, 'openid'=>$openid], ['id','ordersn','price','status','createtime','supplier_id']);
        if(empty($order_info)){
            sdk_return('', 0, '订单信息错误');
        }

        //获取店铺信息
        $supplier_info = $this->supplier_model->getInfoPro(['id'=>$order_info['supplier_id']], ['name sup_name','address']);

        $order_info = array_merge($order_info, $supplier_info);

        //获取订单商品列表
        $order_goods = $this->orders_goods_model->getAllListPro(['orderid'=>$param['order_id']], ['goodsid', 'orderid', 'price', 'total']);

        //获取订单商品详情
        foreach ($order_goods as $k => $v){
            $goods_info = $this->shop_goods_model->getInfoPro(['id'=>$v['goodsid']], ['title','thumb']);
            $goods_info['thumb'] = imgSrc($goods_info['thumb']);
            $v['unit_price'] = $v['price'] / $v['total'];
            $order_goods[ $k ] = array_merge($v, $goods_info);
        }

        $order_info['goods_num'] = count($order_goods);
        $order_info['goods'] = $order_goods;

        sdk_return($order_info, 1, '获取订单详情成功');
    }

    /**
     * 判断订单是否超过取消订单的时间（判断超时）
     */
    public function cancelTime(){
        $param = $this->request_param;
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'缺少参数');
        //查询当前订单创建时间
        $order_time = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->field('createtime')->where([['id','=',$order_id]])->find();
        if(!empty($order_time)){
            $out_time = $order_time['createtime'] + $this->order_cancel_time;
//            $out_time = $order_time['createtime'] + ((60));
            if($out_time <= time()){
                sdk_return('',6,'订单已超时暂不可取消，请联系业务员');
            }
        }else{
            sdk_return('',6,'订单错误');
        }
        sdk_return('',1,'可以取消');
    }

    /**
     * 取消订单
     * auth：maic
     */
    public function cancelOrder(){
        $param = $this->request_param;
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'缺少参数');
        $cancel_note = !empty($param['cancel_note']) ? $param['cancel_note'] : sdk_return('',6,'缺少参数');
        //查询当前订单创建时间
        $order_time = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->field('createtime,openid,supplier_id,coupon_id')->where([['id','=',$order_id]])->find();
        if(!empty($order_time)){
            $out_time = $order_time['createtime'] + $this->order_cancel_time;
//            $out_time = $order_time['createtime'] + ((60));
            if($out_time <= time()){
                sdk_return('',6,'订单已超时暂不可取消，请联系业务员');
            }
        }else{
            sdk_return('',6,'订单错误');
        }
        $ret = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id', $order_id)->update(array('status' => -1, 'canceltime' => time(),'cancel_note' => $cancel_note));
        if(!empty($ret)){
            //取消正常订单之后，将关联的金币兑换订单更改为未关联状态，就是把shop_order_id置为0，并查找当日是否有最近的订单
            $start_time = strtotime(date('Y-m-d'));
            $end_time = strtotime("+1 day", $start_time);
            //查询当日订单
            $shop_order = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->field('id')->where('is_month_card = 1 and status = 0 and supplier_id = '.$order_time['supplier_id'].' and openid = "'.$order_time['openid'].'" and createtime > '.$start_time.' and createtime < '.$end_time)->order('createtime','desc')->find();
            $shop_order_id = 0;
            if(!empty($shop_order)){
                $shop_order_id = $shop_order['id'];
            }
            $update = null;
            $update['shop_order_id'] = $shop_order_id;
            $update['bind_time'] = 0;
            Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where('is_dist_order = 1 and shop_order_id = '.$order_id)->update($update);
            //退回优惠券
            Db::connect('db_mini_mall')->table('ims_ewei_shop_member_coupon')->where('order_id = '.$order_id)->update(['coupon_status'=>2,'order_id'=>0,'order_time'=>0]);
            if(!empty($order_time['coupon_id'])){
                $msg = '取消成功，优惠券已退回';
            }else{
                $msg = '取消成功';
            }
            //修改商品库存
            $goods_id = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->field('goodsid,total')->where('total > 0 and orderid = '.$order_id)->select();
            if(!empty($goods_id)){
                foreach ($goods_id as $one_goods){
                    Db::connect('db_mini_mall')->execute("UPDATE ims_ewei_shop_goods set total = total + {$one_goods['total']},erp_total = erp_total + {$one_goods['total']} where id = {$one_goods['goodsid']}");
                }
            }
            sdk_return('',1,$msg);
        }else{
            sdk_return('',6,'取消失败');
        }
    }

    /**
     * 订单详情
     * auth:maic
     */
    public function orderDetail(){
        $param = $this->request->param();
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'参数错误');
        //查询订单信息
        $order_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->alias('a')->leftJoin('ims_yd_supplier b','a.supplier_id = b.id')->field('a.*,b.name as sup_name')->where('a.id = '.$order_id)->find();
        if(!empty($order_data)){
            $realname = '';//接收人真实姓名
            $phone = '';//接收人真实联系电话
            $address = '';//接收人配送地址
            $delivery_type = '店铺自提';
            if ($order_data['delivery_type'] == 1) {
                $delivery_type = '货到付款';
                try {
                    $address = unserialize($order_data['address']);
                } catch (Exception $exceptione) {
                    $address = '';
                }
                if (!empty($address)) {
                    $realname = $address['realname'];//接收人真实姓名
                    $phone = $address['mobile'];//接收人真实联系电话
                    $address = $address['address'].$address['street'];//接收人配送地址
                }
                if($order_data['order_type'] == 1){
                    $delivery_type = '送货上门';
                }
            }
            $status = $order_data['status'];
            if($order_data['order_type'] == 2){
                if($order_data['status'] == 1){
                    $status = 3;
                }
            }
            //判断订单是否可以取消
            $order_time = $order_data['createtime'];
            $is_cancel = 0;//不可以取消
            if(($status == 0) || ($status == 2)){
                $out_time = $order_time + $this->order_cancel_time;
                if($out_time > time()){
                    $is_cancel = 1;//可以取消
                }
            }
            //查询商品信息
            $order_goods_arr = array();
//            $gold_goods = pdo_fetchall("SELECT a.title,a.img,b.total from ims_ewei_shop_exchange_goods as a left join ims_ewei_shop_exchange_order as b on a.id = b.goodsid where b.shop_order_id = {$order_id} and b.is_dist_order = 1");
            $gold_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->alias('a')->leftJoin('ims_ewei_shop_exchange_order b','a.id = b.goodsid')->where("b.shop_order_id = {$order_id} and b.is_dist_order = 1")->field('a.title,a.img,b.total')->select();
            $gold_goods_arr = array();
            if(!empty(count($gold_goods))){
                foreach ($gold_goods as $gold_key => $gold_value){
                    $gold_goods_one = [
                        'price' => '兑换订单',
                        'goodsid' => '',
                        'total' => !empty($gold_value['total']) ? $gold_value['total'] : 0,
                        'title' => !empty($gold_value['title']) ? $gold_value['title'] : '',
                        'thumb' => !empty($gold_value['img']) ? imgSrc($gold_value['img']) : '',
                        'realprice' => '',
                        'goods_type' => 1,
                    ];
                    $order_goods_arr[] = $gold_goods_one;
                }
            }

            $order_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->alias('a')->leftJoin('ims_ewei_shop_goods b','a.goodsid = b.id')->field('a.id as order_goods_id,b.title,b.thumb,a.realprice,a.price,a.total')->where('a.total > 0 and a.orderid = '.$order_id)->select();
            $goods_sum = 0;//全部商品数量
            if(!empty($order_goods)){
                foreach ($order_goods as $one_goods){
                    $one_goods['thumb'] = imgSrc($one_goods['thumb']);
                    $goods_sum = $one_goods['total'] + $goods_sum;
                    $one_goods['goods_type'] = 0;//0表示正常商品，1：表示金币兑换商品
                    $order_goods_arr[] = $one_goods;
                }
            }

            //判断当前订单是否是孙宇店铺类型订单
            $supplier_data = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('id = '.$order_data['supplier_id'])->field('is_dist')->find();
            $is_dist = !empty($supplier_data['is_dist']) ? $supplier_data['is_dist'] : 0;//1:是经销商，0：不是
            //物流状态
            $delivery_status_msg = '';
            if($order_data['status'] != -1){
                if($order_data['delivery_status'] == 0){
                    $delivery_status_msg = '物流状态：待分拣';
                }elseif($order_data['delivery_status'] == 1){
                    $delivery_status_msg = '物流状态：已分拣';
                }elseif($order_data['delivery_status'] == 2){
                    $delivery_status_msg = '物流状态：配送中';
                }elseif($order_data['delivery_status'] == 4){
                    $delivery_status_msg = '物流状态：配送完成';
                }elseif($order_data['delivery_status'] == 5){
                    $status = -1;
                    $delivery_status_msg = '物流状态：已拒收';
                }
            }
            $return_data = [
                'address' => $address,
                'realname' => $realname,
                'phone' => $phone,
                'order_sn' => $order_data['ordersn'],
                'create_time' => !empty($order_time) ? date('Y-m-d H:i:s',$order_time) : '----',
                'pay_time' => !empty($order_data['paytime']) ? date('Y-m-d H:i:s',$order_data['paytime']) : '----',
                'finishtime' => !empty($order_data['finishtime']) ? date('Y-m-d H:i:s',$order_data['finishtime']) : '',
                'sup_name' => $order_data['sup_name'],//下单店铺
                'delivery_type' => $delivery_type,//订单类型
                'send_type' => $order_data['delivery_type'],//配送类型，0：自提，1：商家配送
                'status' => $status,//订单状态：-1：已取消，0：待支付，1：已支付，2：已确认，3：已完成
                'order_all_price' => round(($order_data['price'] + $order_data['coupon_money']),2),//订单总额
                'coupon_money' => $order_data['coupon_money'],//优惠金额
                'price' => $order_data['price'],//实际付款金额
                'is_cancel' => $is_cancel,//是否可以取消订单，0：不可以取消订单，1：可以取消订单
                'goods_sum' => $goods_sum,//全部商品数量
                'is_dist' => $is_dist,//1:是经销商，0：不是
                'pick_code' => $order_data['pick_code'],//提货码
                'order_goods_arr' => $order_goods_arr,//商品数组
                'after_sale_id' => $order_data['after_sale_id'],//售后ID
                'remark' => $order_data['remark'],//订单备注
                'delivery_status_msg' => $delivery_status_msg,//物流状态
            ];
            sdk_return($return_data,1,'success');
        }else{
            sdk_return('',6,'没有找到相应订单');
        }
    }

    /**
     * 新版c端订单列表
     * 2019年11月5日13:40:51
     * auth：maic
     */
    public function orderListC(){
        $param = $this->request->param();
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数错误');//用户openID
        $user_openid = is_sns($user_openid);
        $show_status = !empty($param['show_status']) ? $param['show_status'] : 1;//1：全部，2：代付款，3：已付款，4：已完成，5：售后
        $page = !empty($param['page']) ? $param['page'] : 1;
        $pageSize = 10;
        $where = "a.openid = '{$user_openid}'";
        switch ($show_status){
            case '1':
                //1：全部
                break;
            case '2':
                //2：代付款
                $where .= ' and (a.status = 0 or a.status = 2) and a.delivery_status != 5 ';
                break;
            case '3':
                //3：已付款，（只有普通店铺有已付款，孙宇店铺类型的已付款就是已完成，没有已付款状态）
                $where .= ' and a.status = 1 and a.order_type = 1';
                break;
            case '4':
                //4：已完成，（普通店铺就是已完成，孙宇类型店铺支付即完成，已拒收（不判断订单状态））
                $where .= ' and (((a.status = 3 and a.order_type = 1) or ((a.status = 1 or a.status = 3) and a.order_type = 2) or a.status = -1) or a.delivery_status = 5 )';
                break;
            case '5':
                //5：售后，（孙宇店铺类型支付成功即可售后，取消不可以售后）
                $return_data = $this->afterSaleOrder($user_openid,$page);
                sdk_return($return_data,1,'success');
                break;
        }
        $order_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->alias('a')->leftJoin('ims_yd_supplier b','a.supplier_id = b.id')->field('a.id,a.ordersn,a.createtime,a.delivery_type,a.order_type,a.pick_code,a.supplier_id,b.name as sup_name,a.price,a.status,b.is_dist,a.after_sale_id,a.delivery_status,a.is_affirm')->where($where)->where('(a.is_month_card = 1 or a.is_month_card = 3)')->order('a.createtime','desc')->paginate($pageSize)->toArray();
        $return_data = [];
        if(!empty($order_data['data'])){
            $order_data_arr = $order_data['data'];
            foreach ($order_data_arr as $key => $value){
                $order_time = $value['createtime'];
                $value['createtime'] = !empty($order_time) ? date('Y-m-d H:i:s',$order_time) : '----';
                //查询商品信息
                $one_order_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->alias('a')->leftJoin('ims_ewei_shop_goods b','a.goodsid = b.id')->where('a.total > 0 and a.orderid = '.$value['id'])->field('b.thumb')->order('a.total','desc')->select();
                $value['order_goods_sum'] = count($one_order_goods);//商品款数
                if(count($one_order_goods) > 0){
                    foreach ($one_order_goods as $goods_key => $one_goods){
                        $one_order_goods[$goods_key]['thumb'] = imgSrc($one_goods['thumb']);
                    }
                    $value['order_goods'] = array_slice($one_order_goods,0,3);//商品数组
                }else{
                    $value['order_goods'] = [];
                }
                $value['is_cancel'] = 0;//0:不可以取消，1：可以取消
                //区别物流状态
                $value['delivery_status_msg'] = '';
                if($value['status'] != -1){
                    if($value['delivery_status'] == 0){
                        $value['delivery_status_msg'] = '物流状态：待分拣';
                    }elseif($value['delivery_status'] == 1){
                        $value['delivery_status_msg'] = '物流状态：已分拣';
                    }elseif($value['delivery_status'] == 2){
                        $value['delivery_status_msg'] = '物流状态：配送中';
                    }elseif($value['delivery_status'] == 4){
                        $value['delivery_status_msg'] = '物流状态：配送完成';
                    }elseif($value['delivery_status'] == 5){
                        $value['status'] = -1;
                        $value['delivery_status_msg'] = '物流状态：已拒收';
                    }
                }
                //查询订单状态，（主要区分孙宇店铺类型）
                //订单状态：-1：取消订单，0：待支付，1：已支付，3：已完成
                if($value['order_type'] == 2){
                    //货到付款类型，孙宇店铺类型
                    //是否可以取消
                    if($value['status'] == 0 || $value['status'] == 2){
                        $out_time = $order_time + $this->order_cancel_time;
                        if($out_time > time()){
                            $value['is_cancel'] = 1;
                        }
                        $value['status'] = 0;
                    }
                    //订单状态
                    if($value['status'] == 1){
                        $value['status'] = 3;
                    }
                }
                $value['edit_is_affirm'] = 0;//1:可以确认，0：不可以确认
                $return_data[] = $value;
            }
        }
        sdk_return($return_data,1,'success');
    }

    /**
     * 售后订单列表
     * auth：maic
     * @param string $user_openid
     * @param int $page
     * @return array
     * @throws Exception
     */
    private function afterSaleOrder($user_openid = '',$page = 1){
        $user_openid = is_sns($user_openid);
        $pageSize = 10;
//        $three_day = 60 * 60 * 24 * 3;
        $three_day = 60 * 2;
        $w_time = time() - $three_day;
        $where = ' and a.finish_time > '.$w_time.' and a.finish_time <> 0';
        $after_sale_order = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_after_sale')->alias('a')->leftJoin('ims_ewei_shop_order b','a.order_id = b.id')->where('a.open_id = "'.$user_openid.'" and (a.status = 1 or (a.status = 2 '.$where.'))')->field('a.id as after_sale_id,a.status as after_sale_status,b.id,b.ordersn,b.createtime,b.delivery_type,b.order_type,b.pick_code,b.supplier_id,b.price,b.status,b.after_sale_id')->order(['a.status'=>'asc','a.create_time'=>'desc'])->page($page,$pageSize)->select();
        $after_sale_order_return = [];
        if(!empty($after_sale_order)){
            $after_sale_order_list = $after_sale_order;
            foreach ($after_sale_order_list as $key => $value){
                $order_time = $value['createtime'];
                $value['createtime'] = !empty($order_time) ? date('Y-m-d H:i:s',$order_time) : '----';
                //查询商品信息
                $one_order_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->alias('a')->leftJoin('ims_ewei_shop_goods b','a.goodsid = b.id')->where('a.total > 0 and a.orderid = '.$value['id'])->field('b.thumb')->order('a.total','desc')->select();
                $value['order_goods_sum'] = count($one_order_goods);//商品款数
                if(count($one_order_goods) > 0){
                    $value['order_goods'] = array_slice($one_order_goods,0,3);//商品数组
                }else{
                    $value['order_goods'] = [];
                }
                $value['is_cancel'] = 0;//0:不可以取消，1：可以取消
                //查询订单状态，（主要区分孙宇店铺类型）
                //订单状态：-1：取消订单，0：待支付，1：已支付，3：已完成
                if($value['order_type'] == 2){
                    //货到付款类型，孙宇店铺类型
                    //是否可以取消
                    if($value['status'] == 0 || $value['status'] == 2){
                        $out_time = $order_time + $this->order_cancel_time;
                        if($out_time > time()){
                            $value['is_cancel'] = 1;
                        }
                        $value['status'] = 0;
                    }
                    //订单状态
                    if($value['status'] == 1){
                        $value['status'] = 3;
                    }
                }
                //查询店铺名称
                $sup_name_arr = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('id = '.$value['supplier_id'])->field('name,is_dist')->find();
                $value['sup_name'] = !empty($sup_name_arr['name']) ? $sup_name_arr['name'] : '';
                $value['is_dist'] = !empty($sup_name_arr['is_dist']) ? $sup_name_arr['is_dist'] : 0;
                $after_sale_order_return[] = $value;
            }
        }
        return $after_sale_order_return;
    }

    /**
     * 修改订单（2019年12月16日11:42:02）
     * auth：maic
     */
    public function editOrderC(){
        $param = $this->request->param();
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'参数缺失');//订单ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//用户openID
        $goods_id = !empty($param['goods_id']) ? $param['goods_id'] : sdk_return('',6,'参数缺失');//商品ID
        $order_goods_id = !empty($param['order_goods_id']) ? $param['order_goods_id'] : sdk_return('',6,'参数缺失');//订单商品关联表的ID
        $price = !empty($param['price']) ? $param['price'] : sdk_return('',6,'参数缺失');//单品价格
        $total = !empty($param['total']) ? $param['total'] : sdk_return('',6,'参数缺失');//购买数量
        $edit_note = !empty($param['edit_note']) ? $param['edit_note'] : '';//修改备注说明
        //查询修改前的价格和数量
        $old_order_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->where('id = '.$order_goods_id)->find();
        //查询订单状态
        $order_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id = '.$order_id)->field('status,is_month_card,supplier_id,openid,ordersn,is_affirm')->find();
        if(($order_data['status'] == 0) && ($order_data['is_month_card'] == 3)){
//            if($order_data['is_affirm'] == 1){
//                sdk_return('',6,'当前订单已经确认，不能修改');
//            }
            $new_price = round(($price * $total),2);//四舍五入新的价格
            $update = null;
            $update['price'] = $new_price;
            $update['total'] = $total;
            $update['realprice'] = $price;
            Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->where('id = '.$order_goods_id)->update($update);
            $update_order = null;
            $update_order['price'] = $new_price;
            $update_order['goodsprice'] = $new_price;
            $update_order['is_edit'] = 1;
            $update_order['edit_note'] = $edit_note;
            $update_order['update_time'] = time();
            Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id = '.$order_id)->update($update_order);
            //修改member_log表价格
            $update_log = null;
            $update_log['money'] = $new_price;
            Db::connect('db_mini_mall')->table('ims_ewei_shop_member_log')->where('logno = "'.$order_data['ordersn'].'"')->update($update_log);
            //增加商品修改记录
            unset($param);
            $param = [
                'sup_id' => $order_data['supplier_id'],
                'openid' => $order_data['openid'],
                'ordersn' => $order_data['ordersn'],
                'goods_id' => $goods_id,
                'r_goods_id' => $old_order_goods['r_goodsid'],
                'count_old' => $old_order_goods['total'],
                'count_new' => $total,
                'old_price' => $old_order_goods['realprice'],
                'new_price' => $price,
//                    'createuser' => $order_data['openid'],
                'createtime' => time(),
                'type' => 7,
                'desc' => '采购订单划单',
                'user_id' => 0
            ];
            Db::connect('db_mini_mall')->table('ims_member_order_change_log')->insert($param);
            sdk_return('',1,'修改成功');
        }else{
            sdk_return('',6,'当前订单不支持修改');
        }
    }

    /**
     * 确认采购订单
     */
    public function affirmOrder(){
        $param = $this->request->param();
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'缺少参数');
//        $order_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id = '.$order_id)->find();
//        if(){
//
//        }
        $update = null;
        $update['is_affirm'] = 1;
        $update['affirm_time'] = time();
        Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id = '.$order_id)->update($update);
        sdk_return('',1,'已确认');
    }

    /**
     * 判断将要导入的订单是否有未售商品
     */
    public function isOrderSell(){
        $param = $this->request->param();
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'参数缺失');//订单ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//用户openID
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');
    }

    /**
     * 获取采购订单详情
     * auth:maic
     */
    public function getCGOrder(){
        $param = $this->request->param();
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'参数缺失');//订单ID
        //查询订单信息
        $order_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->alias('a')->leftJoin('ims_yd_supplier b','a.supplier_id = b.id')->field('a.*,b.name as sup_name')->where('a.id = '.$order_id)->find();
        if((!empty($order_data)) && ($order_data['is_month_card'] == 3)){
            $order_time = $order_data['createtime'];
            $realname = '';//接收人真实姓名
            $phone = '';//接收人真实联系电话
            $address = '';//接收人配送地址
//            $delivery_type = '店铺自提';
//            $delivery_type = '货到付款';
            $delivery_type = '采购订单';
            try {
                $address = unserialize($order_data['address']);
            } catch (Exception $exceptione) {
                $address = '';
            }
            if (!empty($address)) {
                $realname = $address['realname'];//接收人真实姓名
                $phone = $address['mobile'];//接收人真实联系电话
                $address = $address['address'].$address['street'];//接收人配送地址
            }
            $status = $order_data['status'];
            //查询订单商品
            $order_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->alias('a')->leftJoin('ims_ewei_shop_goods b','a.goodsid = b.id')->field('a.id as order_goods_id,b.title,b.thumb,a.realprice,a.price,a.total,b.skuid,b.bb_step,a.goodsid,b.sale_pirce')->where('a.total > 0 and a.orderid = '.$order_id)->select();
            $goods_sum = 0;//全部商品数量
            $order_goods_arr = [];
            if(!empty($order_goods)){
                foreach ($order_goods as $one_goods){
                    $one_goods['thumb'] = imgSrc($one_goods['thumb']);
                    $goods_sum = $one_goods['total'] + $goods_sum;
                    $one_goods['goods_type'] = 0;//0表示正常商品，1：表示金币兑换商品
                    //查询最低报价
                    $goods_price_list = Db::connect('db_btj_new')->table('btj_bd_pirce_list')->where('sku_id = '.$one_goods['skuid'])->field('min(price) as min_price')->find();
                    $one_goods['min_price'] = !empty($goods_price_list['min_price']) ? $goods_price_list['min_price'] : '暂无';
                    //查询最近采购价格
                    $lately_price = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->alias('a')->leftJoin('ims_ewei_shop_order b','a.orderid = b.id')->where('a.goodsid = '.$one_goods['goodsid'].' and b.is_month_card = 3 and status = 3')->field('a.realprice')->find();
                    $one_goods['lately_price'] = !empty($lately_price['lately_price']) ? $lately_price['lately_price'] : '暂无';
                    //查询销售价格unit_count,unit_name
                    $unit = Db::connect('db_mini_mall')->table('ims_bb_sku')->where('id = '.$one_goods['skuid'])->field('id,unit_count,unit_name,spec')->find();
                    $one_goods['unit'] = !empty($unit['spec']) ? $unit['spec'] : '暂无';
                    //订单商品修改备注说明
                    $one_goods['edit_note'] = !empty($order_data['edit_note']) ? $order_data['edit_note'] : '';
                    $order_goods_arr[] = $one_goods;
                }
            }
            //是否确认'0：未确认，1：已确认'
            $affirm_msg = '待确认';
            if($order_data['is_affirm'] == 1){
                $affirm_msg = '已确认';
            }
            $is_cancel = 1;//可以取消
            if($status == 3){
                $is_cancel = 0;//不可以取消
            }
            $status_msg = '';
            $is_affirm = $order_data['is_affirm'];
            if($status == -1){
                $status_msg = '已取消';
                $is_affirm = 1;
                $is_cancel = 0;
            }elseif($status == 0){
                $status_msg = '待付款';
            }elseif ($status == 3){
                $status_msg = '已完成';
            }
            //处理订单图片信息
            $order_img = [];
            if(!empty($order_data['order_img'])){
                $order_img_arr = json_decode($order_data['order_img'],true);
                if(!empty($order_img_arr)){
                    foreach ($order_img_arr as $one_img){
                        $order_img[] = imgSrc($one_img);
                    }
                }
            }
            //是否可以修改订单商品信息
            $is_can_edit = 0;//0:不可以修改，1：可以修改
//            if(($order_data['status'] == 0) && ($order_data['is_month_card'] == 3) && ($order_data['is_affirm'] == 0)){
            if(($order_data['status'] == 0) && ($order_data['is_month_card'] == 3)){
                $is_can_edit = 1;//0:不可以修改，1：可以修改
            }
            //是否开启可以上传图片凭证
            $upload_img = 1;//1开启，0：关闭
            //返回信息
            $return_data = [
                'address' => !empty($address) ? $address : '',
                'realname' => $realname,
                'phone' => $phone,
                'order_sn' => $order_data['ordersn'],//订单编号
                'create_time' => !empty($order_time) ? date('Y-m-d H:i:s',$order_time) : '----',
                'pay_time' => !empty($order_data['paytime']) ? date('Y-m-d H:i:s',$order_data['paytime']) : '----',//支付时间
                'sup_name' => $order_data['sup_name'],//下单店铺
                'delivery_type' => $delivery_type,//订单类型
                'status' => $status,//订单状态：-1：已取消，0：待支付，1：已支付，2：已确认，3：已完成
                'status_msg' => $status_msg,//订单状态说明
                'order_all_price' => round(($order_data['price'] + $order_data['coupon_money']),2),//订单总额
                'coupon_money' => $order_data['coupon_money'],//优惠金额
                'price' => $order_data['price'],//实际付款金额
                'goods_sum' => $goods_sum,//全部商品数量
                'order_goods_arr' => $order_goods_arr,//商品数组
                'remark' => $order_data['remark'],//订单备注
                'order_img' => $order_img,//采购订单专用图片地址
                'affirm_msg' => $affirm_msg,//确认提示信息
                'is_affirm' => $is_affirm,//'0：未确认，1：已确认'
                'is_cancel' => $is_cancel,//'0：不可以取消，1：可以取消'edit_note
                'edit_note' => !empty($order_data['edit_note']) ? $order_data['edit_note'] : '',//修改备注说明'
                'cancel_note' => $order_data['cancel_note'],//订单取消原因
                'is_can_edit' => $is_can_edit,//0:不可以修改，1：可以修改
                'upload_img' => $upload_img,////1开启，0：关闭
            ];
            sdk_return($return_data,1,'success');
        }else{
            sdk_return('',6,'当前订单不支持进入详情页面');
        }
    }

    /**
     * 存储采购订单图片
     * auth：maic
     */
    public function setOrderImg(){
        $param = $this->request->param();
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'参数缺失');//订单ID
        $order_img = !empty($param['order_img']) ? $param['order_img'] : sdk_return('',6,'参数缺失');//订单图片
//        print_r($order_img);exit;
        $update = null;
        $update['order_img'] = $order_img;
        Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id = '.$order_id)->update($update);
        sdk_return('',1,'添加成功');
    }

    /**
     * 取消采购订单
     * auth：maic
     */
    public function cancelCGOrder() {
        $param = $this->request_param;
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('', 6, '缺少参数');
        $cancel_note = !empty($param['cancel_note']) ? $param['cancel_note'] : sdk_return('', 6, '缺少参数');
        //查询订单信息
        $order_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->alias('a')->leftJoin('ims_yd_supplier b','a.supplier_id = b.id')->field('a.*,b.name as sup_name')->where('a.id = '.$order_id)->find();
        if($order_data['status'] == 3){
            sdk_return('',6,'当前订单已支付，不能取消');
        }else{
            $update = null;
            $update['cancel_note'] = $cancel_note;
            $update['status'] = -1;
            $update['update_time'] = time();
            Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id = '.$order_id)->update($update);
            sdk_return('',1,'取消成功');
        }
    }
}
