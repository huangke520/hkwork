<?php

namespace app\api\controller;

use app\api\model\ydxq\Signin as SigninModel;
use app\api\model\ydxq\BbBaseCoupon as BbBaseCouponModel;
use app\api\model\ydxq\ShopMemberCoupon as ShopMemberCouponModel;
use app\api\model\ydxq\MemberGoldList as MemberGoldListModel;
use app\api\model\ydxq\MemberGoldCount as MemberGoldCountModel;
use think\Db;

class Signin extends BaseController {

    private $signin_model;
    private $member_coupon_model;
    private $member_gold_list_model;
    private $member_gold_count_model;

    private $week_data = [1 => '周一', 2 => '周二', 3 => '周三', 4 => '周四', 5 => '周五', 6 => '周六', 7 => '周日'];
    private $week_gold = [1 => 100, 2 => 100, 3 => 100, 4 => 100, 5 => 100, 6 => 100, 7 => 100];
    private $sign_gold = 100;//签到领取的金币

    public function __construct() {
        parent::__construct();
        $this->signin_model = new SigninModel();
        $this->member_coupon_model = new ShopMemberCouponModel();
        $this->member_gold_list_model = new MemberGoldListModel();
        $this->member_gold_count_model = new MemberGoldCountModel();
    }

    /**
     * 获取最近7天的签到记录
     * time 2019年12月5日11:51:02
     * maic
     */
    public function getSingInLog(){
//        sdk_return('', 6, '暂未开放');
        $param = $this->request->param();
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//用户openID
        $user_openid = is_sns($user_openid);
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');//店铺ID
        if($sup_id != 461){
            sdk_return('', 6, '暂未开放');
        }
        //查询连续签到了几天
        $time = time();
        $start_time = strtotime(date('Y-m-d',$time));
//        $start_time = strtotime(date('Y-m-d',$time));
        $end_time = strtotime(date("Y-m-d",strtotime("+1 day",$time)));//前一天
//        $start_time = strtotime(date("Y-m-d",strtotime("-2 day",$time)));//前两天
        $sign_log = Db::connect('db_mini_mall')->table('ims_yd_member_signin')->where('openid = "'.$user_openid.'" and createtime > '.$start_time.' and createtime <= '.$end_time)->order('createtime','desc')->find();
        $data = [];
//        echo $sign_log['sign_day'];exit;
        if((!empty($sign_log)) && ($sign_log['sign_day'] <= 7)){
            $log_num = $sign_log['sign_day'];
            $sign_log_arr = Db::connect('db_mini_mall')->table('ims_yd_member_signin')->where('openid = "'.$user_openid.'"')->order('createtime','desc')->limit($log_num)->select();
            for ($i = 1; $i <= 7; $i++){
                $day = $i.'天';
                if($sign_log['sign_day'] == $i){
                    $day = '今';
                }
                $key = $i - 1;
                $log_num_key = $log_num - $key;
                if((!empty($sign_log_arr[$key]['sign_day'])) && ($log_num_key == $sign_log_arr[$key]['sign_day']) && ($key >= 0)){
                    $data[] = [
                        'week'      =>  $day,
                        'gold'      =>  $sign_log_arr[$key]['gold'],//领取金币数量
                        'is_draw'   =>  1,//当日是否已领取
                    ];
                }else{
                    $data[] = [
                        'week'      =>  $day,
                        'gold'      =>  $this->week_gold[$i],//领取金币数量
                        'is_draw'   =>  0,//当日是否已领取
                    ];
                }
            }
        }else{
            $log_num = 1;
            for ($i = 1; $i <= 7; $i++){
                if($i == 1){
                    $day = '今';
                }else{
                    $day = $i.'天';
                }
                $data[] = [
                    'week'      =>  $day,
                    'gold'      =>  $this->week_gold[$i],//领取金币数量
                    'is_draw'   =>  0,//当日是否已领取
                ];
            }
        }
        $coupon_draw = 0;
        $return_data = [
            'content'       =>  "已签到{$log_num}天，连续签到满7天额外赠送800金币",
//            'content_msg'    =>  "每日进店即签到成功自动领取金币，断签将重新计算天数",
            'content_msg'    =>  "每日进店即签到成功自动领取100金币，断签将重新计算天数",
//            'coupon_msg'    =>  "每周满5天签到额外获赠{$coupon_info['money_value']}元优惠券1张",
            'coupon_msg'    =>  "每周满7天签到额外获赠800金币",
            'coupon_draw'   =>  $coupon_draw,//优惠券领取状态 0不可领取  1待领取  2已领取
            'lists'         =>  $data,
        ];
        sdk_return($return_data, 1, '请求成功');
    }

