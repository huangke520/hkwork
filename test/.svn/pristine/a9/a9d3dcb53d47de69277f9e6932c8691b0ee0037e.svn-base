<?php

namespace app\api\controller;

use app\api\model\CommonBaseModel as CommonBase;
use app\api\model\CommonBtjNewModel;
use think\Loader;
use think\Db;
use app\api\model\ydxq\Supplier;

class Home extends BaseController
{
    protected $m_ProjectModel;

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * $ huangke 首页智能推荐
     */
    public function getList()
    {
        $param = input('get.');
        $openids = $param['openid'];
        $supplier_id = !empty($param['supplier_id']) ? $param['supplier_id'] : 0;
        if (empty($openids)) {
            return json_encode(['status' => 1, 'msg' => 'openid不能为空']);
        }
        $is_sns = strstr($openids,'sns_wa_');
        if(!$is_sns){
            $openids =   'sns_wa_'.$openids;
        }
        $page = $param['page'] > 0 ? $param['page'] - 1 : 0;
        $star = $page * 10;
        $end = $star + 10;

        if(empty($supplier_id)){
            //根据openID查询是否有店铺
            $supplier_model = new Supplier();
            $supplier_where = [
                ['openid','=',$openids],
            ];
            $suppler_res = $supplier_model->getInfo($supplier_where);
            if(!empty($suppler_res['id'])){
                $supplier_id = 461;
            }
        }

        $unionid = Db::connect('db_mini_mall')->table('ims_ewei_shop_member')->where('openid',$openids)->value('unionid');
        if (empty($unionid)) {
            return json_encode(['status' => 1, 'msg' => '此用户不存在']);
        }

        $sql = "select * from ims_introduce_log where unionid = '".$unionid."' and is_introduce = 1 and goods_id > 0 and status = 1 limit $star,$end";

        $list = Db::connect('db_mini_mall')->query($sql);
        if($list){
            $goodsids = array_column($list,'goods_id');
            $goodsids = implode(',',$goodsids);

            $sql = "select g.id,g.title,g.thumb,g.marketprice as h_price,sg.supplier_price as marketprice,g.total,g.skuid,g.salesreal,g.bb_start_count,g.bb_end_count from ims_yd_supplier_goods sg left join ims_ewei_shop_goods g on sg.goods_id=g.id where sg.supplier_id=$supplier_id and g.status=1 and g.deleted=0 and sg.status=1 and g.total > 0 and g.id IN ($goodsids) and g.skuid <> '' group by g.id ORDER BY g.sales DESC";

//            echo $sql;exit;

            $data = Db::connect('db_mini_mall')->query($sql);
            foreach ($data as $k => $v){
                $arr = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_cart')->where('openid',$openids)->where('deleted',0)->where('goodsid',$v['id'])->find();
                if($arr){
                    $data[$k]['cart_total'] = $arr['total'];
                    $data[$k]['cart_id'] = $arr['id'];
                }else{
                    $data[$k]['cart_total'] = 0;
                    $data[$k]['cart_id'] = 0;
                }
                $data[$k]['thumb'] = imgSrc($v['thumb']);
                //查询规格
                $data[$k]['bb_spec'] = '';
                $one_goods_id = Db::connect('db_mini_mall')->table('ims_bb_sku')->where([['id','=',$v['skuid']]])->field('goods_id')->find();
                if(!empty($one_goods_id)){
                    $spec = Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$one_goods_id['goods_id']]])->field('content')->find();
                    if(!empty($spec)){
                        $data[$k]['bb_spec'] = $spec['content'];
                    }
                }
                //处理销量
                $data[$k]['salesreal'] = ($v['salesreal'] * 10) + rand(0,10);
                //查询商品报价价格
                $hbsj_data = Db::connect('db_mini_mall')->table('ims_bb_price_list')->alias('a')->leftJoin('ims_bb_channel b','a.channel_id = b.id')->field('a.price,b.c_name')->where([['a.sku_id','=',$v['skuid']]])->select();
                $goods_channel_arr = array();
                if(!empty(count($hbsj_data))){
                    foreach ($hbsj_data as $c_k => $c_v){
                        $channel_arr = array();
                        $channel_arr['c_name'] = $c_v['c_name'];
                        $channel_arr['price'] = $c_v['price'];
                        $goods_channel_arr[] = $channel_arr;
                    }
                }
                $data[$k]['goods_channel_arr'] = $goods_channel_arr;
                //查询报价数量
                $hbsj_data = Db::connect('db_mini_mall')->table('ims_bb_city_sku')->where([['sku_id','=',$v['skuid']]])->find();
                $data[$k]['goods_channel_count'] = !empty($hbsj_data['channel_count']) ? $hbsj_data['channel_count'] : 0;
            }
        }else{
            return json_encode(['status' => 1, 'msg' => '暂无数据']);
        }

        if ($data) {
            return json_encode(['status' => 0, 'msg' => '获取成功', 'data' => $data]);
        } else {
            return json_encode(['status' => 1, 'msg' => '获取失败']);
        }

        return view();
    }

    //扫码进入小程序绑定小程序与点位(作废)
    public function qrcodeBind(){
        $param = input('get.');
        $openid = 'sns_wa_'.$param['user_openid'];
        $customer_id = $param['customer_id'];
        if(empty($openid)){
            sdk_return('',1,'openid不能为空');
        }
        if(empty($customer_id)){
            sdk_return('',1,'customer_id不能为空');
        }
        //判断当前点位是否有小程序openid
        $info = Db::connect('db_mall_erp')
            ->table('potential_customer')
            ->field('xcx_openid,service_id')
            ->where('id',$customer_id)
            ->find();
        if($info['service_id'] > 0){
            Db::connect('db_mini_mall')
                ->table('ims_ewei_shop_member')
                ->where('openid',$openid)
                ->update(['admin_user_id'=>$info['service_id']]);
        }
        if(!empty($info['xcx_openid'])){
            if($openid != $info['xcx_openid']){
                //判断是否已加入
                $r = Db::connect('db_mall_erp')
                    ->table('potential_customer')
                    ->where('xcx_openid',$openid)
                    ->count();
                //获取用户信息
                $data = Db::connect('db_mini_mall')
                    ->table('ims_ewei_shop_member')
                    ->where('comefrom','sns_wa')
                    ->where('openid',$openid)
                    ->find();
                if($r == 0){
                    $res = Db::connect('db_mall_erp')
                        ->table('potential_customer')
                        ->insert(['xcx_openid'=>$openid,
                            'wx_name'=>$data['nickname'] ? $data['nickname'] : '暂无',
                            'header_url'=>$data['avatar'] ? $data['avatar'] : '暂无',
                            'service_id'=>$info['service_id'] > 0 ? $info['service_id'] : 0,
                            'parent_id'=>$customer_id]);
                }else{
                    $res = Db::connect('db_mall_erp')
                        ->table('potential_customer')
                        ->where('xcx_openid',$openid)
                        ->update(['wx_name'=>$data['nickname'] ? $data['nickname'] : '暂无',
                            'header_url'=>$data['avatar'] ? $data['avatar'] : '暂无',
                            'service_id'=>$info['service_id'] > 0 ? $info['service_id'] : 0,
                            'parent_id'=>$customer_id]);
                }
            }
        }else{
            //清空此openid
            @Db::connect('db_mall_erp')
                ->table('potential_customer')
                ->where('xcx_openid',$openid)
                ->update(['xcx_openid'=>null]);
            //赋予openid
            Db::connect('db_mall_erp')
                ->table('potential_customer')
                ->where('id',$customer_id)
                ->update(['xcx_openid'=>$openid]);
        }
        sdk_return('',0);
    }

    //扫码添加成员接口处理
    public function qrcodeBinds(){
        $param = input('get.');
        $openid = 'sns_wa_'.$param['user_openid'];
        $code_id = $param['code_id'];
        if(empty($openid)){
            sdk_return('',1,'openid不能为空');
        }
        if(empty($code_id)){
            sdk_return('',1,'code_id不能为空');
        }
        Db::connect('db_mall_erp')
            ->table('btj_bd_code_list')
            ->where('qr_code',$code_id)
            ->setInc('scan_count');
        //获取此二维码绑定点位id
        $customer = Db::connect('db_mall_erp')
            ->table('potential_customer')
            ->where('bd_code',$code_id)
            ->where('parent_id',0)
            ->where('is_validity',1)
            ->find();
        if(!$customer){
            sdk_return('',1,'此二维码未激活');
        }
        //获取此openid有无点位信息
        $info = Db::connect('db_mall_erp')
            ->table('potential_customer')
            ->where('xcx_openid',$openid)
            ->where('is_validity',1)
            ->find();
        if($info){//有此openid点位
            if($info['id'] != $customer['id']){//此openid所在点位与二维码绑定点位不同
                if($info['service_id'] != $customer['service_id']){//两个点位负责人不相同
                    //写入审核表
                    Db::connect('db_mall_erp')
                        ->table('btj_customer_examine')
                        ->insert(['user_id'=>$customer['service_id'],
                            'openid'=>$openid,
                            'one_id'=>$info['id'],
                            'two_id'=>$customer['id'],
                            'type'=>0,
                            'createtime'=>time()]);
                }
            }
        }else{//无此openid点位
            //获取用户信息
            $data = Db::connect('db_mini_mall')
                ->table('ims_ewei_shop_member')
                ->where('openid',$openid)
                ->find();
            if($customer['xcx_openid']){//二维码绑定点位已有openid创建子集
                Db::connect('db_mall_erp')
                    ->table('potential_customer')
                    ->insert(['xcx_openid'=>$openid,
                        'wx_name'=>$data['nickname'] ? $data['nickname'] : '暂无',
                        'header_url'=>$data['avatar'] ? $data['avatar'] : '暂无',
                        'service_id'=>$customer['service_id'] > 0 ? $customer['service_id'] : 0,
                        'parent_id'=>$customer['id']]);
            }else{//二维码绑定点位无openid
                Db::connect('db_mall_erp')
                    ->table('potential_customer')
                    ->where('id',$customer['id'])
                    ->update(['wx_name'=>$data['nickname'] ? $data['nickname'] : '暂无',
                        'header_url'=>$data['avatar'] ? $data['avatar'] : '暂无',
                        'xcx_openid'=>$openid]);
            }
            //该成员写入点位成员表;
            $res = Db::connect('db_mall_erp')
                ->table('potential_customer_user')
                ->where('openid',$openid)
                ->count();
            if($res == 0){
                Db::connect('db_mall_erp')
                    ->table('potential_customer_user')
                    ->insert(['wx_name'=>$data['nickname'] ? $data['nickname'] : '暂无',
                        'header_url'=>$data['avatar'] ? $data['avatar'] : '暂无',
                        'openid'=>$openid,
                        'customer_id'=>$customer['id'],
                        'createtime'=>time()]);
            }
        }

        sdk_return('',0);
    }

    /**
     * 首页banner信息
     */
    public function getBannerData(){
        $return_data = [
            [
                'img_src' => 'http://oss.yundian168.com/ydxq/img/system/xcx/activity/banner1.png',//图片地址
                'url' => 1,//小程序页面标识
                'height' => '',//高度
                'width' => '',//宽度
            ],
        ];
        sdk_return($return_data,1,'获取成功');
    }

    /**
     * 商品降价列表
     */
    public function getJiangJia(){
        $param = $this->request->param();
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');//店铺ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//用户openID
        $page = !empty($param['page']) ? $param['page'] : 1;//页数

        //最多给5页数据，取100条
        if($page == 6){
            sdk_return([],1,'获取成功');
        }

        $page_size = 10;
        //查询最近100条降价商品
//        SELECT DISTINCT a.skuid FROM `ims_system_log` as a LEFT JOIN ims_bb_sku as b on a.skuid = b.id LEFT JOIN ims_bb_goods_item as c on b.goods_id = c.id WHERE a.front_price > a.after_price ORDER BY custom_repeat_num desc;
        $goods_id = Db::connect('db_mini_mall')->table('ims_system_log')->alias('a')->leftJoin('ims_bb_sku b','a.skuid = b.id')->leftJoin('ims_bb_goods_item c','b.goods_id = c.id')->where('a.front_price > a.after_price')->field('DISTINCT a.goods_id')->order('c.custom_repeat_num','desc')->paginate($page_size)->toArray();
        if(!empty($goods_id['data'])){
            $goods_id_arr_one = $goods_id['data'];
            foreach ($goods_id_arr_one as $one){
                $goods_id_arr[] = $one['goods_id'];
            }
        }else{
            $goods_id_arr = [];
        }
        //查询商品数据
//        $sql = "select g.id,g.title,g.thumb,g.sale_pirce as marketprice,g.marketprice as h_price,g.salesreal,g.total,g.bb_start_count,g.bb_end_count,g.skuid,g.bb_step,g.sale_pirce,g.sale_type,g.erp_total,g.first_order_price,g.level2_price,g.level3_price,g.level4_price,g.level5_price,g.is_level_goods from ".tablename($this->table_supplier)." s left join ".tablename($this->table_supplier_goods)." sg on s.id=sg.supplier_id left join ".tablename($this->table_goods)." g on sg.goods_id=g.id where {$sql_where} and s.status=1 and g.status=1 and g.deleted=0 and sg.status=1 and g.isrecommand = 1 {$whereOr} {$area_where} group by g.id order by g.sales desc,g.createtime desc,g.id desc limit {$start},{$pagesize}";
        $goods_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->where('deleted = 0 and status = 1 and sup_id = '.$sup_id.' and is_activity = 0')->where([['id','in',$goods_id_arr]])->field('id,title,thumb,sale_pirce as marketprice,marketprice as h_price,salesreal,total,bb_start_count,bb_end_count,skuid,bb_step,sale_pirce,sale_type,erp_total,first_order_price,level2_price,level3_price,level4_price,level5_price,is_level_goods')->select();

        //获取会员等级
        $user_level = getMemberLevel($user_openid,$sup_id);
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
        $return_data = [];
        if(!empty($goods_data)){
            foreach ($goods_data as $one){
                //查询购物车
                $arr = null;
                $arr = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_cart')->where([['goodsid','=',$one['id']],['openid','=',$user_openid],['deleted','=',0]])->find();
                if(!empty($arr)){
                    $one['cart_total'] = $arr['total'];
                    $one['cart_id'] = $arr['id'];
                }else{
                    $one['cart_total'] = 0;
                    $one['cart_id'] = 0;
                }

                //判断会员价
                $one['level_price'] = '';
                $one['level_msg1'] = '';
                $one['level_msg2'] = '';
                if(!empty($one['is_level_goods'])){
                    $one['level_price'] = $level_price;
                    if(!empty($is_have_order)){
                        if (!empty($level_price)){
                            $one['supplier_price'] = $one['level'.$user_level.'_price'];
                            if(($user_level != 5) && (!empty($next_level_price)) && (!empty($next_level))){
                                $one['level_msg1'] = '升级可享'.$next_level_price.':￥'.$one['level'.$next_level.'_price'];
                                $one['level_msg2'] = '最高可享钻卡价:￥'.$one['level5_price'];
                            }
                        }else{
                            $one['level_msg1'] = '升级可享普卡价:￥'.$one['level2_price'];
                            $one['level_msg2'] = '最高可享钻卡价:￥'.$one['level5_price'];
                        }
                    }else{
                        $one['level_price'] = '首单价';
                        $one['supplier_price'] = $one['first_order_price'];
                    }
                }
                /*********************************************************/
                //查询规格
                $one['bb_spec'] = '';
                $one_goods_id = Db::connect('db_mini_mall')->table('ims_bb_sku')->where([['id','=',$one['skuid']]])->field('spec')->find();
                if(!empty($one_goods_id)){
                    $one['bb_spec'] = $one_goods_id['spec'];
                }

                //处理销量
//                $info[$k]['salesreal'] = $this->fix_sale_count($info[$k]['id'],$info[$k]['salesreal']);
                $one['salesreal'] = $this->fix_sale_count($one['id'],$one['salesreal']);

                //查询商品报价价格
                $hbsj_data = Db::connect('db_mini_mall')->table('ims_bb_price_list')->alias('a')->leftJoin('ims_bb_channel b','a.channel_id = b.id')->field('a.price,b.c_name')->where([['a.sku_id','=',$one['skuid']],['b.is_b2b','=',1]])->order('a.price','desc')->limit(4)->select();
                $goods_channel_arr = array('0' => array('c_name'=>'','price'=>''), '1' => array('c_name'=>'','price'=>''), '2' => array('c_name'=>'','price'=>''), '3' => array('c_name'=>'','price'=>''), '4' => array('c_name'=>'','price'=>''));
                if(!empty(count($hbsj_data))){
                    foreach ($hbsj_data as $c_k => $c_v){
                        $channel_arr = array();
                        $channel_arr['c_name'] = $c_v['c_name'].'￥';
                        $channel_arr['price'] = $c_v['price'];
                        $goods_channel_arr[$c_k] = $channel_arr;
                    }
                }
                $one['goods_channel_arr'] = $goods_channel_arr;
                $one['goods_channel_res1'] = $goods_channel_arr[0]['c_name'].$goods_channel_arr[0]['price'].' '.$goods_channel_arr[1]['c_name'].$goods_channel_arr[1]['price'];
                $one['goods_channel_res2'] = $goods_channel_arr[2]['c_name'].$goods_channel_arr[2]['price'].' '.$goods_channel_arr[3]['c_name'].$goods_channel_arr[3]['price'];
                unset($goods_channel_arr);

                //查询报价数量
                $hbsj_data_2 = Db::connect('db_mini_mall')->table('ims_bb_price_list')->alias('a')->leftJoin('ims_bb_channel b','a.channel_id = b.id')->field('a.price,b.c_name')->where([['a.sku_id','=',$one['skuid']],['b.is_b2b','=',1]])->order('a.price','desc')->count();
                $one['goods_channel_count'] = !empty($hbsj_data_2) ? $hbsj_data_2 : 0;
                if($one['id'] == 68293){
                    $one['goods_channel_count'] = 0;
                }

                //查询当前商品是否为抢购商品
                $one['is_time'] = 0;//不是抢购商品
                $is_time = Db::connect('db_mini_mall')->table('ims_goods_flash_sale')->where([['status','=',1],['endtime','>=',time()],['goods_id','=',$one['id']]])->find();
                if(!empty($is_time)){
                    $one['is_time'] = 1;//是抢购商品
                }

                $one['thumb'] = !empty($one['thumb']) ? $one['thumb'] : 'https://mallm.yundian168.com/attachment/images/xiaochengxu/zw.png';
                $one['thumb'] = imgSrc($one['thumb']);

                if($one['sale_type'] == 1){
                    $one['total'] = $one['erp_total'] < $one['total'] ? $one['erp_total'] : $one['total'];
                }
                //如果步长大于库存 , 重置为 0
                if($one['bb_step'] > $one['total']){
                    $one['total'] = 0;
                }
                $return_data[] = $one;
            }
        }
        sdk_return($return_data,1,'获取成功');
    }

    private function fix_sale_count($id,$real_sale_count){
        $res_sale_count = $real_sale_count;
        if ($id <> 68293) {
            $res_sale_count = round(($real_sale_count * 10 + date('d') * 100) / 4)+date('H');
        }
        return $res_sale_count;
    }
}