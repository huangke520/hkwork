<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\controller;

use app\admin\model\ydhl\RemoveParityProduct as RemoveParityProductModel;
use app\api\controller\BaseController;
use app\api\model\ydhl\ParityProduct;
use app\api\model\ydxq\MemberGoldCount;
use app\api\model\ydxq\MemberGoldList;
use app\api\model\ydxq\MemberLevelLog;
use app\api\model\ydxq\MemberMonthLevel;
use app\api\model\ydxq\MemberRedemptionVoucher;
use app\api\model\ydxq\MemberRuleLevel;
use app\api\model\ydxq\ShopBusiness;
use app\api\model\ydxq\ShopMemberLog;
use app\api\model\ydxq\ShopOrder;
use app\api\model\ydxq\Supplier as SupplierModel;
use app\api\model\ydxq\YdSupplierGift;

//use PHPExcel;
//use PHPExcel_IOFactory;
use think\Db;
use think\Debug;
use think\Exception;
use think\Loader;

class Supplier extends BaseController {
    protected $m_SupplierModel;

    public function __construct() {
        parent::__construct();
        //111
        $this->m_SupplierModel = new SupplierModel();
    }

    /**
     * @cc 店铺列表
     * @Author   seaboyer@163.com
     * @DateTime 2019-08-11
     * @return   [type]        [description]
     */
    public function index() {
        $where = array();
        $where[] = ['is_c', '=', 1];
        $where[] = ['is_t', '=', 0];
        $field = 'id,openid,name,nickname,create_time';
        $order = 'id desc';
        $list_data = $this->m_SupplierModel->getPageListPro($where, 15, $field, $order);
        //$this->assign("data", $data);
        //return view();
        if (!empty($list_data)) {
            $this->writeApiLog($this->request_action, $this->uid, json_encode(''), json_encode($list_data), 1);
            sdk_return($list_data);
        } else {
            $this->writeApiLog($this->request_action, $this->uid, json_encode(''), json_encode($list_data), 6);
            sdk_return('', 6, 'request为空');
        }
    }

    /**
     * @cc 添加店铺
     * @Author   seaboyer@163.com
     * @DateTime 2019-08-11
     * @return   [type]        [description]
     */
    public function add_project() {
        if ($this->request->isAjax()) {
            $param = input("post.");
            $validate = Loader::validate('Project');
            if (!$validate->check($param)) {
                return ['status' => 0, 'msg' => $validate->getError()];
            }
            $res = $this->m_SupplierModel->insertInfo($param);
            if ($res) {
                //logger("添加服务项目,数据为" . json_encode($param));
                return ['status' => 1, 'msg' => '添加成功', 'url' => url('store/project/index')];
            } else {
                return ['status' => 0, 'msg' => '添加失败'];
            }
        }
        return view();
    }

    /**
     * @cc 编辑店铺
     * @Author   seaboyer@163.com
     * @DateTime 2019-08-11
     * @return   [type]        [description]
     */
    public function edit_project() {
        if ($this->request->isAjax()) {
            $project_id = input("post.project_id");
            $param = input("post.");
            unset($param['project_id']);

            $validate = Loader::validate('Project');
            if (!$validate->check($param)) {
                return ['status' => 0, 'msg' => $validate->getError()];
            }
            if ($this->m_SupplierModel->updateInfo($project_id, $param)) {
                //logger("编辑项目ID为" . $project_id);
                return ['status' => 1, 'msg' => '保存成功', 'url' => url('store/project/index')];
            } else {
                return ['status' => 0, 'msg' => '保存失败'];
            }
        }
        $id = $this->request->param("id", 0, "intval");
        $data = $this->m_ProjectModel->getProjectInfo($id);
        $this->assign('data', $data);
        return view('add_project');
    }

    /**
     * @cc 删除店铺
     * @Author   seaboyer@163.com
     * @DateTime 2019-08-11
     * @return   [type]        [description]
     */
    public function delete_project() {
        $project_id = input("post.id");
        if ($this->m_SupplierModel->deleteInfo($project_id)) {
            logger("删除项目ID为" . $project_id);
            return ['status' => 1, 'msg' => ''];
        } else {
            return ['status' => 0, 'msg' => ''];
        }
    }

    /**
     * 判断用户等级
     * @param int $day_sum 月消费天数
     * @param int $money_sum 月消费金额
     * @param int $sup_id 店铺ID
     * @return bool|int
     */
    protected function level($day_sum = 0, $money_sum = 0, $sup_id = 0) {
        $rule = new MemberRuleLevel();
        if (empty($day_sum) || empty($money_sum) || empty($sup_id)) {
            return false;
        }
        //查询店铺是否是经销商
        $supplier_res = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where([['id', '=', $sup_id]])->field('is_dist')->find();
        if ($supplier_res['is_dist'] == 1) {
            //经销商的规则
            $where_day = [
                ['l_day1', '<=', $day_sum],
                ['l_day2', '>=', $day_sum],
                ['type', '=', 2],
            ];
            $where_money = [
                ['l_money1', '<=', $money_sum],
                ['l_money2', '>=', $money_sum],
                ['type', '=', 2],
            ];
        } else {
            $where_day = [
                ['l_day1', '<=', $day_sum],
                ['l_day2', '>=', $day_sum],
                ['type', '=', 1],
            ];
            $where_money = [
                ['l_money1', '<=', $money_sum],
                ['l_money2', '>=', $money_sum],
                ['type', '=', 1],
            ];
        }
        //查询天数的等级
        $day_level_res = $rule->getInfo($where_day);
        $day_level = !empty($day_level_res['l_level']) ? $day_level_res['l_level'] : 0;

        //查询金额的等级
        $money_level_res = $rule->getInfo($where_money);
        $money_level = !empty($money_level_res['l_level']) ? $money_level_res['l_level'] : 0;

        $level_res = intval($money_level) > intval($day_level) ? $day_level : $money_level;
        $level = !empty($level_res) ? $level_res : 1;
        return $level;
    }

    /**
     * 查询对应的升级礼
     * @param int $gift_id
     * @param int $level
     * @return array|bool
     */
    protected function shopGift($gift_id = 0, $level = 0) {
        if ((empty($gift_id)) || (empty($level))) {
            return true;
        }
        $shop_gift_model = new YdSupplierGift();
        $shop_gift_data = $shop_gift_model->getInfo([['id', '=', $gift_id]]);
        $gift_data = array();
        switch ($level) {
            case 2:
                //普卡升级礼
                $gift_data['gold_count'] = $shop_gift_data['level1_gold'];
                break;
            case 3:
                //银卡升级礼
                $gift_data['gold_count'] = $shop_gift_data['level2_gold'];
                break;
            case 4:
                //金卡升级礼
                $gift_data['gold_count'] = $shop_gift_data['level3_gold'];
                break;
            case 5:
                //钻石卡升级礼
                $gift_data['gold_count'] = $shop_gift_data['level4_gold'];
                break;
            default :
                break;
        }
        return $gift_data;
    }

