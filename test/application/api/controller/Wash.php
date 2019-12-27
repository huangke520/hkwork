<?php

namespace app\api\controller;

use app\api\model\ydxq\BbSku;
use app\api\model\ydxq\ShopGoods as ShopGoodsModel;
use app\api\model\ydxq\BbSku as BbSkuModel;
use app\api\model\ydxq\BbGoodsItem as BbGoodsItemModel;
use app\api\model\ydxq\BbBrand as BbBrandModel;
use app\api\model\ydxq\BbCateBb as BbCateBbdModel;
use app\api\model\ydxq\BbPriceList as BbPriceListModel;
use app\api\model\ydxq\BbChannel as BbChannelModel;
use app\api\model\ydxq\ShopMemberCart as ShopMemberCartModel;

use think\Db;

class Wash extends BaseController
{
    private $shop_goods_model;
    private $bb_sku_model;
    private $bb_goods_item_model;
    private $bb_brand_model;
    private $bb_cate_bb_model;
    private $bb_price_list_model;
    private $bb_channel_model;
    private $bb_member_cart_model;

    private $ydxq_test = [
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => 'rm-2zeap44sq13kgg34p8o.mysql.rds.aliyuncs.com',
        // 用户名
        'username'        => 'ydxq_test',
        // 密码
        'password'        => 'Ydxq1234',
        // 数据库名称
        'database'        => 'ydxq_test',//ydxq_test
    ];

    private $db_btj_new = [
        // 数据库类型
        'type'            => 'mysql',
            // 服务器地址
        'hostname'        => 'rm-2zeap44sq13kgg34p8o.mysql.rds.aliyuncs.com',
            // 用户名
        'username'        => 'product_web',
            // 密码
        'password'        => 'Web20192019',
            // 数据库名称
        'database'        => 'ydxq_btj',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->shop_goods_model = new ShopGoodsModel();
        $this->bb_sku_model = new BbSkuModel();
        $this->bb_goods_item_model = new BbGoodsItemModel();
        $this->bb_brand_model = new BbBrandModel();
        $this->bb_cate_bb_model = new BbCateBbdModel();
        $this->bb_price_list_model = new BbPriceListModel();
        $this->bb_channel_model = new BbChannelModel();
        $this->bb_member_cart_model = new ShopMemberCartModel();
    }

    public function wash_sku(){

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $time = time();

        $db_config = $this->ydxq_test;

        //$db_config = 'db_mini_mall';

        //ims_bb_sku
        $where = [
            ['id', '>', 100080001],
            ['id', '<', 100090000]
        ];

        $items = DB::connect($db_config)->field('id,goods_name,img,unit,content')->table('ims_bb_goods_item')->where($where)->select();

        $items_ids = array_column($items, 'id');

        $skus = DB::connect($db_config)->field(['unit_count','code_list_new','goods_id','id'])->table('ims_bb_sku')->where([['goods_id', 'in', $items_ids]])->select();

        $sku_tmp_arr = [];
        foreach ($skus as $k => $v){
            $sku_tmp_arr[ $v['goods_id'] ][] = $v;
        }

        foreach ($items as $k => $v){
            //获取当前item的所有规格
            if(isset( $sku_tmp_arr[ $v['id'] ] )){
                $sku = $sku_tmp_arr[ $v['id'] ];

                $unit_sku_id = 0;
                $code_list_temp_str = '';
                foreach ($sku as $sk => $sv){
                    if($sv['code_list_new'] != 'null' && $sv['code_list_new'] != '' && $sv['code_list_new'] != '无条形码'){
                        $code_list_temp_str .= $sv['code_list_new'] . ',';
                    }
                    if($sv['unit_count'] == 1){
                        $unit_sku_id = $sv['id'];
                    }
                }

                $code_list_temp_arr = implode(',', array_unique(explode(',', trim($code_list_temp_str, ','))));

                //如果当前item存在单品规格,更新
                if($unit_sku_id != 0){
                    $update_data = [
                        'code_list_temp'        =>  $code_list_temp_arr
                    ];
                    DB::connect($db_config)->table('ims_bb_sku')->where(['id'=>$unit_sku_id])->update($update_data);
                }else{//如果当前item不存在单品规格，新增
                    $insert_data = [
                        'goods_id'      =>  $v['id'],
                        'sku_name'      =>  $v['goods_name'],
                        'sku_img'       =>  $v['img'],
                        'unit_name'     =>  $v['unit'],
                        'unit_count'    =>  1,
                        'comefrom'      =>  99,
                        'createtime'    =>  $time,
                        'code_list_temp'=>  $code_list_temp_arr,
                        'is_used'       =>  1,
                        'spec'          =>  $v['content'] . '*' . $v['unit']
                    ];
                    //插入一条新的数据
                    DB::connect($db_config)->table('ims_bb_sku')->insert($insert_data);
                }
            }
        }
        var_dump(count($sku_tmp_arr), count($skus), count($unit_sku_id));
    }

    public function washItemPriceCount(){
        $tmp = 'db_mini_mall';

        $item_sql = "select id from ims_bb_goods_item";
        $item = DB::connect($tmp)->query($item_sql);

        $channel_sql = 'select sum(channel_count) as channel_count,sku.goods_id from ims_bb_sku as sku join ims_bb_city_sku as cs on cs.sku_id = sku.id group by sku.goods_id';

        $channels = DB::connect($tmp)->query($channel_sql);

        $channels_tmp = [];
        foreach ($channels as $k => $v){
            $channels_tmp[ $v['goods_id'] ] = $v['channel_count'];
        }

        foreach ($item as $k => $v){
            if(isset($channels_tmp[ $v['id'] ])){
                $channel_data = $channels_tmp[ $v['id'] ];

                $update_sql = 'update ims_bb_goods_item set sku_price_count = '.$channel_data . ' where id = '.$v['id'];

                DB::connect($tmp)->execute($update_sql);
            }
        }
    }


