<?php

namespace app\api\controller;

use OSS\Core\OssException;
use think\Db;
use think\facade\Config;
use library\Oss;

class H5CaiGou extends BaseController {

    public function __construct() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET");
        parent::__construct();
    }

    /**
     * 查询供应商中的基本数据
     * @param int $sup_id
     * @param int $user_id
     * @param string $date1
     * @param string $date2
     * @return array
     * @throws \think\Exception
     */
    private function getSupDate($sup_id = 0,$user_id = 0,$date1 = '',$date2 = ''){
        if((empty($date1)) || (empty($user_id)) || (empty($date2))){
            $arr = [];
        }else{
            $where = '';
            $where2 = '';
            if(!empty($sup_id)){
                $where = ' and b.supplier_id = '.$sup_id;
                $where2 = ' and sup_id = '.$sup_id;
            }
            $date_arr = $this->getDate($date1,$date2);
            $start_time = $date_arr['start_time'];//选择日期的凌晨
            $end_time = $date_arr['end_time'];//第二天的凌晨
            $order_data = Db::connect('db_mini_mall')->table('ims_ewei_cai_gou_audit')->alias('a')->leftJoin('ims_ewei_shop_order b','a.order_id = b.id')->where('b.createtime > '.$start_time.' and b.createtime <= '.$end_time.' and a.status = 1 and user_id = '.$user_id.$where)->field('a.id,b.price,a.status')->select();
            //审核中数据
            $goods_kind_audit = 0;
            $all_price_audit = 0.00;
            $goods_kind_ok = 0;
            $all_price_ok = 0.00;
            if(!empty($order_data)){
                foreach ($order_data as $one){
                    if($one['status'] == 0){
                        //待审核
                        $goods_kind_audit = $goods_kind_audit + 1;//商品种类
                        $all_price_audit = $all_price_audit + $one['price'];//审核中的订单金额
                    }else{
                        //已通过
                        $goods_kind_ok = $goods_kind_ok + 1;//商品种类
                        $all_price_ok = $all_price_ok + $one['price'];//已通过的订单金额
                    }
                }
            }
            //查询总应收：已支付（财务审核已通过）/待支付（财务审核中）/当日全部订单
            $goods_kind_all = $goods_kind_audit + $goods_kind_ok;//全部商品种类
            $all_price_all = $all_price_audit + $all_price_ok;//全部订单价格
            //查询票据数量
            $order_date1 = str_replace('-','',$date1);
            $order_date2 = str_replace('-','',$date2);
            $bill_count = Db::connect('db_mini_mall')->table('ims_ewei_cai_gou_bill')->where('date1 <= '.$order_date1.' and date2 >= '.$order_date2.$where2)->count();
            $arr = [
                'goods_kind_audit_all' => $goods_kind_audit,//总应收中审核中品
                'goods_kind_ok_all' => $goods_kind_ok,//总应收中已通过品
                'goods_kind_all' => $goods_kind_all,//总应收中 总共
                'all_price_audit_all' => $all_price_audit,//总应收中审核中钱
                'all_price_ok_all' => $all_price_ok,//总应收中已通过钱
                'all_price_all' => $all_price_all,//总应收中 总共
                'bill_count' => $bill_count,//票据数量
            ];
        }
        return $arr;
    }

    /**
     * 查询当天是否上传了图片
     * @param int $sup_id
     * @param string $date1
     * @param string $date2
     * @return int
     * @throws \think\Exception
     */
    private function getBill($sup_id = 0,$date1 = '',$date2 = ''){
        if((empty($sup_id)) || (empty($date))){
            return 0;
        }
        $where2 = ' and sup_id = '.$sup_id;

        //查询票据数量
        $order_date1 = str_replace('-','',$date1);
        $order_date2 = str_replace('-','',$date2);
        $bill_count = Db::connect('db_mini_mall')->table('ims_ewei_cai_gou_bill')->where('date1 <= '.$order_date1.' and date2 >= '.$order_date2.$where2)->count();
        return $bill_count;
    }

    /**
     * 上传oss图片
     * @param string $local_path
     * @param string $oss_path
     * @param string $img_name
     * @return string
     */
    private function upOssImg($local_path = '', $oss_path = '', $img_name = '') {
        if (empty($local_path) || empty($oss_path) || empty($img_name)) {
            return '上传失败';
        }
        $ext = strrchr($local_path, '.');
        if (!in_array($ext, ['.jpg', '.png', '.jpeg', '.gif'])) {
            return '上传失败';
        }

        $aliYun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliYun_oss_config['Bucket'];
        // 上传阿里云服务器 oss
        try {
            // $fileName = $oss_path . '/' . $img_name . $local_temp_path['ext']; // oss路径文件
            $fileName = $oss_path . '/' . $img_name . $ext; // oss路径文件
            $oss = new Oss($aliYun_oss_config['KeyId'], $aliYun_oss_config['KeySecret'], $aliYun_oss_config['Endpoint'], true);
            $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_path);
            $res['oss_path'] = $fileName;
//            unlink($local_path);
            // return sdk_return($res, 0, '上传成功');
            return $res['oss_path'];
        } catch (OssException $e) {
            // return sdk_return([], 1, $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * 查询日期的开始结束时间
     * @param string $date1
     * @param string $date2
     * @return array
     */
    private function getDate($date1 = '',$date2 = ''){
        $date = [];
        if((empty($date1)) || (empty($date2))){
            return $date;
        }
        $date['start_time'] = strtotime($date1);//选择日期的凌晨
        $date['end_time'] = strtotime($date2) + (60 * 60 * 24);//第二天的凌晨
        return $date;
    }

    /**
     * 获取当前采购数据
     */
    public function getCGDate(){
        $param = $this->request->param();
        $date1 = !empty($param['date1']) ? $param['date1'] : sdk_return('',6,'参数缺失');//要查询的日期
        $date2 = !empty($param['date2']) ? $param['date2'] : sdk_return('',6,'参数缺失');//要查询的日期
        $user_id = !empty($param['user_id']) ? $param['user_id'] : sdk_return('',6,'参数缺失');//当前使用人的用户ID
        $date_arr = $this->getDate($date1,$date2);
        $start_time = $date_arr['start_time'];//选择日期的凌晨
        $end_time = $date_arr['end_time'];//第二天的凌晨
        //查询审核中品的种类和金额
        $order_data = Db::connect('db_mini_mall')->table('ims_ewei_cai_gou_audit')->alias('a')->leftJoin('ims_ewei_shop_order b','a.order_id = b.id')->where('b.createtime > '.$start_time.' and b.createtime <= '.$end_time.' and a.status = 1 and user_id = '.$user_id)->field('a.id,b.price,a.status')->select();
//        echo Db::connect('db_mini_mall')->getLastSql();exit;
        //审核中数据
        $goods_kind_audit = 0;
        $all_price_audit = 0.00;
        $goods_kind_ok = 0;
        $all_price_ok = 0.00;
        if(!empty($order_data)){
            foreach ($order_data as $one){
                if($one['status'] == 0){
                    //待审核
                    $goods_kind_audit = $goods_kind_audit + 1;//商品种类
                    $all_price_audit = $all_price_audit + $one['price'];//审核中的订单金额
                }else{
                    //已通过
                    $goods_kind_ok = $goods_kind_ok + 1;//商品种类
                    $all_price_ok = $all_price_ok + $one['price'];//已通过的订单金额
                }
            }
        }
        //查询总应收：已支付（财务审核已通过）/待支付（财务审核中）/当日全部订单
        $goods_kind_all = $goods_kind_audit + $goods_kind_ok;//全部商品种类
        $all_price_all = $all_price_audit + $all_price_ok;//全部订单价格
        //查询全部供应商
        $supplier_data = Db::connect('db_mini_mall')
            ->table('ims_yd_supplier')
            ->where('cai_gou = 1')
            ->field('id as sup_id,name')
            ->select();
        $supplier_arr = [];
        $sup_data_count = 0;
        if(!empty($supplier_data)){
            foreach ($supplier_data as $one_sup){
                $supplier_arr[$one_sup['sup_id']] = $one_sup;
                $sup_data_count = $sup_data_count + 1;
            }
        }
        $all = [
            "sup_id" => 0,
            "name" => '全部供应商',
        ];
        array_unshift($supplier_arr,$all);
        //查询总数
        $order_all = Db::connect('db_mini_mall')
            ->table('ims_ewei_shop_order')
            ->where('createtime > '.$start_time.' and createtime <= '.$end_time)
            ->where('status = 0 or status = 3')
            ->field('count(id) as goods_all,sum(price) as price_all')
            ->find();
        $goods_kind_all = !empty($order_all['goods_all']) ? $order_all['goods_all'] : 0;
        $all_price_all = !empty($order_all['price_all']) ? $order_all['price_all'] : 0;
        $return_data = [
            'goods_kind_audit' => $goods_kind_audit,//审核中品
            'all_price_audit' => $all_price_audit,//审核中钱
            'goods_kind_audit_all' => $goods_kind_audit,//总应收中审核中品
            'goods_kind_ok_all' => $goods_kind_ok,//总应收中已通过品
            'goods_kind_all' => $goods_kind_all,//总应收中 总共
            'all_price_audit_all' => $all_price_audit,//总应收中审核中钱
            'all_price_ok_all' => $all_price_ok,//总应收中已通过钱
            'all_price_all' => $all_price_all,//总应收中 总共
            'sup_data_count' => $sup_data_count,//供应商数量
            'sup_data' => $supplier_arr,//店铺信息
        ];
        sdk_return($return_data,1,'获取成功');
    }

    /**
     * 查询采购订单
     */
    public function getOrder(){
        $param = $this->request->param();
        $date1 = !empty($param['date1']) ? $param['date1'] : sdk_return('',6,'参数缺失');//查询的日期
        $date2 = !empty($param['date2']) ? $param['date2'] : sdk_return('',6,'参数缺失');//查询的日期
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : 0;//店铺ID
        $type = !empty($param['type']) ? $param['type'] : 1;//类型1：（已确认/待交货），2：（待确认/待交货），3：（付款审核中），4：（已付款）
        $page = !empty($param['page']) ? $param['page'] : 1;//页码
        $user_id = !empty($param['user_id']) ? $param['user_id'] : sdk_return('',6,'参数缺失');//当前使用人的用户ID
        $page_size = 10;
        $date_arr = $this->getDate($date1,$date2);
        $start_time = $date_arr['start_time'];//选择日期的凌晨
        $end_time = $date_arr['end_time'];//第二天的凌晨
        $where = 'b.is_month_card = 3 and b.createtime > '.$start_time.' and b.createtime <= '.$end_time;
        if(!empty($sup_id)){
            $where .= ' and b.supplier_id = '.$sup_id;
        }
        $return_order = [];
        $return_order_list = [];
        //查询供应商中的基本数据
        $sup_date = $this->getSupDate($sup_id,$user_id,$date1,$date2);
        $return_order[] = $sup_date;
        if(($type == 1) || ($type == 2)){
            $where .= ' and b.status = 0';
            if($type == 1){
                //查询：（已确认/待交货）订单商品数量和订单金额
                $where .= ' and b.is_affirm = 1';
            }else{
                //查询：（待确认/待交货）订单商品数量和订单金额
                $where .= ' and b.is_affirm = 0';
            }
            $order_list = Db::connect('db_mini_mall')
                ->table('ims_ewei_shop_order_goods')
                ->alias('a')
                ->leftJoin('ims_ewei_shop_order b','a.orderid = b.id')
                ->leftJoin('ims_ewei_shop_goods c','a.goodsid = c.id')
                ->where($where)
                ->field('a.id as order_goods_id,b.price,b.status,b.is_affirm,b.id as order_id,c.id as goods_id,c.title,c.thumb,a.realprice as one_price,a.price,a.total,b.ordersn,b.createtime,b.hang_id,b.paytime,b.update_time,b.affirm_time,b.supplier_id')
                ->paginate($page_size)->toArray();
            if(!empty($order_list['data'])){
                $order_list_data = $order_list['data'];
                foreach ($order_list_data as $one){
                    $one['create_date'] = !empty($one['createtime']) ? date('Y年m月d日H:i:s',$one['createtime']) : '';
                    //
                    $one['update_date'] = !empty($one['update_time']) ? date('Y年m月d日H:i:s',$one['update_time']) : '';
                    //
                    $one['affirm_date'] = !empty($one['affirm_time']) ? date('Y年m月d日H:i:s',$one['affirm_time']) : '';
                    //
                    $one['paytime_date'] = !empty($one['paytime']) ? date('Y年m月d日H:i:s',$one['paytime']) : '';
                    //查询原始订单
                    $order_change = Db::connect('db_mini_mall')->table('ims_member_order_change_log')->where('ordersn = "'.$one['ordersn'].'"')->field('count_old,old_price')->order('id','asc')->find();
                    $old_count = $one['total'];
                    $old_price = $one['one_price'];
                    if(!empty($order_change)){
                        $old_count = $order_change['count_old'];
                        $old_price = $order_change['old_price'];
                    }
                    $one['old_count'] = $old_count;
                    $one['old_one_price'] = $old_price;
                    $one['old_price'] = $old_count * $old_price;
                    //查询真实供应商
                    //查询customer_id
                    $customer_id_arr = Db::connect('db_mini_mall')
                        ->table('ims_fixup_hangup')
                        ->where('id = '.$one['hang_id'])
                        ->field('customer_id')
                        ->find();
                    $customer_id = !empty($customer_id_arr['customer_id']) ? $customer_id_arr['customer_id'] : 0;
                    $customer_arr = Db::connect('db_btj_new')
                        ->table('potential_customer')
                        ->where('id = '.$customer_id)
                        ->field('user_name')
                        ->find();
                    $one['customer_name'] = !empty($customer_arr['user_name']) ? $customer_arr['user_name'] : '';
                    $return_order_list[] = $one;
                }
            }
        }else{
//            $where = 'b.is_month_card = 3 and b.createtime > '.$start_time.' and b.createtime <= '.$end_time;
            $where = 'a.user_id = '.$user_id.' and a.create_time > '.$start_time.' and a.createtime <= '.$end_time;
            if($type == 3){
                //查询：（付款审核中）订单商品数量和订单金额
                $where .= ' and a.status = 0';
            }else{
                //查询：（已付款）订单商品数量和订单金额
                $where .= ' and a.status = 1';
            }
            $order_list = Db::connect('db_mini_mall')
                ->table('ims_ewei_cai_gou_audit')
                ->alias('a')
                ->leftJoin('ims_ewei_shop_order_goods b','a.order_id = b.orderid')
                ->leftJoin('ims_ewei_shop_goods c','b.goodsid = c.id')
                ->where($where)
                ->field('a.id as order_audit_id,a.create_time,a.update_time,c.id as goods_id,c.title,c.thumb,b.realprice as one_price,b.price,b.total,a.order_id,a.order_sn')
                ->paginate($page_size)->toArray();
            if(!empty($order_list['data'])){
                $order_list_data = $order_list['data'];
                foreach ($order_list_data as $one){
                    $hang_id = 0;
                    //时间处理
                    $one['create_date'] = !empty($one['create_time']) ? date('Y年m月d日H:i:s',$one['create_time']) : '';
                    //
                    $one['audit_date'] = !empty($one['update_time']) ? date('Y年m月d日H:i:s',$one['update_time']) : '';
                    $one['audit_time'] = !empty($one['update_time']) ? $one['update_time'] : '';
                    $one['update_time'] = '';
                    //查询订单数据
                    $one_order = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id = '.$one['order_id'])->field('id as order_id,createtime,update_time,paytime,affirm_time,hang_id,price,supplier_id')->find();
                    $hang_id = !empty($one_order['hang_id']) ? $one_order['hang_id'] : 0;
                    $one['price'] = !empty($one_order['price']) ? $one_order['price'] : '';
                    //
                    $one['supplier_id'] = !empty($one_order['supplier_id']) ? $one_order['supplier_id'] : '';
                    //
                    $one['update_date'] = !empty($one_order['update_time']) ? date('Y年m月d日H:i:s',$one_order['update_time']) : '';//订单最后编辑时间
                    $one['update_time'] = !empty($one_order['update_time']) ? $one_order['update_time'] : '';
                    //
                    $one['create_date'] = !empty($one_order['createtime']) ? date('Y年m月d日H:i:s',$one_order['createtime']) : '';
                    $one['createtime'] = !empty($one_order['createtime']) ? $one_order['createtime'] : '';
                    //
                    $one['paytime_date'] = !empty($one_order['paytime']) ? date('Y年m月d日H:i:s',$one_order['paytime']) : '';
                    $one['paytime'] = !empty($one_order['paytime']) ? $one_order['paytime'] : '';
                    //
                    $one['affirm_date'] = !empty($one_order['affirm_time']) ? date('Y年m月d日H:i:s',$one_order['affirm_time']) : '';
                    $one['affirm_time'] = !empty($one_order['affirm_time']) ? $one_order['affirm_time'] : '';
                    //查询原始订单
                    $order_change = Db::connect('db_mini_mall')->table('ims_member_order_change_log')->where('ordersn = "'.$one['order_sn'].'"')->field('count_old,old_price')->order('id','asc')->find();
                    $old_count = $one['total'];
                    $old_price = $one['one_price'];
                    if(!empty($order_change)){
                        $old_count = $order_change['count_old'];
                        $old_price = $order_change['old_price'];
                    }
                    $one['old_count'] = $old_count;
                    $one['old_one_price'] = $old_price;
                    $one['old_price'] = $old_count * $old_price;
                    //查询真实供应商
                    //查询customer_id
                    $customer_id_arr = Db::connect('db_mini_mall')
                        ->table('ims_fixup_hangup')
                        ->where('id = '.$hang_id)
                        ->field('customer_id')
                        ->find();
                    $customer_id = !empty($customer_id_arr['customer_id']) ? $customer_id_arr['customer_id'] : 0;
                    $customer_arr = Db::connect('db_btj_new')
                        ->table('potential_customer')
                        ->where('id = '.$customer_id)
                        ->field('user_name')
                        ->find();
                    $one['customer_name'] = !empty($customer_arr['user_name']) ? $customer_arr['user_name'] : '';
                    $return_order_list[] = $one;
                }
            }
        }
        $return_order[] = $return_order_list;
        sdk_return($return_order,1,'获取成功');
    }

    /**
     * 上传单据
     */
    public function uploadBill(){
        $param = $this->request->param();
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'请选择供应商');
        $date1 = !empty($param['date1']) ? $param['date1'] : sdk_return('',6,'请选择日期');
        $date2 = !empty($param['date2']) ? $param['date2'] : sdk_return('',6,'请选择日期');
        $file = request()->file('bill_img');
        // 移动到框架应用根目录/uploads/ 目录下
        $now_time = time();
        $file_name = $now_time.'-'.$sup_id;
        $dir = date('Ymd');
        $info = $file->move('uploads', $dir . '/' . $file_name);
        if ($info) {
            $oss_path = $this->upOssImg('./uploads/'.$info->getSaveName(),'ydxq/img/system/order/bill',$file_name);//上传到oss
            $order_date1 = str_replace('-','',$date1);
            $order_date2 = str_replace('-','',$date2);
            //添加数据
            $bill_one = [
                'sup_id' => $sup_id,
                'bill_img' => $oss_path,
                'status' => 0,
                'date1' => $order_date1,
                'date2' => $order_date2,
                'create_time' => $now_time,
                'update_time' => $now_time,
            ];
            Db::connect('db_mini_mall')->table('ims_ewei_cai_gou_bill')->insert($bill_one);
            unlink('./uploads/'.$dir.'/'.$file_name);
            sdk_return(['img_url'=>$oss_path],1,'上传成功');
        }else{
            sdk_return($file->getError(),1,'上传失败，错误信息：'.$file->getError());
        }
    }

    /**
     * 采购订单提交审核
     */
    public function submitCGOrder(){
        $param = $this->request->param();
        $date1 = !empty($param['date1']) ? $param['date1'] : sdk_return('',6,'参数缺失');//要查询的日期
        $date2 = !empty($param['date2']) ? $param['date2'] : sdk_return('',6,'参数缺失');//要查询的日期
        $user_id = !empty($param['user_id']) ? $param['user_id'] : sdk_return('',6,'参数缺失');//当前使用人的用户ID
        $order_id = !empty($param['order_id']) ? $param['order_id'] : sdk_return('',6,'参数缺失');//要提交审核的订单ID
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : sdk_return('',6,'参数缺失');//店铺ID
        $order_id_arr = json_decode($order_id,true);
        $now_time = time();
        $res = 0;
        //查询选择的日期下的票据数量
        $sup_bill = $this->getBill($sup_id,$date1,$date2);
        if(empty($sup_bill)){
            sdk_return('',6,'未上传单据，请先上传单据');
        }
        foreach ($order_id_arr as $one_order_id){
            //查询order_sn
            $order_sn_arr = Db::connect('db_mini_mall')
                ->table('ims_ewei_shop_order')
                ->where('id = '.$one_order_id)
                ->field('ordersn,supplier_id')
                ->find();
            $order_sn = !empty($order_sn_arr['ordersn']) ? $order_sn_arr['ordersn'] : '';
            $sup_id = !empty($order_sn_arr['sup_id']) ? $order_sn_arr['sup_id'] : 0;
            $one_insert = [
                'order_id' => $one_order_id,
                'order_sn' => $order_sn,
                'sup_id' => $sup_id,
                'user_id' => $user_id,
                'auditor' => 0,
                'status' => 0,
                'create_time' => $now_time,
                'update_time' => $now_time,
            ];
            $res = Db::connect('db_mini_mall')->table('ims_ewei_cai_gou_audit')->insert($one_insert);
            unset($one_insert);
        }
        if(!empty($res)){
            sdk_return('',1,'提交成功');
        }else{
            sdk_return('',1,'提交失败');
        }
    }

    /**
     * 待审核采购订单列表
     */
    public function getAudit(){
        $param = $this->request->param();
        $date1 = !empty($param['date1']) ? $param['date1'] : sdk_return('',6,'参数缺失');//选择的日期
        $date2 = !empty($param['date2']) ? $param['date2'] : sdk_return('',6,'参数缺失');//选择的日期
        $user_id = !empty($param['user_id']) ? $param['user_id'] : sdk_return('',6,'参数缺失');//当前使用人的用户ID
        $type = !empty($param['type']) ? $param['type'] : 1;//3：（付款审核中），4：（已付款）
        $sup_id = !empty($param['sup_id']) ? $param['sup_id'] : 0;//店铺ID
        $page = !empty($param['page']) ? $param['page'] : 1;
        $page_size = 10;
        $date_arr = $this->getDate($date1,$date2);
        $start_time = $date_arr['start_time'];//选择日期的凌晨
        $end_time = $date_arr['end_time'];//第二天的凌晨
        $where = 'a.user_id = '.$user_id.' and a.create_time > '.$start_time.' and a.createtime <= '.$end_time;
        if($type == 3){
            //查询：（付款审核中）订单商品数量和订单金额
            $where .= ' and a.status = 0';
        }else{
            //查询：（已付款）订单商品数量和订单金额
            $where .= ' and a.status = 1';
        }
        if(!empty($sup_id)){
            $where .= ' and a.sup_id = '.$sup_id;
        }
        $return_order_list = [];
        $order_list = Db::connect('db_mini_mall')
            ->table('ims_ewei_cai_gou_audit')
            ->alias('a')
            ->leftJoin('ims_ewei_shop_order_goods b','a.order_id = b.orderid')
            ->leftJoin('ims_ewei_shop_goods c','b.goodsid = c.id')
            ->where($where)
            ->field('a.id as order_audit_id,a.create_time,a.update_time,c.id as goods_id,c.title,c.thumb,b.realprice as one_price,b.price,b.total,a.order_id,a.order_sn')
            ->paginate($page_size)->toArray();
        if(!empty($order_list['data'])){
            $order_list_data = $order_list['data'];
            foreach ($order_list_data as $one){
                $hang_id = 0;
                //时间处理
                $one['create_date'] = !empty($one['create_time']) ? date('Y年m月d日H:i:s',$one['create_time']) : '';
                //
                $one['audit_date'] = !empty($one['update_time']) ? date('Y年m月d日H:i:s',$one['update_time']) : '';
                $one['audit_time'] = !empty($one['update_time']) ? $one['update_time'] : '';
                $one['update_time'] = '';
                //查询订单数据
                $one_order = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('id = '.$one['order_id'])->field('id as order_id,createtime,update_time,paytime,affirm_time,hang_id,price,supplier_id')->find();
                $hang_id = !empty($one_order['hang_id']) ? $one_order['hang_id'] : 0;
                $one['price'] = !empty($one_order['price']) ? $one_order['price'] : '';
                //
                $one['supplier_id'] = !empty($one_order['supplier_id']) ? $one_order['supplier_id'] : '';
                //
                $one['update_date'] = !empty($one_order['update_time']) ? date('Y年m月d日H:i:s',$one_order['update_time']) : '';//订单最后编辑时间
                $one['update_time'] = !empty($one_order['update_time']) ? $one_order['update_time'] : '';
                //
                $one['create_date'] = !empty($one_order['createtime']) ? date('Y年m月d日H:i:s',$one_order['createtime']) : '';
                $one['createtime'] = !empty($one_order['createtime']) ? $one_order['createtime'] : '';
                //
                $one['paytime_date'] = !empty($one_order['paytime']) ? date('Y年m月d日H:i:s',$one_order['paytime']) : '';
                $one['paytime'] = !empty($one_order['paytime']) ? $one_order['paytime'] : '';
                //
                $one['affirm_date'] = !empty($one_order['affirm_time']) ? date('Y年m月d日H:i:s',$one_order['affirm_time']) : '';
                $one['affirm_time'] = !empty($one_order['affirm_time']) ? $one_order['affirm_time'] : '';
                //查询原始订单
                $order_change = Db::connect('db_mini_mall')->table('ims_member_order_change_log')->where('ordersn = "'.$one['order_sn'].'"')->field('count_old,old_price')->order('id','asc')->find();
                $old_count = $one['total'];
                $old_price = $one['one_price'];
                if(!empty($order_change)){
                    $old_count = $order_change['count_old'];
                    $old_price = $order_change['old_price'];
                }
                $one['old_count'] = $old_count;
                $one['old_one_price'] = $old_price;
                $one['old_price'] = $old_count * $old_price;
                //查询真实供应商
                //查询customer_id
                $customer_id_arr = Db::connect('db_mini_mall')
                    ->table('ims_fixup_hangup')
                    ->where('id = '.$hang_id)
                    ->field('customer_id')
                    ->find();
                $customer_id = !empty($customer_id_arr['customer_id']) ? $customer_id_arr['customer_id'] : 0;
                $customer_arr = Db::connect('db_btj_new')
                    ->table('potential_customer')
                    ->where('id = '.$customer_id)
                    ->field('user_name')
                    ->find();
                $one['customer_name'] = !empty($customer_arr['user_name']) ? $customer_arr['user_name'] : '';
                $return_order_list[] = $one;
            }
        }
        sdk_return($return_order_list,1,'获取成功');
    }

    /**
     * 通过采购订单审核
     */
    public function agreeCGOrder(){
        $param = $this->request->param();
    }
}