    /**
     * 判断是否升级，并更新插入数据
     * @param int $sup_id 店铺ID
     * @param string $openid 用户openID
     * @param int $money 消费金额
     * @param string $order_sn 订单号
     * @param int $is_order 2区分流水或者1订单
     * @return bool|int
     */
    protected function upgradeMemberDo($sup_id = 0, $openid = '', $money = 0, $order_sn = '', $is_order = 1) {
        //初始化
        $return_level = [
            'level' => 0,
            'endtime' => 0,
        ];
        if (empty($sup_id) || empty($openid) || empty($money) || empty($order_sn)) {
            return $return_level;
        }

        $level_cry = [
            2 => 10,
            3 => 30,
            4 => 50,
            5 => 100,
        ];

        ini_set("max_execution_time", 300);

        $last_day = date('Ymd');//当前年月日
        $month = date('Ym');//当前年月
        $today_month = date('m');//当前月
        $year = date('Y');//当前年
        $db_mini_mall_member_month_level = new MemberMonthLevel();
        $db_mini_mall_level_log = new MemberLevelLog();

        //查询当前的用户是否在当前的店铺中已经是会员

        $where = [
            ['sup_id', '=', $sup_id],
            ['openid', '=', $openid],
//            ['month', '=', $month],
        ];
        $member_res = $db_mini_mall_member_month_level->getInfoPro($where, '', 'id desc');
//        echo $db_mini_mall_member_month_level->getLastSql();exit;
        if (!empty($member_res)) {
            if ($month == $member_res['month']) {
                $return_level['endtime'] = $member_res['time_end'];
                //说明存在，可能需要更新会员等级
                $day_count_data = $member_res['day_count'];
                $last_day_data = $member_res['last_day'];
                $money_data = $member_res['money'] + $money;//增加金额
                if ($last_day_data != $last_day) {
                    //说明不是同一天消费的
                    $last_day_data = $last_day;
                    $day_count_data = $day_count_data + 1;
                }
                //判断等级
                $level = $this->level($day_count_data, $money_data, $sup_id);
                if ($level > $member_res['level']) {
                    //需要更新   等级、总金额、总天数、开始时间（时间戳）、结束时间（时间戳）、最后一次统计的日期的第一笔的日期
                    $level_data = $level;
                    $time_start = time();
                    $time_end = strtotime("+2 month", strtotime(date('Y-m') . '-01'));
                    $update = [
                        'level' => $level_data,
                        'money' => $money_data,
                        'day_count' => $day_count_data,
                        'time_start' => $time_start,
                        'time_end' => $time_end,
                        'last_day' => $last_day_data,
                    ];
                    //记录升级记录
                    $upgrade_log = [
                        'sup_id' => $sup_id,
                        'openid' => $openid,
                        'level1' => $member_res['level'],
                        'level2' => $level,
                        'money1' => $member_res['money'],
                        'money2' => $money_data,
                        'money' => $money,
                        'order_sn' => $order_sn,
                        'createtime' => $time_start,
                    ];
                    $db_mini_mall_level_log->insertInfo($upgrade_log);//插入升级记录
                    //判断是否需要发放升级礼
                    //判断当前店铺是否配置了升级礼
                    $shop_gift_model = new SupplierModel();
                    $shop_gift_id_arr = $shop_gift_model->getInfo([['id', '=', $sup_id]]);
                    if (!empty($shop_gift_id_arr['gift_id'])) {
                        if ($level_data > 1) {
                            //发放升级礼
                            $gift_data = $this->shopGift($shop_gift_id_arr['gift_id'], $level_data);
                            if (!empty($gift_data)) {
                                $voucher = [
                                    'openid' => $openid,
                                    'sup_id' => $sup_id,
                                    'gold_count' => $gift_data['gold_count'],
                                    'day_time' => date('Y-m-d'),
                                    'add_time' => time(),
                                    'update_time' => time(),
                                    'goods_id' => $shop_gift_id_arr['gift_id'],
                                    'level' => $level_data,
                                    'type' => 2,
                                ];
                                $voucher_model = new MemberRedemptionVoucher();
                                $voucher_model->insertInfo($voucher);
                            }
                        }
                    }
                    //当前判断当前订单是否发放过水晶
//                    ['type'=>2,'eff_time'=>$effect_time,'crystal'=>50,'supplier_id'=>$supplier_id,'openid'=>$openid]
                    $crystal = $this->crystal_number($member_res['level'],$level);
                    $cry_where = [
                        ['type', '=', 2],
                        ['eff_time', '=', $time_end],
                        ['crystal', '=', $crystal],
                        ['supplier_id', '=', $sup_id],
                        ['openid', '=', $openid],
                        ['crystal_info', '=', $order_sn],
                    ];
                    $crystal_data = Db::connect('db_mini_mall')->table('ims_yd_supplier_crystal_log')->where($cry_where)->find();
                    if(empty($crystal_data)){
                        //增加水晶
                        $this->add_crystal($sup_id,$openid,$crystal,$time_end,$order_sn);
                    }
                } else {
                    $update = [
                        'money' => $money_data,
                        'day_count' => $day_count_data,
                        'last_day' => $last_day_data,
                    ];
                }
                //更新
                $db_mini_mall_member_month_level->updateInfo($member_res['id'], $update);
            } else {
                $level = $this->level(1, $money, $sup_id);
                $time_start = time();
                $time_end = strtotime("+2 month", strtotime(date('Y-m') . '-01'));
                $insert_data = [
                    'sup_id' => $sup_id,
                    'openid' => $openid,
                    'month' => $month,
                    'level' => $level,
                    'money' => $money,
                    'day_count' => 1,
                    'time_start' => $time_start,
                    'time_end' => $time_end,
                    'createtime' => $time_start,
                    'last_day' => $last_day,
                ];
                $db_mini_mall_member_month_level->insertInfo($insert_data);
            }
        } else {
            $return_level['endtime'] = 0;
            //需要插入
            //判断等级
            $sum_money = 0;//月总消费金额
            $sum_count = 0;//月总消费次数
            $sum_day = 0;//记录消费天数
            $month_start = strtotime(date('Y-m'));
            $month_end = strtotime("+1 month", $month_start);
            $month = date("Ym", $month_start);
            $day_num = date("t", strtotime($month_start));
            unset($date_arr);
            for ($i = 1; $i <= $day_num; $i++) {
                $date_arr[] = $today_month . '-' . $i;
            }
            //查询店铺的openID
            $shop_openid_arr = $db_mini_mall_member_month_level->querySql("SELECT openid from ims_yd_supplier where id = {$sup_id}");
            $shop_openid = !empty($shop_openid_arr[0]['openid']) ? $shop_openid_arr[0]['openid'] : '';
            //查询当前用户的当月的所有消费记录
            $user_money_sql = "SELECT pay_openid,money,createtime FROM ims_ewei_shop_member_log where (type = 80 or type = 100) and status=1 and openid = '" . $shop_openid . "' and createtime >= {$month_start} and createtime < {$month_end}  and pay_openid = '{$openid}'";
            $user_money = $db_mini_mall_member_month_level->querySql($user_money_sql);

            $user_money_arr = array();
            if (!empty(count($user_money))) {
                foreach ($user_money as $k => $v) {
                    $user_money_arr[$v['pay_openid']][] = $v;
                }
                foreach ($date_arr as $one_date) {
                    $day_start = strtotime($year . '-' . $one_date);
                    $day_end = strtotime("+1 day", $day_start);
                    $day_money = 0;//天消费金额
                    $user_count = 0;//天消费次数
                    if (!empty($user_money_arr[$v['pay_openid']])) {
                        $user_money_res = $user_money_arr[$v['pay_openid']];
                        foreach ($user_money_res as $money_one) {
                            if (($money_one['createtime'] >= $day_start) && $money_one['createtime'] < $day_end) {
                                $day_money = $day_money + $money_one['money'];//天消费金额
                                $user_count = $user_count + 1;//天消费次数
                            }
                        }
                    }
                    //计算总数
                    $sum_money = $sum_money + $day_money;//月总消费金额
                    $sum_count = $sum_count + $user_count;//月总消费次数
                    if (!empty($user_count)) {
                        $sum_day = $sum_day + 1;//记录消费天数
                    }
                }
                //查询等级
                $level = $this->level($sum_day, $sum_money, $sup_id);
                $time_start = time();
                $time_end = strtotime("+2 month", strtotime(date('Y-m') . '-01'));
                $insert_data = [
                    'sup_id' => $sup_id,
                    'openid' => $openid,
                    'month' => $month,
                    'level' => $level,
                    'money' => $sum_money,
                    'day_count' => $sum_day,
                    'time_start' => $time_start,
                    'time_end' => $time_end,
                    'createtime' => $time_start,
                    'last_day' => $last_day,
                ];
                $db_mini_mall_member_month_level->insertInfo($insert_data);
                //记录升级记录
                $upgrade_log = [
                    'sup_id' => $sup_id,
                    'openid' => $openid,
                    'level1' => 0,
                    'level2' => $level,
                    'money1' => 0,
                    'money2' => $sum_money,
                    'money' => $sum_money,
                    'order_sn' => $order_sn,
                    'createtime' => $time_start,
                ];
                $db_mini_mall_level_log->insertInfo($upgrade_log);//插入升级记录
            }
        }

        //查询当月会员的等级
        $level_month_model = new MemberMonthLevel();
        $level_where = [
            ['openid', '=', $openid],
            ['sup_id', '=', $sup_id],
        ];
        $level_arr = $level_month_model->getInfoPro($level_where, 'level,time_end', 'id desc');
        $level = !empty($level_arr['level']) ? $level_arr['level'] : 0;
        $time_end = !empty($level_arr['time_end']) ? $level_arr['time_end'] : 0;

        //查询上月会员的等级
        $last_month = date('Ym', strtotime('last month'));//上个月
        $last_level_where = [
            ['openid', '=', $openid],
            ['sup_id', '=', $sup_id],
            ['month', '=', $last_month],
        ];
        $last_level_arr = $level_month_model->getInfoPro($last_level_where, 'level,time_end', 'id desc');
        $last_level = !empty($last_level_arr['level']) ? $last_level_arr['level'] : 0;
        $last_time_end = !empty($last_level_arr['time_end']) ? $last_level_arr['time_end'] : 0;

        $level = (intval($level) > intval($last_level)) ? $level : $last_level;
        $time_end = (intval($level) > intval($last_level)) ? $time_end : $last_time_end;

        $return_level['level'] = $level;
        $return_level['endtime'] = $time_end;
        return $return_level;
    }