    //清洗 ims_bb_city_sku 表的channel_count（b2b渠道报价数）
    public function wash_city_channel_count(){

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $bd_config = $this->ydxq_test;

        //$bd_config = 'db_mini_mall';

        //获取ims_bb_city_sku表的所有数据
        $sku_ids = DB::connect($bd_config)->table('ims_bb_city_sku')->column('sku_id');
        //统计
        $price_where = [
            ['sku_id', 'in', $sku_ids],
            ['channel_id', 'in', [1,2,3,4,6,7,15]]
        ];
        $price = DB::connect($bd_config)->field(['sku_id'])->table('ims_bb_price_list')->where($price_where)->select();

        //var_dump(DB::connect($bd_config)->getlastsql());
        $price_tmp = [];
        foreach ($price as $k => $v){
            $price_tmp[ $v['sku_id'] ][] = $v;
        }

        foreach ($sku_ids as $k => $v){
            if(isset($price_tmp[ $v ])){
                DB::connect($bd_config)->table('ims_bb_city_sku')->where(['sku_id'=>$v])->update(['channel_count'=>count($price_tmp[ $v ])]);
            }
        }
        //DB::connect($bd_config)->table('ims_bb_city_sku')->where(['sku_id'=>$v['sku_id']])->update(['channel_count'=>$v['count']]);
    }


    public function goodsToErp(){
            //写入erp
            $data=[
                'goods_code'    =>  '86342',
                'goods_name'    =>  '甘源酱汁牛肉味蚕豆75g',
                'goods_pic'     =>  'none.jpg'
            ];
            $postdata = http_build_query($data);
            $opts = array('http' =>
                array( 'method'  => 'POST','header'  => 'Content-type: application/form-data', 'content' => $postdata ) );
            $url='http://ydxqtptest.yundian168.com/api/erp/goods_upload';
            $context = stream_context_create($opts);
            $request_rst = file_get_contents($url, false, $context);
            $request_arr = json_decode($request_rst, true);
            var_dump($request_arr);
    }


    //导入excel
    public function lead_in_goods(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //引入excel
        include '../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
        $file = request()->file('file');//获取文件，file是请求的参数名

        $dir = '../runtime/huimin/';
        $info = $file->move($dir);
        //表用函数方法 返回数组
        $excel_path = $info->getSaveName();  //获取上传文件名
        $file_name = $dir . $excel_path;   //上传文件的地址
        $obj_PHPExcel = \PHPExcel_IOFactory::load($file_name);  //加载文件内容

        $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式

        //删除第一行
        array_shift($excel_array);
        //var_dump($excel_array);die;
        //$db_config = 'db_mini_mall';
        $db_config = $this->ydxq_test;
        $time = time();

        foreach ($excel_array as $k => $v){
            $goods_id = $v[0];
            $bb_start_count = empty($v[4]) ? 1 : $v[4];//起购数，步长
            $bb_end_count = empty($v[5]) ? 0 : $v[5];//最大限购数
            $price = $v[3];


            Db::connect($db_config)->table($db_config)->table('ims_yd_supplier_goods')->where([['id', '=', $goods_id]])->update(['status'=>1,'supplier_price'=>$price]);

            $sup_goods = Db::connect($db_config)->table($db_config)->table('ims_yd_supplier_goods')->where([['id', '=', $goods_id]])->find();

            $where = [
                ['id', '=', $sup_goods['goods_id']]
            ];
            $shop_goods_data = [
                'status'        =>  1,
                'updatetime'    =>  $time,
                'sale_pirce'    =>  $price,
                'bb_start_count'=>  $bb_start_count,
                'bb_step'       =>  $bb_start_count,
                'bb_end_count'  =>  $bb_end_count
            ];

            Db::connect($db_config)->table('ims_ewei_shop_goods')->where($where)->update($shop_goods_data);
        }
    }

    //清洗商品表中的barcode
    public function wash_shop_goods_barcode(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        //$db_config = 'db_mini_mall';
        $db_config = $this->ydxq_test;
        //获取商品表中所有的sku
        $shop_goods_where = [
            ['sup_id', '=', 461],
            ['status', '=', 1],
            ['deleted', '=', 0]
        ];

        $skuids = Db::connect($db_config)->table('ims_ewei_shop_goods')->where($shop_goods_where)->column('skuid');

        //根据skuid获取所有的item_id
        $item_ids = Db::connect($db_config)->table('ims_bb_sku')->where([['id', 'in', $skuids]])->column('goods_id');

        //获取每个item下的单个商品的barcode
        $sku_where = [
            ['goods_id', 'in', $item_ids],
            ['unit_count', '=', 1]
        ];
        $barcodes = Db::connect($db_config)->field(['id', 'code_list_new'])->table('ims_bb_sku')->where($sku_where)->select();
        $barcodes_tmp = [];
        foreach ($barcodes as $k => $v){
            $barcodes_tmp[ $v['id'] ] = $v['code_list_new'];
        }

        //更新商品表中的barcode字段
        foreach ($barcodes_tmp as $k => $v){
            $code_arr = explode(',', $v);
            $code = isset($code_arr[0]) ? $code_arr[0] : '';
            $update_data = [
                'goods_code_list'       =>  $v,
                'goods_code'            =>  $code
            ];
            Db::connect($db_config)->table('ims_ewei_shop_goods')->where([['skuid', '=', $k]])->update($update_data);
        }
    }

