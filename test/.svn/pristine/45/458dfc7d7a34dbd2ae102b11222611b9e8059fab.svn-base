<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\controller;

use app\api\model\ydxq\BbBaseCoupon as BbBaseCouponModel;
use app\api\model\ydxq\ShopMemberCoupon as ShopMemberCouponModel;
use app\api\model\ydxq\OrderAppendCoupon as OrderAppendCouponModel;
use app\api\model\ydxq\ShopOrder as ShopOrderModel;
use app\api\model\ydxq\MemberLog as MemberLogModel;
use app\api\model\btjnew\AdminUser as AdminUserModel;
use think\Db;

class BbBaseCoupon extends BaseController {

    private $bb_base_coupon_model;
    private $shop_member_coupon_model;
    private $order_append_coupon_model;
    private $shop_order_model;
    private $btj_admin_user_model;
    private $member_log_model;
    private $apply_append_coupon_price = [6, 10, 16, 20, 26];

    public function __construct() {

        //h5跨域问题
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET");

        parent::__construct();
        $this->bb_base_coupon_model = new BbBaseCouponModel();
        $this->shop_member_coupon_model = new ShopMemberCouponModel();
        $this->order_append_coupon_model = new OrderAppendCouponModel();
        $this->btj_admin_user_model = new AdminUserModel();
        $this->shop_order_model = new ShopOrderModel();
        $this->member_log_model = new MemberLogModel();
    }

    //用户领取的优惠券列表
    public function receive_lists(){
        $param = $this->request_param;

        if(!isset($param['openid']) || empty($openid = $param['openid'])){
            return sdk_return('', 0, '缺少参数openid');
        }

        //修改优惠券状态
        $this->updateUserCouponStatus($openid);

        $time = time();

        //获取当前用户的优惠券列表
//        $user_coupons = $this->shop_member_coupon_model->getAllListPro([['openid', '=', $openid], ['coupon_status', 'in', [1,2,3,7]]], ['id', 'coupon_id', 'time_end', 'coupon_status'], ['id'=>'desc']);
        $user_coupons = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_coupon')->alias('a')->leftJoin('ims_bb_base_coupon b','a.coupon_id = b.id')->field(['a.id', 'a.coupon_id', 'a.time_end', 'a.coupon_status'])->order(['a.coupon_status'=>'asc','a.time_end'=>'asc','b.money_value'=>'desc','b.limit_money'=>'asc'])->where([['a.openid', '=', $openid], ['a.coupon_status', 'in', [1,2,3,7]]])->select();;

        $coupon_ids = array_column($user_coupons, 'coupon_id');
        $base_coupons = $this->bb_base_coupon_model->getAllList([['id', 'in', $coupon_ids]]);
        $base_coupons_tmp = [];
        foreach ($base_coupons as $k => $v){
            $base_coupons_tmp[ $v['id'] ] = $v;
        }

        foreach ($user_coupons as $k => $v){
            if(isset($base_coupons_tmp[ $v['coupon_id'] ])){
                $base_coupon = $base_coupons_tmp[ $v['coupon_id'] ];
                $user_coupons[$k]['limit_money'] = $base_coupon['limit_money'];//开始使用金额
                $user_coupons[$k]['money_value'] = $base_coupon['money_value'];//面值
                $user_coupons[$k]['coupon_title'] = '全品类通用优惠券';
                $user_coupons[$k]['surplus_day'] = $this->diffBetweenTwoDays($time, $v['time_end']);
                $user_coupons[$k]['time_end'] = date('Y-m-d', $v['time_end']);//到期时间
            }
        }

        return sdk_return($user_coupons, 1, '获取成功');
    }

    //计算两个时间之间的天数
    public function diffBetweenTwoDays ($start_date, $end_date){
//        return round(abs($end_date - $start_date) / 3600 / 24) + 1;
        return ceil(abs($end_date - $start_date) / 3600 / 24);
    }