    /**
     * 判断增加多少水晶
     * @param $old_level
     * @param $new_level
     * @return int
     */
    public function crystal_number($old_level,$new_level){
        $crystal = 0;
        if($old_level == 1){
            if($new_level == 2){
                $crystal = 10;
            }
            if($new_level == 3){
                $crystal = 40;
            }
            if($new_level == 4){
                $crystal = 90;
            }
            if($new_level == 5){
                $crystal = 190;
            }
        }
        if($old_level == 2){
            if($new_level == 3){
                $crystal = 30;
            }
            if($new_level == 4){
                $crystal = 80;
            }
            if($new_level == 5){
                $crystal = 180;
            }
        }
        if($old_level == 3){
            if($new_level == 4){
                $crystal = 50;
            }
            if($new_level == 5){
                $crystal = 150;
            }
        }
        if($old_level == 4){
            if($new_level == 5){
                $crystal = 100;
            }
        }
        return $crystal;
    }

    /**
     * 金币记录写入
     * @param int $is_order 区分流水或者订单
     * @param string $order_no 交易订单号
     */
    protected function gold($is_order = 1, $order_no = '') {
        if ($is_order == 1) {
//            $order = pdo_get('ewei_shop_order',array('ordersn'=>$order_no));//查询订单信息
            $order_model = new ShopOrder();
            $order_where = [
                ['ordersn', '=', $order_no],
            ];
            $order = $order_model->getInfo($order_where);
//            $supplier = pdo_get('yd_supplier',array('id'=>$order['supplier_id']));//查询店铺信息
            $supplier_model = new SupplierModel();
            $supplier_where = [
                ['id', '=', $order['supplier_id']],
            ];
            $supplier = $supplier_model->getInfo($supplier_where);
            $gold = $order['price'] * ($supplier['gold_rate'] ? $supplier['gold_rate'] : 1);
            $supplier_id = $order['supplier_id'];
            $openid = $order['openid'];
            $unionid = $order['unionid'];
            $price = $order['price'];
            $rate = ($supplier['gold_rate'] ? $supplier['gold_rate'] : 1);
            $is_open = $supplier['gold_open'];
        } else {
//            $order = pdo_get('ewei_shop_member_log',array('logno'=>$order_no,'status'=>1));
            $ShopMemberLog = new ShopMemberLog();
            $ShopMemberLog_where = [
                ['logno', '=', $order_no],
                ['status', '=', 1],
            ];
            $order = $ShopMemberLog->getInfo($ShopMemberLog_where);
//            $supplier = pdo_get('yd_supplier',array('openid'=>$order['openid']));
            $supplierModel = new SupplierModel();
            $supplier_where = [
                ['openid', '=', $order['openid']],
            ];
            $supplier = $supplierModel->getInfo($supplier_where);
            $gold = $order['money'] * ($supplier['gold_rate'] ? $supplier['gold_rate'] : 1);
            $supplier_id = $supplier['id'];
            $openid = $order['pay_openid'];
            $unionid = $supplier['unionid'];
            $price = $order['money'];
            $rate = ($supplier['gold_rate'] ? $supplier['gold_rate'] : 1);
            $is_open = $supplier['gold_open'];
        }
        $param = [
            'type1' => 1,
            'type2' => $is_order,
            'unionid' => $unionid,
            'openid' => $openid,
            'sup_id' => $supplier_id,
            'order_id' => $order_no,
            'goods_price' => $price,
            'gold_price' => $price,
            'gold_rate' => $rate,
            'gold_value' => $gold,
            'status' => $is_open,
            'addtime' => time(),
            'modtime' => time(),
        ];
//        $ret = pdo_insert('member_gold_list',$param);
        $member_gold_list = new MemberGoldList();
        $ret = $member_gold_list->insertInfo($param);
        //开启金币时 进行更改金币总额
        if ($is_open == 1) {
//            $sup_gold = pdo_get('member_gold_count',array('sup_id'=>$supplier_id,'openid'=>$openid));
            $member_gold_count = new MemberGoldCount();
            $member_gold_count_where = [
                ['sup_id', '=', $supplier_id],
                ['openid', '=', $openid],
            ];
            $sup_gold = $member_gold_count->getInfo($member_gold_count_where);
            if (!empty($sup_gold)) {
                $gold = $sup_gold['gold_count'] + $gold;
//                pdo_update('member_gold_count',array('gold_count'=>$gold,'modtime'=>time()),array('sup_id'=>$supplier_id,'openid'=>$openid));
                $update_count = [
                    'gold_count' => $gold,
                    'modtime' => time(),
                ];
                $member_gold_count->updateInfoPro($member_gold_count_where, $update_count);
            } else {
                $arr = [
                    'type' => 1,
                    'sup_id' => $supplier_id,
                    'unionid' => $unionid,
                    'openid' => $openid,
                    'gold_count' => $gold,
                    'addtime' => time(),
                    'modtime' => time()
                ];
//                pdo_insert('member_gold_count',$arr);
                $member_gold_count->insertInfo($arr);
            }
        }
    }