    //更新item
    public function update_item(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //获取product表中的所有数据
        $db_config_ydhl = 'db_ydhl';

        $db_ydxq_test = $this->ydxq_test;

        //获取所有的product
        $products_ids = Db::connect($db_config_ydhl)->table('bsj_parity_product')->column('id');

        //var_dump(count($products_ids));die;
        //获取商品库所有的skuids

        $hbsj_sku_ids = Db::connect($db_ydxq_test)->table('ims_bb_sku')->column('hbsj_sku_id');

        //取差集，再取交集,去除空值
        $diff_ids = array_filter(array_intersect(array_diff($hbsj_sku_ids, $products_ids), $products_ids));

        //如果存在新的sku,则在商品库创建新的item和sku
        if(count($diff_ids)){
            //var_dump($diff_ids);die;
            //获取product表中的空的数据
            $diff_where = [
                ['id', 'in', $diff_ids]
            ];

            $diff_products = Db::connect($db_config_ydhl)->table('bsj_parity_product')->where($diff_where)->select();
            //var_dump($diff_product);
            $item_ids = array_unique(array_column($diff_products, 'itemId'));

            //获取item表所有的hbsj_item_id
            $hbsj_item_ids = Db::connect($db_ydxq_test)->table('ims_bb_goods_item')->column('hbsj_item_id');

            $diff_item_ids = array_filter(array_intersect(array_diff($hbsj_item_ids, $item_ids), $item_ids));

            $no_in_db_item = [];
            foreach ($diff_products as $k => $v){
                if(in_array($v['itemId'], $diff_item_ids)){
                    $no_in_db_item[] = $v;
                }
            }

            //获取当前所有的品牌
            $brands = Db::connect($db_ydxq_test)->field(['id', 'b_name'])->table('ims_bb_brand')->select();
            $brand_tmp = [];
            foreach ($brands as $k => $v){
                $brand_tmp[ $v['id'] ] = $v['b_name'];
            }

            //插入新的item
            $insert_item_data = [];
            foreach ($no_in_db_item as $k => $v){
                //验证当前品牌是否存在
                $brand_name = $v['bandName'];
                $brand_id = array_search($brand_name, $brand_tmp);
                if(!$brand_id){
                    //创建一个新的品牌
                    //Db::connect($db_ydxq_test)->table('ims_');
                }
            }
        }

    }

    //清洗sku的订单数和下单用户数
    public function wash_sku_orders(){
        $db_config = $this->ydxq_test;
        $goods_sql = "select id,skuid from ims_ewei_shop_goods where sup_id = 461 and status = 1 and deleted = 0 and is_activity = 0";
        $goods = Db::connect($db_config)->query($goods_sql);

        $goods_ids = implode(',', array_column($goods, 'id'));

        //获取当前商品的所有订单
        $order_sql = "select o.openid,goods.goodsid from ims_ewei_shop_order_goods as goods join ims_ewei_shop_order as o on o.id = goods.orderid where o.status in (0,1,2,3) and goods.goodsid in ({$goods_ids})";

        $orders = Db::connect($db_config)->query($order_sql);

        $goods_tmp = [];
        foreach ($orders as $k => $v){
            $goodsid = $v['goodsid'];
            if(isset($goods_tmp[$goodsid])){
                $goods_tmp[$goodsid]['order_num'] += 1;//订单数加一
                if(!in_array($v['openid'],  $goods_tmp[$goodsid]['openids'])){
                    $goods_tmp[$goodsid]['order_user_num'] += 1;//订单用户数加一
                    $goods_tmp[$goodsid]['openids'][] = $v['openid'];
                }
            }else{
                $v['order_num'] = 1;
                $v['order_user_num'] = 1;
                $v['openids'][] = $v['openid'];
                $goods_tmp[$goodsid] = $v;
            }
        }

        foreach ($goods as $k => $v){
            if(isset($goods_tmp[$v['id']])){
                $goods[$k]['order_num'] = $goods_tmp[$v['id']]['order_num'];
                $goods[$k]['order_user_num'] = $goods_tmp[$v['id']]['order_user_num'];
            }else{
                unset($goods[$k]);
            }
        }

        $goods_arr = [];
        foreach ($goods as $k => $v){
            $goods_arr[] = [
                'id'                =>  $v['skuid'],
                'order_num'         =>  $v['order_num'],
                'order_user_num'    =>  $v['order_user_num']
            ];
        }

        $goods_arr = array_chunk($goods_arr, 100);
        foreach ($goods_arr as $k => $v){
            //生成批量更新sql
            $sql = $this->batchUpdate('ims_bb_sku', $v, 'id');

            Db::connect($db_config)->execute($sql);//执行更新语句
        }
    }



    //给订单添加优惠券
    public function add_order_coupon(){
        $ordersn = $this->request_param['ordersn'];

        $money = $this->request_param['money'];
        //获取订单详情
        $db_config = $this->ydxq_test;
        //$db_config = 'db_mini_mall';

        $order_info = Db::connect($db_config)->table('ims_ewei_shop_order')->where([['ordersn', '=', $ordersn]])->find();

        if(!$order_info){
            die('订单号不存在');
        }

        if(!empty($order_info['coupon_id']) && $order_info['goodsprice'] == $order_info['price']){
            echo "已使用过订单";die;
        }

        $price = $order_info['goodsprice'] - $money;

        if($money == 10){
            $coupon_id = 100021;
        }else if($money == 20){
            $coupon_id = 100020;
        }else if($money == 6){
            $coupon_id = 100022;
        }else if($money == 15){
            $coupon_id = 100035;
        }else if($money == 21){
            $coupon_id = 100036;
        }else if($money == 30){
            $coupon_id = 100025;
        }else if($money == 16){
            $coupon_id = 100023;
        }else if($money == 26){
            $coupon_id = 100024;
        }else if($money == 25){
            $coupon_id = 100038;
        }else if($money == 27){
            $coupon_id = 100039;
        }else if($money == 12){
            $coupon_id = 100040;
        }else if($money == 60){
            $coupon_id = 100049;
        }else if ($money == 5){
            $coupon_id = 100050;
        }else if($money == 33){
            $coupon_id = 100051;
        }

        $coupon_data = [
            'coupon_id'     =>  $coupon_id,
            'openid'        =>  $order_info['openid'],
            'order_id'      =>  $order_info['id'],
            'coupon_status' =>  9,
            'status'        =>  1,
            'coupon_code'   =>  date('Ymd')
        ];

        $c_id = Db::connect($db_config)->table('ims_ewei_shop_member_coupon')->insertGetId($coupon_data);

        //更新订单信息
        Db::connect($db_config)->table('ims_ewei_shop_order')->where([['id', '=', $order_info['id']]])->update(['coupon_id'=>$c_id, 'coupon_money'=>$money, 'price'=>$price]);

        //更新member_log
        Db::connect($db_config)->table('ims_ewei_shop_member_log')->where([['logno', '=', $ordersn]])->update(['money'=>$price]);

        $info = Db::connect($db_config)->field(['openid','ordersn','price','goodsprice','status','coupon_id','coupon_money'])->table('ims_ewei_shop_order')->where([['ordersn', '=', $ordersn]])->find();

        dump($info);
    }

