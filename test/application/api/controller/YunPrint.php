<?php
/**
 * 易联云小票打印机
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-11
 * Time: 09:20
 */
namespace app\api\controller;

use library\Yilianyun;
use think\App;
use think\Db;
use think\Controller;

use app\api\controller\BaseController;

use app\api\model\ydxq\YunPrint as YunPrintModel;
use app\api\model\ydxq\ShopOrder;
use app\api\model\ydxq\ShopOrderGoods;
use app\api\model\ydxq\ShopGoods;
use app\api\model\ydxq\ShopMember;
use app\api\model\ydxq\YunPrintList;
use app\api\model\ydxq\YunPrintLog;

use app\api\validate\YunPrint as YunPrintValidate;

class YunPrint extends BaseController
{

    // 以下数据以后从数据库中获取
    protected $app_id;              // 应用ID,以后从数据库中获取
    protected $app_secret;          // 应用密钥,以后从数据库中获取
    protected $access_token;        // token 获取一次就可以，自有应用服务模式token无失效时间,以后从数据库中获取
    protected $config;

    protected $m_YunPrintModel;
    protected $m_YunPrintListModel;
    protected $m_YunPrintLogModel;
    protected $m_ShopOrderModel;
    protected $m_ShopOrderGoodsModel;
    protected $m_ShopGoodsModel;
    protected $m_ShopMemberModel;



    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->app_id       = '1040923140';
        $this->app_secret   = '3f8dca392a28d2f5f4e2c7cd7b955219';
        $this->access_token = '58464ec2ca7349e9aeed9bdbd91f5555';
        $this->config       = [
            'content' => '微信支付到账100元'
        ];

