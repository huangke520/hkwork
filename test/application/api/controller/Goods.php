<?php

namespace app\api\controller;

use app\api\model\ydxq\ShopGoods as ShopGoodsModel;
use app\api\model\ydxq\BbSku as BbSkuModel;
use app\api\model\ydxq\BbGoodsItem as BbGoodsItemModel;
use app\api\model\ydxq\BbBrand as BbBrandModel;
use app\api\model\ydxq\BbCateBb as BbCateBbdModel;
use app\api\model\ydxq\BbPriceList as BbPriceListModel;
use app\api\model\ydxq\BbChannel as BbChannelModel;
use app\api\model\ydxq\ShopMemberCart as ShopMemberCartModel;
use app\api\model\ydxq\GoodsFlashSale as GoodsFlashSaleModel;
use app\api\model\ydxq\YdStock as YdStockModel;
use app\api\model\ydxq\Jingpiwang as JingpiwangModel;
use app\api\model\ydxq\Huiminwang as HuiminwangModel;
use app\api\model\btjnew\PriceList as BtjPriceListmodel;//报价渠道

use think\Db;

class Goods extends BaseController
{
    private $shop_goods_model;
    private $bb_sku_model;
    private $bb_goods_item_model;
    private $bb_brand_model;
    private $bb_cate_bb_model;
    private $bb_price_list_model;
    private $bb_channel_model;
    private $bb_member_cart_model;
    private $goods_flash_sale_model;
    private $btj_price_list_model;
    private $stock_model;
    private $jingpiwang_model;
    private $huiminwang_model;

    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET");