    /**
     * 签到接口
     * time 2019年12月5日11:51:10
     * maic
     */
    public function singInV2(){
//        sdk_return('', 6, '暂未开放');
        $param = $this->request->param();
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//用户openID
        $user_openid = is_sns($user_openid);
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');//店铺ID
        if($sup_id != 461){
            sdk_return('', 6, '暂未开放');
        }
        $time = time();
        $start_time = strtotime(date('Y-m-d', $time));//凌晨时间
        $end_time = strtotime(date("Y-m-d",strtotime("+1 day",$time)));//第二天的凌晨时间
        //获取今日是否已签到
        $where = [
            ['openid', '=', $user_openid],
            ['createtime', '>', $start_time],
            ['createtime', '<=', $end_time]
        ];
        $sign_count = $this->signin_model->getCount($where);
        if($sign_count){
            sdk_return('', 200, '今日已签到，请勿重复签到');
        }
        //获取用户unionid
        $shop_member_model = new \app\api\model\ydxq\ShopMember();
        $member_info = $shop_member_model->getInfoPro([['openid', '=', $user_openid]], ['unionid']);
        $unionid = $member_info['unionid'];
        //查询连续签到了几天
        $time = time();
        //查询昨天是否签到
        $start_time = strtotime(date("Y-m-d",strtotime("-1 day",$time)));//前一天
        $end_time = strtotime(date('Y-m-d',$time));
        $sing_log = Db::connect('db_mini_mall')->table('ims_yd_member_signin')->where('openid = "'.$user_openid.'" and createtime > '.$start_time.' and createtime <= '.$end_time)->find();
        if((!empty($sing_log)) && ($sing_log['sign_day'] <= 6)){
            $log_num = $sing_log['sign_day'] + 1;
            //表示昨天签到了，继续
            $gold_value = $this->week_gold[$log_num];
        }else{
            $log_num = 1;
            //表示昨天没有签到，从头开始
            $gold_value = $this->week_gold[1];
        }
        //增加签到记录
        $sign_data = [
            'openid'        =>  $user_openid,
            'gold'          =>  $gold_value,//签到的金币数量
            'nature_week'   =>  date('Y').date('W'),//（年）自然周
            'week_day'      =>  date('w'),
            'sign_day'      =>  $log_num,//连续天数
            'createtime'    =>  $time
        ];
        $this->signin_model->insertInfo($sign_data);
        //增加金币
        $this->addGold($user_openid,$unionid,$sup_id,$gold_value);
        //本周第7天签到，获赠800金币
        $content = '';
        $award = 0;
        if($log_num >= 7){
            $award = 800;
            $content = '已连续7天签到，额外获赠800金币';
            //为当前用户增加金币领取记录
            $golad_list_data = null;
            $golad_list_data = [
                'type1'         =>  5,
                'type2'         =>  4,
                'openid'        =>  $user_openid,
                'goods_title'   =>  '签到奖励',
                'unionid'       =>  $unionid,
                'sup_id'        =>  $sup_id,
                'gold_value'    =>  $award,
                'status'        =>  1,
                'addtime'       =>  $time,
                'modtime'       =>  $time
            ];
            $this->member_gold_list_model->insertInfo($golad_list_data);
            //修改当前用户在当前店铺的金币数量
            Db::connect('db_mini_mall')->execute("UPDATE ims_member_gold_count set gold_count = gold_count + {$award} where sup_id = {$sup_id} and openid = '{$user_openid}' and `status` = 1");
        }
        //增加记录
        $this->addGoldList($user_openid,$unionid,$sup_id,$gold_value);
        $return_ata = [
            'content'       =>  $content,
            'gold'          =>  ($gold_value + $award).'金币',
        ];
        sdk_return($return_ata, 1, '签到成功');
    }

