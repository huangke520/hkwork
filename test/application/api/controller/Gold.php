<?php
/**
 * ceadr 2019-08-13
 */

namespace app\api\controller;

use app\api\controller\BaseController;
use app\api\model\ydxq\MemberMonthLevel;
use think\Loader;
use app\api\model\ydxq\ExchangeGoods;
use app\api\model\ydxq\ExchangeOrder;
use app\api\model\ydxq\MemberLog;
use think\Db;

class Gold extends BaseController {
    private $gold = ['one_gold','two_gold'];

    public function __construct() {
        parent::__construct();
        $this->gold = [
            'one_gold'  =>  get_task_gold(2),
            'two_gold'  =>  get_task_gold(3),
        ];
    }
    public function goods_list($id) {
        echo $id;
    }

    /*
    * 创建商品
    */
    public function add_goods() {
        $param = $this->request_param;
        if (!$param['sid'] || !$param['title'] || !$param['ex_type']) {
            sdk_return('', 0, '参数缺失');
        }
        $arr = [
            'supplier_id' => $param['sid'],
            'title' => $param['title'],
            'ex_type' => $param['ex_type'],//兑换方式：1：金币，2：现金+金币
            'level_limit' => $param['level'] ? $param['level'] : 0,
            'cycle_limit' => $param['cycle'] ? $param['cycle'] : 0,
            'send_limit' => $param['send'] ? $param['send'] : 0,
            'stock' => $param['stock'],
            'img' => $param['img'],
            'money' => $param['money'],
            'gold' => $param['gold'],
            'createtime' => time(),
            'modtime' => time(),
            'is_deleted' => 0
        ];
        $goods = new ExchangeGoods();
        $ret = $goods->insertInfo($arr);
        if (!$ret) {
            sdk_return('', 0, '添加失败');
        } else {
            sdk_return('', 1, '添加成功');
        }
    }

    /*
  * 创建商品
  */
    public function getGoldGoods() {
        $param = $this->request_param;
        if (empty($param['goods_id'])) {
            sdk_return('', 0, '参数缺失');
        }
        //查询商品信息
        $goods_res = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where([['id', '=', $param['goods_id']]])->find();
        if (!empty($goods_res)) {
            sdk_return($goods_res, 1, '查询成功');
        } else {
            sdk_return('', 0, '未查到数据');
        }
    }

    /**
     * 修改兑换商品信息
     */
    public function editGoldGoods() {
        $param = $this->request_param;
        if (empty($param['goods_id'])) {
            sdk_return('', 0, '参数缺失');
        }
        $arr = [
            'supplier_id' => $param['sid'],
            'title' => $param['title'],
            'ex_type' => $param['ex_type'],
            'level_limit' => $param['level'] ? $param['level'] : 0,
            'cycle_limit' => $param['cycle'] ? $param['cycle'] : 0,
            'send_limit' => $param['send'] ? $param['send'] : 0,
            'stock' => $param['stock'],
            'img' => $param['img'],
            'money' => $param['money'],
            'gold' => $param['gold'],
            'modtime' => time(),
            'is_deleted' => 0
        ];
        $goods = new ExchangeGoods();
        if (!empty($param['goods_id'])) {
            $ret = $goods->updateInfo($param['goods_id'], $arr);
        }
        if ($ret) {
            sdk_return('', 1, '修改成功');
        } else {
            sdk_return('', 0, '修改失败');
        }
    }

    /*
    * 金币列表
    */
    public function goodsList() {
        $param = $this->request_param;
        if (!$param['sid']) {
            sdk_return('', 0, '参数缺失');
        }
        $goods = new ExchangeGoods();
        $sid = $param['sid'];
        $level = isset($param['level']) ? $param['level'] : 0;
        $where = ' and is_deleted = 0 ';
        if ($level > 0) {
            $where .= ' and level_limit = ' . $level;
        }
        $sql = 'SELECT id,title,img,ex_type,level_limit,send_limit,cycle_limit,stock,money,gold,from_unixtime(createtime) as createtime FROM ims_ewei_shop_exchange_goods where supplier_id = ' . $sid . $where . ' order by createtime desc';
        $list = $goods->querySql($sql);
        $list2 = array();
        foreach ($list as $key => $value) {
            if ($value['cycle_limit'] == 0) {
                $status = '不限次数';
            } elseif ($value['cycle_limit'] == 1) {
                $status = '每日限兑1次';
            } elseif ($value['cycle_limit'] == 2) {
                $status = '每周限兑1次';
            } elseif ($value['cycle_limit'] == 3) {
                $status = '每月限兑1次';
            } elseif ($value['cycle_limit'] == 4) {
                $status = '每月限兑2次';
            } elseif ($value['cycle_limit'] == 5) {
                $status = '每月限兑3次';
            } elseif ($value['cycle_limit'] == 6) {
                $status = '每月限兑4次';
            } elseif ($value['cycle_limit'] == 7) {
                $status = '每月限兑5次';
            }
            if($value['ex_type'] == 1){
                $value['money'] = 0;
            }
            $value['statusmsg'] = $status;
            $list2[] = $value;
        }
        sdk_return($list2, 1, '获取成功');
    }

    /*
    * 删除商品
    */
    public function remove() {
        $param = $this->request_param;
        if (!$param['openid'] || !$param['goodsid']) {
            sdk_return('', 0, '参数缺失');
        }
        $id = $param['goodsid'];
        $openid = $param['openid'];
        $supplier = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('openid', $openid)->find();
        if (!$supplier) {
            sdk_return('', 0, '用户未绑定店铺');
        }
        $goods = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where(['id' => $id, 'supplier_id' => $supplier['id'], 'is_deleted' => 0])->find();
        if (!$goods) {
            sdk_return('', 0, '商品不存在');
        }
        $arr = [
            'modtime' => time(),
            'is_deleted' => 1
        ];
        $ret = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where(['id' => $id, 'supplier_id' => $supplier['id'], 'is_deleted' => 0])->update($arr);
        if (!$ret) {
            sdk_return('', 0, '删除失败');
        } else {
            sdk_return('', 1, '删除成功');
        }
    }