    //给用户增加一张优惠券
    public function add_user_coupon(){


        $money = $this->request_param['money'];
        //获取订单详情
        $db_config = $this->ydxq_test;
        //$db_config = 'db_mini_mall';

        $openid = $this->request->param('openid');

        if(!$openid){

            $ordersn = $this->request_param['ordersn'];

            $order_info = Db::connect($db_config)->table('ims_ewei_shop_order')->where([['ordersn', '=', $ordersn]])->find();
            
            if(!$order_info){
                die('订单号不存在');
            }

            $openid = $order_info['openid'];
        }

        if($money == 10){
            $coupon_id = 100021;
        }else if($money == 20){
            $coupon_id = 100020;
        }else if($money == 6){
            $coupon_id = 100022;
        }else if($money == 15){
            $coupon_id = 100035;
        }else if($money == 21){
            $coupon_id = 100036;
        }else if($money == 30){
            $coupon_id = 100025;
        }else if($money == 16){
            $coupon_id = 100023;
        }else if($money == 26){
            $coupon_id = 100024;
        }

        $data = [
            'coupon_id'     =>  $coupon_id,
            'openid_time'   =>  time(),
            'time_start'    =>  time(),
            'time_end'      =>  strtotime($this->request_param['time_end']),
            'create_time'   =>  time(),
            'coupon_status' =>  2,
            'status'        =>  1,
            'openid'        =>  $openid,
        ];

        $id = Db::connect($db_config)->table('ims_ewei_shop_member_coupon')->insert($data);

        dump($id);
    }

    public function get_orders(){
        $sql = 'SELECT
	o.coupon_money,o.price,o.ordersn,o.goodsprice
FROM
	ims_ewei_shop_order_goods AS g
join
	ims_ewei_shop_order AS o ON o.id = g.orderid
WHERE
	o. STATUS != - 1
AND g.goodsid IN (86313,86317) and o.createtime > 1576252800 and o.createtime < 1576339200';

    $orders = Db::connect($this->ydxq_test)->query($sql);
        dump($orders);
    }


    public function lead_code(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $param = $this->request->param();

        if(!isset($param['potential_id']) || empty($potential_id = $param['potential_id'])){
            die('参数potential_id不能为空');
        }

        if(!isset($param['potential_name']) || empty($potential_name = $param['potential_name'])){
            die('参数potential_name不能为空');
        }

        //$db_config = 'db_mini_mall';
        $db_config = $this->ydxq_test;

        //引入excel
        include '../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
        $file = request()->file('file');//获取文件，file是请求的参数名
        $dir = '../runtime/huimin/';
        $info = $file->move($dir);
        //表用函数方法 返回数组
        $excel_path = $info->getSaveName();  //获取上传文件名
        $file_name = $dir . $excel_path;   //上传文件的地址
        $obj_PHPExcel = \PHPExcel_IOFactory::load($file_name);  //加载文件内容

        $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式


        $date = date("Y-m-d");
        $addtime = strtotime($date);

        //dump($excel_array);
        foreach ($excel_array as $k => $v){
            $code = $v[0];
            if(empty($code)){
                continue;
            }

            //查询当前code是否存在当前店铺中
            $d1 = Db::connect($db_config)->table('ims_goods_code_info')->where([['code', '=', $code], ['potential_id', '=', $potential_id]])->find();

            if($d1){
                continue;
            }

            //查询当前code是否存在goods_code中
            $d2 = Db::connect($db_config)->table('ims_goods_code')->where([['code', '=', $code]])->find();

            //如果没有查到数据，查询京东库,插入数据
            if(!$d2){
                $jd_data = Db::connect('db_ydhl')->table('yd_base_goods')->where([['code', '=', $code]])->find();

                if(!$jd_data){
                    continue;
                }

                $code_data = [
                    'code'      =>  $code,
                    'jd_name'   =>  $jd_data['goods_name'],
                    'factory'   =>  $jd_data['factory'],
                    'spec'      =>  $jd_data['spec'],
                    'price'     =>  $jd_data['price'],
                    'brand'     =>  $jd_data['trademark'],
                    'img'     =>  $jd_data['img'],
                    'is_img'     =>  empty($jd_data['img']) ? 0 : 1,
                    'ben_imglist'     =>  $jd_data['ben_img'],
                    'inter_category'     =>  $jd_data['category4'],
                    'mall_category'     =>  $jd_data['mall_category'],
                    'mall_category_id'     =>  $jd_data['mall_category_id'],
                    'addtime'     =>  $addtime,
                ];

                //插入数据
                $rst1 = Db::connect($db_config)->table('ims_goods_code')->insertGetId($code_data);
                if(!$rst1){
                    continue;
                }
            }

            //插入到codeInfo中
            $code_info_data = [
                'code'      =>  $code,
                'type'      =>  0,
                'add_time'   =>  $date,
                'potential_id'  =>  $potential_id,
                'is_valid'  =>  1,
                'potential_name'    =>  $potential_name,
            ];
            Db::connect($db_config)->table('ims_goods_code_info')->insertGetId($code_info_data);
        }
    }