    /**
     * 日常签到金币增加记录
     * @param string $openid
     * @param string $unionid
     * @param string $sup_id
     * @param string $gold_value
     * @return bool
     */
    public function addGoldList($openid = '',$unionid = '',$sup_id = '',$gold_value = ''){
        if((empty($openid)) && (empty($unionid)) && (empty($sup_id)) && (empty($gold_value))){
            return false;
        }
        $time = time();
        //为当前用户增加金币领取记录
        $golad_list_data = [
            'type1'         =>  5,
            'type2'         =>  4,
            'openid'        =>  $openid,
            'goods_title'   =>  '日常签到',
            'unionid'       =>  $unionid,
            'sup_id'        =>  $sup_id,
            'gold_value'    =>  $gold_value,
            'status'        =>  1,
            'addtime'       =>  $time,
            'modtime'       =>  $time
        ];
        $this->member_gold_list_model->insertInfo($golad_list_data);
        return true;
    }

    /**
     * 增加用户金币数量
     * @param string $openid
     * @param string $unionid
     * @param string $sup_id
     * @param int $gold_vale
     * @return bool
     */
    public function addGold($openid = '',$unionid = '',$sup_id = '',$gold_vale = 0){
        $time = time();
        if((empty($openid)) && (empty($sup_id)) && (empty($gold_vale))){
            return false;
        }
        //增加用户金币总量
        $gold_count_info = $this->member_gold_count_model->getInfo([['sup_id', '=', $sup_id], ['openid', '=', $openid]]);
        if(empty($gold_count_info)){
            $gold_count_data = [
                'sup_id'        =>  $sup_id,
                'unionid'       =>  $unionid,
                'openid'        =>  $openid,
                'gold_count'    =>  $gold_vale,
                'addtime'       =>  $time,
                'modtime'       =>  $time
            ];
            $this->member_gold_count_model->insertInfo($gold_count_data);
        }else{
            $gold_count = $gold_count_info['gold_count'] + $gold_vale;
            $this->member_gold_count_model->updateInfo($gold_count_info['id'], ['gold_count'=>$gold_count]);
        }
        return true;
    }

    //获取用户本周签到记录表
    public function week_signin_recode(){
        sdk_return('', 6, '暂未开放');
        $param = $this->request_param;
        $time = time();
        if(!isset($param['openid']) || empty($openid = $param['openid'])){
            sdk_return('', 0, '缺少参数openid');
        }
        //获取当前用户本周的签到记录
        $week_time = $this->getMondayAndSundayTime($time);
        $where = [
            ['openid', '=', $openid],
            ['createtime', 'between', [$week_time['monday'], $week_time['sunday']]],
        ];
        $sings = $this->signin_model->getAllList($where);
        $sings_tmp = [];
        foreach ($sings as $k => $v){
            $sings_tmp[ $v['week_day'] ] = $v;
        }
        $sings_lists = [];
        $draw_day_num = 0;//签到的天数
        $coupon_draw = 0;//领取状态 0不可领取  1待领取  2已领取
        for ($i=1;$i<=7;$i++ ){
            $data = [
                'week'      =>  $this->week_data[$i],
                'gold'      =>  $this->sign_gold,//领取金币数量
                'is_draw'   =>  0,//当日是否已领取
            ];
            if(isset($sings_tmp[$i])){
                $data['is_draw'] = 1;
                $draw_day_num++;
            }
            $sings_lists[] = $data;
        }
        //获取本周的签到领取的优惠券信息
//        $coupon_info = $this->getSigninCoupon();
//        if(empty($coupon_info)){
//            return sdk_return('', 0, '获取优惠券待领取信息错误');
//        }
        //如果签到天数大于等于5天
//        if($draw_day_num >= 5){
//            //获取当前用户本周是否已经领取优惠券
//            $member_coupon_where = [
//                ['coupon_id', '=', $coupon_info['id']],
//                ['openid', '=', $openid],
//                ['create_time', 'between', [$week_time['monday'], $week_time['sunday']]],
//            ];
//            $member_coupon_info = $this->member_coupon_model->getInfo($member_coupon_where);
//            if(empty($member_coupon_info)){
//                $coupon_draw = 1;// 1待领取
//            }else{
//                $coupon_draw = 2;// 2已领取
//            }
//        }
        $return_data = [
            'content'       =>  "已签到{$draw_day_num}天",
//            'coupon_msg'    =>  "每周满5天签到额外获赠{$coupon_info['money_value']}元优惠券1张",
            'coupon_msg'    =>  "每周满7天签到额外获赠800金币",
            'coupon_draw'   =>  $coupon_draw,//优惠券领取状态 0不可领取  1待领取  2已领取
            'lists'         =>  $sings_lists,
        ];
        sdk_return($return_data, 1, '请求成功');
    }