        $this->m_YunPrintModel          = new YunPrintModel();
        $this->m_YunPrintListModel      = new YunPrintList();
        $this->m_YunPrintLogModel       = new YunPrintLog();
        $this->m_ShopOrderModel         = new ShopOrder();
        $this->m_ShopOrderGoodsModel    = new ShopOrderGoods();
        $this->m_ShopGoodsModel         = new ShopGoods();
        $this->m_ShopMemberModel        = new ShopMember();
    }

    // 小票打印接口
    public function text()
    {
        $param = input('post.');
        // 参数验证
        $validate = new YunPrintValidate();
        if (!$validate->scene('text')->check($param)) {
            sdk_return([],0,$validate->getError());
        }
//        $content = "<FS2><center>**#{num} 社区派**</center></FS2>................................<FS2><center>--{order_type}--</center></FS2>下单时间：{order_time}\n订单编号：{order_no}\n**************商品**************<table>{order_content}</table>................................配送费:￥0\n................................<FS2>小计:￥{order_total_price}</FS2>\n<FS2>折扣:￥0 </FS2>\n********************************<FS2>订单总价:￥{order_total_price}</FS2> \n{address}\n{name}({gender}){phone}\n订单备注：{remark}\n<FS2><center>**#{num} 完**</center></FS2>";
        // 先根据店铺ID查询是否已经绑定过
        $sup_id = $param['sup_id'];
        $shop_print_info = $this->m_YunPrintModel->getInfo(['sup_id'=>$sup_id,'status'=>1]);
        if (empty($shop_print_info)) { // 未启用打印机或未绑定
            return json(['status' => 0, 'msg' => '店铺未绑定设备或未启用']);
        }

        // 订单ID
        $order_id = $param['order_id'];
        $order_data = $this->get_print_data($order_id,$sup_id);
        if(!$order_data) {
            return json(['status' => 0, 'msg' => '订单不存在']);
        }

        $time = get_time();
        $current_start_date = strtotime(date('Y-m-d',$time).' 00:00:00');// 当天开始时间
        $current_end_date = strtotime(date('Y-m-d',$time).' 23:59:59');// 当前结束时间

        //每日打印单数
        $print_log_count = $this->m_YunPrintLogModel->getCount([['sup_id','=',$sup_id],['createtime','between',[$current_start_date,$current_end_date]]]);
        $print_log_count = $print_log_count == 0 ? $print_log_count + 1 : $print_log_count + 1;

        // 模板处理
        // 自定义播放语音
        $custom_voice = "<audio>".$order_data['custom_voice'].",".$shop_print_info['volume'].",0</audio>";
        $templates = htmlspecialchars_decode($shop_print_info['templates']);
        $templates = str_replace('{num}',$print_log_count,$templates);
        $templates = str_replace('{order_type}',$order_data['order_type'],$templates);
        $templates = str_replace('{order_time}',$order_data['order_time'],$templates);
        $templates = str_replace('{order_no}',$order_data['order_no'],$templates);
        $templates = str_replace('{order_content}',$order_data['order_content'],$templates);
        $templates = str_replace('{order_total_price}',$order_data['order_total_price'],$templates);
        $templates = str_replace('{address}',$order_data['address'],$templates);
        $templates = str_replace('{name}',$order_data['name'],$templates);
        $templates = str_replace('{gender}',$order_data['gender'],$templates);
        $templates = str_replace('{phone}',$order_data['phone'],$templates);
        $templates = str_replace('{remark}',$order_data['remark'],$templates);

        // 暂时先不做验证
        $print = new Yilianyun($this->app_id,$this->app_secret,$this->access_token);

        $print_api_res = $print::text($custom_voice.$templates,$order_data['order_no'],$shop_print_info['dev_id']);
        $res = json_decode($print_api_res,true);
        if ($res['error'] == 0) {
            $print_log_data = [
                'sup_id' => $sup_id,
                'order_id' => $order_id,
                'print_text' => $templates,
                'createtime' => get_time()
            ];
            // 写入打印成功日志
            $this->m_YunPrintLogModel->insertInfo($print_log_data);

            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            // 写入打印失败日志
            $print_fail_log_data = [
                'sup_id' => $sup_id,
                'order_id' => $order_id,
                'print_text' => $res['error_description'],
                'createtime' => get_time()
            ];
            $this->m_YunPrintLogModel->insertInfo($print_fail_log_data);

            return json(['status' => 0, 'msg' => $res['error_description']]);
        }

    }

    // 语音播放
    public function voice()
    {
        $param = input('post.');
        // 参数验证
        $validate = new YunPrintValidate();
        if (!$validate->scene('voice')->check($param)) {
            sdk_return([],0,$validate->getError());
        }
        $print = new Yilianyun($this->app_id,$this->app_secret,$this->access_token);
        // 先根据店铺ID查询是否已经绑定过
        $sup_id = $param['sup_id'];
        $shop_print_info = $this->m_YunPrintModel->getInfo(['sup_id'=>$sup_id,'status'=>1]);
        if (empty($shop_print_info)) {
            return json(['status' => 0, 'msg' => '店铺未绑定设备或未启用']);
        }

        // 2：测试播音
        $content = isset($param['voice_type']) && $param['voice_type'] == 2 ? $this->config['content'] : $param['content'];
        // 音量1-9
        $volume  = isset($param['volume']) ? $param['volume'] : $shop_print_info['volume'];
        $id = date('Y',get_time()).get_time();

        $print_api_res = $print::voice($content,$id,$shop_print_info['dev_id'],$volume);
        $res = json_decode($print_api_res,true);

        if ($res['error'] == 0) {
            return json(['status' => 1, 'msg' => '操作成功']);
        } else {
            return json(['status' => 0, 'msg' => $res['error_description']]);
        }

    }

    public function index()
    {
        return 'index';
    }

    // 获取店铺打印机信息
    public function get_shop_printer()
    {
        $param = input('post.');
        // 参数验证
        $validate = new YunPrintValidate();
        if (!$validate->scene('get_shop_printer')->check($param)) {
            sdk_return([],0,$validate->getError());
        }
        $sup_id = $param['sup_id'];
        $shop_printer_info = $this->m_YunPrintModel->getInfo([['sup_id','=',$sup_id],['status','<>',9]]);
        if ($shop_printer_info) {
            return json(['status' => 1, 'msg' => '操作成功','res_data'=>$shop_printer_info]);
        }

        return json(['status' => 0, 'msg' => '店铺未绑定设备']);
    }

    // 商户绑定打印机
    public function shop_add_printer()
    {
        //sup_id 店铺ID，必填
        //dev_id 打印机编号，必填

        //dev_ver 打印机版本，非必填
        //dev_pw 打印机编号，非必填
        //access_token 后期从数据库中获取
        $print = new Yilianyun($this->app_id,$this->app_secret,$this->access_token);
        $param = input('post.');
        // 参数验证
        $validate = new YunPrintValidate();
        if (!$validate->scene('shop_add_printer')->check($param)) {
            sdk_return([],0,$validate->getError());
        }
        // 先根据店铺ID查询是否已经绑定过
        $sup_id = $param['sup_id'];
        $shop_printer_info = $this->m_YunPrintModel->getInfo([['sup_id','=',$sup_id],['status','<>',9]]);
        if ($shop_printer_info) {
            return json(['status' => 0, 'msg' => '店铺已绑定设备']);
        }
        // 根据打印机编号查找是否存在库记录中
        $dev_id = $param['dev_id'];
        $printer_info = $this->m_YunPrintListModel->getInfo(['dev_id'=>$dev_id,'status'=>0]);
        if (!$printer_info) {
            return json(['status' => 0, 'msg' => '设备不存在或已绑定']);
        }
        $templates = "<FS2><center>**#{num} 社区派**</center></FS2>................................<FS2><center>--{order_type}--</center></FS2>下单时间：{order_time}\n订单编号：{order_no}\n**************商品**************<table>{order_content}</table>................................配送费:￥0\n................................<FS2>小计:￥{order_total_price}</FS2>\n<FS2>折扣:￥0 </FS2>\n********************************<FS2>订单总价:￥{order_total_price}</FS2> \n{address}\n{name}{gender}{phone}\n订单备注：{remark}\n<FS2><center>**#{num} 完**</center></FS2>";
        $add_shop_print_data = [
            'sup_id'        => $sup_id,
            'dev_ver'       => !empty($printer_info['dev_ver']) ? $printer_info['dev_ver'] : '',
            'dev_id'        => $printer_info['dev_id'],
            'dev_pw'        => $printer_info['dev_pw'],
            'templates'     => htmlspecialchars($templates),
            'volume'        => 9, // 音量
            'parm1'         => '',
            'parm2'         => '',
            'date_start'    => get_time(),
            'createtime'    => get_time(),
            'status'        => 1,
        ];
        // 开启事务
        Db::startTrans();
        $res = $this->m_YunPrintModel->insertInfo($add_shop_print_data);

        if ($res) {
            // 店铺绑定打印机，同时授权打印机到易联云开发平台
            $print::add_printer($printer_info['dev_id'],$printer_info['dev_pw'],'',$sup_id);

            $update_print_data = [
                'sup_id' => $sup_id,
                'bind_time' => $add_shop_print_data['date_start'],
                'status' => 1, // 0：未绑定；1：已绑定
            ];
            $up_res = $this->m_YunPrintListModel->updateInfoPro(['id'=>$printer_info['id']],$update_print_data);
            if ($up_res) {
                // 更新成功,提交事务
                Db::commit();
                return json(['status' => 1, 'msg' => '绑定成功']);
            } else {
                // 更新失败,回滚事务
                Db::rollback();
                return json(['status' => 0, 'msg' => '绑定失败']);
            }
        }

        return json(['status' => 0, 'msg' => '绑定失败']);
    }

    // 店铺解除绑定打印机
    public function shop_unbind_printer()
    {
        $param = input('post.');

        // 参数验证
        $validate = new YunPrintValidate();
        if (!$validate->scene('shop_unbind_printer')->check($param)) {
            sdk_return([],0,$validate->getError());
        }

        $sup_id = $param['sup_id']; // 店铺ID
        $dev_id = $param['dev_id']; // 设备编号

        $shop_printer_info = $this->m_YunPrintModel->getInfo([['sup_id','=',$sup_id],['dev_id','=',$dev_id],['status','<>',9]]);
        if (!$shop_printer_info) {
            return json(['status' => 0, 'msg' => '解绑失败,设备不存在']);
        }

        // 开启事务
        Db::startTrans();
        // 软删除店铺绑定设备，状态改为9
        $res = $this->m_YunPrintModel->deleteInfo($shop_printer_info['id']);

        if ($res) {

            $update_printer_data = [
                'sup_id'    => 0,
                'bind_time' => 0,
                'status'    => 0, // 0：未绑定；1：已绑定
            ];
            $up_res = $this->m_YunPrintListModel->updateInfoPro(['dev_id'=>$shop_printer_info['dev_id']],$update_printer_data);
            if ($up_res) {
                // 更新成功,提交事务
                Db::commit();
                return json(['status' => 1, 'msg' => '解绑成功']);
            } else {
                // 更新失败,回滚事务
                Db::rollback();
                return json(['status' => 0, 'msg' => '解绑失败']);
            }
        }

        return json(['status' => 0, 'msg' => '解绑失败']);
    }

    // 后台入库打印机
    public function admin_add_printer()
    {
        $param = input('post.');
        // 参数验证
        $validate = new YunPrintValidate();
        if (!$validate->scene('admin_add_printer')->check($param)) {
            sdk_return([],0,$validate->getError());
        }
        $param['come_time'] = !empty($param['come_time']) ? $param['come_time'] : get_time();
        $param['createtime'] = !empty($param['createtime']) ? $param['createtime'] : get_time();
        $param['status'] = 0; // 0 未绑定
        // 先根据打印机编号查询是否已经入库
        $dev_id = $param['dev_id'];
        $printer_info = $this->m_YunPrintListModel->getInfo(['dev_id'=>$dev_id]);
        if ($printer_info) {
            return json(['status' => 0, 'msg' => '入库失败,已存在']);
        }

        $res = $this->m_YunPrintListModel->insertInfo($param);
        if ($res) {
            return json(['status' => 1, 'msg' => '入库成功']);
        } else {
            return json(['status' => 0, 'msg' => '入库失败']);
        }
    }

    // 获取终端状态
    public function get_shop_printer_status()
    {
        $param = input('post.');
        // 参数验证
        $validate = new YunPrintValidate();
        $print = new Yilianyun($this->app_id,$this->app_secret,$this->access_token);
        if (!$validate->scene('get_shop_printer_status')->check($param)) {
            sdk_return([],0,$validate->getError());
        }
        // 先根据店铺ID查询是否已经绑定过
        $sup_id = $param['sup_id'];
        $shop_printer_info = $this->m_YunPrintModel->getInfo([['sup_id','=',$sup_id],['status','<>',9]]);
        if (empty($shop_printer_info)) {
            return json(['status' => 0, 'msg' => '店铺未绑定设备','res_data'=>'未绑定']);
        }

        $print_api_res = $print::get_printer_status($shop_printer_info['dev_id']);
        $res = json_decode($print_api_res,true);
        // 打印机状态
        if ($res['error'] == 0) {
            if ($res['body']['state'] == 0) { // 0离线
                $state = '离线';
            } elseif($res['body']['state'] == 1) { // 1在线
                $state = '在线';
            } elseif($res['body']['state'] == 2) { // 2缺纸
                $state = '缺纸';
            } else{
                $state = '异常';
            }
            return json(['status' => 1, 'msg' => '操作成功', 'res_data'=>$state]);
        } else {
            return json(['status' => 0, 'msg' => $res['error_description'],'res_data'=>'异常']);
        }

    }

    // 店铺更新打印机配置
    public function shop_update_printer()
    {
        // sup_id 必传参数
        // volume 音量，可选
        $param = input('post.');

        $allow_field = [
            'sup_id',
            'dev_ver',
            'dev_id',
            'dev_pw',
            'templates',
            'volume',
            'parm1',
            'parm2',
            'status',
        ];
        $update_data = $this->remove_field($allow_field,$param);

        // 参数验证
        $validate = new YunPrintValidate();
        if (!$validate->scene('shop_update_printer')->check($update_data)) {
            sdk_return([],0,$validate->getError());
        }
        // 先根据店铺ID查询是否已经绑定过
        $sup_id = $param['sup_id'];
        $shop_printer_info = $this->m_YunPrintModel->getInfo(['sup_id'=>$sup_id,'status'=>1]);
        if (empty($shop_printer_info)) {
            return json(['status' => 0, 'msg' => '店铺未绑定设备或未启用']);
        }

        $res = $this->m_YunPrintModel->updateInfoPro(['dev_id'=>$shop_printer_info['dev_id']],$update_data);

        if ($res !== false) {
            return json(['status' => 1, 'msg' => '操作成功']);
        }

        return json(['status' => 0, 'msg' => '操作失败']);
    }

    /**
     * 移除不允许存储字段
     * @param $allow_field
     * @param $data
     * @return mixed
     */
    private function remove_field($allow_field,$data)
    {
        foreach ($data as $k=>$v) {
            if (!in_array($k,$allow_field)) {
                unset($data[$k]);
            }
        }

        return $data;
    }

    /**
     * 处理订单打印数据
     * @param $order_id [订单ID]
     * @param $sup_id [店铺ID]
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function get_print_data($order_id,$sup_id)
    {
        // 订单数据
        $order_info = $this->m_ShopOrderModel->getInfo(['id'=>$order_id,'supplier_id'=>$sup_id]);
        if (empty($order_info)) {
            return false;
        }

        // 订单商品信息
        $shop_order_goods_info = $this->m_ShopOrderGoodsModel->getAllListPro(['orderid'=>$order_info['id']],['goodsid','price','total']);
        $goods_content = '';
        if (!empty($shop_order_goods_info)) {
            foreach ($shop_order_goods_info as $k=>$v) {
                $goods_name = null;
                $goods_name = $this->m_ShopGoodsModel->getInfoPro(['id'=>$v['goodsid']],['title']);
                $goods_content .='<tr><td>'.$goods_name['title'].'    '.'x'.$v['total'].'    '.$v['price'].'</td></tr>';
            }
        }

        // 会员性别
        $member_gender = $this->m_ShopMemberModel->getInfoPro(['openid'=>$order_info['openid']],['gender']);
        $gender = '';
        if ($member_gender['gender'] == '1') {
            $gender = '(先生)';
        } elseif ($member_gender['gender'] == '2') {
            $gender = '(女士)';
        }
        $address_arr = unserialize($order_info['address']);

        $temp = [];
        $temp['custom_voice']       = $order_info['delivery_type'] == 1 ? '社区派提醒您有新的外卖订单' : '社区派提醒您有新的自提订单';
        $temp['order_type']         = $order_info['delivery_type'] == 1 ? '外卖订单' : '自提订单';
        $temp['order_time']         = date("Y-m-d H:i:s",$order_info['createtime']);
        $temp['order_no']           = $order_info['ordersn'];
        $temp['order_content']      = $goods_content;
        $temp['order_total_price']  = $order_info['price'];
        $temp['address']            = empty($address_arr['address']) ? '' : $address_arr['address'];
        $temp['name']               = empty($address_arr['realname']) ? '' : $address_arr['realname'];
        $temp['gender']             = empty($address_arr['realname']) ? '' : $gender;
        $temp['phone']              = empty($address_arr['mobile']) ? '' : $address_arr['mobile'];
        $temp['remark']             = !empty($order_info['remark']) ? $order_info['remark'] : '空';

        return $temp;
    }


}