    /**
     * 支付完成之后操作会员信息入口1
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function upgradeMember() {
        $request = $this->request_param;
        $order_sn = !empty($request['order_sn']) ? $request['order_sn'] : sdk_return('', 6, '参数错误');//订单号
        $is_order = !empty($request['is_order']) ? $request['is_order'] : sdk_return('', 6, '参数错误');//区分2：流水，1：订单

        //查询消费金额
        if ($is_order == 1) {
            //订单（购物车下单）
            $shop_order = new ShopOrder();
            $order_where = [
                ['ordersn', '=', $order_sn],
            ];
            $money_arr = $shop_order->getInfoPro($order_where, 'openid,price,supplier_id');
            if (empty($money_arr)) {
                sdk_return('', 6, '没有查到购物车订单信息1');
            }
        } elseif ($is_order == 2) {
            //流水（扫码下单）
            $ShopMember_order = new ShopMemberLog();
            $ShopMember_order_where = [
                ['logno', '=', $order_sn],
                ['status', '=', 1],
                ['type', '=', 80],
            ];
            $ShopMember = $ShopMember_order->getInfoPro($ShopMember_order_where, 'pay_openid,money,openid');
            //查询用户openID，金额，店铺ID
            if (!empty($ShopMember)) {
                $sup_id_arr_model = new SupplierModel();
                $sup_id_arr_model_where = [
                    ['openid', '=', $ShopMember['openid']],
                ];
                $sup_id_arr = $sup_id_arr_model->getInfoPro($sup_id_arr_model_where, 'id');
                $money_arr['supplier_id'] = $sup_id_arr['id'];
                $money_arr['openid'] = $ShopMember['pay_openid'];
                $money_arr['price'] = $ShopMember['money'];
            } else {
                sdk_return('', 6, '没有查到扫码订单信息1');
            }
        }
        $sup_id = $money_arr['supplier_id'];
        $openid = $money_arr['openid'];
        $money = $money_arr['price'];
        //处理金币
//            $this->gold($is_order,$order_sn);
        //判断升级操作
        $level = $this->upgradeMemberDo($sup_id, $openid, $money, $order_sn, $is_order);
        if (!empty($level['level'])) {
            $data = [
                'level' => $level['level'],
                'endtime' => $level['endtime'],
            ];
            sdk_return($data, 1, 'success');
        } else {
            sdk_return('', 6, '未查询到等级信息');
        }
    }

    //水晶写入
    public function add_crystal($supplier_id, $openid, $crystal, $eff_time,$order_sn = '') {
        $param = [
            'supplier_id' => $supplier_id,
            'openid' => $openid,
            'crystal' => $crystal,
            'addtime' => time(),
            'type' => 2,
            'eff_time' => $eff_time,
            'crystal_info' => $order_sn
        ];
        Db::connect('db_mini_mall')->table('ims_yd_supplier_crystal_log')->insert($param);
        $sup = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where([['id', '=', $supplier_id]])->find();
        if (!empty($sup)) {
            $crystal = $sup['crystal'] + $crystal;
            $up_param['crystal'] = $crystal;
            Db::connect('db_mini_mall')->table('ims_yd_supplier')->where([['id', '=', $supplier_id]])->update($up_param);
        }
    }

    /********************************************************************************************************/

    public function updateSupplier() {
        return true;
        //查询服务范围为空的店铺
        $where = [
            ['is_c', '=', '1']
        ];
        $supplier = Db::connect('db_mini_mall')->table('ims_yd_supplier')->field('id,un_id,unionid')->where($where)->select();
        $shop_business = [
            '烟酒茶糖',
            '冷饮批发',
            '零食百货',
            '日杂百货',
            '热销果品',
            '米面粮油',
        ];
        if (!empty(count($supplier))) {
            foreach ($supplier as $s_k => $s_v) {
                foreach ($shop_business as $k => $v) {
                    $arr['unionid'] = !empty($s_v['un_id']) ? $s_v['un_id'] : $s_v['unionid'];
                    $arr['b_name'] = $v;
                    $arr['status'] = 1;
                    $arr['platform_id'] = $s_v['id'];
                    $arr['addtime'] = time();
                    $arr['modtime'] = time();
                    $shop_business_model = new ShopBusiness();
                    $shop_business_model->insertInfo($arr);
                    unset($arr);
                }
                $supplier_model = new SupplierModel();
                $parm['purpose_server'] = '诚信经营  服务至上';
                $parm['shop_img'] = 'ydxq/img/system/xcx/img/web/890.png';
                $supplier_model->updateInfo($s_v['id'], $parm);
                unset($parm);
            }
        }
    }