    /*
    * 商品详情
    */
    public function info() {
        $param = $this->request_param;
        if (!$param['shop_openid'] || !$param['goodsid']) {
            sdk_return('', 0, '参数缺失');
        }
        $id = $param['goodsid'];
        $openid = $param['shop_openid'];
        $supplier = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('openid', $openid)->find();
        if (!$supplier) {
            sdk_return('', 0, '用户未绑定店铺');
        }
        $goods = new ExchangeGoods();
        $info = $goods->getInfo(['supplier_id' => $supplier['id'], 'id' => $id, 'is_deleted' => 0]);
        if ($info['cycle_limit'] == 0) {
            $status = '不限次数';
        } elseif ($info['cycle_limit'] == 1) {
            $status = '每日限兑1次';
        } elseif ($info['cycle_limit'] == 2) {
            $status = '每周限兑1次';
        } elseif ($info['cycle_limit'] == 3) {
            $status = '每月限兑1次';
        } elseif ($info['cycle_limit'] == 4) {
            $status = '每月限兑2次';
        } elseif ($info['cycle_limit'] == 5) {
            $status = '每月限兑3次';
        } elseif ($info['cycle_limit'] == 6) {
            $status = '每月限兑4次';
        } elseif ($info['cycle_limit'] == 7) {
            $status = '每月限兑5次';
        }
        $info['statusmsg'] = $status;
        sdk_return($info, 1, '请求成功');
    }

    /*
    * 商品编辑
    */
    public function edit() {
        $param = $this->request_param;
        if (!$param['sid'] || !$param['title'] || !$param['ex_type'] || !$param['send'] || !$param['goodsid']) {
            sdk_return('', 0, '参数缺失');
        }
        $goods = new ExchangeGoods();
        $info = $goods->getInfo(['supplier_id' => $param['sid'], 'id' => $param['goodsid'], 'is_deleted' => 0]);
        if (!$info) {
            sdk_return('', 0, '参数有误');
        }
        $arr = [
            'title' => $param['title'],
            'ex_type' => $param['ex_type'],
            'level_limit' => $param['level'] ? $param['level'] : 0,
            'cycle_limit' => $param['cycle'] ? $param['cycle'] : 0,
            'send_limit' => $param['send'] ? $param['send'] : 0,
            'stock' => $param['stock'],
            'img' => $param['img'],
            'money' => $param['money'],
            'gold' => $param['gold'],
            'modtime' => time(),
        ];
        $ret = $goods->updateInfo($param['goodsid'], $arr);
        if (!$ret) {
            sdk_return('', 0, '编辑失败');
        } else {
            sdk_return('', 1, '编辑成功');
        }
    }