    //获取可使用优惠券
    public function get_canuse_coupon(){
        $param = $this->request_param;
        if(!isset($param['openid']) || empty($openid = $param['openid'])){
            return sdk_return('', 0, '缺少参数openid');
        }

        if(!isset($param['price']) || empty($price = $param['price'])){
            return sdk_return('', 0, '缺少参数price');
        }

        //修改优惠券状态
        $this->updateUserCouponStatus($openid);

        $time = time();

        $coupons = $this->shop_member_coupon_model->getCanuseCoupon($openid);  //$price

        $can_use_arr = $not_can_use_arr = [];
        //获取当前用户所有可用的优惠券
        foreach ($coupons as $k => $v){
            $v['coupon_title'] = '全品类通用优惠券';
            $v['surplus_day'] = $this->diffBetweenTwoDays($time, $v['time_end']);
            $v['time_end'] = date('Y-m-d', $v['time_end']);//到期时间
            if($v['limit_money'] <= $price){
                $v['is_canuse'] = 1;//1可用 0不可用
                $can_use_arr[] = $v;
            }else{
                $v['is_canuse'] = 0;//0不可用
                $not_can_use_arr[] = $v;
            }
        }

        //按照面值排序
        //$user_coupons = arraySequence($user_coupons, 'money_value');
//        $can_use_arr = $this->arraySort($can_use_arr, 'money_value');

        $user_coupons = array_merge($can_use_arr, $not_can_use_arr);

        $content = '';
        if(!empty($can_use_arr)){
            //$content = '本单' . count($can_use_arr) . '张优惠换可供您挑选，最高可节省' . $can_use_arr[0]['money_value'] . '元，点击“立即购买”进入确认点单页确认优惠券已使用';
            $content = '您有' . count($can_use_arr) . '张优惠券，点击立即购买后可选择使用';
        }

        $return_data = [
            'defaults'       =>  empty($can_use_arr) ? '' : $can_use_arr[0],
            'content'       =>  $content,
            'lists'         =>  $user_coupons
        ];

        return sdk_return($return_data, 1, '获取成功');
    }

    //更新当前用户优惠券状态
    public function updateUserCouponStatus($openid = ''){
        if(empty($openid)){
            return [];
        }

        $time = time();
        //修改已过期
        $where = "openid = '{$openid}' and coupon_status = 2 and time_end <= $time";
        $this->shop_member_coupon_model->updateInfoPro($where, ['coupon_status'=>7]);

        //修改已删除
        $del_time = $time - 5 * 24 * 3600;
        $del_where = "openid = '{$openid}' and coupon_status in (3, 7) and time_end < $del_time";
        $this->shop_member_coupon_model->updateInfoPro($del_where, ['coupon_status'=>9]);
    }