    public function goodsRes() {
//        exit('goodsRes');
        \think\facade\Debug::remark('begin');
//        $goods = Db::connect('db_mini_mall')->table('bsj_parity_product')->alias('a')->leftJoin('bsj_parity_compare');

        //查询所有带goods_code的商品
        $shop_goods_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->leftJoin('ims_yd_supplier c', 'b.supplier_id = c.id')->where([['c.city_name', '<>', '']])->where('a.goods_code', 'not null')->where('b.supplier_id', 'not null')->field('a.id,a.title,a.goods_code,b.supplier_price,b.supplier_id,c.city_name')->select();
//        echo Db::connect('db_mini_mall')->getLastSql();exit;

        $order = 'priceCount desc';//报价数量倒叙
//        $order = '_score desc';//积分倒序
        $data = array();
        //查询分类
        $category = Db::connect('db_ydhl')->table('bsj_parity_cate')->where([['pid', '>', 0]])->select();
        foreach ($category as $key => $value) {
            $goods = Db::connect('db_ydhl')->table('bsj_parity_product')->where([['categorySecondId', '=', $value['id']]])->field('id,barcode,name,_score,brandName,brandScore,priceCount,categoryFirstName,categorySecondName,specValue,unitValue,spec,lowestPrice,highestPrice')->order($order)->limit(50)->select();
//            echo "<pre>";print_r($goods);exit;
            foreach ($goods as $g_k => $g_v) {
                $g_v['goods_id'] = 0;
                $g_v['fugai'] = 0;
                $g_v['chonghedu'] = 0;
                $g_v['bj_fugai'] = 0;
                $g_v['zhenzhou_fugai'] = 0;
                $g_v['redu'] = 0;
                $g_v['goods_id'] = 0;
                $g_v['supplier_price'] = 0;
                $g_v['baojiazuiduo'] = '';
                $g_v['baojiazuiduo_totalPrice'] = '';

                $g_v['channel'] = '';
                $g_v['min_totalPrice'] = '';

                $g_v['baishi'] = 0;
                $g_v['zhaongshang'] = 0;
                $g_v['yijiu'] = 0;
                $g_v['lianshang'] = 0;
                $g_v['meicai'] = 0;

                $g_v['xiadanshu'] = 0;
                $g_v['xiadankehushu'] = 0;
                $g_v['xiaoshouliang'] = 0;
                $g_v['xiaoshou_e'] = 0;

                $g_v['jd_name'] = '';
                $g_v['jd_brand'] = '';
                $g_v['jd_code'] = '';
                //查询当前商品在孙宇店铺的商品ID
                if (!empty($g_v['barcode'])) {
                    $code_arr = explode(',', $g_v['barcode']);
//                    echo "<pre>";
//                    print_r($code_arr);
                    if (!empty(count($code_arr))) {
                        $fugai = 0;
                        $bj_fugai = 0;
                        $zhenzhou_fugai = 0;
                        $chonghedu = 0;
                        foreach ($code_arr as $code_k => $code_v) {
                            //                            $barcode = $code_arr[0];
                            $barcode = trim($code_v);

                            //查询当前商品门店覆盖数
                            $one_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->where([['a.goods_code', '=', $barcode], ['b.supplier_id', '<>', 'null']])->group('b.supplier_id')->field('a.id')->select();
                            $fugai = $fugai + count($one_goods);
                            //查询商品在北京门店中出现的次数
                            $bj_one_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->leftJoin('ims_yd_supplier c', 'b.supplier_id = c.id')->where([['a.goods_code', '=', $barcode], ['c.city_name', '=', '北京']])->where('b.supplier_id', 'not null')->group('b.supplier_id')->field('a.id')->select();
                            echo Db::connect('db_mini_mall')->getLastSql();
                            exit;
                            $bj_fugai = $bj_fugai + count($bj_one_goods);
                            //查询商品在郑州门店中出现的次数
                            $zhenzhou_one_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->leftJoin('ims_yd_supplier c', 'b.supplier_id = c.id')->where([['a.goods_code', '=', $barcode], ['c.city_name', '=', '郑州']])->where('b.supplier_id', 'not null')->group('b.supplier_id')->field('a.id')->select();
                            $zhenzhou_fugai = $zhenzhou_fugai + count($zhenzhou_one_goods);

                            //查询重合度
                            $chongheduone_goods = Db::connect('db_mini_mall')->table('ims_goods_code_info')->where([['code', '=', $barcode]])->field('id')->group('sup_id')->select();
                            $chonghedu = $chonghedu + count($chongheduone_goods);
                        }

                        $g_v['chonghedu'] = $chonghedu;

                        $g_v['fugai'] = $fugai;
                        $g_v['bj_fugai'] = $bj_fugai;
                        $g_v['zhenzhou_fugai'] = $zhenzhou_fugai;
                        $barcode = trim($code_arr[0]);

                        //查询goods_code中的商品名，品牌，条码
                        $goods_code_res = Db::connect('db_mini_mall')->table('ims_goods_code')->where([['code', '=', $barcode]])->field('jd_name,brand,code')->find();
                        $g_v['jd_name'] = $goods_code_res['jd_name'];
                        $g_v['jd_brand'] = $goods_code_res['brand'];
                        $g_v['jd_code'] = $goods_code_res['code'];

                        //查询当前商品热销指数
                        $one_goods = Db::connect('db_history')->table('stat_goods')->where([['barcode', '=', $barcode], ['type', '=', 2]])->field('type_data')->find();
                        $g_v['redu'] = $one_goods['type_data'];

                        $one_goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->where([['a.goods_code', '=', $barcode], ['b.supplier_id', '=', '461']])->field('a.id')->find();
                        $g_v['goods_id'] = $one_goods['id'];
                        if (!empty($g_v['goods_id'])) {
                            //查询当前商品在孙宇店铺的售价
                            $one_goods = Db::connect('db_mini_mall')->table('ims_yd_supplier_goods')->where([['goods_id', '=', $g_v['goods_id']]])->field('supplier_price')->find();
                            $g_v['supplier_price'] = $one_goods['supplier_price'];

                            //查询报价最多的价格
                            $db_historyl = new ParityProduct();
                            $baojiazuiduo = $db_historyl->querySql("SELECT count(id) as num,totalPrice from bsj_parity_compare where skuId = '{$g_v['id']}' group by totalPrice order by num desc limit 1");
                            $g_v['baojiazuiduo'] = !empty($baojiazuiduo[0]['num']) ? $baojiazuiduo[0]['num'] : 0;
                            $g_v['baojiazuiduo_totalPrice'] = !empty($baojiazuiduo[0]['totalPrice']) ? $baojiazuiduo[0]['totalPrice'] : 0;

                            //查询最低报价渠道
                            $price_min = $db_historyl->querySql("SELECT totalPrice from bsj_parity_compare where skuId = '{$g_v['id']}' order by totalPrice asc limit 1");
                            if (!empty(count($price_min))) {
                                $channel = $db_historyl->querySql("SELECT totalPrice,channel from bsj_parity_compare where skuId = '{$g_v['id']}' and  totalPrice = '{$price_min[0]['totalPrice']}' order by totalPrice asc limit 1");
                                $channel_arr = array();
                                foreach ($channel as $one) {
                                    $channel_arr[] = $one['channel'];
                                }
                                $g_v['channel'] = implode(',', $channel_arr);
                                $g_v['min_totalPrice'] = !empty($channel[0]['totalPrice']) ? $channel[0]['totalPrice'] : 0;
                            }

                            $channel_price_arr = $db_historyl->querySql("SELECT totalPrice,channel,channelId from bsj_parity_compare where channelId in(444,461,1012,460,967) and skuId = '{$g_v['id']}' group by channelId");
                            $channel_total_res = '';
                            if (!empty(count($channel_price_arr))) {
                                foreach ($channel_price_arr as $c_k => $c_v) {
                                    if (!empty($c_v['channelId'] == 444)) {
                                        $g_v['baishi'] = $c_v['totalPrice'];
                                    }
                                    if (!empty($c_v['channelId'] == 461)) {
                                        $g_v['zhaongshang'] = $c_v['totalPrice'];
                                    }
                                    if (!empty($c_v['channelId'] == 1012)) {
                                        $g_v['yijiu'] = $c_v['totalPrice'];
                                    }
                                    if (!empty($c_v['channelId'] == 460)) {
                                        $g_v['lianshang'] = $c_v['totalPrice'];
                                    }
                                    if (!empty($c_v['channelId'] == 967)) {
                                        $g_v['meicai'] = $c_v['totalPrice'];
                                    }
                                }
                            }
                            //查询下单数，根据商品ID去order——goods表中查询出现的次数
                            $db_mini_mall = new SupplierModel();
                            $goods_order_count = $db_mini_mall->querySql("SELECT orderid from ims_ewei_shop_order_goods where goodsid = {$g_v['goods_id']} group by orderid");
                            $g_v['xiadanshu'] = count($goods_order_count);//订单数
                            //查询下单客户数
                            $user_order_order = $db_mini_mall->querySql("SELECT count(*) as num from ims_ewei_shop_order where id in(SELECT orderid from ims_ewei_shop_order_goods where goodsid = {$g_v['goods_id']} group by orderid) group by openid");
                            $g_v['xiadankehushu'] = count($user_order_order);//下单客户数
                            //查询销售量
                            $goods_order_sum = $db_mini_mall->querySql("SELECT sum(total) as total from ims_ewei_shop_order_goods where goodsid = {$g_v['goods_id']}");
                            $g_v['xiaoshouliang'] = !empty($goods_order_sum[0]['total']) ? $goods_order_sum[0]['total'] : 0;//下单客户数
                            //查询销售金额
                            $goods_order_price = $db_mini_mall->querySql("SELECT sum(price) as price from ims_ewei_shop_order_goods where goodsid = {$g_v['goods_id']}");
                            $g_v['xiaoshou_e'] = !empty($goods_order_price[0]['total']) ? $goods_order_price[0]['total'] : 0;//下单客户数
                        }

                        $data[] = [
                            $g_v['jd_name'],
                            $g_v['jd_brand'],
                            $g_v['jd_code'],
                            $g_v['chonghedu'],
                            $g_v['id'],
                            $g_v['goods_id'],
                            $g_v['barcode'],
                            $g_v['name'],
                            $g_v['_score'],
                            $g_v['brandName'],
                            $g_v['brandScore'],
                            $g_v['priceCount'],
                            $g_v['fugai'],
                            $g_v['bj_fugai'],
                            $g_v['zhenzhou_fugai'],
                            $g_v['redu'],
                            $g_v['categoryFirstName'],
                            $g_v['categorySecondName'],
                            $g_v['specValue'] . '/' . $this->toString($g_v['unitValue']),
                            $g_v['spec'],
                            $g_v['supplier_price'],
                            $g_v['lowestPrice'] . '--' . $g_v['highestPrice'],
                            $g_v['baojiazuiduo'] . '(' . $g_v['baojiazuiduo_totalPrice'] . ')',
                            $g_v['channel'] . '(' . $g_v['min_totalPrice'] . ')',
                            $g_v['baishi'],
                            $g_v['zhaongshang'],
                            $g_v['yijiu'],
                            $g_v['lianshang'],
                            $g_v['meicai'],
                            $g_v['xiadankehushu'],
                            $g_v['xiadanshu'],
                            $g_v['xiaoshouliang'],
                            $g_v['xiaoshou_e'],
                        ];
                    }
                }
            }
        }

        $title = ['商品名', '品牌', '条码', '重合度', 'skuid', 'goodsid(孙宇店)', '条码（，隔开）', '商品名', '单品分', '品牌', '品牌分', '报价数', '覆盖门店数', '北京门店覆盖数', '郑州门店覆盖数', '热销指数', '一级分类', '二级分类', '单品规格', '销售规格', '云店售价', '价格带', '报价最多的价格', '最低报价渠道', '百世店家', '中商惠民', '易久批', '链商', '美菜', '下单客户数', '订单数', '销售量', '销售总额'];
        \think\facade\Debug::remark('end');
//        echo \think\facade\Debug::getRangeTime('begin','end').'s';
        $this->exportExcel($title, $data, '商品数据分析表');
//        $this->exportExcel($lie, $goods_excel, '书新超市', './', true);
    }