    /*
    * 下单兑换
    */
    public function order() {
        $param = $this->request_param;
        $sid = $param['sid'];
        $openid = $param['openid'];
        $address = isset($param['addr_id']) ? $param['addr_id'] : '';
        $goodsid = $param['goodsid'];
        $type = $param['type'] ? $param['type'] : 1;
        $total = $param['total'] ? $param['total'] : 1;//订单数量，先默认为1

        $goods = new ExchangeGoods();
        $order = new ExchangeOrder();
        $info = $goods->getInfo(['id' => $goodsid, 'is_deleted' => 0]);
        //判断兑换品库存
        if($info['stock'] < $total){
            sdk_return('',6,'库存不足');
        }
        //判断今日是否还有剩余的可兑换数量
        //查询当前用户兑换了多少个当前商品
        $user_total = $this->getUserExchangeTotal($openid,$goodsid,$sid);
        if((($user_total + $total) > $info['num_limit']) && (!empty($info['num_limit']))){
            sdk_return('',6,'您已超出当日最大兑换数量');
        }

        //等级
//        $level = Db::connect('db_mini_mall')->table('ims_member_month_level')->where(['sup_id' => $sid, 'openid' => $openid])->find();
        //查询当月会员的等级
        $level_month_model = new MemberMonthLevel();
        $level_where = [
            ['openid','=',$openid],
            ['sup_id','=',$sid],
        ];
        $level_arr = $level_month_model->getInfoPro($level_where,'level,time_end','id desc');
        $level = !empty($level_arr['level']) ? $level_arr['level'] : 0;
        $time_end = !empty($level_arr['time_end']) ? $level_arr['time_end'] : 0;

        //查询上月会员的等级
        $last_month = date('Ym',strtotime('last month'));//上个月
        $last_level_where = [
            ['openid','=',$openid],
            ['sup_id','=',$sid],
            ['month','=',$last_month],
        ];
        $last_level_arr = $level_month_model->getInfoPro($last_level_where,'level,time_end','id desc');
        $last_level = !empty($last_level_arr['level']) ? $last_level_arr['level'] : 0;
        $last_time_end = !empty($last_level_arr['time_end']) ? $last_level_arr['time_end'] : 0;

        $level = (intval($level) > intval($last_level)) ? $level : $last_level;
        $time_end = (intval($level) > intval($last_level)) ? $time_end : $last_time_end;

        if ($info['level_limit'] > $level) {
            sdk_return('', 2, '您当前等级无兑换该商品权限');
        }

        //判断金额余额
        $gold = Db::connect('db_mini_mall')->table('ims_member_gold_count')->where(['openid' => $openid, 'sup_id' => $sid, 'status' => 1])->find();
        if ($gold['gold_count'] < ($info['gold'] * $total)) {
            sdk_return('', 2, '您当前金币数量不足');
        }
        $where[] = ['openid', '=', $openid];
        $where[] = ['supplier_id', '=', $sid];
        $where[] = ['goodsid', '=', $goodsid];
        $where[] = ['status', '>', 0];
        //兑换次数
        if ($info['cycle_limit'] == 1) {
            //每日
            $where[] = ['createtime', '>', strtotime(date('Y-m-d'))];
            $count = $order->getCount($where);
            if ($count >= 1) {
                sdk_return('', 2, '您当前已暂无兑换机会');
            }
        } elseif ($info['cycle_limit'] == 2) {
            //每周
            $zhouyi = date('Y-m-d', (time() - ((date('w', time()) == 0 ? 7 : date('w', time())) - 1) * 24 * 3600));
            $where[] = ['createtime', '>', strtotime($zhouyi)];
            $count = $order->getCount($where);
            if ($count >= 1) {
                sdk_return('', 2, '您当前已暂无兑换机会');
            }
        } elseif ($info['cycle_limit'] == 3) {
            //每月
            $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
            $where[] = ['createtime', '>', $yihao];
            $count = $order->getCount($where);
            if ($count >= 1) {
                sdk_return('', 2, '您当前已暂无兑换机会');
            }
        } elseif ($info['cycle_limit'] == 4) {
            //每月两次
            $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
            $where[] = ['createtime', '>', $yihao];
            $count = $order->getCount($where);
            if ($count >= 2) {
                sdk_return('', 2, '您当前已暂无兑换机会');
            }
        } elseif ($info['cycle_limit'] == 5) {
            //每月三次
            $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
            $where[] = ['createtime', '>', $yihao];
            $count = $order->getCount($where);
            if ($count >= 3) {
                sdk_return('', 2, '您当前已暂无兑换机会');
            }
        } elseif ($info['cycle_limit'] == 6) {
            //每月四次
            $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
            $where[] = ['createtime', '>', $yihao];
            $count = $order->getCount($where);
            if ($count >= 24) {
                sdk_return('', 2, '您当前已暂无兑换机会');
            }
        } elseif ($info['cycle_limit'] == 7) {
            //每月五次
            $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
            $where[] = ['createtime', '>', $yihao];
            $count = $order->getCount($where);
            if ($count >= 5) {
                sdk_return('', 2, '您当前已暂无兑换机会');
            }
        }
        //查询当前店铺是否是孙宇店铺类型
        $shop_data = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('id = '.$sid)->field('is_dist')->find();
        $is_dist = 0;
        if(!empty($shop_data['is_dist'])){
            $is_dist = 1;
        }

        //查询当天最近的一个正常订单，关联
        $start_time = strtotime(date('Y-m-d'));
        $end_time = strtotime("+1 day", $start_time);
        $shop_order_id = 0;
        $shop_order = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('is_month_card = 1 and supplier_id = '.$sid.' and openid = "'.$openid.'" and createtime > '.$start_time.' and status = 0 and createtime < '.$end_time)->field('id')->order('createtime','desc')->select();
        if(!empty(count($shop_order))){
            $shop_order_id = $shop_order[0]['id'];
        }

        //查询是第几次兑换商品
        $pay_count = $order->getCount([['openid','=',$openid],['supplier_id','=',$sid]]);

        //记录绑定时间
        $bind_time = 0;
        if(!empty($shop_order_id)){
            $bind_time = time();
        }

        //创建订单
        $orderno = 'HD' . date('YmdHis') . rand(1000, 9999);
        $orderinfo = [
            'goodsid' => $goodsid,
            'openid' => $openid,
            'supplier_id' => $sid,
            'orderno' => $orderno,
            'status' => 0,
            'money' => $info['money'] * $total,
            'gold' => $info['gold'] * $total,
            'delivery_type' => $type,
            'createtime' => time(),
            'is_dist_order' => $is_dist,//是否是孙宇店铺类型兑换订单
            'shop_order_id' => $shop_order_id,//关联的正常订单ID
            'bind_time' => $bind_time,
            'total' => $total,//订单商品数量
            'pay_count' => $pay_count + 1,
        ];
        if ($type == 1) {
            $addr = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_address')->where('id', $address)->find();
            $orderinfo['username'] = $addr['realname'];
            $orderinfo['mobile'] = $addr['mobile'];
            $orderinfo['address'] = $addr['address'];
        }
        $ret = $order->insertInfo($orderinfo);
        //修改库存
        Db::connect('db_mini_mall')->execute("UPDATE ims_ewei_shop_exchange_goods set stock = stock - {$total} where id = {$goodsid}");
        $exchange_order_id = $ret;//兑换订单ID
        if (!$ret) {
            sdk_return('', 0, '订单创建失败');
        }

        //创建交易
        if ($info['ex_type'] == 2 && $info['money'] > 0) {
            //交易订单
            $supplier = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('id', $sid)->find();
            $arr = [
                'uniacid' => 4,
                'openid' => $supplier['openid'],
                'type' => 120, //兑换
                'logno' => $orderno,
                'title' => '金币兑换',
                'createtime' => time(),
                'status' => 0,
                'money' => $info['money'] * $total,
                'pay_openid' => $openid
            ];
            $log = new MemberLog();
            $ret = $log->insertInfo($arr);
            if (!$ret) {
                sdk_return('', 0, '交易记录创建失败');
            }
            sdk_return(array('order_no' => $orderno,'is_money' => 1), 1, '订单创建成功');
        } elseif ($info['ex_type'] == 1) {
            //扣金币
            $num = $gold['gold_count'] - ($info['gold'] * $total);
            $ret = Db::connect('db_mini_mall')->table('ims_member_gold_count')->where(['openid' => $openid, 'sup_id' => $sid, 'status' => 1])->update(['gold_count' => $num, 'modtime' => time()]);
            if ($ret) {
                $arr = [
                    'type1' => 2,
                    'unionid' => '',
                    'openid' => $openid,
                    'sup_id' => $sid,
                    'goods_title' => $info['title'],
                    'goods_id' => $goodsid,
                    'order_id' => $orderno,
                    'gold_value' => $info['gold'] * $total,
                    'total' => $total,
                    'addtime' => time(),
                    'modtime' => time()
                ];
                //插入金币消费记录
                Db::connect('db_mini_mall')->table('ims_member_gold_list')->insert($arr);
                $code = mt_rand(1000, 9999);
                $result = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where('orderno', $orderno)->update(['status' => 1, 'paytime' => time(), 'exchange_code' => $code]);
                if ($result) {
                    sdk_return(['gold_order_no' => $orderno,'is_money' => 0], 1, '兑换成功');
//                    sdk_return(['gold_order_no' => 'HD201910071708449894'], 1, '兑换成功');
                } else {
                    sdk_return('', 0, '兑换失败');
                }
            } else {
                sdk_return('', 0, '兑换失败');
            }
        }
    }

    /**
     * 检查金币订单是否已经绑定了正常的订单
     * 2019年10月27日16:38:48，新接口
     */
    public function checkGoldOrder(){
        $param = $this->request->param();
        $gold_order_no = !empty($param['gold_order_no']) ? $param['gold_order_no'] : sdk_return('',6,'参数缺失');//金币兑换订单号
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');//店铺ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//用户openID
        $user_openid = is_sns($user_openid);
        //查询金币订单详情
        $gold_order_data = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where('supplier_id = '.$sup_id.' and openid = "'.$user_openid.'" and orderno = "'.$gold_order_no.'"')->field('shop_order_id')->find();
        $is_band_shop_order = 0;
        if(!empty($gold_order_data['shop_order_id'])){
            $is_band_shop_order = 1;
        }
        sdk_return(['is_band_shop_order' => $is_band_shop_order],1,'success');
    }