    //领取签到优惠券
    public function draw_coupon(){
        $param = $this->request_param;

        if(!isset($param['openid']) || empty($openid = $param['openid'])){
            return sdk_return('', 0, '缺少参数openid');
        }

        $time = time();

        $week_time = $this->getMondayAndSundayTime($time);

        //判断当前用户本周是否已经领取过优惠券
        $coupon_info = $this->getSigninCoupon();

        //获取当前用户本周是否已经领取优惠券
        $member_coupon_where = [
            ['coupon_id', '=', $coupon_info['id']],
            ['openid', '=', $openid],
            ['create_time', 'between', [$week_time['monday'], $week_time['sunday']]],
        ];

        $count = $this->member_coupon_model->getCount($member_coupon_where);
        if($count){
            return sdk_return('', 0, '用户已领取优惠券，请勿重复领取');
        }

        //验证当前用户是否具备领取条件
        $sign_where = [
            ['openid', '=', $openid],
            ['createtime', 'between', [$week_time['monday'], $week_time['sunday']]],
        ];
        $signin_daynum = $this->signin_model->getCount($sign_where);
        if($signin_daynum < 5){
            return sdk_return('', 0, '签到天数不满足领取条件');
        }

        //valid_type  失效类型1：自领取日起，2固定日期失效
        if($coupon_info['valid_type'] == 1){
            $time_end = strtotime(date('Y-m-d', $time)) + $coupon_info['valid_day'] * 60 * 60 * 24;
        }else{
            $time_end = $coupon_info['valid_date'];
        }

        $member_coupon_data = [
            'coupon_id'     =>  $coupon_info['id'],
            'openid'        =>  $openid,
            'openid_time'   =>  $time,
            'time_start'    =>  $time,
            'time_end'      =>  $time_end,
            'create_time'   =>  $time,
            'coupon_status' =>  2,
            'status'        =>  1
        ];
        $id = $this->member_coupon_model->insertInfo($member_coupon_data);

        $return_data = [
            'coupon_id'     =>  $id,
            'name'          =>  '全品类通用优惠券',
            'content1'      =>  '店铺活动',
            'content2'      =>  '奖励赠送',
            'endtime'       =>  date('Y-m-d', $time_end),
            'day'           =>  $coupon_info['valid_day'],
            'limit_money'   =>  $coupon_info['limit_money'],
            'money_value'   =>  $coupon_info['money_value']
        ];

        return sdk_return($return_data, 1, '领取成功');
    }