        parent::__construct();
        $this->shop_goods_model = new ShopGoodsModel();
        $this->bb_sku_model = new BbSkuModel();
        $this->bb_goods_item_model = new BbGoodsItemModel();
        $this->bb_brand_model = new BbBrandModel();
        $this->bb_cate_bb_model = new BbCateBbdModel();
        $this->bb_price_list_model = new BbPriceListModel();
        $this->bb_channel_model = new BbChannelModel();
        $this->bb_member_cart_model = new ShopMemberCartModel();
        $this->goods_flash_sale_model = new GoodsFlashSaleModel();
        $this->btj_price_list_model = new BtjPriceListmodel();
        $this->stock_model = new YdStockModel();
        $this->jingpiwang_model = new JingpiwangModel();
        $this->huiminwang_model = new HuiminwangModel();
    }

    /**
     * 查询是否抽过奖了
     * @param int $goods_id
     * @param string $open_id
     * @param int $sup_id
     * @return bool
     * @throws \think\Exception
     */
    private function isRaffled($goods_id = 0,$open_id = '',$sup_id = 0){
        $is_raffle = Db::connect('db_mini_mall')->table('ims_ewei_shop_activity')->where('sup_id = '.$sup_id.' and openid = "'.$open_id.'" and goods_id = '.$goods_id)->find();
        if(!empty($is_raffle)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 查询用户在当天当前店铺是否下过单了
     * @param string $openid
     * @param int $sup_id
     * @return bool
     * @throws \think\Exception
     */
    private function isDayOrder($openid = '',$sup_id = 0){
        //查询当前用户今天是否已经下过单了
        $start_time = strtotime(date('Y-m-d',time()));
        $user_order = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('createtime > '.$start_time.' and openid = "'.$openid.'" and supplier_id = '.$sup_id.' and (status = 0 or status = 2)')->count();
        if(!empty($user_order)){
            return true;
        }else{
            return false;
        }
    }

    //商品详情接口
    public function detail(){
        $param = $this->request_param;

        $sup_id = !empty(intval($param['sup_id'])) ? intval($param['sup_id']) : 461;//店铺ID。默认461

        if(!isset($param['goodsid']) || !isset($param['openid'])){
            sdk_return('', 0, '参数缺失');
        }
        $goods_id = intval($param['goodsid']);
        $openid = $param['openid'];
        $openid = is_sns($openid);

        //获取商品基本参数
        $goods_info = $this->shop_goods_model->getInfoPro(['id'=>$goods_id], ['title', 'thumb', 'salesreal', 'sale_pirce', 'skuid', 'brand_id', 'bb_cate1', 'bb_cate2', 'content', 'bb_start_count', 'bb_end_count', 'bb_step', 'is_activity', 'marketprice', 'total','goods_area', 'status', 'deleted','goods_order_on_off','old_activity_num','new_activity_num','activity_num','erp_total','sale_type','first_order_price','level2_price','level3_price','level4_price','level5_price','is_level_goods']);
//        a.first_order_price,a.level2_price,a.level3_price,a.level4_price,a.level5_price,a.is_level_goods
        if(empty($goods_info)){
            sdk_return('', 0 , '获取商品详情错误');
        }

        //库存处理（是否启用erp库存）
        if($goods_info['sale_type'] == 1){
            $goods_info['total'] = $goods_info['erp_total'] < $goods_info['total'] ? $goods_info['erp_total'] : $goods_info['total'];
        }

        //如果步长不为领 并且 步长大于库存 , 重置为 0
        if($goods_info['bb_step'] > $goods_info['total']){
            $goods_info['total'] = 0;
        }

        //判断当前商品针对当前人是否可以展示分享
        $is_raffled = 0;//默认还没有抽过奖
        $goods_operation = 3;//默认可以去抽奖
        $is_share = 1;
        $activity_num = 0;
        if($goods_info['is_activity'] != 0){
            $manager_where = [
                ['sup_id','=',$sup_id],
                ['openid','=',$openid],
                ['status','=',1],
            ];
            $is_special_user = Db::connect('db_mini_mall')->table('ims_yd_supplier_manager')->where($manager_where)->find();
            if(empty($is_special_user)){
                //表示没有权限分享
                $is_share = 0;
            }
//            goods_order_on_off
            if($goods_info['goods_order_on_off'] == 1){
                //查询当前用户今天是否已经下过单了
                $is_order_day = $this->isDayOrder($openid,$sup_id);
                if($is_order_day){
                    $goods_info['total'] = 0;
                }
            }
            if($this->isRaffled($goods_id,$openid,$sup_id)){
                $is_raffled = 1;//抽过奖了
            }else{
                $is_raffled = 0;//还没有抽过奖
            }

            //判断商品可以操作状态
//            $activity_num = $goods_info['new_activity_num'] + $goods_info['old_activity_num'];
            $activity_num = $goods_info['activity_num'];
            if($is_raffled == 1){
                $goods_operation = 2;//已抽过
            }elseif(($goods_info['total'] <= 0) || ($activity_num <= 0)){
//                $goods_operation = 1;//已抢光
                $goods_operation = 3;//去抽奖
            }else{
                $goods_operation = 3;//去抽奖
            }
        }

        //查询当前购买c端店铺的点位地址
        $user_area_id = Db::connect('db_btj_new')->table('potential_customer')->field('area_id,address')->where('is_validity = 1 and xcx_openid = "'.$openid.'"')->find();
//        echo Db::connect('db_btj_new')->getLastSql();exit;
        if(empty($user_area_id['area_id'])){
            //没有area_id,调用高德接口获取
            if(!empty($user_area_id['address'])){
                $url = "https://restapi.amap.com/v3/geocode/geo?output=JSON&key=ea30dd0bc2c1f965f535433fd54d292d&address=".preg_replace('# #','',$user_area_id['address']);
// 执行请求
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_URL, $url);
                $data = curl_exec($ch);
                curl_close($ch);
                $result = json_decode($data, true);
                $location = $result['geocodes'][0]['location'];
                $loca = explode(',',$location);
//                var_dump($location.$result['geocodes'][0]['province'].",".$result['geocodes'][0]['city'].",".$result['geocodes'][0]['district'].":".$i++);
                if($location) {
                    //省码
                    $province_arr = Db::connect('db_wehub')->table('regionh')->field('id')->where('parent_id = 0 and name = "'.$result['geocodes'][0]['province'].'"')->find();
                    $province = $province_arr['id'] > 0 ? $province_arr['id'] : '0';
                    //市码
                    $city_arr = Db::connect('db_wehub')->table('regionh')->field('id')->where('parent_id = '.$province.' and name = "'.mb_substr($result['geocodes'][0]['city'],0,mb_strlen($result['geocodes'][0]['city'])-1).'"')->find();
                    $city = $city_arr['id'] > 0 ? $city_arr['id'] : '0';
                    //区码
                    $area_arr = Db::connect('db_wehub')->table('regionh')->field('id')->where('parent_id = '.$city.' and name = "'.$result['geocodes'][0]['district'].'"')->find();
                    $area = $area_arr['id'] > 0 ? $area_arr['id'] : '0';
                    $area_id = $area;
                }
            }
        }else{
            $area_id = $user_area_id['area_id'];
        }
        $goods_area = 1;//0不可以购买，1可以购买
        if(!empty($area_id)){
            if(!empty($goods_info['goods_area'])){
                if(strpos($goods_info['goods_area'].'',$area_id.'') === false){
                    $goods_area = 0;
                }
            }
        }else{
            if(!empty($goods_info['goods_area'])){
                $goods_area = 0;
            }
        }

        //商品详情处理
        $content = unserialize($goods_info['content']);
        $goods_deltail = [];
        if(is_array($content)){
            foreach ($content as $k => $v){
                $goods_deltail[]['url'] = $v;
            }
        }

        //获取当前商品在购物车中的数量
        $cart_info = $this->bb_member_cart_model->getInfo(['goodsid'=>$goods_id, 'openid'=>$openid, 'deleted'=>0]);
        $cart['cart_total'] = 0;
        if($cart_info){
            $cart = [
                'cart_id'       =>  $cart_info['id'],
                'cart_total'    =>  $cart_info['total'],
            ];
        }

        //获取是否已经限时抢购
        $flash_sale_where = [
            ['endtime', '>', time()],
            ['status', '=', 1],
            ['goods_id', '=', $goods_id]
        ];
        $flash_sale_info = $this->goods_flash_sale_model->getInfo($flash_sale_where);
        if($flash_sale_info){
            $return_flash_sale = [
                'flash_sale_endtime'       =>  $flash_sale_info['endtime'] - time(),
                'is_flash_sale' =>  1,
            ];
        }else{
            $return_flash_sale = [
                'is_flash_sale' =>  0,
            ];
        }

        //查询用户在当前店铺是否有订单
        $is_have_order = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('supplier_id = '.$sup_id.' and (status = 0 or status = 1 or status = 2 or status = 3) and openid = "'.$openid.'"')->count();

        //判断会员价
        $level_msg1 = '';
        $level_msg2 = '';
        $level_price = '';
        if(!empty($goods_info['is_level_goods'])){
            //获取会员等级
            $user_level = getMemberLevel($openid,$sup_id);
            if($user_level > 1){
                $level_arr = array(2=>'普卡价',3=>'银卡价',4=>'金卡价',5=>'钻卡价');
                $level_price = $level_arr[$user_level];
                if($user_level != 5){
                    $next_level = $user_level + 1;
                    $next_level_price = $level_arr[$next_level];
                }
            }else{
                $level_price = '';
            }

            if(!empty($is_have_order)){
                if (!empty($level_price)){
                    $goods_info['sale_pirce'] = $goods_info['level'.$user_level.'_price'];
                    if(($user_level != 5) && (!empty($next_level_price)) && (!empty($next_level))){
                        $level_msg1 = '升级可享'.$next_level_price.':￥'.$goods_info['level'.$next_level.'_price'];
                        $level_msg2 = '最高可享钻卡价:￥'.$goods_info['level5_price'];
                    }
                }else{
                    $goods_info['level_msg1'] = '升级可享普卡价:￥'.$goods_info['level2_price'];
                    $goods_info['level_msg2'] = '最高可享钻卡价:￥'.$goods_info['level5_price'];
                }
            }else{
                $level_price = '首单价';
                $goods_info['sale_pirce'] = $goods_info['first_order_price'];
            }
        }

        //获取商品sku, 如果不存在sku，只返回基本参数
        $sku_info = $this->bb_sku_model->getInfoPro(['id'=>$goods_info['skuid']], ['unit_name','unit_count','goods_id', 'spec']);

        //查询当前用户对当前商品是否设置了降价通知ims_ewei_jiang_jia_inform
        $inform_data = Db::connect('db_mini_mall')->table('ims_ewei_jiang_jia_inform')->where('user_openid = "'.$openid.'" and goods_id = '.$goods_id)->find();
        $inform = 0;//0:不通知，1：通知
        if((!empty($inform_data)) && ($inform_data['status'] == 1)){
            $inform = 1;
        }
        if(empty($sku_info)){
            $return_data = [
                'title'         =>  $goods_info['title'],//标题
                'sale_pirce'    =>  $goods_info['sale_pirce'],//销售价格
                'marketprice'   =>  $goods_info['marketprice'],//划线价
                'cart_price'    =>  number_format($cart_info['total'] * $goods_info['sale_pirce'], 2),//购物车价格
                'suggest_price' =>  '暂无数据',//建议零售价
                'thumb'         =>  imgSrc($goods_info['thumb']),//图片缩略图
                'max_buy_number'=>  $goods_info['bb_end_count'] == 0 ? '不限购' : $goods_info['bb_end_count'],//每单限购数量, 0不限购
                'bb_start_count'=>  empty($goods_info['bb_start_count']) ? 1 : $goods_info['bb_start_count'],//起订数量
                'bb_end_count'  =>  $goods_info['bb_end_count'],//商品限购数，0不限制
                'goods_deltail' =>  $goods_deltail,
                'bb_step'       =>  $goods_info['bb_step'],
                'is_share'      =>  $is_share,
                'total'         =>  $goods_info['total'],
                'is_activity'   =>  $goods_info['is_activity'],
                'goods_area'    =>  $goods_area,
                'status'        =>  $goods_info['status'],
                'deleted'       =>  $goods_info['deleted'],
                'activity_num'  =>  $activity_num,//抽奖名额
                'is_raffled'    =>  $is_raffled,
                'goods_operation'    =>  $goods_operation,
                'level_price'   =>  $level_price,//会员价
                'level_msg1'    =>  $level_msg1,//会员提示
                'level_msg2'    =>  $level_msg2,//会员提示
                'inform'        =>  $inform,//0：没有降价通知，1：有降价通知
            ];
            $return_data = array_merge($return_data, $cart, $return_flash_sale);

            sdk_return($return_data, 1, '获取商品详情成功');
        }

        //获取商品item
        $item_info = $this->bb_goods_item_model->getInfoPro(['id'=>$sku_info['goods_id']], ['content', 'out_date']);

        //获取品牌
        $brand_info = $this->bb_brand_model->getInfo(['id'=>$goods_info['brand_id']]);

        //获取分类信息
        $cate_where = [
            ['id', 'in', [$goods_info['bb_cate1'], $goods_info['bb_cate2']]]
        ];
        $cates = $this->bb_cate_bb_model->getAllListPro($cate_where, ['id', 'c_name']);
        $cate_tmp = [];
        foreach ($cates as $k => $v){
            $cate_tmp[ $v['id'] ] = $v['c_name'];
        }

        $salesreal = empty($goods_info['salesreal']) ? 10 : $goods_info['salesreal'];
        $goods_id_arr = str_split($goods_id);//数字转换为数组，销售数量处理
        $spec_arr = explode('/', $sku_info['spec']);//规格处理

        //当前单位
        $sale_unit = $sku_info['unit_count'] == 1 ? $sku_info['unit_name'] : $spec_arr[ count($spec_arr) - 1 ];

        $return_data = [
                'title'         =>  $goods_info['title'],//标题
                'sale_pirce'    =>  $goods_info['sale_pirce'],//销售价格
                'marketprice'   =>  $goods_info['marketprice'],//划线价
                'cart_price'    =>  number_format($cart_info['total'] * $goods_info['sale_pirce'], 2),//购物车价格
                'suggest_price' =>  '暂无数据',//建议零售价
                'spec'          =>  $sku_info['spec'],//规格
                'thumb'         =>  imgSrc($goods_info['thumb']),//图片缩略图
                'salesreal'     =>  $salesreal * 10 + $goods_id_arr[ count($goods_id_arr) - 1 ],
                'avg_price'     =>  number_format($goods_info['sale_pirce'] / $sku_info['unit_count'], 2),//单价
                //'unit'          =>  $spec_arr[ count($spec_arr) - 1 ],//最小单位
                'unit'          =>  $sku_info['unit_name'],//最小单位
                'sale_unit'     =>  $sale_unit,//销售单位
                'max_buy_number'=>  $goods_info['bb_end_count'] == 0 ? '不限购' : $goods_info['bb_end_count'],//每单限购数量, 0不限购
                'brand_id'      =>  $goods_info['brand_id'],
                'brand_name'    =>  $brand_info['b_name'],//品牌名称
                'cate1'         =>  isset($cate_tmp[ $goods_info['bb_cate1'] ]) ? $cate_tmp[ $goods_info['bb_cate1'] ] : '暂无分类',//商品一级分类
                'cate2'         =>  isset($cate_tmp[ $goods_info['bb_cate2'] ]) ? $cate_tmp[ $goods_info['bb_cate2'] ] : '暂无分类',//商品二级分类
                'one_cate'      =>  $goods_info['bb_cate1'],//一级分类id
                'category_id'   =>  $goods_info['bb_cate2'],//二级分类id
                'out_date'      =>  $item_info['out_date'],//保质期
                'bb_start_count'=>  empty($goods_info['bb_start_count']) ? 1 : $goods_info['bb_start_count'],//起订数量
                'bb_end_count'  =>  $goods_info['bb_end_count'],//商品限购数，0不限制
                'bb_step'       =>  $goods_info['bb_step'],//步时
                'goods_deltail' =>  $goods_deltail,
                'is_share'      =>  $is_share,
                'total'         =>  $goods_info['total'],
                'is_activity'   =>  $goods_info['is_activity'],
                'goods_area'    =>  $goods_area,
                'status'        =>  $goods_info['status'],
                'deleted'       =>  $goods_info['deleted'],
                'activity_num'  =>  $activity_num,//抽奖名额
                'is_raffled'    =>  $is_raffled,
                'goods_operation'    =>  $goods_operation,
                'level_price'   =>  $level_price,//会员价
                'level_msg1'    =>  $level_msg1,//会员提示
                'level_msg2'    =>  $level_msg2,//会员提示
                'inform'        =>  $inform,//0：没有降价通知，1：有降价通知
                /*'goods_deltail' =>  [
                    ['url'=>'https://oss.yundian168.com/ydxq/img/system/goods/lqchs/20190824/3c64ab685a9216a6d83418fa31eba035.jpeg'],
                    ['url'=>'https://oss.yundian168.com/ydxq/img/system/goods/lqchs/20190824/3c64ab685a9216a6d83418fa31eba035.jpeg'],
                ],*/
        ];
        $return_data = array_merge($return_data, $cart, $return_flash_sale);

        //获取商品报价
        $price_list = $this->bb_price_list_model->getAllListPro(['sku_id'=>$goods_info['skuid']], ['id', 'price', 'channel_id', 'date', 'price_avg']);

        $channel_id = array_column($price_list, 'channel_id');
        if(!empty($channel_id)){
            $channel_where = [
                ['id', 'in', $channel_id],
                ['is_b2b', '=', 1]
            ];
            $channels = $this->bb_channel_model->getAllListPro($channel_where, ['id', 'c_name']);
            $channels_tmp = [];
            foreach ($channels as $k => $v){
                $channels_tmp[ $v['id'] ] = $v['c_name'];
            }
            foreach ($price_list as $k => $v){
                if(isset($channels_tmp[ $v['channel_id'] ])){
                    $price_list[ $k ]['channel_name'] = $channels_tmp[ $v['channel_id'] ];
                }else{
                    unset($price_list[ $k ]);
                }
            }
        }

        $price_list = [
            'lists'         =>  $price_list,
            'count'         =>  count($price_list)
        ];
        //报价信息
        $return_data['price_list'] = $price_list;

        sdk_return($return_data, 1, '获取商品详情成功');
    }

    /**
     * 插入抽奖记录
     * @param int $sup_id
     * @param string $user_openid
     * @param int $goods_id
     * @param int $status
     * @param int $luck_num
     * @param int $user_type
     * @throws \think\Exception
     */
    public function insertRaffleNote($sup_id = 0,$user_openid = '',$goods_id = 0,$status = 0,$luck_num = 0,$user_type = 1){
        //插入抽奖记录
        $insert_data = null;
        $insert_data['sup_id'] = $sup_id;
        $insert_data['order_id'] = 0;
        $insert_data['openid'] = $user_openid;
        $insert_data['goods_id'] = $goods_id;
        $insert_data['status'] = $status;
        $insert_data['create_time'] = time();
        $insert_data['luck_num'] = $luck_num;
        $insert_data['user_type'] = $user_type;
        Db::connect('db_mini_mall')->table('ims_ewei_shop_activity')->insert($insert_data);
    }

    /**
     * 计算抽奖概率接口
     * @param int $probability
     * @return bool
     */
    private function raffleProbability($probability = 0){
        $luck_num_arr = [];
        if(empty($probability)){
            $luck_num_arr['raffle'] = 0;//1：中奖，0：未中奖
            $luck_num_arr['luck_num'] = 0;
        }else{
            $luck_num = mt_rand(1,100);
            $luck_num_arr['luck_num'] = $luck_num;
            if($luck_num <= $probability){
                $luck_num_arr['raffle'] = 1;
            }else{
                $luck_num_arr['raffle'] = 0;
            }
        }
        return $luck_num_arr;
    }

    /**
     * 抽奖接口
     */
    public function raffleGoods(){
        $param = $this->request->param();
        $goods_id = !empty($param['goods_id']) ? $param['goods_id'] : sdk_return('',6,'参数缺失');//商品ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//用户openid
        $user_openid = is_sns($user_openid);
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');//店铺ID

        //获取商品基本参数
        $goods_info = $this->shop_goods_model->getInfoPro(['id'=>$goods_id], ['title', 'thumb', 'salesreal', 'sale_pirce', 'skuid', 'brand_id', 'bb_cate1', 'bb_cate2', 'content', 'bb_start_count', 'bb_end_count', 'bb_step', 'is_activity', 'marketprice', 'total','goods_area', 'status', 'deleted','goods_order_on_off','old_activity_num','new_activity_num','activity_num','erp_total','sale_type']);
        //判断商品是否还有库存
        if(($goods_info['total'] <= 0) || $goods_info['activity_num'] <= 0){
//            sdk_return('',6,'库存不足');
            $this->insertRaffleNote($sup_id,$user_openid,$goods_id,0,0,2);//插入抽奖记录
            unset($data);
            $data['order_id'] = 0;
            $data['raffle'] = 0;
            $data['cart_id'] = 0;
            $data['raffle_type'] = 0;//未中奖，啥也不是
            sdk_return($data,1,'未中奖');
        }
        $raffle = 0;//0：未中奖
        $user_type = 0;
        if($goods_info['is_activity'] > 0){
            //查询购物车列表是否已经有特价品
            $is_cart_activity_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_cart')->where('deleted = 0 and openid = "'.$user_openid.'"')->field('goodsid')->select();
            if(!empty(count($is_cart_activity_goods))){
                $cart_goods_id = array();
                foreach ($is_cart_activity_goods as $one_cart){
                    $cart_goods_id[] = $one_cart['goodsid'];
                }
                $cart_activity = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->where([['id','IN',$cart_goods_id],['is_activity','>',0]])->count();
                if(!empty($cart_activity)){
                    //购物车中有一个特价品
                    sdk_return('',6,'您已添加特价活动品，每单限1种，不支持叠加购买，请结算订单或从购物车移除后再添加');
                }
            }
            //查询当前openid是否已经抽过奖了
            if($this->isRaffled($goods_id,$user_openid,$sup_id)){
                sdk_return('',6,'您已抽过奖了');
            };
            $start_time = strtotime(date('Y-m-d',time()));
            //查询用户当天下过的订单
//            $is_activity_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->alias('a')->leftJoin('ims_ewei_shop_order_goods b','a.id = b.orderid')->where('a.supplier_id = '.$sup_id.' and a.openid = "'.$user_openid.'" and (a.status = 0 or a.status = 2) and a.createtime > '.$start_time)->field('a.id as order_id,b.is_activity')->order(['b.is_activity'=>'desc','a.createtime'=>'desc'])->select();
            $is_activity_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('supplier_id = '.$sup_id.' and openid = "'.$user_openid.'" and (status = 0 or status = 2) and createtime > '.$start_time)->field('id as order_id')->order(['createtime'=>'desc'])->select();
            if(!empty(count($is_activity_goods))){
                $user_type = 1;
                foreach ($is_activity_goods as $one_order){
                    //查询当前订单是否有特价品：如果有特价品则加入到购物车中
                    $is_have_activity_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->where('is_activity > 0 and orderid = '.$one_order['order_id'])->count();
                    if(!empty($is_have_activity_goods)){
                        //当天订单中有一个活动品
//                    sdk_return('',6,'您已添加特价活动品，每单限1种，不支持叠加购买，请结算订单或从购物车移除后再添加');
                    }else{
                        //当天订单中，有个订单没有活动品，需要直接插入
                        //判断是否还有名额
                        $luck_num_arr = $this->raffleProbability($goods_info['old_activity_num']);
//                        if(!empty($goods_info['old_activity_num'])){
                        if(!empty($luck_num_arr['raffle'])){
                            $this->insertRaffleNote($sup_id,$user_openid,$goods_id,$luck_num_arr['raffle'],$luck_num_arr['luck_num'],$user_type);//插入抽奖记录
                            //当前用户在当天有下单且没有下过活动品的订单
                            $order_goods = array();
                            $order_goods['uniacid'] = 4;
                            $order_goods['orderid'] = $one_order['order_id'];
                            $order_goods['goodsid'] = $goods_id;
                            $order_goods['price'] = $goods_info['sale_pirce'];
                            $order_goods['total'] = 1;
                            $order_goods['createtime'] = time();
                            $order_goods['realprice'] = $goods_info['sale_pirce'];
                            $order_goods['oldprice'] = $goods_info['sale_pirce'];
                            $order_goods['is_activity'] = $goods_info['is_activity'];
                            $order_goods['openid'] = $user_openid;
                            Db::connect('db_mini_mall')->table('ims_ewei_shop_order_goods')->insert($order_goods);
                            //修改订单总价格
                            Db::connect('db_mini_mall')->execute("UPDATE ims_ewei_shop_order set price = price + {$goods_info['sale_pirce']},goodsprice = goodsprice + {$goods_info['sale_pirce']} where id = {$one_order['order_id']}");

                            Db::connect('db_mini_mall')->execute("UPDATE ims_ewei_shop_goods set total = total - 1,erp_total = erp_total - 1,activity_num = activity_num - 1 where id = {$goods_id}");
                            $raffle = 1;
                            unset($data);
                            $data['order_id'] = $one_order['order_id'];
                            $data['raffle'] = $raffle;
                            $data['cart_id'] = 0;
                            $data['raffle_type'] = 1;//放入订单中
                            sdk_return($data,'1','该特价品已放到今天的订单中，同时安排配送');
                        }else{
                            $this->insertRaffleNote($sup_id,$user_openid,$goods_id,$luck_num_arr['raffle'],$luck_num_arr['luck_num'],$user_type);//插入抽奖记录
                            unset($data);
                            $data['order_id'] = 0;
                            $data['raffle'] = $raffle;
                            $data['cart_id'] = 0;
                            $data['raffle_type'] = 0;//未中奖，啥也不是
                            sdk_return($data,1,'未中奖');
                        }
                    }
                }
                //判断是否还有名额
//                if(empty($goods_info['old_activity_num'])) {
                $luck_num_arr = $this->raffleProbability($goods_info['old_activity_num']);
//                        if(!empty($goods_info['old_activity_num'])){
                if(empty($luck_num_arr['raffle'])){
                    $this->insertRaffleNote($sup_id,$user_openid,$goods_id,$luck_num_arr['raffle'],$luck_num_arr['luck_num'],$user_type);//插入抽奖记录
                    unset($data);
                    $data['order_id'] = 0;
                    $data['raffle'] = $raffle;
                    $data['cart_id'] = 0;
                    $data['raffle_type'] = 0;//未中奖，啥也不是
                    sdk_return($data,1,'未中奖');
                }

                //修改名额
//                Db::connect('db_mini_mall')->execute("UPDATE ims_ewei_shop_goods set old_activity_num = old_activity_num - 1 where id = {$goods_id}");
            }else{
//                if(empty($goods_info['new_activity_num'])){
                $luck_num_arr = $this->raffleProbability($goods_info['new_activity_num']);
//                        if(!empty($goods_info['new_activity_num'])){
                if(empty($luck_num_arr['raffle'])){
                    $this->insertRaffleNote($sup_id,$user_openid,$goods_id,$luck_num_arr['raffle'],$luck_num_arr['luck_num'],$user_type);//插入抽奖记录
                    unset($data);
                    $data['order_id'] = 0;
                    $data['raffle'] = $raffle;
                    $data['cart_id'] = 0;
                    $data['raffle_type'] = 0;//未中奖，啥也不是
                    sdk_return($data,1,'未中奖');
                }
                //修改名额
//                Db::connect('db_mini_mall')->execute("UPDATE ims_ewei_shop_goods set new_activity_num = new_activity_num - 1 where id = {$goods_id}");
            }
        }
        //修改名额
        Db::connect('db_mini_mall')->execute("UPDATE ims_ewei_shop_goods set activity_num = activity_num - 1 where id = {$goods_id}");
//        $deadline_time = time() + (60 * 120);
        $deadline_time = time() + (60 * 30);
//        $deadline_time = time() + (60 * 1);
        //这个是抽奖接口，直接加入购物车中
//        $data = array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid'], 'goodsid' => $id, 'marketprice' => $goods['marketprice'], 'total' => $total, 'deadline_time' => $deadline_time, 'selected' => 1, 'createtime' => time());
        $cart_data['uniacid'] = 4;
        $cart_data['openid'] = $user_openid;
        $cart_data['goodsid'] = $goods_id;
        $cart_data['marketprice'] = $goods_info['sale_pirce'];
        $cart_data['total'] = 1;
        $cart_data['deadline_time'] = $deadline_time;
        $cart_data['selected'] = 1;
        $cart_data['createtime'] = time();
        $cart_id = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_cart')->insertGetId($cart_data);
        unset($data);
        $raffle = 1;
        $data['raffle'] = $raffle;
        $data['order_id'] = 0;
        $data['cart_id'] = $cart_id;
        $data['raffle_type'] = 2;//放入购物车中
//        $this->insertRaffleNote($sup_id,$user_openid,$goods_id);//插入抽奖记录
        $this->insertRaffleNote($sup_id,$user_openid,$goods_id,$luck_num_arr['raffle'],$luck_num_arr['luck_num'],$user_type);//插入抽奖记录
        sdk_return($data,1,'已添加到购物车');
    }

    /**
     * 删除购物车商品
     */
    public function cancelCart(){
        $param = $this->request->param();
        $cart_id = !empty($param['cart_id']) ? $param['cart_id'] : sdk_return('',6,'参数缺失');
        Db::connect('db_mini_mall')->table('ims_ewei_shop_member_cart')->where('id = '.$cart_id)->update(['deleted'=>0]);
        sdk_return('',1,'操作成功');
    }

    //推荐商品列表
    public function recommends(){
        $param = $this->request_param;
        if(!isset($param['goodsid'])){
            sdk_return('', 0, '参数缺失');
        }

        $goods_id = intval($param['goodsid']);

        //获取商品基本参数
        $goods_info = $this->shop_goods_model->getInfoPro(['id'=>$goods_id], ['brand_id', 'sup_id', 'bb_cate2', 'skuid']);
        if(empty($goods_info) || empty($goods_info['skuid'])){
            sdk_return([], 1, '获取成功');
        }

        $goods = [];
        if(!empty($goods['brand_id'])){
            //获取skuid的报价数量
            $where = [
                ['g.brand_id', '=', $goods_info['brand_id']],
                ['g.sup_id', '=', $goods_info['sup_id']],
                ['g.id', '<>', $goods_id],
                ['g.status', '=', 1],
                ['g.is_activity', '=', 0]
            ];
            $goods = $this->shop_goods_model->getSkuChannelGoods($where);
        }

        if(count($goods) < 5){
            //获取当前二级分类下的商品
            if(!empty($goods_info['bb_cate2'])){
                $cate_where = [
                    ['bb_cate2', '=', $goods_info['bb_cate2']],
                    ['sup_id', '=', $goods_info['sup_id']],
                    ['id', '<>', $goods_id],
                    ['status', '=', 1],
                    ['is_activity', '=', 0]
                ];

                $cate_goods = $this->shop_goods_model->getCateGoods($cate_where, 5 - count($goods));

                $goods = array_merge($goods, $cate_goods);
            }
        }

        foreach ($goods as $k => $v){
            $goods[ $k ]['thumb'] = imgSrc($v['thumb']);

            $goods[ $k ]['channel_count'] = isset($v['channel_count']) ? $v['channel_count'] : 0;
        }
        sdk_return(['goods_lists'=>$goods], 1, '获取成功');
        //var_dump($goods);
    }

    //获取商品的报价信息
    public function getQuotePrice(){
        $param = $this->request->param();
        if(!isset($param['goods_id']) || empty($goods_id = intval($param['goods_id']))){
            return sdk_return('', 0, '缺少参数goods_id');
        }
        //获取商品详情
        $goods_where = [
            ['id', '=', $goods_id],
            ['sup_id', '=', '461'],
            ['deleted', '=', 0],
            ['is_activity', '=', 0]
        ];
        $goods_info = $this->shop_goods_model->getInfoPro($goods_where, ['id','title','sale_pirce','skuid','is_factory_sale','is_zero_profit']);
        if(empty($goods_info)){
            return sdk_return('', 0, '获取商品详情错误');
        }
        //获取sku
        $sku_info = $this->bb_sku_model->getInfo([['id', '=', $goods_info['skuid']]]);
        if(empty($sku_info)){
            return sdk_return('', 0, '获取sku错误');
        }
        //获取item
        $item_info = $this->bb_goods_item_model->getInfo([['id', '=', $sku_info['goods_id']]]);

        //获取当前品牌的所有在售商品
        $brands_goods = $this->shop_goods_model->getBrandsGoods($item_info['brand_id']);
        foreach ($brands_goods as $k => $v){
            $brands_goods[$k]['code'] = explode(',', $v['code_list_str']);
        }
        $codes = array_column($brands_goods, 'code');
        //dump($codes);die;
        $code_tmps = [];
        foreach ($codes as $k => $v){
            $code_tmps = array_merge($code_tmps, $v);
        }
        //获取每个code的所属品牌,所在点位
        $customs = $this->getCustomByCodes($code_tmps);
        //获取当前商品在code_info中的品牌
        //$shop_goods_brand_name = $this->shop_goods_model->getCodeInfoBrandByShopGoodsCodes(explode(',', $item_info['code_list_str']));
        //code按照点位id分组
        $customs_tmp = [];
        foreach ($customs as $k => $v){
            $customs_tmp[$v['potential_id']][] = $v['code'];
        }
        foreach ($brands_goods as $k => $v){
            $brands_goods[$k]['repeat'] = 1;
            foreach ($customs_tmp as $cstk => $cstv){
                $count = array_intersect($cstv, $v['code']);
                if(count($count)){
                    $brands_goods[$k]['repeat'] += 1;
                }
            }
        }
        $brands_goods = $this->arraySort($brands_goods, 'repeat', SORT_DESC);
        $rank_num = 1;
        foreach ($brands_goods as $k => $v){
            if($v['id'] == $goods_id){
                break;
            }
            $rank_num++;//排名
        }

        //获取供应商报价列表
        $price_lists = $this->btj_price_list_model->getAllList([['sku_id', '=', $goods_info['skuid']]]);
        $prices = array_column($price_lists, 'price');

        //最新有效报价
        $quote_time = 0;
        $now_quote = '暂无';
        foreach ($price_lists as $k => $v){
            if($quote_time <= $v['createtime']){
                $now_quote = round($v['price'], 2);
                $quote_time = $v['createtime'];
            }
        }

        //获取当前商品的最新采购价
        $stock_info = $this->stock_model->getInfoPro([['pin_hao', '=', $goods_id]], ['dan_jia'], ['date'=>'desc']);
        $purchas_price = empty($stock_info) ? '' : $stock_info['dan_jia'];
        //当前毛利
        $profit = $goods_info['sale_pirce'] - $purchas_price;

        //获取中商惠民价格
        $huimin_goods_info = $this->huiminwang_model->getInfoPro([['id', '=', $sku_info['huiminwang_goods_id']]], ['curPrice as price']);
        //获取京批网价格
        $jingpi_goods_info = $this->jingpiwang_model->getInfoPro([['id', '=', $sku_info['jingpiwang_goods_id']]], ['per_piece as price']);

        //建议售价  零毛利品 零毛利
        if($goods_info['is_zero_profit'] == 1){
            $advise_price = $purchas_price;
        }else{//非零毛利品、3%毛利
            //如果存在采购价
            if(!empty($purchas_price) && is_numeric($purchas_price)){
                $advise_price = round(($purchas_price + $purchas_price * 3 / 100), 2); //3%毛利
            }else{
                $advise_price = $purchas_price;
            }
        }

        //当前售价毛利率
        $sale_price_rate = '无';
        if(!empty($purchas_price) && is_numeric($purchas_price)){
            $sale_price_rate = round(($goods_info['sale_pirce'] - $purchas_price) / $goods_info['sale_pirce'] * 100, 2);
        }

        //建议售价毛利率
        $advise_price_rate = '无';
        if(!empty($purchas_price) && is_numeric($purchas_price) && !empty($advise_price) && is_numeric($advise_price)){
            $advise_price_rate = round(($advise_price - $purchas_price) / $advise_price * 100, 2);
        }

        $return_data = [
            'goods_id'          =>  $goods_id,
            'title'             =>  $goods_info['title'],
            'sale_price'        =>  $goods_info['sale_pirce'],//当前售价
            'is_factory_sale'   =>  $goods_info['is_factory_sale'],//1厂价直供
            'is_zero_profit'    =>  $goods_info['is_zero_profit'],//是否零毛利
            'custom_repeat_num' =>  $item_info['custom_repeat_num'],//code点位重复度
            'agent_quote_num'   =>  count(array_unique(array_column($price_lists, 'customer_id'))),//供应商报价数
            'min_quote'         =>  empty($prices) ? '' : round(min($prices), 2),//最低报价
            'max_quote'         =>  empty($prices) ? '' : round(max($prices), 2),//最高报价
            'now_quote'         =>  $now_quote,//最新报价
            'purchas_price'     =>  $purchas_price,//最新采购价
            'profit'            =>  round($profit, 2),//当前毛利
            'huimin_price'      =>  empty($huimin_goods_info) ? '无' : $huimin_goods_info['price'],
            'jingpi_price'      =>  empty($jingpi_goods_info) ? '无' : $jingpi_goods_info['price'],
            'advise_price'      =>  $advise_price,//系统建议售价
            'rank_num'          =>  $rank_num,//品牌内排名
            'sale_price_rate'   =>  $sale_price_rate,//当前售价毛利率
            'advise_price_rate' =>  $advise_price_rate,//建议售价毛利率
        ];
        //dump($return_data);
        return sdk_return($return_data, 1, '获取成功');
    }

    public function getCustomByCodes($codes){
        $codes = array_unique($codes);
        $custom_codes = $this->shop_goods_model->getBrandCustomRank($codes);
        return $custom_codes;
    }

    //二位数组排序
    public function arraySort($data, $field = '', $sort_type = SORT_DESC){
        if(!count($data) || count($data) == count($data, 1)  || empty($field)){
            return [];
        }
        foreach ($data as $k => $v){
            if(false == array_key_exists($field, $v)){
                unset($data[$k]);//数组元素过滤,避免报错
            }
        }
        $vs = array_column($data, $field);
        if(!count($vs)){
            return [];
        }

        array_multisort($vs, $sort_type, $data);
        return array_values($data);
    }

    /**
     * 添加商品降价通知
     */
    public function jiangJiaInform(){
        $param = $this->request->param();
        $goods_id = !empty($param['goods_id']) ? $param['goods_id'] : sdk_return('',6,'参数缺失');//商品ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//用户openID
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');//店铺ID
        $status = !empty($param['status']) ? $param['status'] : 0;//通知状态：0：不通知，1：通知
        $user_openid = is_sns($user_openid);
        //查询当前用户对当前商品是否设置了降价通知
        $inform = Db::connect('db_mini_mall')->table('ims_ewei_jiang_jia_inform')->where('goods_id = '.$goods_id.' and user_openid = "'.$user_openid.'"'.' and sup_id = '.$sup_id)->find();
        $now_time = time();
        if(!empty($inform)){
            //更新
            $update = null;
            $update['status'] = $status;
            $update['update_time'] = $now_time;
            Db::connect('db_mini_mall')->table('ims_ewei_jiang_jia_inform')->where('id = '.$inform['id'])->update($update);
        }else{
            //插入
            $insert = null;
            $insert['user_openid'] = $user_openid;
            $insert['goods_id'] = $goods_id;
            $insert['sup_id'] = $sup_id;
            $insert['status'] = $status;
            $insert['create_time'] = $now_time;
            $insert['update_time'] = $now_time;
            Db::connect('db_mini_mall')->table('ims_ewei_jiang_jia_inform')->insert($insert);
        }
        sdk_return('',1,'操作成功');
    }
}