    /**
     * 检查金币是否足够
     * 2019年10月27日16:38:48，新接口
     */
    public function checkGold(){
        $param = $this->request->param();
        $gold_goods_id = !empty($param['goods_id']) ? $param['goods_id'] : sdk_return('',6,'参数缺失');//金币兑换品ID
        $total = !empty($param['total']) ? $param['total'] : sdk_return('',6,'参数缺失');//商品数量
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');//店铺ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('',6,'参数缺失');//店铺ID
        $user_openid = is_sns($user_openid);

        $goods = new ExchangeGoods();
        $info = $goods->getInfo(['id' => $gold_goods_id, 'is_deleted' => 0]);

        //判断库存
        if($total > $info['stock']){
            sdk_return('',6,'库存不足');
        }

        //查询当前用户兑换了多少个当前商品
        $user_total = $this->getUserExchangeTotal($user_openid,$gold_goods_id,$sup_id);
        if($user_total >= $info['num_limit']){
            sdk_return('',6,'今日已达到最大兑换上限，请明日再来');
        }
        if((($user_total + $total) > $info['num_limit']) && (!empty($info['num_limit']))){
            sdk_return('',6,'您已超出当日最大兑换数量');
        }else{
            //判断金额余额
            $gold = Db::connect('db_mini_mall')->table('ims_member_gold_count')->where(['openid' => $user_openid, 'sup_id' => $sup_id, 'status' => 1])->find();
            if ($gold['gold_count'] < ($info['gold'] * $total)) {
                sdk_return('', 6, '您当前金币数量不足');
            }else{
                sdk_return('', 1, '金币数量充足');
            }
        }
    }

    /**
     * 查询当日用户已兑换指定商品的数量
     * @param string $user_openid
     * @param int $goods_id
     * @param int $sup_id
     * @return int
     * @throws \think\Exception
     */
    private function getUserExchangeTotal($user_openid = '',$goods_id = 0,$sup_id = 0){
        //查询当天最近的一个正常订单，关联
        $start_time = strtotime(date('Y-m-d'));
        $end_time = strtotime("+1 day", $start_time);
        $user_total = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where('(status = 1 or status = 3) and supplier_id = '.$sup_id.' and openid = "'.$user_openid.'" and goodsid = '.$goods_id.' and createtime > '.$start_time.' and createtime <= '.$end_time)->field('sum(total) as all_total')->find();
        return !empty($user_total['all_total']) ? $user_total['all_total'] : 0;
    }

    /**
     * 获取兑换商品详情
     */
    public function getGoodsDetail(){
        $param = $this->request->param();
        $gold_goods_id = !empty($param['gold_goods_id']) ? $param['gold_goods_id'] : sdk_return('',6,'参数缺失');//兑换品ID
        $gold_goods_model = new ExchangeGoods();
        $goods_data = $gold_goods_model->getInfo(['id' => $gold_goods_id, 'is_deleted' => 0]);
        $cycle_limit_arr = ['不限',
            '每人每日限兑1次',
            '每人每周限兑1次',
            '每人每月限兑1次',
            '每人每月限兑2次',
            '每人每月限兑3次',
            '每人每月限兑4次',
            '每人每月限兑5次'];
        $level_limit_arr = [0 => '不限',
            2 => '普卡及以上会员专享',
            3 => '银卡及以上会员专享',
            4 => '金卡及以上会员专享',
            5 => '钻石卡会员专享'];
        $cycle_limit_msg = '';
        $level_limit_msg = '';
        $stock_msg = '';
        $num_limit_msg = '';
        $detail_img_arr = [];
        //兑换周期
        if(!empty($goods_data['cycle_limit'])){
            $cycle_limit_msg = $cycle_limit_arr[$goods_data['cycle_limit']];
        }
        //等级限制
        if(!empty($goods_data['level_limit'])){
            $level_limit_msg = $level_limit_arr[$goods_data['level_limit']];
        }
        //库存展示
        if($goods_data['stock'] <= 10){
            $stock_msg = '库存：'.$goods_data['stock'];
        }
        //兑换数量
        if($goods_data['num_limit'] > 0){
            $num_limit_msg = '每日兑换上限'.$goods_data['num_limit'];
        }
        //商品详情图片处理
        if(!empty($goods_data['detail_img'])){
            $detail_img_arr = json_decode($goods_data['detail_img'],true);
        }
        $return_data = [
            'goods_title' => $goods_data['title'],
            'img' => $goods_data['img'],
            'detail_img' => $detail_img_arr,//详情图片
            'gold' => $goods_data['gold'],
            'num_limit' => $goods_data['num_limit'],
            'level_limit' => $goods_data['level_limit'],
            'cycle_limit' => $goods_data['cycle_limit'],
            'cycle_limit_msg' => $cycle_limit_msg,
            'level_limit_msg' => $level_limit_msg,
            'stock' => $goods_data['stock'],
            'stock_msg' => $stock_msg,
            'num_limit_msg' => $num_limit_msg,
        ];
        sdk_return($return_data,1,'获取成功');
    }