    //签到
    public function signin(){
        sdk_return('', 6, '暂未开放');
//        die;
        $param = $this->request_param;
        if(!isset($param['openid']) || empty($openid = $param['openid'])){
            sdk_return('', 0, '参数openid错误');
        }
        $openid = is_sns($openid);
        //云店门店
        $sup_id = isset($param['sup_id']) ? intval($param['sup_id']) : 461;
        $time = time();
        $start_time = strtotime(date('Y-m-d', $time));//凌晨时间
//        date("Y-m-d",strtotime("+1 day"))
        $end_time = strtotime(date("Y-m-d",strtotime("+1 day",$time)));//第二天的凌晨时间
        //获取今日是否已签到
        $where = [
            ['openid', '=', $openid],
            ['createtime', '>', $start_time],
            ['createtime', '<=', $end_time]
        ];
        $sign_count = $this->signin_model->getCount($where);
        if($sign_count){
            sdk_return('', 200, '今日已签到，请勿重复签到');
        }
        //增加签到记录
        $sign_data = [
            'openid'        =>  $openid,
            'gold'          =>  $this->sign_gold,//签到的金币数量
            'nature_week'   =>  date('Y').date('W'),//（年）自然周
            'week_day'      =>  date('w'),
            'createtime'    =>  $time
        ];
        $this->signin_model->insertInfo($sign_data);
        $shop_member_model = new \app\api\model\ydxq\ShopMember();
        $member_info = $shop_member_model->getInfoPro([['openid', '=', $openid]], ['unionid']);
        $unionid = $member_info['unionid'];
        //为当前用户增加金币领取记录
        $golad_list_data = [
            'type1'         =>  5,
            'type2'         =>  4,
            'openid'        =>  $openid,
            'goods_title'   =>  '日常签到',
            'unionid'       =>  $unionid,
            'sup_id'        =>  $sup_id,
            'gold_value'    =>  $this->sign_gold,
            'status'        =>  1,
            'addtime'       =>  $time,
            'modtime'       =>  $time
        ];
        $this->member_gold_list_model->insertInfo($golad_list_data);
        //增加用户金币总量
        $gold_count_info = $this->member_gold_count_model->getInfo([['sup_id', '=', $sup_id], ['openid', '=', $openid]]);
        if(empty($gold_count_info)){
            $gold_count_data = [
                'sup_id'        =>  $sup_id,
                'unionid'       =>  $unionid,
                'openid'        =>  $openid,
                'gold_count'    =>  $this->sign_gold,
                'addtime'       =>  $time,
                'modtime'       =>  $time
            ];
            $this->member_gold_count_model->insertInfo($gold_count_data);
        }else{
            $gold_count = $gold_count_info['gold_count'] + $this->sign_gold;
            //var_dump($gold_count);die;
            $this->member_gold_count_model->updateInfo($gold_count_info['id'], ['gold_count'=>$gold_count]);
        }
        $week_time = $this->getMondayAndSundayTime();
        $sign_where = [
            ['openid', '=', $openid],
            ['createtime', 'between', [$week_time['monday'], $week_time['sunday']]],
        ];
        $signin_daynum = $this->signin_model->getCount($sign_where);
//        $content = $signin_daynum >= 5 ? '本周已签到5天，有待领取优惠券一张' : '';
        $content = '';
        if($signin_daynum >= 7){
            $award = 800;
            $content = '本周第7天签到，获赠800金币';
            //为当前用户增加金币领取记录
            $golad_list_data = null;
            $golad_list_data = [
                'type1'         =>  5,
                'type2'         =>  4,
                'openid'        =>  $openid,
                'goods_title'   =>  '签到奖励',
                'unionid'       =>  $unionid,
                'sup_id'        =>  $sup_id,
                'gold_value'    =>  $award,
                'status'        =>  1,
                'addtime'       =>  $time,
                'modtime'       =>  $time
            ];
            $this->member_gold_list_model->insertInfo($golad_list_data);
            //修改当前用户在当前店铺的金币数量
            Db::connect('db_mini_mall')->execute("UPDATE ims_member_gold_count set gold_count = gold_count + {$award} where sup_id = {$sup_id} and openid = '{$openid}' and `status` = 1");
        }
        $return_ata = [
            'content'       =>  $content,
            'gold'          =>  $this->sign_gold
        ];
        sdk_return($return_ata, 1, '签到成功');
    }

    //获取签到优惠券
    public function getSigninCoupon(){
        //获取优惠券信息
        $bb_base_coupon_model = new BbBaseCouponModel();
        $coupon_info = $bb_base_coupon_model->getInfo([['status', '=', 1],['c_type', '=', 8]]);
        return $coupon_info;
    }

    //获取当前时间所在周 周一和周天的时间戳
    public function getMondayAndSundayTime($time = 0){
        $time = empty($time) ? time() : $time;
        //获取周一的时间
        $monday = $time - ((date('w', $time) == 0 ? 7 : date('w', $time)) - 1) * 24 * 3600;//当前时间的周一时间戳
        $sunday_times = $time + (7 - (date('w', $time) == 0 ? 7 : date('w', $time))) * 24 * 3600;//当前订单时间所在周周天时间戳
        $sunday = strtotime('+1 day', strtotime(date('Y-m-d', $sunday_times)));
        return [
            'monday'        =>  strtotime(date('Y-m-d', $monday)),
            'sunday'        =>  $sunday
        ];
    }
}