    public function give_user_coupon(){

        $db_config = $this->ydxq_test;
        //$db_config = 'db_mini_mall';

        $param = $this->request->param();

        if(!isset($param['openid'])){
            if(!isset($param['ordersn']) || empty($ordersn = $param['ordersn'])){
                die('ordersn');
            }

            //获取当前订单opneid
            $order_info = Db::connect($db_config)->table('ims_ewei_shop_order')->where([['ordersn', '=', $ordersn]])->find();

            if(!$order_info){
                die('未找到订单信息');
            }

            $openid = $order_info['openid'];
        }else{
            $openid = $param['openid'];
        }

        $end_time = strtotime('+30 day', time());

        $coupon_data = [];
        //4张10元的
        for ($i = 1; $i <= 4; $i++){
            $coupon_data[] = [
                'coupon_id'      =>  100021,//10元优惠券id
                'openid'        =>  $openid,
                'openid_time'   =>  time(),
                'time_start'    =>  time(),
                'time_end'      =>  $end_time,
                'coupon_status' =>  2,
                'status'        =>  1,
                'coupon_code'   =>  date('Ymd')
            ];
        }

        //4 or 3 张15的
        if($param['num'] == 4){
            //4张15
            $coupon_count = 4;
        }else if($param['num'] == 3){
            //3张15
            $coupon_count = 3;
        }else if($param['num'] == 2){
            //2张15
            $coupon_count = 2;
        }else if($param['num'] == 1){
            //1张15
            $coupon_count = 1;
        }

        //100035   15元优惠券id
        for ($i = 1; $i <= $coupon_count; $i++){
            $coupon_data[] = [
                'coupon_id'      =>  100035,//15元优惠券id
                'openid'        =>  $openid,
                'openid_time'   =>  time(),
                'time_start'    =>  time(),
                'time_end'      =>  $end_time,
                'coupon_status' =>  2,
                'status'        =>  1,
                'coupon_code'   =>  date('Ymd')
            ];
        }

        //dump($coupon_data);
        $count = Db::connect($db_config)->table('ims_ewei_shop_member_coupon')->insertAll($coupon_data);

        dump($count);
    }

    public function wash_item_repeat_nums(){

        //获取所有的item
        $db_config = $this->ydxq_test;

        $itemsql = "select item.code,item.item_id,ci.potential_id from ims_bb_item_code as item join ims_goods_code_info as ci on ci.code = item.code where ci.is_valid = 1";

        $itemcodes = Db::connect($db_config)->query($itemsql);

        $itemcodes_tmp = [];
        foreach ($itemcodes as $k => $v){
            $itemcodes_tmp[$v['item_id']][] = $v;
        }

        $update_data = [];
        foreach ($itemcodes_tmp as $k => $v){
            $custom_ids_num = count(array_unique(array_column($v, 'potential_id')));

            $update_data[$k] = [
                'custom_repeat_num'     =>  $custom_ids_num,
                'id'                    =>  $k
            ];
        }

        $update_data = array_chunk($update_data, 500);
        foreach ($update_data as $k => $v){
            //生成批量更新sql
            $sql = $this->batchUpdate('ims_bb_goods_item', $v, 'id');
            Db::connect($db_config)->execute($sql);//执行更新语句
        }
    }

    //清洗下单点位数
    public function wash_shop_goods_customnum(){

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        //获取所有的商品id
        $db_cofig = $this->ydxq_test;

        $shop_goods_sql = "select id from ims_ewei_shop_goods where sup_id = 461 and deleted = 0 and is_activity = 0";

        $shop_goods = Db::connect($db_cofig)->query($shop_goods_sql);
        //dump($shop_goods);
        $goods_ids = array_column($shop_goods, 'id');

        $goods_ids_arr = array_chunk($goods_ids, 300);

        $orders_arr = [];
        foreach ($goods_ids_arr as $k => $v){

            $goods_str = implode(',', $v);

            //获取每个品的订单
            $order_sql = "select o.openid,og.goodsid from ims_ewei_shop_order_goods as og join ims_ewei_shop_order as o on og.orderid = o.id where og.goodsid in ({$goods_str})";

            $orders = Db::connect($db_cofig)->query($order_sql);

            $open_customs = [];
            $openids = array_chunk(array_unique(array_column($orders, 'openid')), 500);
            foreach ($openids as $ok => $ov){
                $openids_str = '';
                foreach ($ov as $ovk => $ovv){
                    $openids_str .= '\'' . $ovv . '\',';
                }
                $openids_str = trim($openids_str, ',');

                $cus_sql = "select id,parent_id,xcx_openid from potential_customer where xcx_openid in ($openids_str) and is_validity = 1";
                //获取这些openid的点位id
                $cups = Db::connect('db_mall_erp')->query($cus_sql);

                $open_customs = array_merge($open_customs, $cups);
            }

            //dump($open_customs);die;
            $open_customs_tmp = [];
            foreach ($open_customs as $ock => $ocv){
                $open_customs_tmp[$ocv['xcx_openid']] = !empty($ocv['parent_id']) ? $ocv['parent_id'] : $ocv['id'];
            }
            foreach ($orders as $odk => $odv){
                if(isset($open_customs_tmp[$odv['openid']])){
                    $orders[$odk]['custom_id'] = $open_customs_tmp[$odv['openid']];
                }else{
                    unset($orders[$odk]);
                }
            }

            //dump($orders);die;
            $orders_arr = array_merge($orders_arr, $orders);
        }

        //dump($orders_arr);
        $orders_goods_arr = [];
        foreach ($orders_arr as $k => $v){
            $orders_goods_arr[$v['goodsid']][] = $v['custom_id'];
        }

        $orders_goods_cus_nums = [];
        foreach ($orders_goods_arr as $k => $v){
            $orders_goods_cus_nums[] = [
                'id'                =>  $k,
                'customer_count'    =>  count(array_unique($v))
            ];
        }
        //dump($orders_goods_cus_nums);

        $orders_goods_cus_nums = array_chunk($orders_goods_cus_nums, 500);
        foreach ($orders_goods_cus_nums as $k => $v){
            $sql = $this->batchUpdate('ims_ewei_shop_goods', $v, 'id');

            Db::connect($db_cofig)->query($sql);
        }
    }