    /*
    *C端福利商品
    */
    public function welfare_goods() {
        $param = $this->request_param;
        if ((empty($param['sup_id'])) || (empty($param['user_openid']))) {
            sdk_return('', 0, '参数丢失');
        }
        $comfrom = isset($param['comefroms']) ? $param['comefroms'] : 0;//1：会员中心，0：首页
//        $is_sns = strstr($param['shop_openid'], 'sns_wa');
//        if (!$is_sns) {
//            $openid = 'sns_wa_' . $param['shop_openid'];
//        } else {
//            $openid = $param['shop_openid'];
//        }
        $is_sns = strstr($param['user_openid'], 'sns_wa');
        if (!$is_sns) {
            $user_openid = 'sns_wa_' . $param['user_openid'];
        } else {
            $user_openid = $param['user_openid'];
        }
        //店铺
        $sup_id = $param['sup_id'];
        $supplier = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('id', $sup_id)->find();

        if (!$supplier) {
            sdk_return('', 0, '未绑定店铺');
        }
        $supplier_id = $supplier['id'];
        if ($comfrom == 0) {
            if($supplier['is_dist'] == 1){
                $list = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where(['supplier_id' => $supplier_id, 'is_deleted' => 0])->order(['sort'=>'asc'])->limit(2)->select();
            }else{
                //等级
                $level = $this->getMemberLevel($user_openid,$supplier_id);
                if (($level == 0) || ($level == 1)){
                    $where1 = [
                        ['supplier_id','=',$supplier_id],
                        ['is_deleted','=','0'],
                    ];
                    $list = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where($where1)->order('level_limit')->limit(2)->select();
                }else{
                    //同级商品
                    $where = [
                        ['supplier_id','=',$supplier_id],
                        ['is_deleted','=','0'],
                        ['level_limit','=',$level],
                    ];
                    $list = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where($where)->order('level_limit')->limit(2)->select();
                    if (count($list) < 2) {
                        $where = [
                            ['supplier_id','=',$supplier_id],
                            ['is_deleted','=','0'],
                            ['level_limit','<',$level],
                        ];
                        $list_xiao = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where($where)->order('level_limit','desc')->limit(2)->select();
                        if(count($list_xiao) >= 2){
                            array_push($list,$list_xiao[0]);
                            if(count($list) < 2){
                                array_push($list,$list_xiao[1]);
                            }
                        }elseif(count($list_xiao) > 0){
                            array_push($list,$list_xiao[0]);
                            if(count($list) < 2) {
                                $where = [
                                    ['supplier_id', '=', $supplier_id],
                                    ['is_deleted', '=', '0'],
                                    ['level_limit', '>', $level],
                                ];
                                $list_2 = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where($where)->order('level_limit')->limit(2)->select();
//                echo Db::connect('db_mini_mall')->getLastSql();exit;
                                if (count($list_2) >= 1) {
                                    array_push($list, $list_2[0]);
                                }
                            }
                        }else{
                            $where = [
                                ['supplier_id', '=', $supplier_id],
                                ['is_deleted', '=', '0'],
                                ['level_limit', '>', $level],
                            ];
                            $list_2 = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where($where)->order('level_limit')->limit(2)->select();
//                echo Db::connect('db_mini_mall')->getLastSql();exit;
                            if (count($list_2) >= 1) {
                                array_push($list, $list_2[0]);
                            }
                            if(count($list_2) >= 2){
                                if(count($list) < 2){
                                    array_push($list, $list_2[1]);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $list = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_goods')->where(['supplier_id' => $supplier_id, 'is_deleted' => 0])->order('createtime desc')->select();
        }
//        echo Db::connect('db_mini_mall')->getLastSql();exit;
//        print_r($list);
//echo 3;exit;
        $order = new ExchangeOrder();
        foreach ($list as $key => $info) {
            //周期限制
            if ($info['cycle_limit'] == 0) {
//                $status = '不限次数';
                $status = '每日兑换上限'.$info['num_limit'];
            } elseif ($info['cycle_limit'] == 1) {
                $status = '每日限兑1次';
            } elseif ($info['cycle_limit'] == 2) {
                $status = '每周限兑1次';
            } elseif ($info['cycle_limit'] == 3) {
                $status = '每月限兑1次';
            } elseif ($info['cycle_limit'] == 4) {
                $status = '每月限兑2次';
            } elseif ($info['cycle_limit'] == 5) {
                $status = '每月限兑3次';
            } elseif ($info['cycle_limit'] == 6) {
                $status = '每月限兑4次';
            } elseif ($info['cycle_limit'] == 7) {
                $status = '每月限兑5次';
            }
            $list[$key]['statusmsg'] = $status;
            //是否能兑换
            //兑换次数
            $list[$key]['is_exchange'] = 1;
            unset($where);
            $where[] = ['openid', '=', $user_openid];
            $where[] = ['supplier_id', '=', $supplier_id];
            $where[] = ['goodsid', '=', $info['id']];
            $where[] = ['status', '>', 0];
            if ($info['cycle_limit'] == 1) {
                //每日
                $where[] = ['createtime', '>', strtotime(date('Y-m-d'))];
                $count = $order->getCount($where);
                if ($count >= 1) {
                    $list[$key]['is_exchange'] = 0;
//                    unset($list[$key]);
//                    continue;
//                    sdk_return('', 2, '您当前已暂无兑换机会');
                }
            } elseif ($info['cycle_limit'] == 2) {
                //每周
                $zhouyi = date('Y-m-d', (time() - ((date('w', time()) == 0 ? 7 : date('w', time())) - 1) * 24 * 3600));
                $where[] = ['createtime', '>', strtotime($zhouyi)];
                $count = $order->getCount($where);
                if ($count >= 1) {
                    $list[$key]['is_exchange'] = 0;
                }
            } elseif ($info['cycle_limit'] == 3) {
                //每月
                $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
                $where[] = ['createtime', '>', $yihao];
                $count = $order->getCount($where);
                if ($count >= 1) {
                    $list[$key]['is_exchange'] = 0;
                }
            } elseif ($info['cycle_limit'] == 4) {
                //每月两次
                $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
                $where[] = ['createtime', '>', $yihao];
                $count = $order->getCount($where);
                if ($count >= 2) {
                    $list[$key]['is_exchange'] = 0;
                }
            } elseif ($info['cycle_limit'] == 5) {
                //每月三次
                $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
                $where[] = ['createtime', '>', $yihao];
                $count = $order->getCount($where);
                if ($count >= 3) {
                    $list[$key]['is_exchange'] = 0;
                }
            } elseif ($info['cycle_limit'] == 6) {
                //每月四次
                $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
                $where[] = ['createtime', '>', $yihao];
                $count = $order->getCount($where);
                if ($count >= 4) {
                    $list[$key]['is_exchange'] = 0;
                }
            } elseif ($info['cycle_limit'] == 7) {
                //每月五次
                $yihao = strtotime(date('Y-m', time()) . '-01 00:00:00');
                $where[] = ['createtime', '>', $yihao];
                $count = $order->getCount($where);
                if ($count >= 5) {
                    $list[$key]['is_exchange'] = 0;
                }
            }

//            $level = Db::connect('db_mini_mall')
//                ->table('ims_member_month_level')
//                ->where(['sup_id' => $supplier_id, 'openid' => $user_openid])
//                ->where('time_end','>',time())
//                ->value('level');

            //查询当月会员的等级
            $level_month_model = new MemberMonthLevel();
            $level_where = [
                ['openid','=',$user_openid],
                ['sup_id','=',$supplier_id],
            ];
            $level_arr = $level_month_model->getInfoPro($level_where,'level,time_end','id desc');
            $level = !empty($level_arr['level']) ? $level_arr['level'] : 0;
            $time_end = !empty($level_arr['time_end']) ? $level_arr['time_end'] : 0;

            //查询上月会员的等级
            $last_month = date('Ym',strtotime('last month'));//上个月
            $last_level_where = [
                ['openid','=',$user_openid],
                ['sup_id','=',$supplier_id],
                ['month','=',$last_month],
            ];
            $last_level_arr = $level_month_model->getInfoPro($last_level_where,'level,time_end','id desc');
            $last_level = !empty($last_level_arr['level']) ? $last_level_arr['level'] : 0;
            $last_time_end = !empty($last_level_arr['time_end']) ? $last_level_arr['time_end'] : 0;

            $level = (intval($level) > intval($last_level)) ? $level : $last_level;
            $time_end = (intval($level) > intval($last_level)) ? $time_end : $last_time_end;


            $list[$key]['is_level'] = 1;
            if ($info['level_limit'] == 2) {
                if($level < 2){
                    $list[$key]['is_level'] = 0;//会员未达到等级
                }
                $list[$key]['level'] = '普卡及以上会员专享';
            } elseif ($info['level_limit'] == 3) {
                if($level < 3){
                    $list[$key]['is_level'] = 0;//会员未达到等级
                }
                $list[$key]['level'] = '银卡及以上会员专享';
            } elseif ($info['level_limit'] == 4) {
                if($level < 4){
                    $list[$key]['is_level'] = 0;//会员未达到等级
                }
                $list[$key]['level'] = '金卡及以上会员专享';
            } elseif ($info['level_limit'] == 5) {
                if($level < 5){
                    $list[$key]['is_level'] = 0;//会员未达到等级
                }
                $list[$key]['level'] = '钻石卡及以上会员专享';
            }else{
                $list[$key]['level'] = '';
            }
            if(!$level){
                $list[$key]['is_level'] = 0;//会员未达到等级
            }
            $gold = Db::connect('db_mini_mall')->table('ims_member_gold_count')->where(['openid' => $user_openid, 'sup_id' => $supplier['id'], 'status' => 1])->value('gold_count');
            $list[$key]['is_gold'] = 1;
            if($info['gold'] > $gold){
                $list[$key]['is_gold'] = 0;//金币不足
            }
            if($supplier['is_dist'] == 1){
                $show_send = 0;//1：展示配送限制，0：不展示配送限制
            }else{
                $show_send = 1;//1：展示配送限制，0：不展示配送限制
            }
            $list[$key]['show_send'] = $show_send;//1：展示配送限制，0：不展示配送限制
        }
        if(!empty(count($list))){
            if($supplier['is_dist'] != 1){
                $list_data = arraySequence($list,'level_limit','SORT_ASC');
            }else{
                $list_data = $list;
            }
        }else{
            $list_data = array();
        }
//        $list_data = arraySequence($list,'level_limit','SORT_ASC');
        sdk_return($list_data, 1, '查询成功');
    }

    /*
    * 订单列表
    */
    public function orderList() {
        $param = $this->request_param;
        if (!$param['sid'] || !$param['user_openid']) {
            sdk_return('', 0, '参数丢失');
        }
        $is_sns = strstr($param['user_openid'], 'sns_wa');
        if (!$is_sns) {
            $openid = 'sns_wa_' . $param['user_openid'];
        } else {
            $openid = $param['user_openid'];
        }
        //0: C 端  1：B端
        $comefrom = isset($param['comefroms']) ? $param['comefroms'] : 0;
        $status = isset($param['status']) ? $param['status'] : 1;
        $sid = $param['sid'];
        $page = $param['page'] ? $param['page'] : 1;
        $type = $param['type'] ? $param['type'] : 0; //金币   金币+现金
        if ($comefrom == 0) {
            $orwhere = [];
            if ($status == 3) {
                $where['ims_ewei_shop_exchange_order.status'] = [-1, 3];
            } else {
//        $where['ims_ewei_shop_exchange_order.status'] = $status;
                $where['ims_ewei_shop_exchange_order.status'] = [0, 1];
            }
            $where['ims_ewei_shop_exchange_order.openid'] = $openid;
            //$orwhere['ims_ewei_shop_exchange_order.openid'] = $openid;
            if ($type > 0) {
                $where['ims_ewei_shop_exchange_goods.ex_type'] = $type;
                //$orwhere['ims_ewei_shop_exchange_goods.ex_type'] = $type;
            }
            $where['ims_ewei_shop_exchange_order.supplier_id'] = $sid;
            $list = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->leftJoin('ims_yd_supplier', 'ims_ewei_shop_exchange_order.supplier_id = ims_yd_supplier.id')->leftJoin('ims_ewei_shop_exchange_goods', 'ims_ewei_shop_exchange_order.goodsid = ims_ewei_shop_exchange_goods.id')->field(['ims_yd_supplier.name as supplier_name', 'ims_ewei_shop_exchange_goods.title', 'ims_ewei_shop_exchange_order.money', 'ims_ewei_shop_exchange_order.gold', Db::raw('from_unixtime(ims_ewei_shop_exchange_order.createtime) as paytime'), Db::raw('from_unixtime(ims_ewei_shop_exchange_order.modtime) as completetime'), 'ims_ewei_shop_exchange_order.exchange_code', 'ims_ewei_shop_exchange_order.orderno', 'ims_ewei_shop_exchange_order.delivery_type', 'ims_ewei_shop_exchange_order.status', 'ims_ewei_shop_exchange_goods.img', 'ims_ewei_shop_exchange_order.id as orderid','ims_ewei_shop_exchange_order.shop_order_id','ims_ewei_shop_exchange_order.is_dist_order','ims_ewei_shop_exchange_order.total'])->where(function ($query) use ($where) {
                $query->where($where);
            })->order('ims_ewei_shop_exchange_order.createtime desc')->page($page, 10)->select();
 //       echo Db::connect('db_mini_mall')->getLastSql();
        } else {
            $orwhere = [];
            if ($status == 3) {
                $orwhere['ims_ewei_shop_exchange_order.status'] = -1;
            }
            $where['ims_ewei_shop_exchange_order.status'] = $status;

            $where['ims_ewei_shop_exchange_order.supplier_id'] = $sid;
            if ($type > 0) {
                $where['ims_ewei_shop_exchange_goods.ex_type'] = $type;
            }
            $list = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->leftJoin('ims_yd_supplier', 'ims_ewei_shop_exchange_order.supplier_id = ims_yd_supplier.id')->leftJoin('ims_ewei_shop_exchange_goods', 'ims_ewei_shop_exchange_order.goodsid = ims_ewei_shop_exchange_goods.id')->field(['ims_yd_supplier.name as supplier_name', 'ims_ewei_shop_exchange_goods.title', 'ims_ewei_shop_exchange_order.money', 'ims_ewei_shop_exchange_order.pay_count', 'ims_ewei_shop_exchange_order.gold', Db::raw('from_unixtime(ims_ewei_shop_exchange_order.createtime) as createtime'), Db::raw('from_unixtime(ims_ewei_shop_exchange_order.modtime) as completetime'), 'ims_ewei_shop_exchange_order.exchange_code', 'ims_ewei_shop_exchange_order.orderno', 'ims_ewei_shop_exchange_order.delivery_type', 'ims_ewei_shop_exchange_order.status', 'ims_ewei_shop_exchange_order.username', 'ims_ewei_shop_exchange_order.mobile', 'ims_ewei_shop_exchange_order.address', 'ims_ewei_shop_exchange_order.openid', 'ims_ewei_shop_exchange_order.id as orderid','ims_ewei_shop_exchange_order.shop_order_id','ims_ewei_shop_exchange_order.is_dist_order','ims_ewei_shop_exchange_order.total'])->where(function ($query) use ($where) {
                $query->where($where);
            })->whereOr(function ($query) use ($orwhere) {
                $query->where($orwhere);
            })->order('createtime desc')->page($page, 10)->select();
 //       echo Db::connect('db_mini_mall')->getLastSql();
            $order = new ExchangeOrder();
            $where = [];
            foreach ($list as $key => $value) {
                $info = Db::connect('db_mini_mall')->table('ims_ewei_shop_member')->where('openid', $value['openid'])->find();
                $list[$key]['avatar'] = $info['avatar'];
                $list[$key]['nickname'] = $info['nickname'];
                $where[] = ['openid', '=', $openid];
                $where[] = ['supplier_id', '=', $sid];
                $where[] = ['status', '>', 0];
                $where[] = ['createtime', '<=', strtotime($value['createtime'])];
//                $list[$key]['order_count'] = $order->getCount($where);
                $list[$key]['order_count'] = $value['pay_count'];
                unset($where);
            }
        }
        foreach ($list as $key => $value) {
            $list[$key]['is_associated'] = 0;//associated：关联  是否关联
            $list[$key]['associated_order_sn'] = '';//所关联的订单号
            //查询关联的订单号
            if(($value['is_dist_order'] == 1) && ($value['shop_order_id'] != 0)){
                $order_sn = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->field('ordersn')->where('id = '.$value['shop_order_id'])->find();
                $list[$key]['associated_order_sn'] = !empty($order_sn['ordersn']) ? $order_sn['ordersn'] : '';
                $list[$key]['is_associated'] = 1;
            }
            if ($value['status'] == -1) {
                $list[$key]['msg'] = '已取消';
            }
            if ($value['status'] == 3) {
                $list[$key]['msg'] = '已完成';
            }
            if(empty($list[$key]['total'])){
                $list[$key]['total'] = 1;
            }
        }
        sdk_return($list, 1, '请求成功');
    }

    /*
    * 兑换订单取消
    */
    public function cancel() {
        $param = $this->request_param;
        if (!$param['openid'] || !$param['orderid']) {
            sdk_return('', 0, '参数丢失');
        }
        $openid = $param['openid'];
        $supplier = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('openid', $openid)->find();
        if (!$supplier) {
            sdk_return('', 0, '参数有误');
        }
        $order = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where(['supplier_id' => $supplier['id'], 'id' => $param['orderid'], 'status' => 1])->find();
        if (!$order) {
            sdk_return('', 0, '订单不存在');
        }
        $ret = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where(['supplier_id' => $supplier['id'], 'id' => $param['orderid'], 'status' => 1])->update(['status' => -1]);
        if (!$ret) {
            sdk_return('', 0, '取消失败');
        }
        //退还金币
        $gold = Db::connect('db_mini_mall')->table('ims_member_gold_count')->where(['openid' => $order['openid'], 'sup_id' => $supplier['id'], 'status' => 1])->value('gold_count');
        $num = $gold + $order['gold'];
        Db::connect('db_mini_mall')->table('ims_member_gold_count')->where(['openid' => $order['openid'], 'sup_id' => $supplier['id'], 'status' => 1])->update(['gold_count' => $num, 'modtime' => time()]);
        //插入退还金币记录
        $goods = new ExchangeGoods();
        $info = $goods->getInfo(['id' => $order['goodsid'], 'is_deleted' => 0]);
        $arr = [
            'type1' => 3,
            'unionid' => '',
            'openid' => $openid,
            'sup_id' => $order['supplier_id'],
            'goods_title' => $info['title'],
            'goods_id' => $order['goodsid'],
            'order_id' => $order['orderno'],
            'gold_value' => $order['gold'],
            'addtime' => time(),
            'modtime' => time()
        ];
        Db::connect('db_mini_mall')->table('ims_member_gold_list')->insert($arr);
        sdk_return('', 1, '取消成功');
    }

    /*
    * 兑换订单完成
    */
    public function complete() {
        $param = $this->request_param;
        if (!$param['openid'] || !$param['orderid']) {
            sdk_return('', 0, '参数丢失');
        }
        $openid = $param['openid'];
        $supplier = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where('openid', $openid)->find();
        if (!$supplier) {
            sdk_return('', 0, '参数有误');
        }
        $order = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where(['supplier_id' => $supplier['id'], 'id' => $param['orderid'], 'status' => 1])->find();
        if (!$order) {
            sdk_return('', 0, '订单不存在');
        }
        $ret = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where(['supplier_id' => $supplier['id'], 'id' => $param['orderid'], 'status' => 1])->update(['status' => 3]);
        if (!$ret) {
            sdk_return('', 0, '设置失败');
        }
        sdk_return('', 1, '订单完成');
    }

    /**
     * 查询用户获取的金币数量
     * @throws \think\Exception
     */
    public function getGold(){
        $param = $this->request_param;
        if((empty($param['sup_id'])) || (empty($param['openid'])) || (empty($param['money']))){
            sdk_return('','6','缺少参数');
        }
        //查询当前用户在当前店铺的金币数量
        $where = [
            ['sup_id','=',$param['sup_id']],
            ['openid','=',$param['openid']],
            ['type1','=',1],
            ['addtime','>',strtotime(date('Y-m-d'))],
            ['addtime','<=',strtotime(date('Y-m-d 23:59:59'))],
        ];
        $gold = Db::connect('db_mini_mall')->table('ims_member_gold_list')->field('gold_value')->where($where)->select();
        $gold_count = 0;
        if(!empty(count($gold))){
            foreach ($gold as $key => $value){
                $gold_count = $gold_count + $value['gold_value'];
            }
        }
        $toast = '支付成功可得';
        if($gold_count >= 50){
            $toast .= '0金币';
        }else{
            $add_gold = 50 - $gold_count;
            if($add_gold >= $param['money']){
                $toast .= $param['money'].'金币';
            }else{
                $toast .= $add_gold.'金币';
            }
        }
        //查询店铺是否有供应商
        $sup_arr = Db::connect('db_mini_mall')->table('ims_yd_supplier')->where([['id','=',$param['sup_id']]])->find();
        if($sup_arr['is_dist'] == 1){
            $data = [
                'toast' => '',
            ];
        }else{
            $data = [
                'toast' => $toast.'（每日最多获取50金币）',
            ];
        }

        sdk_return($data,1,'获取成功');
    }

    /**
     * 查询用户等级
     * @param string $openid
     * @param string $sup_id
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getMemberLevel($openid = '',$sup_id = ''){
        //查询当月会员的等级
        $level_month_model = new MemberMonthLevel();
        $level_where = [
            ['openid','=',$openid],
            ['sup_id','=',$sup_id],
        ];
        $level_arr = $level_month_model->getInfoPro($level_where,'level,time_end','id desc');
        $level = !empty($level_arr['level']) ? $level_arr['level'] : 0;
        $time_end = !empty($level_arr['time_end']) ? $level_arr['time_end'] : 0;
        if(time() > $time_end){
            $level = 1;
        }

        //查询上月会员的等级
        $last_month = date('Ym',strtotime('last month'));//上个月
        $last_level_where = [
            ['openid','=',$openid],
            ['sup_id','=',$sup_id],
            ['month','=',$last_month],
        ];
        $last_level_arr = $level_month_model->getInfoPro($last_level_where,'level,time_end','id desc');
        $last_level = !empty($last_level_arr['level']) ? $last_level_arr['level'] : 0;
        $last_time_end = !empty($last_level_arr['time_end']) ? $last_level_arr['time_end'] : 0;
        if(time() > $last_time_end){
            $last_level = 1;
        }

        $level = (intval($level) > intval($last_level)) ? $level : $last_level;
        $time_end = (intval($level) > intval($last_level)) ? $time_end : $last_time_end;
        return $level;
    }

    /**
     * 领取活动金币
     */
    public function getActivityGold() {
        $param = $this->request->param();
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('', 6, '参数缺失');//店铺ID
        $user_openid = !empty($param['user_openid']) ? $param['user_openid'] : sdk_return('', 6, '参数缺失');//用户openID
        $union_id = !empty($param['union_id']) ? $param['union_id'] : '';//用户union_id
        $task_id = !empty($param['task_id']) ? $param['task_id'] : sdk_return('', 6, '参数缺失');//领取的数据ID（taskID）
        $gift = !empty($param['gift']) ? $param['gift'] : sdk_return('', 6, '参数缺失');//领取的是哪一次的
//        $gold = !empty($param['gold']) ? $param['gold'] : sdk_return('', 6, '参数缺失');//领取数量
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
        //查询task数据
        $task_arr = Db::connect('db_mini_mall')->table('ims_yd_supplier_task')->where('id = '.$task_id)->find();
        //判断是否领取过金币
        if(!empty($task_arr[$is_gift])){
            sdk_return('',6,'您已领取过当次金币');
        }
        $gold = get_task_gold($gift);
        if(empty($is_gift)){
            sdk_return('',6,'领取天数错误');
        }
        $update = null;
        $update = [
            $is_gift => time(),
            $gift_type => 1,//1金币，2优惠券
            $gift_value => $gold,//值
        ];
        Db::connect('db_mini_mall')->table('ims_yd_supplier_task')->where('id = '.$task_id)->update($update);
        unset($update);
        $insert_gold_list = [
            'type1' => 4,//周活动领取金币
            'type2' => 3,//周活动领取金币
            'goods_title' => '周下单活动奖励',
            'unionid' => $union_id,
            'openid' => $user_openid,
            'sup_id' => $sup_id,
            'order_id' => 0,
            'goods_price' => 0,
            'gold_price' => 0,
            'gold_rate' => 0,
            'gold_value' => $gold,
            'status' => 1,
            'week' => !empty($task_arr['week']) ? $task_arr['week'] : 0,
            'addtime' => time(),
            'modtime' => time(),
        ];
        $gold_list_id = Db::connect('db_mini_mall')->table('ims_member_gold_list')->insertGetId($insert_gold_list);
        if(!empty($gold_list_id)){
            $gold_count = Db::connect('db_mini_mall')->table('ims_member_gold_count')->where('sup_id = '.$sup_id.' and openid = "'.$user_openid.'"')->find();
            if (!empty($gold_count)) {
                $new_gold_count = $gold_count['gold_count'] + $gold;
                Db::connect('db_mini_mall')->table('ims_member_gold_count')->where('sup_id = '.$sup_id.' and openid = "'.$user_openid.'"')->update(['gold_count' => $new_gold_count]);
            } else {
                $arr = [
                    'type' => 1,
                    'sup_id' => $sup_id,
                    'unionid' => $union_id,
                    'openid' => $user_openid,
                    'gold_count' => $gold,
                    'addtime' => time(),
                    'modtime' => time()
                ];
                Db::connect('db_mini_mall')->table('ims_member_gold_count')->insertGetId($arr);
            }
        }

        //查询当前领取状况
        $task_data = Db::connect('db_mini_mall')->table('ims_yd_supplier_task')->where('id = '.$task_id)->find();
        $content = '暂无可领取金币~~';
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
        /*//下单两次
        if($task_data['pay_count'] == 2){
            if($task_data['gift2'] == 0){
                $content = '您有 ' . $this->gold['one_gold'] . ' 金币待领取，请及时领取！';
            }
        }
        //下单三次及以上
        if($task_data['pay_count'] >= 3){
            if($task_data['gift2'] == 0 && $task_data['gift3'] == 0){
                $content = '您有 ' . array_sum($this->gold) . ' 金币待领取，请及时领取！';
            }else if($task_data['gift2'] != 0 && $task_data['gift3'] == 0){
                $content = '您有 ' . $this->gold['two_gold'] . ' 金币待领取，请及时领取！';
            }else if($task_data['gift2'] == 0 && $task_data['gift3'] != 0){
                $content = '您有 ' . $this->gold['one_gold'] . ' 金币待领取，请及时领取！';
            }
        }*/
        sdk_return(['content' => $content],1,'领取成功');
    }
}


?>