    //组合价格详情数据
    //组合价格详情数据
    function check_compare($sku_id, $one_compare, $list_compare) {
        $arr = array();
        if (!empty($one_compare)) {
            //$arr = array();
            $arr1 = array();
            if (isset($list_compare[$sku_id])) {
                $arr = $list_compare[$sku_id];
            }
            $arr['price_list'][] = $one_compare;
            $arr1 = $arr['price_list'];
            if (count($arr1) == 1) {
                $arr['price_min_money'] = $arr1[0]['totalPrice'];
                $arr['price_min_channel'] = $arr1[0]['channel'];
                $arr['price_more_money'] = $arr1[0]['totalPrice'];
                $arr['price_more_count'] = 1;

            } else {
                $min_price = 0;
                $all_price = null;
                foreach ($arr1 as $one_p) {
                    if (empty($min_price)) {
                        $min_price = $one_p['totalPrice'];
                    } elseif ($min_price > $one_p['totalPrice']) {
                        $min_price = $one_p['totalPrice'];
                    }

                    if (isset($all_price[$one_p['totalPrice']])) {
                        $all_price[$one_p['totalPrice']] = $all_price[$one_p['totalPrice']] + 1;
                    } else {
                        $all_price[$one_p['totalPrice']] = 1;
                    }
                }
                arsort($all_price);
                $i = 1;
                foreach ($all_price as $k => $v) {
                    if ($i == 1) {
                        $arr['price_more_money'] = $k;
                        $arr['price_more_count'] = $v;
                        break;
                    }
                }

                $min_channel = array();
                foreach ($arr1 as $one_pp) {
                    if ($one_pp['totalPrice'] == $min_price) {
                        if (!in_array($one_pp['channel'], $min_channel)) {
                            $min_channel[] = $one_pp['channel'];
                        }
                    }
                }
                $arr['price_min'] = $min_price;
                $arr['price_channel'] = implode(',', $min_channel);
                $arr['price_more_money'] = 1;
                $arr['price_more_count'] = 1;
            }
        }
        return $arr;
    }

    public function index_m1() {
        set_time_limit(0);
        //$res = array();
//        $m_RemoveModel = new RemoveParityProductModel();
        $m_RemoveModel = new ParityProduct();
        $st = time();
//        echo "a " . time() . "<br>";
        $sqlCompareAll = "SELECT skuId,totalPrice,channel FROM  bsj_parity_compare where id > 0 order by skuId asc";//channelId
        $parityCompareAll = $m_RemoveModel->querySql($sqlCompareAll);
        //var_dump($parityCompareAll);
//        echo "原始共" . count($parityCompareAll) . "条记录<br>";
        $sku_compare_list = array();
        $one_compare = array();
//        echo "b " . time() . "<br>";
        foreach ($parityCompareAll as $one) {
            $one_compare = null;
            $sku_id = $one['skuId'];
            unset($one['skuId']);
            $one_compare = $one;
            $sku_compare = $this->check_compare($sku_id, $one_compare, $sku_compare_list);
            $sku_compare_list[$sku_id] = $sku_compare;
        }
//        echo "c " . time() . "<br>";
//        echo "整理后" . count($sku_compare_list) . "条记录<br>";
//        foreach ($sku_compare_list as $k => $v) {
//            if ($v['price_count'] > 1) {
//                echo $k . ":";
//                var_dump($v);
//                echo "<br>";
//            }
//        }
        set_time_limit(30);
        return $sku_compare_list;
        return "up  data ok !";
    }

