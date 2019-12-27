<?php

/**
 * Author: maic
 * Date: 2019-08-08
 */

namespace app\api\controller;

use think\Db;

class MonthCard extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    /**
     * 查询月卡详情
     */
    public function getMonthCard(){
        $month_card_coupon = Db::connect('db_mini_mall')->table('ims_bb_base_coupon')->where('c_type = 11')->select();
        $money = 0;
        $month_card_price = 20;//月卡价格
        $data = null;
        $status = 6;
        $coupon_text = [
            [
                'title' => '【优惠超值】',
                'text' => '购买后可获得4张15元优惠券（满600可用），4张10元优惠券（满600可用）',
            ],
            [
                'title' => '【立减体验】',
                'text' => '购买后可及时针对当天提交的订单（未用优惠券）全部实施立减',
            ],
            [
                'title' => '【叠加优惠】',
                'text' => '月卡优惠基础上可继续叠加享受奖励优惠券（摇色子奖励6元券）',
            ],
            [
                'title' => '【超长时效】',
                'text' => '优惠券按批次失效，每批2张；失效时间按购买日当天起7天后，14天后，21天后，28天后分批失效',
            ],
            [
                'title' => '【复购无忧】',
                'text' => '月卡没有明确的周期限制，当月内可重复购买，满足订货量大的客户需求',
            ]
        ];
        $data['title'] = '购买月卡订货更便宜';
        $data['content'] = $coupon_text;
        if(!empty($month_card_coupon)){
            $status = 1;
            foreach ($month_card_coupon as $one_c){
                $money += $one_c['money_value'];
            }
            $card_msg = "【花{$month_card_price}省{$money}】";
            $data['card_msg'] = $card_msg;
            $data['month_card_pric'] = $month_card_price;
        }else{
            //没有优惠券数据
            $data = [];
        }
        sdk_return($data,$status,'获取成功');
    }

    /**
     * 购买月卡之后的优惠券列表
     */
    public function getBuyMonthCard(){
        $param = $this->request->param();
        $order_sn = !empty($param['order_sn']) ? $param['order_sn'] : sdk_return('',6,'参数缺失');
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');
        $user_openid = is_sns($user_openid);
//        $coupon_list = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_coupon')->where('buy_order_sn = "'.$order_sn.'"')->select();
        $coupon_list = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_coupon')->alias('a')->leftJoin('ims_bb_base_coupon b','a.coupon_id = b.id')->where('a.buy_order_sn = "'.$order_sn.'"')->field('a.id,b.limit_money,b.money_value,a.coupon_status,a.time_end,a.order_id')->order(['a.time_end'=>'asc','b.money_value'=>'desc'])->select();
        $now_time = time();
        $order_use_num = 0;
        $return_data = [];
        $coupon_data = [];
        $show_type = 0;
        if(!empty(count($coupon_list))){
            foreach ($coupon_list as $one){
                $data = [];
                $data['coupon_title'] = '全品类通用优惠券';
                $data['money_value'] = $one['money_value'];
                $data['limit_money'] = $one['limit_money'];
                $data['limit_money_msg'] = '满'.$one['limit_money'].'可用';
                //计算到期时间
                $surplus_time = $one['time_end'] - $now_time;
                $surplus_date = date('Y-m-d',$one['time_end']);
                $surplus_day = ceil($surplus_time / (60*60*24));
                $data['time_end'] = $surplus_date;
                $data['time_day'] = $surplus_day;
                $data['coupon_status'] = $one['coupon_status'];
                $data['surplus_msg'] = $surplus_date.'到期（剩'.$surplus_day.'天）';
                if(!empty($one['order_id'])){
                    $order_use_num = $order_use_num + 1;
                }
                $coupon_data[] = $data;
            }
            $return_data['coupon_data'] = $coupon_data;
            $return_data['order_use_num'] = $order_use_num;
            if(!empty($order_use_num)){
                $show_type = 1;
            }
            $return_data['show_type'] = $show_type;
            $return_data['show_msg'] = '您今日有'.$order_use_num.'个未用券订单，已成功用券';
        }
        sdk_return($return_data,1,'操作成功');
    }

    /**
     * 取消月卡订单
     */
    public function cancelMonthCardOrder(){
        $param = $this->request->param();
        $order_sn = !empty($param['order_sn']) ? $param['order_sn'] : sdk_return('',6,'参数缺失');
        Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('ordersn = "'.$order_sn.'"')->update(['status'=>-1]);//取消月卡订单
        sdk_return('',1,'操作成功');
    }
}