    /**
     * 二位数组排序
     * @param $data  二维数组
     * @param string $field 需要的字符安
     * @param string $sort_type  SORT_ASC升序  SORT_DESC降序
     * @return string
     */
    public static function arraySort($data, $field = '', $sort_type = SORT_DESC){
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
     * 领取活动优惠券
     */
    public function getActivityCoupon() {
        $param = $this->request->param();
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('', 6, '参数缺失');//店铺ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('', 6, '参数缺失');//用户openID
        $union_id = !empty($param['union_id']) ? $param['union_id'] : '';//用户union_id
        $task_id = !empty($param['task_id']) ? $param['task_id'] : sdk_return('', 6, '参数缺失');//领取的数据ID（taskID）
        $gift = !empty($param['gift']) ? $param['gift'] : sdk_return('', 6, '参数缺失');//领取的是哪一次的
        $coupon_id = !empty($param['coupon_id']) ? $param['coupon_id'] : sdk_return('', 6, '参数缺失');//优惠券ID，json格式
        $base_coupon_id_arr = json_decode($coupon_id, true);
//        $base_coupon_id_arr = explode(',',$coupon_id);
        if(!is_array($base_coupon_id_arr)){
            sdk_return('',6,'领取优惠券错误');
        }else{
            if(empty(count($base_coupon_id_arr))){
                sdk_return('',6,'领取优惠券错误！');
            }
        }
        $user_openid = is_sns($user_openid);
        $is_gift = '';
        $gift_type = '';
        $gift_value = '';
        if ($gift == 2) {
            //领取的二天的金币
            $is_gift = 'gift2';
            $gift_type = 'gift2_type';
            $gift_value = 'gift2_value';
        } elseif ($gift == 3) {
            //领取的三天的金币
            $is_gift = 'gift3';
            $gift_type = 'gift3_type';
            $gift_value = 'gift3_value';
        }
        if(empty($is_gift)){
            sdk_return('',6,'领取天数错误');
        }
        //查询task数据
        $task_arr = Db::connect('db_mini_mall')->table('ims_yd_supplier_task')->where('id = '.$task_id)->find();
        //判断是否领取过奖励
        if(!empty($task_arr[$is_gift])){
            sdk_return('',6,'您已领取过当次奖励');
        }
        //修改领取记录表
        $update = null;
        $update = [
            $is_gift => time(),
            $gift_type => 2,//1金币，2优惠券
            $gift_value => $coupon_id,//优惠券ID
        ];
        Db::connect('db_mini_mall')->table('ims_yd_supplier_task')->where('id = '.$task_id)->update($update);
        unset($update);
        $now_time = time();
        foreach ($base_coupon_id_arr as $key => $base_coupon_id){
            //计算当前优惠券的截止时间
            $coupon_data = $this->bb_base_coupon_model->getInfo(['id'=>$base_coupon_id['id']]);
            $valid_day = !empty($coupon_data['valid_day']) ? $coupon_data['valid_day'] : 1;
            $time_end = $now_time + ((60 * 60 * 24) * $valid_day);
            //优惠券数量
            $coupon_num = $base_coupon_id['num'];
            for ($i = 0; $i < $coupon_num; $i++) {
                $insert_member_coupon = [
                    'coupon_id' => $base_coupon_id['id'],//周活动领取金币
                    'openid' => $user_openid,//周活动领取金币
                    'openid_time' => $now_time,
                    'time_start' => $now_time,
                    'time_end' => $time_end,
                    'create_time' => $now_time,
                    'coupon_status' => 2,
                    'status' => 1,
                ];
                Db::connect('db_mini_mall')->table('ims_ewei_shop_member_coupon')->insertGetId($insert_member_coupon);
            }
        }
        //查询当前领取状况
        $task_data = Db::connect('db_mini_mall')->table('ims_yd_supplier_task')->where('id = '.$task_id)->find();
        $content = '暂无可领取好礼';
        //下单两次
        if($task_data['pay_count'] == 2){
            if($task_data['gift2'] == 0){
                $content = '您有1份好礼待领取';
            }
        }
        //下单三次及以上
        if($task_data['pay_count'] >= 3){
            if($task_data['gift2'] == 0 && $task_data['gift3'] == 0){
                $content = '您有2份好礼待领取';
            }else{
                $content = '您有1份好礼待领取';
            }
        }
        return sdk_return(['content' => $content],1,'领取成功');
    }

    //申请增加优惠券
    public function apply_append_coupon(){
        $param = $this->request_param;
        //申请人user_id
        if(!isset($param['user_id']) || empty($user_id = $param['user_id'])){
            return sdk_return('', 0, 'Invalid param user_id');
        }

        if(!isset($param['content']) || empty($content = $param['content'])){
            return sdk_return('', 0, 'Invalid param content');
        }

        //优惠群金额
        if(!isset($param['price']) || empty($price = $param['price']) || !in_array($price, $this->apply_append_coupon_price)){
            return sdk_return('', 0, 'Invalid param price');
        }

        //申请订单
        if(!isset($param['ordersn']) || empty($ordersn = $param['ordersn'])){
            return sdk_return('', 0, 'Invalid param ordersn');
        }

        //获取当前申请人姓名
        $user_info = $this->btj_admin_user_model->getInfo([['user_id', '=', $user_id]]);
        if(empty($user_info)){
            return sdk_return('', 0, '获取用户账号失败');
        }

        //判断当前订单是否已经提交审核
        $count = $this->order_append_coupon_model->getCount([['ordersn', '=', $ordersn], ['status', 'in', [0, 1]]]);
        if($count){
            return sdk_return('', 0, '当前订单已提交申请，请勿重复申请');
        }
        $data = [
            'user_id'       =>  $user_id,
            'ordersn'       =>  $ordersn,
            'coupon_price'  =>  $price,
            'user_name'     =>  $user_info['name'],
            'content'       =>  $content,
            'status'        =>  0,
            'create_time'   =>  time()
        ];

        $id = $this->order_append_coupon_model->insertInfo($data);

        if(!$id){
            return sdk_return('', 0, '系统错误，请稍后重试');
        }

        return sdk_return(['id'=>$id], 1, '申请成功,请等待审核');

    }

    //申请追加优惠券的金额接口
    public function apply_append_coupon_price(){

        $price_arr = $this->apply_append_coupon_price;

        return sdk_return($price_arr, 1, '获取成功');

    }

    //申请订单追加优惠券待审核列表
    public function apply_append_coupon_users(){
        $users = $this->order_append_coupon_model->getAllListPro([['status', '=', 0]], ['user_name', 'user_id']);
        $group_users = [];
        foreach ($users as $k => $v){
            $group_users[$v['user_id']] = $v;
        }

        $group_users = array_values($group_users);

        array_unshift($group_users, ['user_name'=>'全部', 'user_id'=>0]);

        return sdk_return($group_users, 1, '获取成功');
    }

    //申请列表
    public function apply_append_coupon_list(){
        $param = $this->request_param;

        $user_id = isset($param['user_id']) ? intval($param['user_id']) : 0;

        $where = [
            ['status', '=', 0]
        ];
        if(!empty($user_id)){
            $where[] = ['user_id', '=', $user_id];
        }

        $append_coupons = $this->order_append_coupon_model->getAllListPro($where, ['id', 'ordersn', 'user_name', 'content']);

        $shop_order_where = [
            ['ordersn', 'in', array_merge([0], array_column($append_coupons, 'ordersn'))]
        ];
        $orders = $this->shop_order_model->getAllListPro($shop_order_where, ['ordersn','address', 'createtime', 'openid', 'coupon_money', 'goodsprice']);
        $orders_tmp = [];
        foreach ($orders as $k => $v){
            $orders_tmp[$v['ordersn']] = $v;
        }

        foreach ($append_coupons as $k => $v){

            $append_coupons[$k]['checked'] = false;

            if(isset($orders_tmp[$v['ordersn']])){
                $order = $orders_tmp[$v['ordersn']];

                //售价
                $append_coupons[$k]['goodsprice'] = $order['goodsprice'];

                //优惠券价格
                $append_coupons[$k]['coupon_money'] = $order['coupon_money'];

                //券后价格
                $append_coupons[$k]['price'] = $order['goodsprice'] - $order['coupon_money'];

                $address = unserialize($order['address']);

                $append_coupons[$k]['adressInfo'] = [
                    'address'       =>  $address['address'],
                    'mobile'        =>  $address['mobile'],
                    'realname'      =>  $address['realname']
                ];

                $append_coupons[$k]['bdInfo'] = [
                    'customer_name' =>  $address['street']
                ];

                //获取本周下单次数和历史下单次数
                $week_data = $this->getMondayAndSundayTime($order['createtime']);
                $count_where = [
                    ['openid', '=', $order['openid']],
                    ['status', 'in', [0, 1, 2, 3]],
                    ['createtime', '>', $week_data['monday']],
                    ['createtime', '<', $week_data['sunday']]
                ];
                $append_coupons[$k]['week_order_num'] = $this->shop_order_model->getCount($count_where);
                //获取历史下单次数
                $append_coupons[$k]['all_order_num'] = $this->shop_order_model->getCount([['openid', '=', $order['openid']],['status', 'in', [0, 1, 2, 3]]]);
            }
        }

        return sdk_return($append_coupons, 1, '获取成功');
    }

    //发券审批
    public function apply_append_coupon_check(){
        $param = $this->request_param;

        if(!isset($param['check_uid']) || empty($check_uid = $param['check_uid'])){
            return sdk_return('', 0, 'Invalid param check_uid');
        }

        if(!isset($param['ids']) || empty($ids = $param['ids'])){
            return sdk_return('', 0, 'Invalid param ids');
        }

        if(!isset($param['status']) || empty($status = $param['status']) || !in_array($param['status'], [-1, 1])){
            return sdk_return('', 0, 'Invalid param status');
        }

        //$ids = explode(',', $ids);

        if(is_numeric($ids)){
            $ids = [ $ids ];
        }
        //转数组
        if(!is_array($ids)){
            $ids = json_decode($ids, true);
        }

        //订单去重
        $ids = array_unique($ids);

        if(empty($ids)){
            return sdk_return('', 0, '至少选择一个待审核数据');
        }

        //审核列表条件
        $app_coupon_where = [
            ['id', 'in', $ids]
        ];

        //审核列表修改内容
        $update_info = [
            'status'        =>  $status,
            'check_uid'     =>  $check_uid,
            'check_time'    =>  time(),
            'check_content' => isset($param['content']) ? $param['content'] : ''
        ];

        //驳回
        if($status == -1){

            if(count($ids) > 1){
                return sdk_return('', 0, '每次只能驳回一个请求');
            }
            //驳回理由
            if(empty($update_info['check_content'])){
                return sdk_return('', 0, '请输入驳回理由');
            }

            //更新审核状态
            $this->order_append_coupon_model->updateInfoPro($app_coupon_where, $update_info);

            return sdk_return('', 1, '已驳回请求');

        }

        //同意，增加优惠券，减少金额
        $apple_append_coupon_list = $this->order_append_coupon_model->getAllList($app_coupon_where);

        $ordersns = array_column($apple_append_coupon_list, 'ordersn');

        $orders = $this->shop_order_model->getAllListPro([['ordersn', 'in', $ordersns]], ['ordersn', 'coupon_id', 'id', 'openid', 'goodsprice']);
        $orders_tmp = [];
        foreach ($orders as $k => $v){
            $orders_tmp[$v['ordersn']] = $v;
        }

        //把订单信息归于审核列表中
        foreach ($apple_append_coupon_list as $k => $v){
            if(isset($orders_tmp[$v['ordersn']])){
                $order = $orders_tmp[$v['ordersn']];

                //获取优惠券信息
                $coupon_info = $this->getCheckCouponInfoByPrice($v['coupon_price']);

                //更新审核状态
                $this->order_append_coupon_model->updateInfo($v['id'], $update_info);

                //未使用优惠券，订单追加一张优惠券
                if($order['coupon_id'] == 0){
                    $this->add_order_coupon($order, $coupon_info);
                }else{//给当前用户追加一张优惠券
                    $this->add_user_coupon($order, $coupon_info);
                }
            }
        }

        return sdk_return('', 1, '审核成功');
    }

    //给用户订单追加优惠券
    public function add_order_coupon($order_info, $coupon_info){//$coupon_info['coupon_money']
        $coupon_data = [
            'coupon_id'     =>  $coupon_info['coupon_id'],
            'openid'        =>  $order_info['openid'],
            'order_id'      =>  $order_info['id'],
            'coupon_status' =>  3,
            'status'        =>  1,
            'coupon_code'   =>  date("Ymd"),
            'time_end'      =>  $coupon_info['expiry_date']
        ];

        $coupon_id = $this->shop_member_coupon_model->insertInfo($coupon_data);

        //使用优惠券后的价格
        $price = $order_info['goodsprice'] - $coupon_info['coupon_money'];

        //更新订单信息
        $this->shop_order_model->updateInfo($order_info['id'], ['coupon_id'=>$coupon_id, 'price'=>$price, 'coupon_money'=>$coupon_info['coupon_money']]);

        //更新member_log
        $this->member_log_model->updateInfoPro([['logno', '=', $order_info['ordersn']]], ['money'=>$price]);
    }

    //给用户增加优惠券
    public function add_user_coupon($order_info, $coupon_info){
        $coupon_data = [
            'coupon_id'     =>  $coupon_info['coupon_id'],
            'openid'        =>  $order_info['openid'],
            'order_id'      =>  $order_info['id'],
            'coupon_status' =>  2,
            'status'        =>  1,
            'coupon_code'   =>  date("Ymd"),
            'time_end'      =>  $coupon_info['expiry_date'],
            'openid_time'   =>  time(),
            'time_start'    =>  time(),
        ];

        $coupon_id = $this->shop_member_coupon_model->insertInfo($coupon_data);
    }

    //根据申请的优惠券金额获取订单获取优惠券id
    public function getCheckCouponInfoByPrice($price){

        $where = [
            ['c_type', '=', 10],
            ['money_value', '=', $price],
            ['status', '=', 1]
        ];

        $info = $this->bb_base_coupon_model->getInfo($where);

        if(empty($info)){
            return [];
        }

        if($info['valid_type'] == 1){//有效天数
            $expiry_date = strtotime("+{$info['valid_day']} day", time());
        }else{
            $expiry_date = $info['valid_date'];
        }

        $trturn_data = [
            'coupon_id'     =>  $info['id'],
            'expiry_date'   =>  $expiry_date,
            'coupon_money'  =>  $price
        ];

        return $trturn_data;
    }

    //获取当前时间所在周 周一和周天的时间戳
    public function getMondayAndSundayTime($time = 0){
        $time = empty($time) ? time() : $time;
        //获取周一的时间
        $monday = $time - ((date('w', $time) == 0 ? 7 : date('w', $time)) - 1) * 24 * 3600;//当前时间的周一时间戳
        $sunday_times = $time + (7 - (date('w', $time) == 0 ? 7 : date('w', $time))) * 24 * 3600;//当前订单时间所在周周天时间戳
        $sunday = strtotime('+1 day', strtotime(date('Y-m-d', $sunday_times))) - 100;
        return [
            'monday'        =>  strtotime(date('Y-m-d', $monday)),
            'sunday'        =>  $sunday
        ];
    }

}