    public function goodsResV2() {
//        exit('goodsRes');
        \think\facade\Debug::remark('begin');
//        $goods = Db::connect('db_mini_mall')->table('bsj_parity_product')->alias('a')->leftJoin('bsj_parity_compare');
        //比价
        $db_historyl = new ParityProduct();
        $bijia = $db_historyl->querySql("SELECT skuId,totalPrice,channel from bsj_parity_compare ORDER BY skuId ASC,totalPrice ASC");

        $bijia_arr = $this->index_m1();

        //热度
        $stat_goods_data = Db::connect('db_history')->table('stat_goods')->where([['type', '=', 2]])->field('barcode,type_data')->select();
        $stat_goods_data_arr = array();
        foreach ($stat_goods_data as $one) {
            $stat_goods_data_arr[$one['barcode']] = $one;
        }
        unset($stat_goods_data);

        //查询所有带goods_code的商品
        $shop_goods_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->leftJoin('ims_yd_supplier c', 'b.supplier_id = c.id')->where([['c.city_name', '<>', '']])->where('a.goods_code', 'not null')->where('b.supplier_id', 'not null')->field('a.id,a.title,a.goods_code,b.supplier_price,b.supplier_id,c.city_name')->order('a.goods_code desc')->select();
//        echo Db::connect('db_mini_mall')->getLastSql();exit;
        $shop_goods_data_arr = array();
        foreach ($shop_goods_data as $one) {
            $shop_goods_data_arr[$one['supplier_id'] . $one['goods_code']] = $one;
        }

        $order = 'priceCount desc';//报价数量倒叙
//        $order = '_score desc';//积分倒序
        $data = array();
        //查询分类
        $category = Db::connect('db_ydhl')->table('bsj_parity_cate')->where([['pid', '>', 0]])->select();
        foreach ($category as $key => $value) {
            $goods = Db::connect('db_ydhl')->table('bsj_parity_product')->where([['categorySecondId', '=', $value['id']]])->field('id,barcode,name,_score,brandName,brandScore,priceCount,categoryFirstName,categorySecondName,specValue,unitValue,spec,lowestPrice,highestPrice')->order($order)->limit(50)->select();
//            echo count($category);
//            echo Db::connect('db_ydhl')->getLastSql();exit;
//            echo "<pre>";print_r($goods);exit;
            foreach ($goods as $g_k => $g_v) {
                $g_v['goods_id'] = 0;
                $g_v['fugai'] = 0;
                $g_v['chonghedu'] = 0;
                $g_v['bj_fugai'] = 0;
                $g_v['zhenzhou_fugai'] = 0;
                $g_v['redu'] = 0;
                $g_v['supplier_price'] = 0;
                $g_v['baojiazuiduo'] = '';
                $g_v['baojiazuiduo_totalPrice'] = '';

                $g_v['channel'] = '';
                $g_v['min_totalPrice'] = '';

                $g_v['baishi'] = 0;
                $g_v['zhaongshang'] = 0;
                $g_v['yijiu'] = 0;
                $g_v['lianshang'] = 0;
                $g_v['meicai'] = 0;

                $g_v['xiadanshu'] = 0;
                $g_v['xiadankehushu'] = 0;
                $g_v['xiaoshouliang'] = 0;
                $g_v['xiaoshou_e'] = 0;

                $g_v['jd_name'] = '';
                $g_v['jd_brand'] = '';
                $g_v['jd_code'] = '';
                //查询当前商品在孙宇店铺的商品ID
                if (!empty($g_v['barcode'])) {
                    $code_arr = explode(',', $g_v['barcode']);

                    if (!empty(count($code_arr))) {
                        $fugai = 0;

                        $beijing = array();
                        $zhengzhou = array();
                        foreach ($code_arr as $code_k => $code_v) {
                            $barcode = trim($code_v);
                            foreach ($shop_goods_data as $one_goods) {
                                if ($barcode == $one_goods['goods_code']) {
                                    //查询商品在北京门店中出现的次数
                                    if ($one_goods['city_name'] == '北京') {
                                        if (!in_array($one_goods['supplier_id'], $beijing)) {
                                            $beijing[] = $one_goods['supplier_id'];
                                        }
                                    }
                                    //查询商品在郑州门店中出现的次数
                                    if ($one_goods['city_name'] == '郑州') {
                                        if (!in_array($one_goods['supplier_id'], $zhengzhou)) {
                                            $zhengzhou[] = $one_goods['supplier_id'];
                                        }
                                    }
                                }
                            }
                        }

                        $bj_fugai = count($beijing);
                        $zhenzhou_fugai = count($zhengzhou);
                        $chonghedu = $bj_fugai + $zhenzhou_fugai;

                        $g_v['fugai'] = $chonghedu;
                        $g_v['bj_fugai'] = $bj_fugai;
                        $g_v['zhenzhou_fugai'] = $zhenzhou_fugai;
                        $g_v['chonghedu'] = $chonghedu;
                        /********************************************************************************************/
                        $barcode = trim($code_arr[0]);

                        //查询goods_code中的商品名，品牌，条码
                        $goods_code_res = Db::connect('db_mini_mall')->table('ims_goods_code')->where([['code', '=', $barcode]])->field('jd_name,brand,code')->find();
                        $g_v['jd_name'] = $goods_code_res['jd_name'];
                        $g_v['jd_brand'] = $goods_code_res['brand'];
                        $g_v['jd_code'] = $goods_code_res['code'];

                        //查询当前商品热销指数
                        $g_v['redu'] = !empty($stat_goods_data_arr[$barcode]) ? $stat_goods_data_arr[$barcode]['type_data'] : 0;

                        $g_v['supplier_price'] = !empty($shop_goods_data_arr['461' . $barcode]) ? $shop_goods_data_arr['461' . $barcode]['supplier_price'] : 0;
                        $g_v['goods_id'] = !empty($shop_goods_data_arr['461' . $barcode]) ? $shop_goods_data_arr['461' . $barcode]['id'] : 0;

                        $g_v['baojiazuiduo_totalPrice'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['price_more_money'] : '';
                        $g_v['baojiazuiduo'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['price_more_count'] : '';
                        $g_v['channel'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['price_min_channel'] : '';
                        $g_v['min_totalPrice'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['price_min_money'] : '';

//                        $channel_price_arr = $db_historyl->querySql("SELECT totalPrice,channel,channelId from bsj_parity_compare where channelId in(444,461,1012,460,967) and skuId = '{$g_v['id']}' group by channelId");
//                        if (!empty(count($channel_price_arr))) {
//                            foreach ($channel_price_arr as $c_k => $c_v) {
//                                if (!empty($c_v['channelId'] == 444)) {
//                                    $g_v['baishi'] = $c_v['totalPrice'];
//                                }
//                                if (!empty($c_v['channelId'] == 461)) {
//                                    $g_v['zhaongshang'] = $c_v['totalPrice'];
//                                }
//                                if (!empty($c_v['channelId'] == 1012)) {
//                                    $g_v['yijiu'] = $c_v['totalPrice'];
//                                }
//                                if (!empty($c_v['channelId'] == 460)) {
//                                    $g_v['lianshang'] = $c_v['totalPrice'];
//                                }
//                                if (!empty($c_v['channelId'] == 967)) {
//                                    $g_v['meicai'] = $c_v['totalPrice'];
//                                }
//                            }
//                        }

                        $g_v['baishi'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['baishi'] : '';
                        $g_v['zhaongshang'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['zhaongshang'] : '';
                        $g_v['yijiu'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['yijiu'] : '';
                        $g_v['lianshang'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['lianshang'] : '';
                        $g_v['meicai'] = !empty($bijia_arr[$g_v['id']]) ? $bijia_arr[$g_v['id']]['meicai'] : '';

                        if (!empty($g_v['goods_id'])) {

                            //查询下单数，根据商品ID去order——goods表中查询出现的次数
                            $db_mini_mall = new SupplierModel();
                            $goods_order_count = $db_mini_mall->querySql("SELECT orderid from ims_ewei_shop_order_goods where goodsid = {$g_v['goods_id']} group by orderid");
                            $g_v['xiadanshu'] = count($goods_order_count);//订单数
                            //查询下单客户数
                            $user_order_order = $db_mini_mall->querySql("SELECT count(*) as num from ims_ewei_shop_order where id in(SELECT orderid from ims_ewei_shop_order_goods where goodsid = {$g_v['goods_id']} group by orderid) group by openid");
                            $g_v['xiadankehushu'] = count($user_order_order);//下单客户数
                            //查询销售量
                            $goods_order_sum = $db_mini_mall->querySql("SELECT sum(total) as total from ims_ewei_shop_order_goods where goodsid = {$g_v['goods_id']}");
                            $g_v['xiaoshouliang'] = !empty($goods_order_sum[0]['total']) ? $goods_order_sum[0]['total'] : 0;//下单客户数
                            //查询销售金额
                            $goods_order_price = $db_mini_mall->querySql("SELECT sum(price) as price from ims_ewei_shop_order_goods where goodsid = {$g_v['goods_id']}");
                            $g_v['xiaoshou_e'] = !empty($goods_order_price[0]['total']) ? $goods_order_price[0]['total'] : 0;//下单客户数
                        }

                        $data[] = [
                            $g_v['jd_name'],
                            $g_v['jd_brand'],
                            $g_v['jd_code'],
                            $g_v['chonghedu'],
                            $g_v['id'],
                            $g_v['goods_id'],
                            $g_v['barcode'],
                            $g_v['name'],
                            $g_v['_score'],
                            $g_v['brandName'],
                            $g_v['brandScore'],
                            $g_v['priceCount'],
                            $g_v['fugai'],
                            $g_v['bj_fugai'],
                            $g_v['zhenzhou_fugai'],
                            $g_v['redu'],
                            $g_v['categoryFirstName'],
                            $g_v['categorySecondName'],
                            $g_v['specValue'] . '/' . $this->toString($g_v['unitValue']),
                            $g_v['spec'],
                            $g_v['supplier_price'],
                            $g_v['lowestPrice'] . '--' . $g_v['highestPrice'],
                            $g_v['baojiazuiduo'] . '(' . $g_v['baojiazuiduo_totalPrice'] . ')',
                            $g_v['channel'] . '(' . $g_v['min_totalPrice'] . ')',
                            $g_v['baishi'],
                            $g_v['zhaongshang'],
                            $g_v['yijiu'],
                            $g_v['lianshang'],
                            $g_v['meicai'],
                            $g_v['xiadankehushu'],
                            $g_v['xiadanshu'],
                            $g_v['xiaoshouliang'],
                            $g_v['xiaoshou_e'],
                        ];
                    }
                }
            }
        }

        $title = ['商品名', '品牌', '条码', '重合度', 'skuid', 'goodsid(孙宇店)', '条码（，隔开）', '商品名', '单品分', '品牌', '品牌分', '报价数', '覆盖门店数', '北京门店覆盖数', '郑州门店覆盖数', '热销指数', '一级分类', '二级分类', '单品规格', '销售规格', '云店售价', '价格带', '报价最多的价格', '最低报价渠道', '百世店家', '中商惠民', '易久批', '链商', '美菜', '下单客户数', '订单数', '销售量', '销售总额'];
        \think\facade\Debug::remark('end');
//        echo \think\facade\Debug::getRangeTime('begin','end').'s';
        $this->exportExcel($title, $data, '商品数据分析表');
//        $this->exportExcel($lie, $goods_excel, '书新超市', './', true);
    }

    function exportExcel($title = array(), $data = array(), $fileName = '', $savePath = './', $isDown = true) {
//        include_once 'PHPExcel-1.8/Classes/PHPExcel.php';
        require_once __DIR__ . '/../../../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
        $obj = new \PHPExcel();
        //横向单元格标识
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $obj->getActiveSheet(0)->setTitle('sheet名称');   //设置sheet名称
        $_row = 1;   //设置纵向单元格标识
        if ($title) {
            $_cnt = count($title);
            $obj->getActiveSheet(0)->mergeCells('A' . $_row . ':' . $cellName[$_cnt - 1] . $_row);   //合并单元格
            $obj->setActiveSheetIndex(0)->setCellValue('A' . $_row, '数据导出：' . date('Y-m-d H:i:s'));  //设置合并后的单元格内容
            $_row++;
            $i = 0;
            foreach ($title AS $v) {   //设置列标题
                $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i] . $_row, $v);
                $i++;
            }
            $_row++;
        }
        //填写数据
        if ($data) {
            $i = 0;
            foreach ($data AS $_v) {
                $j = 0;
                foreach ($_v AS $_cell) {
                    $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $_row), $_cell);
                    $j++;
                }
                $i++;
            }
        }
        //文件名处理
        if (!$fileName) {
            $fileName = uniqid(time(), true);
        }
        $objWrite = \PHPExcel_IOFactory::createWriter($obj, 'Excel5');
        if ($isDown) {   //网页下载
            ob_end_clean();
            header('pragma:public');
            header("Content-Disposition:attachment;filename=$fileName.xls");
            $objWrite->save('php://output');
            exit;
        }
        $_fileName = iconv("utf-8", "gb2312", $fileName);   //转码
        $_savePath = $savePath . $_fileName . '.xlsx';
        $objWrite->save($_savePath);
        return $savePath . $fileName . '.xlsx';
    }

    protected function toString($string = '') {
        $number = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0];
        return str_replace($number, '', $string);
    }
}