    public function batchUpdate($table, $data, $field){
        if (!is_array($data) || !$field) {
            return false;
        }

        $updates = $this->parseUpdate($data, $field);

        // 获取所有键名为$field列的值，值两边加上单引号，保存在$fields数组中
        $fields = array_column($data, $field);
        $fields = implode(',', array_map(function($value) {
            return "'".$value."'";
        }, $fields));

        $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` IN (%s)", $table, $updates, $field, $fields);

        return $sql;
    }

    public function parseUpdate($data, $field){
        $sql = '';
        $keys = array_keys(current($data));
        foreach ($keys as $column) {
            if($column == $field){
                continue;
            }
            $sql .= sprintf("`%s` = CASE `%s` \n", $column, $field);
            foreach ($data as $line) {
                $sql .= sprintf("WHEN '%s' THEN '%s' \n", $line[$field], $line[$column]);
            }
            $sql .= "END,";
        }
        return rtrim($sql, ',');
    }

    public function arraySort($data = [], $field = '', $sort_type = SORT_DESC){
        $vs = array_column($data, $field);
        array_multisort($vs, $sort_type, $data);
        return array_values($data);
    }

    public function getCodeBrandInfo($codelist){
        $db_config = $this->ydxq_test;
        $codelist = array_chunk($codelist, 1000);

        $data = [];
        foreach ($codelist as $k => $v){
            $codes = array_column($v, 'code');
            $code_brands = Db::connect($db_config)->field(['code', 'brand'])->table('ims_goods_code')->where([['code', 'in', $codes]])->group('code')->select();

            $code_brands_tmp = [];
            foreach ($code_brands as $bk => $bv){
                $code_brands_tmp[$bv['code']] = $bv['brand'];
            }

            foreach ($v as $vk => $vv){
                if(isset($code_brands_tmp[ $vv['code'] ])){
                    $vv['brand_name'] = $code_brands_tmp[ $vv['code'] ];

                    $data[] = $vv;
                }
            }
        }

        return $data;
    }

    public function leadout_codes(){

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $db_config = $this->ydxq_test;
        $btj_bd = $this->db_btj_new;

        //查经销商下的所有brand
        $agents_ids = Db::connect($btj_bd)->table('potential_customer')->where([['identity', '=', 2], ['is_validity', '=', 1]])->column('id');
        $brands = Db::connect($btj_bd)->field(['customer_id', 'brand_id'])->table('btj_brand_bd_log')->where([['customer_id', 'in', $agents_ids], ['type', '=', 1]])->select();
        $brands_ids = array_unique(array_column($brands, 'brand_id'));

        $brads_names = Db::connect($db_config)->table('ims_bb_brand')->where([['id', 'in', $brands_ids], ['status', '=', 1]])->column('b_name', 'id');
        //dump($brads_names);die;
        foreach ($brands as $k => $v){
            if(isset($brads_names[$v['brand_id']])){
                $brands[$k]['brand_name'] = $brads_names[$v['brand_id']];
            }else{
                unset($brands[$k]);
            }
        }

        $agents_brands = [];
        foreach ($brands as $k => $v){
            $agents_brands[$v['customer_id']][] = $v['brand_name'];
        }


        //按照点位重复度排序code,并取前 n 个code
        $repeat_codes_lists_where = [
            ['gci.is_valid', '=', '1']
        ];

        $repeat_codes_lists = $this->getCodeRepeat($repeat_codes_lists_where);
        $repeat_codes = array_column($repeat_codes_lists, 'code');

        //获取点位的所有的code,并按照点位分组
        $custom_codes_where = [
            ['c.brand', '<>', ''],
            ['c.brand', '<>', '无'],
            ['i.is_valid', '=', 1],
            ['i.code', 'in', $repeat_codes]
        ];
        $custom_codes = $this->getCustomCodes($custom_codes_where);

        //获取重复度
        $repeat_codes_lists_tmp = [];
        foreach ($repeat_codes_lists as $k => $v){
            $repeat_codes_lists_tmp[$v['code']] = $v['count'];
        }

        $custom_codes_tmp = [];
        foreach ($custom_codes as $k => $v){
            if(isset($repeat_codes_lists_tmp[$v['code']])){
                $v['custom_num'] = $repeat_codes_lists_tmp[$v['code']];
            }
            $custom_codes_tmp[$v['code']] = $v;
        }

        $item_codes = $this->getItemCodes([['r.code', 'in', $repeat_codes]]);

        //获取商品中的所有sku 的价格
        $shop_goods = $this->getShopGoodsSku();
        $shop_goods_tmp = [];
        foreach ($shop_goods as $k => $v){
            $shop_goods_tmp[$v['skuid']] = $v;
        }

        //价格更新到sku中
        foreach ($item_codes as $k => $v){
            if(isset($shop_goods_tmp[$v['id']])){
                $item_codes[$k]['shop_goods_id'] = $shop_goods_tmp[$v['id']]['id'];//云店商品id
                $item_codes[$k]['price'] = $shop_goods_tmp[$v['id']]['sale_pirce'];//云店销售价格
                $item_codes[$k]['skuid'] = $shop_goods_tmp[$v['id']]['skuid'];//skuid
            }
        }

        //价格和规格插入到$custom_codes_tmp中
        foreach ($item_codes as $k => $v){
            if(isset($custom_codes_tmp[$v['code']])){
                if( !isset($custom_codes_tmp[$v['code']]['itemid']) || isset($v['price'])){
                    $custom_codes_tmp[$v['code']]['itemid'] = $v['goods_id'];
                    $custom_codes_tmp[$v['code']]['goods_name'] = $v['sku_name'];

                    if(isset($v['spec'])){
                        $spec_arr = explode('*', $v['spec']);
                        $custom_codes_tmp[$v['code']]['spec'] = $spec_arr[0];
                    }

                    if(isset($v['price'])){
                        $custom_codes_tmp[$v['code']]['shop_goods_id'] = $v['shop_goods_id'];
                        $custom_codes_tmp[$v['code']]['skuid'] = $v['skuid'];
                        $custom_codes_tmp[$v['code']]['price'] = $v['price'];
                        $custom_codes_tmp[$v['code']]['sale_spec'] = $v['spec'] . '/' .$v['unit_count'] . $v['unit_name'];
                    }else{
                        $custom_codes_tmp[$v['code']]['skuid'] = 0;
                    }

                }
            }
        }


        //dump($return_data);
        foreach ($custom_codes_tmp as $k => $v){
            $custom_codes_tmp[$k]['agent_num'] = 0;
            foreach ($agents_brands as $bk => $bv){
                if(isset($v['brand']) && in_array($v['brand'], $bv)){
                    $custom_codes_tmp[$k]['agent_num'] += 1;
                }
            }
        }

        //dump($custom_codes_tmp);

        //引入excel
        include '../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';

        $objPHPExcel  = new \PHPExcel();

        $objPHPExcel->getProperties()->setCreator("code")
            ->setLastModifiedBy("code")
            ->setTitle("数据EXCEL导出")
            ->setSubject("数据EXCEL导出")
            ->setKeywords("excel")
            ->setCategory("result file");

        $num = 1;

        $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
            ->setCellValue('A'.$num, 'code')
            ->setCellValue('B'.$num, '商品名')
            ->setCellValue('C'.$num, '品牌')
            ->setCellValue('D'.$num, '门店重复度')
            ->setCellValue('E'.$num, '单品规格')
            ->setCellValue('F'.$num, '社区派售价')
            ->setCellValue('G'.$num, '社区派销售规格')
            ->setCellValue('H'.$num, '供应商品牌重复度');

        foreach ($custom_codes_tmp as $k => $v){
            $brand_name = isset($v['brand']) ? $v['brand'] : '';
            $spec = isset($v['spec']) ? $v['spec'] : '';
            $price = isset($v['price']) ? $v['price'] : '';
            $sale_spec = isset($v['sale_spec']) ? $v['sale_spec'] : '';
            $goods_name = isset($v['goods_name']) ? $v['goods_name'] : '';
            $num++;
            $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A'.$num, $v['code'])
                ->setCellValue('B'.$num, $goods_name)
                ->setCellValue('C'.$num, $brand_name)
                ->setCellValue('D'.$num, $v['custom_num'])
                ->setCellValue('E'.$num, $spec)
                ->setCellValue('F'.$num, $price)
                ->setCellValue('G'.$num, $sale_spec)
                ->setCellValue('H'.$num, $v['agent_num']);
        }

        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: applicationnd.ms-excel');
        header('Content-Disposition: attachment;filename=code.xls');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    public function getCodeRepeat($where = []){
        $reapeat_codes = Db::connect($this->ydxq_test)
            ->field(['count(gc.id) as count', 'gc.code'])
            ->table('ims_goods_code_info')
            ->alias('gci')
            ->join('ims_goods_code gc', 'gci.code = gc.code')
            ->where($where)
            ->group('gci.code')
            ->order('count desc')
            ->select();
        return $reapeat_codes;
    }

    //获取每个点位的code
    public function getCustomCodes($where){
        $codes = Db::connect($this->ydxq_test)
            ->field(['i.code','i.potential_id','c.brand'])
            ->table('ims_goods_code_info')
            ->alias('i')
            ->join('ims_goods_code c', 'c.code = i.code')
            ->where($where)
            ->group('i.code,i.potential_id')
            ->select();

        return $codes;
    }
    //根据code获取item
    public function getItemCodes($where){
        $item_codes = Db::connect($this->ydxq_test)
            ->field(['sku.id','sku.sku_name','sku.goods_id','sku.spec','sku.unit_count','sku.unit_name','r.code'])
            ->table('ims_bb_item_code')
            ->alias('r')
            ->join('ims_bb_goods_item item', 'item.id = r.item_id')
            ->join('ims_bb_sku sku', 'sku.goods_id = item.id')
            ->where($where)
            ->select();
        return $item_codes;
    }

    //获取社区派所有的sku
    public function getShopGoodsSku(){
        $where = [
            ['status', '=', 1],
            ['is_activity', '=', 0],
            ['deleted','=',0],
            ['skuid', '<>', '']
        ];
        $shop_goods = Db::connect($this->ydxq_test)
            ->field(['id', 'skuid', 'sale_pirce'])
            ->table('ims_ewei_shop_goods')
            ->where($where)
            ->select();

        return $shop_goods;
    }

    //导入商品词根
    public function lead_in_shopgoods_keyword(){
        //引入excel
        include '../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
        $file = request()->file('file');//获取文件，file是请求的参数名
        $dir = '../runtime/huimin/';
        $info = $file->move($dir);
        //表用函数方法 返回数组
        $excel_path = $info->getSaveName();  //获取上传文件名
        $file_name = $dir . $excel_path;   //上传文件的地址
        $obj_PHPExcel = \PHPExcel_IOFactory::load($file_name);  //加载文件内容

        $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式

        //删除第一行
        array_shift($excel_array);

        $db_config = 'db_mini_mall';

        $data = [];
        foreach ($excel_array as $k => $v){
            $goods_id = intval($v[0]);
            $base_keyword = $v[3];//基础词根
            $taste = $v[2];//口味词根
            $embellish = $v[4];//修饰词根
            $other = $v[5];//其他词根
            $data[] = [
                'goods_id'      =>  $goods_id,
                'base_keyword'  =>  $base_keyword,
                'taste'         =>  empty($taste) ? '' : $taste,
                'embellish'     =>  empty($embellish) ? '' : $embellish,
                'other'         =>  empty($other) ? '' : $other
            ];
        }

        //插入到数据库
        $data = array_chunk($data, 500);
        foreach ($data as $k => $v){
            Db::connect($db_config)->table('ims_ewei_shop_goods_keyword')->insertAll($v);
        }
        //var_dump($data);
    }

    //获取订单
    public function getOrderGoods(){
        $start_time = strtotime('2019-12-02');
        $end_time = strtotime('2019-12-08');

        $db_config = $this->ydxq_test;
        //获取订单下的所有商品
        $g_where = [
            ['og.total', '>', 0],
            ['o.status', '<>', -1],
            ['o.createtime', 'between', [$start_time, $end_time]],
            ['o.supplier_id', '=', 461]
        ];
        $orders_goods = Db::connect($db_config)
                        ->field(['g.id','o.ordersn','o.price','o.address','og.price','og.realprice','og.total','og.is_activity','g.title','o.createtime','cate.c_name','o.id as oid'])
                        ->table('ims_ewei_shop_order_goods')
                        ->alias('og')
                        ->join('ims_ewei_shop_goods g', 'g.id = og.goodsid')
                        ->join('ims_ewei_shop_order o', 'o.id = og.orderid')
                        ->leftJoin('ims_bb_cate_bb cate', 'cate.id = g.bb_cate2')
                        ->where($g_where)
                        ->select();
        foreach ($orders_goods as $k => $v){
            $orders_goods[$k]['address'] = unserialize($v['address']);
        }

        //dump($orders_goods);die;
        //引入excel
        include '../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';

        $objPHPExcel  = new \PHPExcel();

        $objPHPExcel->getProperties()->setCreator("code")
            ->setLastModifiedBy("code")
            ->setTitle("数据EXCEL导出")
            ->setSubject("数据EXCEL导出")
            ->setKeywords("excel")
            ->setCategory("result file");

        $num = 1;

        $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
            ->setCellValue('A'.$num, '商品ID')
            ->setCellValue('B'.$num, '商品名')
            ->setCellValue('C'.$num, '购买单价')
            ->setCellValue('D'.$num, '购买数量')
            ->setCellValue('E'.$num, '金额')
            ->setCellValue('F'.$num, '订单号')
            ->setCellValue('G'.$num, '姓名')
            ->setCellValue('H'.$num, '手机号')
            ->setCellValue('I'.$num, '收货地址')
            ->setCellValue('J'.$num, '下单时间')
            ->setCellValue('K'.$num, '分类');

        foreach ($orders_goods as $k => $v){
            $c_name = $v['c_name'];
            $num++;
            $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A'.$num, $v['id'])
                ->setCellValue('B'.$num, $v['title'])
                ->setCellValue('C'.$num, $v['realprice'])
                ->setCellValue('D'.$num, $v['total'])
                ->setCellValue('E'.$num, $v['price'])
                ->setCellValue('F'.$num, $v['ordersn'])
                ->setCellValue('G'.$num, $v['address']['realname'])
                ->setCellValue('H'.$num, $v['address']['mobile'])

                ->setCellValue('I'.$num, $v['address']['address'])
                ->setCellValue('J'.$num, date('Y-m-d H:i:s', $v['createtime']))
                ->setCellValue('K'.$num, $c_name);
        }

        //兑换品
        $order_ids = array_column($orders_goods, 'oid');
        $w = [
            ['eo.shop_order_id', 'in', $order_ids]
        ];
        $ex_goods = Db::connect($db_config)
            ->field(['eg.shop_goods_id as id','eo.shop_order_id','eg.title','eg.gold','eo.total','o.ordersn','eo.address','eo.mobile','eo.username as realname','eo.createtime'])
            ->table('ims_ewei_shop_exchange_order')
            ->alias('eo')
            ->join('ims_ewei_shop_exchange_goods eg', 'eo.goodsid = eg.id')
            ->join('ims_ewei_shop_order o', 'eo.shop_order_id = o.id')
            ->where($w)
            ->select();

        foreach ($ex_goods as $k => $v){
            $c_name = '兑换品订单';
            $num++;
            $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推
                ->setCellValue('A'.$num, $v['id'])
                ->setCellValue('B'.$num, $v['title'])
                ->setCellValue('C'.$num, '')
                ->setCellValue('D'.$num, $v['total'])
                ->setCellValue('E'.$num, $v['gold'].'金币')
                ->setCellValue('F'.$num, $v['ordersn'])
                ->setCellValue('G'.$num, $v['realname'])
                ->setCellValue('H'.$num, $v['mobile'])
                ->setCellValue('I'.$num, $v['address'])
                ->setCellValue('J'.$num, date('Y-m-d H:i:s', $v['createtime']))
                ->setCellValue('K'.$num, $c_name);
        }

        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: applicationnd.ms-excel');
        header('Content-Disposition: attachment;filename=20191202到20191208订单商品.xls');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    //清洗货比三家报价信息
    public function wash_sku_price_list(){
        //$db_config = $this->ydxq_test;
        $db_config = 'db_mini_mall';
        $where = [
            ['g.deleted', '=', 0],
            ['g.sup_id', '=', 461],
            ['g.is_activity', '=', 0]
        ];
        $shop_goods = Db::connect($db_config)
                        ->field(['sku.id','jp.price_per_unit','hm.curPrice'])
                        ->table('ims_ewei_shop_goods')
                        ->alias('g')
                        ->join('ims_bb_sku sku', 'sku.id = g.skuid')
                        ->leftJoin('ims_jingpiwang_product jp', 'jp.id = sku.jingpiwang_goods_id')
                        ->leftJoin('ims_huiminwang_product hm', 'hm.id = sku.huiminwang_goods_id')
                        ->where($where)
                        ->select();
        //dump($shop_goods);
        //$skuids = array_column($shop_goods, 'id');
        //Db::connect($db_config)->table('ims_bb_price_list')->where([['sku_id', 'in', $skuids],['channel_id','=',7]])->column();
        $jp_data = [];
        $hm_data = [];
        foreach ($shop_goods as $k => $v){
            if(!empty($v['price_per_unit'])){
                $jp_data[] = [
                    'sku_id'        =>  $v['id'],
                    'channel_id'    =>  7,
                    'price'         =>  $v['price_per_unit'],
                    'date'          =>  strtotime(date('Ymd')),
                    'price_date'    =>  date('Ymd'),
                    'createtime'    =>  time()
                ];
                Db::connect($db_config)->table('ims_bb_price_list')->where([['sku_id', '=', $v['id']], ['channel_id', '=', 7]])->delete();
            }

            if(!empty($v['curPrice'])){
                $hm_data[] = [
                    'sku_id'        =>  $v['id'],
                    'channel_id'    =>  4,
                    'price'         =>  $v['curPrice'],
                    'date'          =>  strtotime(date('Ymd')),
                    'price_date'    =>  date('Ymd'),
                    'createtime'    =>  time()
                ];
                Db::connect($db_config)->table('ims_bb_price_list')->where([['sku_id', '=', $v['id']], ['channel_id', '=', 4]])->delete();
            }
        }
        //echo "<pre>";
        //var_dump($jp_data, $hm_data);

        $data = array_merge($jp_data, $hm_data);
        //dump($data);
        Db::connect($db_config)->table('ims_bb_price_list')->insertAll($data);
    }
}