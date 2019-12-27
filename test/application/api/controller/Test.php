<?php
/**
 * ceadr 2019-08-13
 */

namespace app\api\controller;

use app\admin\model\ydxq\ShopGoods as RemoveShopGoods;
use PHPWord_IOFactory;
use PHPWord_Style_Cell;
use think\Db;
use think\Debug;
use think\Exception;

class Test extends BaseController {
    public function __construct() {
        parent::__construct();
    }

    /**
     * auth:maci
     * 获取优惠券信息
     */
    public function getCouponData(){
        $base_coupon_id = $this->request->param('base_coupon_id',0,'trim');
        //查询优惠券信息
        $coupon_data = Db::connect([
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
        ])->table('ims_bb_base_coupon')->where('id = '.$base_coupon_id)->find();
        exit(json_encode($coupon_data));
    }

    public function test() {
//        phpinfo();exit;
        \think\facade\Debug::remark('begin');
        for ($i = 1; $i < 100000000; $i++) {
            $i_arr = str_split($i);
            $i_num = count($i_arr);
            $mi = 0;
            foreach ($i_arr as $one) {
                $mi = $mi + pow($one, $i_num);
            }
//            if($mi == $i){
//                echo $i.'<br>';
//            }
        }
        \think\facade\Debug::remark('end');
        echo \think\facade\Debug::getRangeTime('begin', 'end') . 's';
    }

    public function word() {
        header("Content-Type: text/html; charset=UTF-8");
        require_once __DIR__ . "/../../../vendor/phpoffice/phpword/PHPWord.php";
        $PHPWord = new \PHPWord();
        $PHPWord->addFontStyle('rStyle', array('bold' => true, 'italic' => true, 'size' => 16));
        $PHPWord->addParagraphStyle('pStyle', array('align' => 'center', 'spaceAfter' => 100));
        $PHPWord->addTitleStyle(1, array('bold' => true), array('spaceAfter' => 240));
        $section = $PHPWord->createSection();//创建新页面
        $PHPWord->setDefaultFontName('宋体'); // 全局字体
        $PHPWord->setDefaultFontSize(11);     // 全局字号为3号
        $fontStyle = [
            'name' => 'Microsoft Yahei UI',
            'size' => 20,
            'color' => '#ff6600',
            'bold' => true
        ];
        $url = [
            'http://www.gsfpjd.com/wx/view/mzjDetail.jsp?id=2e4e82fc6e56c5a2ab08ad7d8a3a25eeb36b022f153e8677&xm=852be5e18203d67d-59d9eed027c23ffa&uuid=a427c496-8906-4588-9fa5-7e3723b38a82',
        ];
        foreach ($url as $one_url) {
            $word = array();
            //        header("Content-type:text/html;charset=utf-8");
            $abc = file_get_contents($one_url);
            //姓名
            $find = "title\">姓名:";
            $tmp = stripos($abc, $find);
            $xingming = substr($abc, $tmp, 100);
            $xingming_arr = explode('</span>', $xingming);
            $xingming = strstr($xingming_arr[1], 'tent">');
            $xingming = str_replace('tent">', '', $xingming);
            $word['xingming'] = $xingming;
            //性别
            $find = "title\">性别:";
            $tmp = stripos($abc, $find);
            $xingbie = substr($abc, $tmp, 100);
            $xingbie_arr = explode('</span>', $xingbie);
            $xingbie = strstr($xingbie_arr[1], 'tent">');
            $xingbie = str_replace('tent">', '', $xingbie);
            $word['xingbie'] = $xingbie;
            //身份证号
            $find = "title\">身份证号:";
            $tmp = stripos($abc, $find);
            $shenfenzhenghao = substr($abc, $tmp, 100);
            $shenfenzhenghao_arr = explode('</span>', $shenfenzhenghao);
            $shenfenzhenghao = strstr($shenfenzhenghao_arr[1], 'tent">');
            $shenfenzhenghao = str_replace('tent">', '', $shenfenzhenghao);
            $word['shenfenzhenghao'] = $shenfenzhenghao;
            //家庭地址
            $find = "right: 2.1%;\">家庭地址:";
            $tmp = stripos($abc, $find);
            $jiatingdizhi = substr($abc, $tmp, 300);
            $jiatingdizhi_arr = explode('</span>', $jiatingdizhi);
            $jiatingdizhi = strstr($jiatingdizhi_arr[1], 'idden;">');
            $jiatingdizhi = str_replace('idden;">', '', $jiatingdizhi);
            $word['jiatingdizhi'] = $jiatingdizhi;

            //本年发放金额
            $find = "z-title\">本年发放金额";
            $tmp = stripos($abc, $find);
            $bennianfafangjinee = substr($abc, $tmp, 100);
            $bennianfafangjinee_arr = explode('</p>', $bennianfafangjinee);
            $bennianfafangjinee = strstr($bennianfafangjinee_arr[1], 'lass="z-content">');
            $bennianfafangjinee = str_replace('lass="z-content">', '', $bennianfafangjinee);
            $word['bennianfafangjinee'] = $bennianfafangjinee;
            //本年发放次数
            $find = "z-title\">本年发放次数";
            $tmp = stripos($abc, $find);
            $bennianfafangcishu = substr($abc, $tmp, 100);
            $bennianfafangcishu_arr = explode('</p>', $bennianfafangcishu);
            $bennianfafangcishu = strstr($bennianfafangcishu_arr[1], 'lass="z-content">');
            $bennianfafangcishu = str_replace('lass="z-content">', '', $bennianfafangcishu);
            $word['bennianfafangcishu'] = $bennianfafangcishu;
            //合计发放金额
            $find = "z-title\">合计发放金额";
            $tmp = stripos($abc, $find);
            $hejifafangjinee = substr($abc, $tmp, 100);
            $hejifafangjinee_arr = explode('</p>', $hejifafangjinee);
            $hejifafangjinee = strstr($hejifafangjinee_arr[1], 'lass="z-content">');
            $hejifafangjinee = str_replace('lass="z-content">', '', $hejifafangjinee);
            $word['hejifafangjinee'] = $hejifafangjinee;
            //合计发放次数
            $find = "z-title\">合计发放次数";
            $tmp = stripos($abc, $find);
            $hejifafangcishu = substr($abc, $tmp, 100);
            $hejifafangcishu_arr = explode('</p>', $hejifafangcishu);
            $hejifafangcishu = strstr($hejifafangcishu_arr[1], 'lass="z-content">');
            $hejifafangcishu = str_replace('lass="z-content">', '', $hejifafangcishu);
            $word['hejifafangcishu'] = $hejifafangcishu;

            //发放
            $fafang_arr = explode('<div class="list-title">', $abc);
            $fafang_arr_v2 = array();
            foreach ($fafang_arr as $one) {
                $a = strstr($one, '政策说明>></span>');
                if (!empty($a)) {
                    $fafang_arr_v2[] = substr($one, 0, strpos($one, '政策说明>></span>'));
                }
            }

            $word['fafang'] = array();
            foreach ($fafang_arr_v2 as $one) {
                $yige = array();
                $fafang_one = explode('</span>', $one);
                //标题
                $title = strstr($fafang_one[0], '<span>');
                $title = str_replace('<span>', '', $title);
                $yige['title'] = $title;
                //发放日期
                $fafangriqi = str_replace('<span class="count-content">', '', $fafang_one[2]);
                $fafangriqi = str_replace('		', '', $fafangriqi);
                $fafangriqi = str_replace('
    ', '', $fafangriqi);
                $yige['fafangriqi'] = $fafangriqi;
                //            echo $fafangriqi;
                //发放地址
                $fafangdizhi = str_replace('<span class="count-content">', '', $fafang_one[4]);
                $fafangdizhi = str_replace('		', '', $fafangdizhi);
                $fafangdizhi = str_replace('
    ', '', $fafangdizhi);
                $yige['fafangdizhi'] = $fafangdizhi;
                //发放金额
                $fafangjinee = str_replace('<span class="amount-content">', '', $fafang_one[6]);
                $fafangjinee = str_replace('		', '', $fafangjinee);
                $fafangjinee = str_replace('
    ', '', $fafangjinee);
                $yige['fafangjinee'] = $fafangjinee;
                //备注内容
                $beizhuneirong = str_replace('<div  class="amount-remark" onClick="checkRemark(this);"><span>', '', $fafang_one[9]);
                $beizhuneirong = str_replace('		', '', $beizhuneirong);
                $beizhuneirong = str_replace('
    ', '', $beizhuneirong);
                $yige['beizhuneirong'] = $beizhuneirong;
                $word['fafang'][] = $yige;
            }
            //        echo "<pre>";print_r($word);exit;
            $section->addText($word['xingming'], ['bold' => true]);
            $section->addText('    个人基础信息', ['bold' => true]);
            $section->addText('        姓名：' . $word['xingming']);
            $section->addText('        性别：' . $word['xingbie']);
            $section->addText('        身份证号：' . $word['shenfenzhenghao']);
            $section->addText('        家庭地址：' . $word['jiatingdizhi']);

            $section->addText('    资金发放统计', ['bold' => true]);
            $section->addText('        本年发放金额：' . $word['bennianfafangjinee']);
            $section->addText('        本年发放次数：' . $word['bennianfafangcishu']);
            $section->addText('        合计发放金额：' . $word['hejifafangjinee']);
            $section->addText('        合计发放次数：' . $word['hejifafangcishu']);

            foreach ($word['fafang'] as $one) {
                $section->addText('        ' . $one['title'], ['bold' => true]);
                $section->addText('            发放日期：' . $one['fafangriqi']);
                $section->addText('            发放地址：' . $one['fafangdizhi']);
                $section->addText('            发放金额：' . $one['fafangjinee']);
                $section->addText('            备注内容：' . $one['beizhuneirong']);
            }
        }
        $file = 'test.docx';
        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        $xmlWriter = PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
        $xmlWriter->save("php://output");

//        $objWriter = PHPWord_IOFactory::createWriter($PHPWord, 'Word2007');
//        $objWriter->save('demo.doc');
    }

    public function editOrder() {
        echo "<pre>";

//        $a = unserialize('a:18:{s:2:"id";s:2:"37";s:7:"uniacid";s:1:"4";s:6:"openid";s:35:"sns_wa_ogrIh0WqvR60SSnBFOToV7Acwt5o";s:8:"realname";s:6:"洋洋";s:6:"mobile";s:11:"13522051943";s:8:"province";s:9:"北京市";s:4:"city";s:9:"北京市";s:4:"area";s:9:"丰台区";s:7:"address";s:49:"北京市丰台区南庭新苑北区19--1--101室";s:9:"isdefault";s:1:"1";s:7:"zipcode";s:0:"";s:7:"deleted";s:1:"0";s:6:"street";s:0:"";s:9:"datavalue";s:20:"110000 110100 110106";s:15:"streetdatavalue";s:0:"";s:3:"lng";s:0:"";s:3:"lat";s:0:"";s:8:"is_range";s:2:"-1";}');
//        print_r($a);
//        echo '自提<br>';
//        $a = unserialize('a:18:{s:2:"id";s:2:"37";s:7:"uniacid";s:1:"4";s:6:"openid";s:35:"sns_wa_ogrIh0WqvR60SSnBFOToV7Acwt5o";s:8:"realname";s:6:"洋洋";s:6:"mobile";s:11:"13522051943";s:8:"province";s:9:"北京市";s:4:"city";s:9:"北京市";s:4:"area";s:9:"丰台区";s:7:"address";s:49:"北京市丰台区南庭新苑北区19--1--101室";s:9:"isdefault";s:1:"1";s:7:"zipcode";s:0:"";s:7:"deleted";s:1:"0";s:6:"street";s:0:"";s:9:"datavalue";s:20:"110000 110100 110106";s:15:"streetdatavalue";s:0:"";s:3:"lng";s:0:"";s:3:"lat";s:0:"";s:8:"is_range";s:2:"-1";}');
//        print_r($a);exit;

        $where = [
//                ['b.is_c', '=', 1],
            ['s.supplier_id', '=', 461],
            ['s.status', '>', -1],
            ['s.delivery_type', '<>', 1],
        ];
        $where[] = ['s.createtime', '>=', 1567785600];
        $shop_order_list = Db::connect('db_ydxq_test_xianshang')->table('ims_ewei_shop_order')->alias('s')->leftJoin('ims_yd_supplier b', 's.openid = b.openid')->field('s.id,s.ordersn,s.createtime,b.name,b.nickname,s.price,s.address,s.delivery_type,s.remark,s.status,s.order_type')->where($where)->order('id desc')->select();
        $new_shop_order = array();
        if (!empty(count($shop_order_list))) {
            foreach ($shop_order_list as $key => $value) {

                unset($new_order);
                $new_order['id'] = $value['id'];//订单ID
                $new_order['ordersn'] = $value['ordersn'];//第三方订单号
                $new_order['createtime'] = !empty($value['createtime']) ? date('Y-m-d H:i:s', $value['createtime']) : '';//订单时间
                $new_order['name'] = $value['name'];//店铺名称
                $new_order['nickname'] = $value['nickname'];//店主昵称
                $new_order['price'] = $value['price'];//订单价格

                if ($value['delivery_type'] == 1) {
                    $new_order['delivery_type'] = '配送';
                    try {
                        $address = unserialize($value['address']);
                    } catch (Exception $exceptione) {
                        $address = '';
                    }
                    if (!empty($address)) {
                        $new_order['realname'] = $address['realname'];//接收人真实姓名
                        $new_order['phone'] = $address['mobile'];//接收人真实联系电话
                        $new_order['address'] = $address['address'];//接收人配送地址
                    } else {
                        $new_order['realname'] = '';//接收人真实姓名
                        $new_order['phone'] = '';//接收人真实联系电话
                        $new_order['address'] = '';//接收人配送地址
                    }
                } else {
                    try {
                        $address = unserialize($value['address']);
                    } catch (Exception $exceptione) {
                        $address = '';
                    }
                    $new_order['delivery_type'] = '自提';
                    $new_order['realname'] = '';//接收人真实姓名
                    $new_order['phone'] = '';//接收人真实联系电话
                    $new_order['address'] = '';//接收人配送地址
                }
                $new_order['remark'] = $value['remark'];//备注

                //查询状态
                if ($value['status'] == 3) {
                    $status = '<span style="color: green;">已完成</span>';
                } elseif ($value['status'] == 1) {
                    $status = '已支付';
                } elseif ($value['status'] == 0) {
                    $status = '<span style="color: red;">待支付</span>';
                } elseif ($value['status'] == 2) {
                    $status = '<span style="color: green;">已确认</span>';
                } else {
                    $status = '<span style="color: red;">已取消</span>';
                }
                $new_order['order_type'] = $value['order_type'];
                $new_order['status'] = $status;
                $new_order['type'] = $value['status'];
                $new_shop_order[] = $new_order;
            }
        }
        print_r($new_shop_order);
    }

    public function moren() {
//        echo 3;exit;
        //查询商品
        $db_mini_mall = new \app\api\model\ydxq\Supplier();
        $goods_res = $db_mini_mall->querySql("SELECT id,jd_name,`code`,spec,price from ims_goods_code WHERE `code` in(SELECT `code` from (SELECT `code`,COUNT(`code`) as cc from ims_goods_code_info GROUP BY `code` HAVING cc > 4 ORDER BY cc desc) as a) and jd_name <> '' ORDER BY id DESC");
        //商品名，条码，规格，价格
        $title = ['商品名', '条码', '规格', '价格'];
        $data = [];
        foreach ($goods_res as $one) {
            $data[] = [
                $one['jd_name'],
                $one['code'],
                $one['spec'],
                $one['price'],
            ];
        }
        $this->exportExcel($title, $data, '默认商品表');
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

    /**
     * 转换shop_goods表中的skuid
     * @throws Exception
     */
    public function updateData() {
        exit('转换shop_goods表中的skuid');
        //查询孙宇店铺商品
        $goods_list = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->field('a.id,a.skuid')->where([['supplier_id', '=', '461'], ['skuid', '<>', '']])->select();
        foreach ($goods_list as $key => $value) {
            if (!empty($value['skuid'])) {
                //查询ims_bb_sku表中的id和goods_id
                $bb_sku_id = Db::connect('db_mini_mall')->table('ims_bb_sku')->where([['hbsj_sku_id', '=', $value['skuid']]])->field('id')->find();
                if (!empty($bb_sku_id)) {
                    //更新shop_goods表中的skuid
                    $update = array();
                    $update['skuid'] = $bb_sku_id['id'];
                    $update['hbsj_sku_id'] = $value['skuid'];
                    Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->where([['id', '=', $value['id']]])->update($update);
                }
            }
        }
    }

    /**
     * 转换分类ID
     */
    public function updateCate() {
        $goods_list = Db::connect('db_mini_mall')->table('ims_bb_goods_item')->field('id,cate_bb1,cate_bb2,brand_id')->where([['hbsj_cate1', '=', 0]])->select();
        foreach ($goods_list as $key => $value) {
            $update = array();
            //更新一级分类
            if (!empty($value['cate_bb1'])) {
                $bb_sku_id = Db::connect('db_mini_mall')->table('ims_bb_cate_bb')->where([['hbsj_cate_id', '=', $value['cate_bb1']], ['hbsj_cate_fid', '=', 0]])->field('id')->find();
                if (!empty($bb_sku_id)) {
                    //更新shop_goods表中的skuid
                    $update['cate_bb1'] = $bb_sku_id['id'];
                    $update['hbsj_cate1'] = $value['cate_bb1'];
//                    Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$value['id']]])->update($update);
                }
            }

            //更新二级分类
            if (!empty($value['cate_bb2'])) {
                $bb_sku_id = Db::connect('db_mini_mall')->table('ims_bb_cate_bb')->where([['hbsj_cate_id', '=', $value['cate_bb2']]])->field('id')->find();
                if (!empty($bb_sku_id)) {
                    //更新shop_goods表中的skuid
//                    $update = array();
                    $update['cate_bb2'] = $bb_sku_id['id'];
                    $update['hbsj_cate2'] = $value['cate_bb2'];
//                    Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$value['id']]])->update($update);
                }
            }

            //更新品牌
            if (!empty($value['brand_id'])) {
                $bb_sku_id = Db::connect('db_mini_mall')->table('ims_bb_brand')->where([['hbsj_id', '=', $value['brand_id']]])->field('id')->find();
                if (!empty($bb_sku_id)) {
                    //更新shop_goods表中的skuid
//                    $update = array();
                    $update['brand_id'] = $bb_sku_id['id'];
                    $update['hbsj_brand_id'] = $value['brand_id'];
//                    Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$value['id']]])->update($update);
                }
            }
            if (!empty($update)) {
                Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id', '=', $value['id']]])->update($update);
            }
        }
    }

    public function updateCate_zt() {
        //one
        $one_cate = Db::connect('db_mini_mall')->table('ims_bb_cate_bb')->where([['hbsj_cate_fid', '=', 0]])->field('id,hbsj_cate_id')->select();
        $one_cate_arr = array();
        foreach ($one_cate as $key => $value) {
            $one_cate_arr[$value['hbsj_cate_id']] = $value['id'];
        }
        unset($one_cate);

        //two
        $two_cate = Db::connect('db_mini_mall')->table('ims_bb_cate_bb')->where([['hbsj_cate_fid', '<>', 0]])->field('id,hbsj_cate_id')->select();
        $two_cate_arr = array();
        foreach ($two_cate as $key => $value) {
            $two_cate_arr[$value['hbsj_cate_id']] = $value['id'];
        }
        unset($two_cate);

        //brand
        $brand_res = Db::connect('db_mini_mall')->table('ims_bb_brand')->field('id,hbsj_id')->select();
        $brand_arr = array();
        foreach ($brand_res as $key => $value) {
            $brand_arr[$value['hbsj_id']] = $value['id'];
        }
        unset($brand_res);

        $goods_list = Db::connect('db_mini_mall')->table('ims_bb_goods_item')->field('id,cate_bb1,cate_bb2,brand_id')->where([['hbsj_cate1', '=', 0]])->select();
        foreach ($goods_list as $key => $value) {
            $update = array();
            //更新一级分类
            if (!empty($value['cate_bb1'])) {
//                $bb_sku_id = Db::connect('db_mini_mall')->table('ims_bb_cate_bb')->where([['hbsj_cate_id','=',$value['cate_bb1']],['hbsj_cate_fid','=',0]])->field('id')->find();
//                if(!empty($bb_sku_id)){
//                    //更新shop_goods表中的skuid
//                    $update['cate_bb1'] = $bb_sku_id['id'];
//                    $update['hbsj_cate1'] = $value['cate_bb1'];
////                    Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$value['id']]])->update($update);
//                }
                if (!empty($one_cate_arr[$value['cate_bb1']])) {
                    //更新shop_goods表中的skuid
                    $update['cate_bb1'] = $one_cate_arr[$value['cate_bb1']];
                    $update['hbsj_cate1'] = $value['cate_bb1'];
//                    Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$value['id']]])->update($update);
                }
            }

            //更新二级分类
            if (!empty($value['cate_bb2'])) {
//                $bb_sku_id = Db::connect('db_mini_mall')->table('ims_bb_cate_bb')->where([['hbsj_cate_id','=',$value['cate_bb2']]])->field('id')->find();
//                if(!empty($bb_sku_id)){
//                    //更新shop_goods表中的skuid
////                    $update = array();
//                    $update['cate_bb2'] = $bb_sku_id['id'];
//                    $update['hbsj_cate2'] = $value['cate_bb2'];
////                    Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$value['id']]])->update($update);
//                }
                if (!empty($two_cate_arr[$value['cate_bb2']])) {
                    //更新shop_goods表中的skuid
                    $update['cate_bb2'] = $two_cate_arr[$value['cate_bb2']];
                    $update['hbsj_cate2'] = $value['cate_bb2'];
//                    Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$value['id']]])->update($update);
                }
            }

            //更新品牌
            if (!empty($value['brand_id'])) {
//                $bb_sku_id = Db::connect('db_mini_mall')->table('ims_bb_brand')->where([['hbsj_id','=',$value['brand_id']]])->field('id')->find();
//                if(!empty($bb_sku_id)){
//                    //更新shop_goods表中的skuid
////                    $update = array();
//                    $update['brand_id'] = $bb_sku_id['id'];
//                    $update['hbsj_brand_id'] = $value['brand_id'];
////                    Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id','=',$value['id']]])->update($update);
//                }
                if (!empty($brand_arr[$value['brand_id']])) {
                    $update['brand_id'] = $brand_arr[$value['brand_id']];
                    $update['hbsj_brand_id'] = $value['brand_id'];
                }
            }
            if (!empty($update)) {
                Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id', '=', $value['id']]])->update($update);
            }
        }
    }

    /**
     * 修改shop_goods表中的品牌ID和分类ID
     */
    public function updateChannel() {
        exit('修改shop_goods表中的品牌ID和分类ID');
        //查询孙宇店铺商品
        $goods_list = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->field('a.id,a.skuid')->where([['supplier_id', '=', '461'], ['skuid', '<>', '']])->select();
        foreach ($goods_list as $key => $value) {
            $update = null;
            if (!empty($value['skuid'])) {
                //通过skuid去sku表中查询基本表本地的商品ID
                $goods_id_arr = Db::connect('db_mini_mall')->table('ims_bb_sku')->where([['id', '=', $value['skuid']]])->field('goods_id')->find();
                if (!empty($goods_id_arr['goods_id'])) {
                    //通过基本表本地的商品ID去ims_bb_goods_item表中查询品牌ID，一级分类ID，二级分类ID
                    $b_c1_c2_data = Db::connect('db_mini_mall')->table('ims_bb_goods_item')->where([['id', '=', $goods_id_arr['goods_id']]])->field('brand_id,cate_bb1,cate_bb2')->find();
                    $update['brand_id'] = !empty($b_c1_c2_data['brand_id']) ? $b_c1_c2_data['brand_id'] : 0;
                    $update['bb_cate1'] = !empty($b_c1_c2_data['cate_bb1']) ? $b_c1_c2_data['cate_bb1'] : 0;
                    $update['bb_cate2'] = !empty($b_c1_c2_data['cate_bb2']) ? $b_c1_c2_data['cate_bb2'] : 0;
                    //更新孙宇店铺商品信息
                    Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->where([['id', '=', $value['id']]])->update($update);
                }
            }
        }
    }

    /**
     * 修改孙宇店铺的分类并记录数量
     */
    public function updateBCate() {
//        exit('修改孙宇店铺的分类并记录数量');
        //查询孙宇店铺商品
        $goods_list = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->field('a.id,a.skuid,a.bb_cate1,a.bb_cate2')->where([['supplier_id', '=', '461'], ['skuid', '<>', '']])->select();
        echo Db::connect('db_mini_mall')->getLastSql();
        exit;
        $time = time();
        foreach ($goods_list as $key => $value) {
            //处理一级分类
            if (!empty($value['bb_cate1'])) {
                //判断是否已经在B2B分类表中
                $is_cate = Db::connect('db_mini_mall')->table('ims_bb_supplier_cate')->where([['cate_bb2', '=', $value['bb_cate1']], ['cate_bb1', '=', 0]])->field('id')->find();
                if (empty($is_cate)) {
                    //查询一级分类名称
                    $one_cate_name_arr = Db::connect('db_mini_mall')->table('ims_bb_cate_bb')->where([['id', '=', $value['bb_cate1']]])->field('c_name')->find();
                    //插入一级分类
                    $one_cate['sup_id'] = '461';
                    $one_cate['cate_bb1'] = 0;
                    $one_cate['cate_bb2'] = $value['bb_cate1'];
                    $one_cate['category_name'] = !empty($one_cate_name_arr['c_name']) ? $one_cate_name_arr['c_name'] : '';
                    $one_cate['create_time'] = $time;
                    $one_cate['skuid_count'] = 1;
                    Db::connect('db_mini_mall')->table('ims_bb_supplier_cate')->insert($one_cate);
                } else {
                    //更新一级分类的商品数量
//                    Db::table('ims_bb_supplier_cate')->execute('');
                    Db::connect('db_mini_mall')->execute("UPDATE ims_bb_supplier_cate set skuid_count = skuid_count + 1 where id = {$is_cate['id']}");
                }
            }
            unset($is_cate, $one_cate, $one_cate_name_arr);
            //处理二级分类
            if (!empty($value['bb_cate2'])) {
                //判断是否已经在B2B分类表中
                $is_cate = Db::connect('db_mini_mall')->table('ims_bb_supplier_cate')->where([['cate_bb2', '=', $value['bb_cate2']], ['cate_bb1', '=', $value['bb_cate1']]])->field('id')->find();
                if (empty($is_cate)) {
                    //查询二级分类名称
                    $two_cate_name_arr = Db::connect('db_mini_mall')->table('ims_bb_cate_bb')->where([['id', '=', $value['bb_cate2']]])->field('c_name')->find();
                    //插入一级分类
                    $one_cate['sup_id'] = '461';
                    $one_cate['cate_bb1'] = $value['bb_cate1'];
                    $one_cate['cate_bb2'] = $value['bb_cate2'];
                    $one_cate['category_name'] = !empty($two_cate_name_arr['c_name']) ? $two_cate_name_arr['c_name'] : '';
                    $one_cate['create_time'] = $time;
                    $one_cate['skuid_count'] = 1;
                    Db::connect('db_mini_mall')->table('ims_bb_supplier_cate')->insert($one_cate);
                } else {
                    //更新二级分类的商品数量
//                    Db::table('ims_bb_supplier_cate')->execute('');
                    Db::connect('db_mini_mall')->execute("UPDATE ims_bb_supplier_cate set skuid_count = skuid_count + 1 where id = {$is_cate['id']}");
                }
            }
            unset($is_cate, $one_cate, $one_cate_name_arr);
        }
    }

    /**
     * @param string $table
     * @param array $data
     * @return bool|string
     */
    function insertIntoSql($table = '', $data = array()) {
        if (empty($data) || !is_array($data) || empty($table)) {
            return false;
        }
        $sql = 'INSERT INTO ' . $table . '(';
        $sql_val = 'values ';
        foreach ($data as $key => $value) {
            if ($key == 0) {
                $s_key = '';
                foreach ($value as $s_k => $s_v) {
                    $s_key .= $s_k . ',';
                }
                $sql .= rtrim($s_key, ',') . ')';
            }
            $s_val = '(';
            foreach ($value as $val) {
                $s_val .= "'" . $val . "',";
            }
            $sql_val .= rtrim($s_val, ',') . '),';
        }
        $sql_val = rtrim($sql_val, ',');
        $inster_sql = $sql . $sql_val;
        return $inster_sql;
    }

    /**
     * 清洗品牌和分类的关联
     * @throws Exception
     */
    public function index_lcy() {
        exit('132');
        set_time_limit(0);
        ini_set('memory_limit', '512M');
//        $m_bb_brand = new RemoveShopGoods();
        echo "<pre>";
        echo time() . "<br>";
//        $sql_goods_item = "SELECT id,cate_bb1,cate_bb2,hbsj_cate1,hbsj_cate2,brand_id from ims_bb_goods_item where cate_bb2 = 22";
        $sql_goods_item = "SELECT id,cate_bb1,cate_bb2,hbsj_cate1,hbsj_cate2,brand_id from ims_bb_goods_item";
//        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);
        $list_goods_item = Db::connect('db_mini_mall')->query($sql_goods_item);
        $arr_goods_item = array();
        foreach ($list_goods_item as $one) {
            $s_key = $one['cate_bb1'] . '_' . $one['cate_bb2'] . '_' . $one['brand_id'];
            if (!isset($arr_goods_item[$s_key])) {
                $arr_goods_item[$s_key] = $one;
            }
        }
        print_r($arr_goods_item);
        $data = array();
        if (!empty(count($arr_goods_item))) {
            foreach ($arr_goods_item as $key => $value) {
                $data[] = [
                    'cate_id' => $value['cate_bb2'],
                    'brand_id' => $value['brand_id'],
                ];
                if (count($data) >= 1000) {
                    $sql = $this->insertIntoSql('ims_bb_cate_brand', $data);
                    Db::connect('db_mini_mall')->execute($sql);
                    unset($data);
                }
            }
        }
        if (count($data) > 0) {
            $sql = $this->insertIntoSql('ims_bb_cate_brand', $data);
            Db::connect('db_mini_mall')->execute($sql);
            unset($data);
        }
        exit;
    }

    /**
     * 更新priceList数据
     */
    public function priceList() {
        $price_list = Db::connect('db_mini_mall')->table('ims_bb_price_list_1007')->field('sku_id,channel_id,price,date,price_avg,price_date,createtime,hbsj_sku_id,hbsj_channel_id')->select();
        $new_price = array();
        foreach ($price_list as $key => $value) {
            $new_price[$value['hbsj_sku_id'] . '_' . $value['hbsj_channel_id'] . '_' . $value['price']] = $value;
        }
        foreach ($new_price as $new_key => $new_value) {
            $data[] = $new_value;
            if (count($data) > 100) {
//                Db::execute();
                $sql = $this->insertIntoSql('ims_bb_price_list', $data);
                Db::connect('db_mini_mall')->execute($sql);
                unset($data);
            }
        }
    }

    /**
     * 更新ims_bb_city_sku
     */
    public function citySku() {
        $city_sku = Db::connect('db_mini_mall')->query("SELECT hbsj_sku_id,COUNT(*) as new_count from ims_bb_price_list GROUP by hbsj_sku_id");
        foreach ($city_sku as $key => $value) {
            $new_count = Db::connect('db_mini_mall')->table('ims_bb_city_sku')->where([['hbsj_sku_id', '=', $value['hbsj_sku_id']]])->update(['channel_count' => $value['new_count']]);
        }
    }
/*************************************************线上更新 ↓ *****************************/
    /**
     * 1
     * 更新线上孙宇店铺商品品牌和分类(不用定时)
     */
    public function onlineGoods() {
//        exit('更新线上孙宇店铺商品品牌和分类');
        //查询线上孙宇店铺商品
        $goods_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.id,a.skuid FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 900075824 ORDER BY id DESC;");
        foreach ($goods_arr as $k => $v){
            //根据skuid去sku表中查询goods_item_id
            if(!empty($v['skuid'])){
                $goods_item_id_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT goods_id from ims_bb_sku where id = {$v['skuid']}");
                if(!empty(count($goods_item_id_arr))){
                    //根据goods_item_id查询品牌ID分类ID
                    $goods_item_id = $goods_item_id_arr[0]['goods_id'];
                    $goods_item_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT brand_id,cate_bb1,cate_bb2,hbsj_brand_id from ims_bb_goods_item where id = {$goods_item_id}");
                    if(!empty(count($goods_item_arr))){
                        $brand_id = !empty($goods_item_arr[0]['brand_id']) ? $goods_item_arr[0]['brand_id'] : 0;
                        $cate_bb1 = !empty($goods_item_arr[0]['cate_bb1']) ? $goods_item_arr[0]['cate_bb1'] : 0;
                        $cate_bb2 = !empty($goods_item_arr[0]['cate_bb2']) ? $goods_item_arr[0]['cate_bb2'] : 0;

                        $hbsj_brand_id = !empty($goods_item_arr[0]['hbsj_brand_id']) ? $goods_item_arr[0]['hbsj_brand_id'] : 0;
//                        $bb_cate1 = $goods_item_arr[0]['hbsj_cate1'];
//                        $hbsj_cate2 = $goods_item_arr[0]['hbsj_cate2'];
                        Db::connect('db_ydxq_test_xianshang')->execute("UPDATE ims_ewei_shop_goods set brand_id = {$brand_id},bb_cate1 = {$cate_bb1},bb_cate2 = {$cate_bb2},hbsj_brand_id = {$hbsj_brand_id} where id = {$v['id']}");
                    }
                }
            }
        }
    }

    /**
     * (定时跑)
     * @throws Exception
     */
    public function cateBrandCount() {
        echo 'a=' . time() . '<br>';
        //加载ims_bb_city_sku数组
        $channel_count_arr = Db::connect('db_mini_mall')->table('ims_bb_city_sku')->field('sku_id,channel_count')->select();
        $channel_city_arr = array();
        foreach ($channel_count_arr as $key => $value) {
            $channel_city_arr[$value['sku_id']] = $value;
        }
        unset($channel_count_arr);
        //加载ims_bb_sku数组
        $sku_count_arr = Db::connect('db_mini_mall')->table('ims_bb_sku')->field('id,goods_id')->select();
        $sku_count_arr_data = array();
        foreach ($sku_count_arr as $key => $value) {
            $sku_count_arr_data[$value['goods_id']][] = $value['id'];
        }
        unset($sku_count_arr);

        //加载ims_bb_goods_item数组
        $goods_item_arr = Db::connect('db_mini_mall')->query("SELECT id,cate_bb2,brand_id from ims_bb_goods_item");
        $goods_item_data = array();
        foreach ($goods_item_arr as $key => $value) {
//            $cate = !empty($value['cate_bb2']) ? $value['cate_bb2'] : 0;
//            $brand = !empty($value['brand_id']) ? $value['brand_id'] : 0;
            $cate = $value['cate_bb2'];
            $brand = $value['brand_id'];
            $goods_item_data[$cate . '_' . $brand][] = $value['id'];
        }
        unset($goods_item_arr);
        //统计sku数量
        //查询某个分类下的子类
        $cate_brand_arr = Db::connect('db_mini_mall')->query("SELECT id,cate_id,brand_id from ims_bb_cate_brand");
        echo 'b=' . time() . '<br>';
        foreach ($cate_brand_arr as $key => $value) {
            //去ims_bb_goods_item表中查询cate_bb2为cate1、brand_id为brand_id。
            if (isset($goods_item_data[$value['cate_id'] . '_' . $value['brand_id']])) {
                $goods_item_arr = $goods_item_data[$value['cate_id'] . '_' . $value['brand_id']];
//                echo "<pre>";
//                print_r($goods_item_arr);exit;
                $sku_count = 0;
                $sku_channel_count = 0;
                if (!empty($goods_item_arr)) {
                    foreach ($goods_item_arr as $g_k => $g_v) {
                        //使用ims_bb_goods_item表中的id。去ims_bb_sku表的goods_id匹配。count有多少个与ims_bb_goods_item表中的ID相同的goods_id
                        if (isset($sku_count_arr_data[$g_v])) {
                            $sku_count = $sku_count + count($sku_count_arr_data[$g_v]);
                            //使用ims_bb_sku表中的ID去ims_bb_city_sku表中的sku_id。取channel_count的值
                            if (!empty(count($sku_count_arr_data[$g_v]))) {
                                foreach ($sku_count_arr_data[$g_v] as $sku_k => $sku_v) {
                                    $channel_count = 0;
                                    if (!empty($channel_city_arr[$sku_v])) {
                                        $channel_count = $channel_city_arr[$sku_v]['channel_count'];
                                    }
                                    $sku_channel_count = $sku_channel_count + $channel_count;
                                }
                            }
                        }
                    }
                }
                //更新
                $update = null;
                $update['sku_count'] = $sku_count;
                $update['sku_channel_count'] = $sku_channel_count;
                Db::connect('db_mini_mall')->table('ims_bb_cate_brand')->where([['id', '=', $value['id']]])->update($update);
            }
        }
        echo 'c=' . time() . '<br>';
    }

    /**
     * 2
     * 插入店铺品牌和分类的关联表
     */
    public function storeBrand() {
//        exit('更新店铺品牌和分类的关联表');
        //查询线上孙宇店铺商品
        $brand_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.brand_id FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 group by a.brand_id ;");
        $time = time();
        $data = [];
        foreach ($brand_arr as $key => $value) {
            //根据品牌ID查询品牌对应的分类
            if(!empty($value['brand_id'])){
                $cate = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.bb_cate1,a.bb_cate2 FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 and a.brand_id = {$value['brand_id']} group by bb_cate2 ORDER BY a.id DESC;");
                if(!empty(count($cate))){
                    foreach ($cate as $k => $v){
//                        $brand_name = Db::connect('db_ydxq_test_xianshang')->table('ims_bb_brand')->where([['id', '=', $value['brand_id']]])->field('b_name')->find();
                        //查询是否存在
                        $cate_brand = Db::connect('db_ydxq_test_xianshang')->query("SELECT id from ims_bb_supplier_cate_brand where sup_id = 461 and cate_id = {$v['bb_cate2']} and brand_id = {$value['brand_id']}");
                        if(empty(count($cate_brand))){
                            $data[] = [
                                'sup_id' => 461,
                                'cate_id' => $v['bb_cate2'],
                                'brand_id' => $value['brand_id'],
//                            'brand_name' => !empty($brand_name['b_name']) ? $brand_name['b_name'] : '',
                                'dist_count' => '',
                                'create_time' => $time,
                                'sku_count' => 0,
                                'sku_channel_count' => 0,
                                'update_time' => $time,
                                'status' => 1,
                            ];
                            if (count($data) > 50) {
                                $sql = $this->insertIntoSql('ims_bb_supplier_cate_brand', $data);
                                Db::connect('db_ydxq_test_xianshang')->execute($sql);
                                unset($data);
                            }
                        }
                    }
                }
            }
        }
        if (count($data) > 0) {
            $sql = $this->insertIntoSql('ims_bb_supplier_cate_brand', $data);
            Db::connect('db_ydxq_test_xianshang')->execute($sql);
            unset($data);
        }
    }

    /**
     * 3
     * 更新孙宇店铺分类：ims_bb_supplier_cate
     */
    public function supplierCate(){
//        exit('更新孙宇店铺分类：ims_bb_supplier_cate');
        //查询孙宇店铺商品一级分类
        $one_cate = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.bb_cate1 FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 group by bb_cate1 DESC;");
        $time = time();
        if(!empty(count($one_cate))){
            foreach ($one_cate as $key => $value){
                //查询是否已经存在当前分类
                $cate1_cate2 = Db::connect('db_ydxq_test_xianshang')->table('ims_bb_supplier_cate')->where([['sup_id','=',461],['cate_bb1','=',0],['cate_bb2','=',$value['bb_cate1']]])->find();
                if(!empty($cate1_cate2)){
                    $one_cate_count = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.bb_cate2 FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 and a.bb_cate1 = {$value['bb_cate1']}");
                    unset($update);
                    $update['skuid_count'] = count($one_cate_count);
                    Db::connect('db_ydxq_test_xianshang')->table('ims_bb_supplier_cate')->where([['id','=',$cate1_cate2['id']]])->update($update);
                }else{
                    //查询分类名称
                    $one_cate_name_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT c_name from ims_bb_cate_bb WHERE id = {$value['bb_cate1']}");
                    //查询当前分类有多少个孙宇店铺商品(根据一级分类ID去孙宇商品表中查询二级分类ID)
                    $one_cate_count = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.bb_cate2 FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 and a.bb_cate1 = {$value['bb_cate1']}");
                    $data[] = [
                        'sup_id' => 461,
                        'cate_bb1' => 0,
                        'cate_bb2' => $value['bb_cate1'],
                        'category_name' => isset($one_cate_name_arr[0]['c_name']) ? $one_cate_name_arr[0]['c_name'] : '',
                        'create_time' => $time,
                        'skuid_count' => count($one_cate_count),
                        'status' => 1,
                    ];
                }

                //根据一级分类ID去孙宇商品表中查询二级分类ID
                $two_cate_count = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.bb_cate2 FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 and a.bb_cate1 = {$value['bb_cate1']} group by a.bb_cate2");
                if(!empty(count($two_cate_count))){
                    foreach ($two_cate_count as $t_k => $t_v){
                        //查询是否已经存在当前分类
                        $cate1_cate2_t = Db::connect('db_ydxq_test_xianshang')->table('ims_bb_supplier_cate')->where([['sup_id','=',461],['cate_bb1','=',$value['bb_cate1']],['cate_bb2','=',$t_v['bb_cate2']]])->find();
                        if(!empty($cate1_cate2_t)){
                            $two_cate_count1 = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.bb_cate2 FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 and a.bb_cate1 = {$value['bb_cate1']} and a.bb_cate2 = {$t_v['bb_cate2']}");
                            unset($update);
                            $update['skuid_count'] = count($two_cate_count1);
                            Db::connect('db_ydxq_test_xianshang')->table('ims_bb_supplier_cate')->where([['id','=',$cate1_cate2_t['id']]])->update($update);
                        }else {
                            //查询分类名称
                            $two_cate_name_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT c_name from ims_bb_cate_bb WHERE id = {$t_v['bb_cate2']}");
                            //查询当前分类有多少个孙宇店铺商品
                            $two_cate_count1 = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.bb_cate2 FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 and a.bb_cate1 = {$value['bb_cate1']} and a.bb_cate2 = {$t_v['bb_cate2']}");
                            $data[] = [
                                'sup_id' => 461,
                                'cate_bb1' => $value['bb_cate1'],
                                'cate_bb2' => $t_v['bb_cate2'],
                                'category_name' => isset($two_cate_name_arr[0]['c_name']) ? $two_cate_name_arr[0]['c_name'] : '',
                                'create_time' => $time,
                                'skuid_count' => count($two_cate_count1),
                                'status' => 1,
                            ];
                        }
                    }
                }
            }
            $sql = $this->insertIntoSql('ims_bb_supplier_cate',$data);
            Db::connect('db_ydxq_test_xianshang')->execute($sql);
        }
    }

    /**
     * 4
     * 更新线上孙宇店铺品牌和分类的商品数量和报价数
     */
    public function onlineSunYu(){
//        exit('更新线上孙宇店铺品牌和分类的商品数量和报价数');
        //查询某个分类下的子类
        $cate_brand_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT id,cate_id,brand_id from ims_bb_supplier_cate_brand where sup_id = 461");
        foreach ($cate_brand_arr as $key => $value) {
            //去孙宇店铺表中查询相应的分类和品牌的商品
            $goods_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.skuid FROM `ims_ewei_shop_goods` as a LEFT JOIN ims_yd_supplier_goods as b on a.id = b.goods_id WHERE b.supplier_id = 461 and a.`status` = 1 and b.`status` = 1 and a.total > 0 and a.deleted = 0 and a.skuid > 1 and a.skuid < 800102797 and a.brand_id = {$value['brand_id']} and a.bb_cate2 = {$value['cate_id']} ORDER BY a.id DESC");
            $sku_count = 0;
            $sku_channel_count = 0;
            if(!empty(count($goods_arr))){
                //根据skuid查询报价数
                foreach ($goods_arr as $k => $v){
                    $channel_count_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT channel_count from ims_bb_city_sku where sku_id = {$v['skuid']}");
                    if(!empty(count($channel_count_arr))){
                        foreach ($channel_count_arr as $cn_k => $cn_v){
                            $sku_channel_count = $sku_channel_count + $cn_v['channel_count'];
                        }
                    }
                }
                $sku_count = $sku_count + count($goods_arr);
            }
            $update = null;
            $update['sku_count'] = $sku_count;
            $update['sku_channel_count'] = $sku_channel_count;
            Db::connect('db_ydxq_test_xianshang')->table('ims_bb_supplier_cate_brand')->where([['id', '=', $value['id']]])->update($update);
        }
        echo 'c=' . time() . '<br>';
    }







    /**
     * 01
     * 更新部分商品的goods_code   goods_code_list  hbsj_sku_id  hbsj_brand_id 	brand_id	bb_cate1	bb_cate2
     * @throws Exception
     */
    public function updateShopGoods(){
        exit('updateShopGoods');
        $goods_list = Db::connect('db_ydxq_test_xianshang')->query("SELECT id,skuid FROM ims_ewei_shop_goods where id in(60467,60472,60481,60486,60487,60889,60974,60975,60976,61031,61055,61057,61058,61059,61065,61067,61068,61120,61146,61162,61164,61175,61192,61199,61207,61247,61260,61263,61265,61271,61277,61279,61286,61313,61315,61351,61353,61354,61356,61357,61358,61360,61361,61363,61364,61365,61369,61381,61425,61445,61449,61461,61465,61480,61486,61493,64255,64828,64829,68229,68230,68231,68232,68233,68234,71618,71621,71623,71624,83098)");
        foreach ($goods_list as $key => $value){
            //根据skuid查询sku表中的goods_id
            $sku_data = Db::connect('db_ydxq_test_xianshang')->table('ims_bb_sku')->field('goods_id,code_list,hbsj_sku_id')->where([['id','=',$value['skuid']]])->find();
            if(!empty($sku_data)){
                if(!empty($sku_data['code_list'])){
                    $hbsj_sku_id = $sku_data['hbsj_sku_id'];
                    $code_list = $sku_data['code_list'];
                    $code_arr = explode(',',$code_list);
                    $code = !empty($code_arr[0]) ? $code_arr[0] : 0;
                    //根据goods_id查询hbsj_brand_id，brand_id，bb_cate1，bb_cate2
                    $goods_item_data = Db::connect('db_ydxq_test_xianshang')->table('ims_bb_goods_item')->field('brand_id,cate_bb1,cate_bb2,hbsj_brand_id')->where([['id','=',$sku_data['goods_id']]])->find();
                    $brand_id = !empty($goods_item_data['brand_id']) ? $goods_item_data['brand_id'] : 0;
                    $cate_bb1 = !empty($goods_item_data['cate_bb1']) ? $goods_item_data['cate_bb1'] : 0;
                    $cate_bb2 = !empty($goods_item_data['cate_bb2']) ? $goods_item_data['cate_bb2'] : 0;
                    $hbsj_brand_id = !empty($goods_item_data['hbsj_brand_id']) ? $goods_item_data['hbsj_brand_id'] : 0;
                    //更新当前商品
                    unset($update);
                    $update['goods_code'] = $code;
                    $update['goods_code_list'] = $code_list;
                    $update['hbsj_sku_id'] = $hbsj_sku_id;
                    $update['hbsj_brand_id'] = $hbsj_brand_id;
                    $update['brand_id'] = $brand_id;
                    $update['bb_cate1'] = $cate_bb1;
                    $update['bb_cate2'] = $cate_bb2;
                    Db::connect('db_ydxq_test_xianshang')->table('ims_ewei_shop_goods')->where([['id','=',$value['id']]])->update($update);
                }
            }
        }
    }

    public function test123(){
        echo time().'<br>';
        $n = 0;
        for ($i = 1; $i <= 100000000; $i++) {
            $n = $n+$i;
        }
//        echo $n.'';
//        echo '<br>';
        echo time().'<br>';
//        echo time().'<br>';
//        $n = 0;
//        for ($i = 1; $i <= 40000; $i++) {
//            $k = 0;
//            for ($j = 1; $j < $i; $j++) {
//                if ($i % $j == 0) {
//                    $k++;
//                }
//            }
//            if ($k == 1) {
////                echo $i."&nbsp;&nbsp;";
//                $n++;
//                if ($n % 20 == 0) {
////                    echo "<br>";
//                }
//                //echo "&nbsp;&nbsp;";
//            }
//        }
//        echo time().'<br>';
    }
    public function localhostSql(){
        echo time().'<br>';
        for($i = 1; $i <= 10000; $i++){
            $a = Db::connect('db_localhost')->query("SELECT * FROM code where id = 2238");
            echo $a[0]['id'];
        }
        echo '<br>'.time().'<br>';
    }

    /**
     * 清洗孙宇店铺的订单，更新每个订单的商品款数和节省金额
     */
    public function orderSaveMoney(){
//        exit('orderSaveMoney');
        echo "<pre>";
        //查询孙宇店铺订单
        $order_list = Db::connect('db_ydxq_test_xianshang')->table('ims_ewei_shop_order')->where('supplier_id = 461')->field('id')->select();
        $order_id_res = '';
        foreach ($order_list as $key => $value){
            $order_id_res .= ','.$value['id'];
        }
//        echo count($order_list);exit;
        $order_id = trim($order_id_res,',');
        $order_goods = Db::connect('db_ydxq_test_xianshang')->table('ims_ewei_shop_order_goods')->alias('a')->leftJoin('ims_ewei_shop_goods b','a.goodsid = b.id')->where('a.orderid in ('.$order_id.') and a.total <> 0 and b.bb_cate2 <> 0')->field('a.id,a.goodsid,a.price,a.total,a.realprice,b.skuid,a.orderid')->select();
        $order_goods_arr = array();
        foreach ($order_goods as $key => $value){
            $order_goods_arr[$value['orderid']][] = $value;
        }
        //开始计算每个订单的商品款数和
        foreach ($order_goods_arr as $o_g_k => $o_g_v){
            $save_money = 0;
            $goods_sum = 0;
            foreach ($o_g_v as $key => $value){
                $one_max_price = 0;
                if(!empty($value['skuid'])){
                    //根据skuid查询最高报价
                    $max_price_arr = Db::connect('db_ydxq_test_xianshang')->table('ims_bb_city_sku')->where('sku_id = '.$value['skuid'])->field('max_price')->find();
                    $one_max_price = !empty($max_price_arr['max_price']) ? $max_price_arr['max_price'] : 0;
                }
                $max_price_all = $one_max_price * $value['total'];
                if($max_price_all > $value['price']){
                    $save_money = $max_price_all - $value['price'] + $save_money;
                }
            }
            $goods_sum = count($o_g_v);
            //更新当前订单
            unset($update);
            $update['goods_count'] = $goods_sum;
            $update['money_save'] = $save_money;
            Db::connect('db_ydxq_test_xianshang')->table('ims_ewei_shop_order')->where('id = '.$o_g_k)->update($update);
            echo $o_g_k.'<br>';
        }
        echo '完成';
    }

    /**
     * 获取周的开始时间和结束时间
     * @param int $week
     * @return mixed
     */
    public function week($week = 0){
        if(empty($week)){
            $week = date('W');
        }
        $dang_nian = date('Y').'-01-01';
        $one_day = date('w',strtotime($dang_nian));
        $start_time = strtotime($dang_nian) + ((60*60*24)*(7-$one_day+1));
        $end = $start_time + (60*60*24*7*($week-1));
        $week_start = date('Y/m/d',strtotime('-1 week',$end));
        $week_end = date('Y/m/d',strtotime('-1 day',$end));
        $arr['week_start'] = $week_start;
        $arr['week_end'] = $week_end;
        return $arr;
    }

    public function test_mysql(){
        $supplier_arr_model = new \app\api\model\ydxq\Supplier();
//        $supplier_arr = Db::connect('db_mini_mall')->query("SELECT * from ims_yd_supplier");
        $supplier_arr = $supplier_arr_model->query("SELECT * from ims_yd_supplier");
        $supplier_arr = $supplier_arr_model->querySql("SELECT * from ims_yd_supplier");
        echo "<pre>";
        print_r($supplier_arr);
    }

    /**
     * 干货调味
     */
    public function ganHuoTiaoWei(){
//        http://www.localhost.ydxqcs_tp_test.com/api/test/ganHuoTiaoWei.html
        echo "<pre>";
        //查询报价
        $channel_data = Db::connect('db_ydxq_test_xianshang')->query("SELECT sku_id,id,price,channel_id FROM `ims_bb_price_list` WHERE channel_id in(1,15,4,2,6,3,20,7)");
        $channel_data_arr = [];
        foreach ($channel_data as $one){
            $channel_data_arr[$one['sku_id']][$one['channel_id']] = $one;
        }
        unset($channel_data);
        //所有的家庭清洁商品
        $goods_item = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.id, b.goods_name, c.channel_count, d.b_name, e.c_name, b.content, a.unit_count, a.unit_name FROM ims_bb_sku a LEFT JOIN ims_bb_goods_item b ON a.goods_id = b.id LEFT JOIN ims_bb_city_sku c ON a.id = c.sku_id LEFT JOIN ims_bb_brand d ON b.brand_id = d.id LEFT JOIN ims_bb_cate_bb e ON b.cate_bb2 = e.id WHERE b.cate_bb1 = 59 AND c.sku_id IS NOT NULL and c.channel_count > 0");
        //查询孙宇店铺货比三家商品
        $sunYu = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.skuid,b.supplier_price,a.bb_step from ims_ewei_shop_goods as a left join ims_yd_supplier_goods as b on a.id = b.goods_id where a.status = 1 and b.status = 1 and a.total> 0 and a.deleted = 0 and b.supplier_id = 461 and a.bb_cate1 = 59 and a.skuid > 199999999 and skuid < 300000000");
        $sunYu_arr = [];
        foreach ($sunYu as $one){
            $sunYu_arr[$one['skuid']] = $one;
        }
        unset($sunYu);
        //查询孙宇店铺在售商品
        $sunYuShow = Db::connect('db_ydxq_test_xianshang')->query("SELECT a.id,a.title,a.skuid,b.supplier_price,a.bb_step from ims_ewei_shop_goods as a left join ims_yd_supplier_goods as b on a.id = b.goods_id where a.status = 1 and b.status = 1 and a.total> 0 and a.deleted = 0 and b.supplier_id = 461 and a.bb_cate1 = 59");
        $sunYuShow_arr = [];
        foreach ($sunYuShow as $one){
            $sunYuShow_arr[$one['skuid']] = $one;
        }
        unset($sunYuShow);
//        echo 'start：'.count($sunYuShow_arr);
//        print_r($sunYuShow_arr);
        $data = array();
        $title = ['skuid','商品名','品牌','二级分类','单品规格','销售规格','是否在售','售价','步长','中商惠民','百世店加','易久批','链商优供','掌柜宝','京批网','大润发e路发','商道行'];
        foreach ($goods_item as $key => $value){
//skuid	商品名	品牌	二级分类	单品规格	销售规格	是否在售	售价	步长	中商惠民	百世店加	易久批	链商优供	掌柜宝	京批网
            $sku_id = $value['id'];
            $goods_data['skuid'] = $sku_id;//skuid
            $goods_data['goods_name'] = $value['goods_name'];//商品名
            $goods_data['b_name'] = $value['b_name'];//品牌名称
            $goods_data['c_name'] = $value['c_name'];//二级分类
            $goods_data['content'] = $value['content'];//单品规格
            $goods_data['unit_count'] = $value['unit_count'];//销售规格
            //判断是否在售
            $is_show = 0;
            $supplier_price = 0;
            $bb_step = 0;
            if(!empty($sunYu_arr[$sku_id])){
                $one_sy_goods = $sunYu_arr[$sku_id];
                $is_show = 1;
                $supplier_price = $one_sy_goods['supplier_price'];
                $bb_step = $one_sy_goods['bb_step'];
            }
            if(!empty($sunYuShow_arr[$sku_id])){
                unset($sunYuShow_arr[$sku_id]);
            }
            $goods_data['is_show'] = !empty($is_show) ? '在售' : '--';//是否在售
            $goods_data['supplier_price'] = !empty($supplier_price) ? $supplier_price : 0;//售价
            $goods_data['bb_step'] = !empty($bb_step) ? $bb_step : 0;//步长
            //查询报价
            $zhongShang = 0;
            $baiShi = 0;
            $yiJiuPi = 0;
            $lianShang = 0;
            $zhangGui = 0;
            $jingPi = 0;
            $shangDao = 0;
            $daRunFa = 0;
            if(!empty($channel_data_arr[$sku_id])){
                $channel_data_one = $channel_data_arr[$sku_id];
                //中商惠民：4
                if(!empty($channel_data_one[4])){
                    $zhongShang = $channel_data_one[4]['price'];
                }
                //百世店加：2
                if(!empty($channel_data_one[2])){
                    $baiShi = $channel_data_one[2]['price'];
                }
                //易久批：6
                if(!empty($channel_data_one[6])){
                    $yiJiuPi = $channel_data_one[6]['price'];
                }
                //链商优供：3
                if(!empty($channel_data_one[3])){
                    $lianShang = $channel_data_one[3]['price'];
                }
                //掌柜宝：20
                if(!empty($channel_data_one[20])){
                    $zhangGui = $channel_data_one[20]['price'];
                }
                //京批网：7
                if(!empty($channel_data_one[7])){
                    $jingPi = $channel_data_one[7]['price'];
                }
                //大润发e路发：1
                if(!empty($channel_data_one[1])){
                    $daRunFa = $channel_data_one[1]['price'];
                }
                //商道行：15
                if(!empty($channel_data_one[15])){
                    $shangDao = $channel_data_one[15]['price'];
                }
            }
            $goods_data['zhongShang'] = $zhongShang;
            $goods_data['baiShi'] = $baiShi;
            $goods_data['yiJiuPi'] = $yiJiuPi;
            $goods_data['lianShang'] = $lianShang;
            $goods_data['zhangGui'] = $zhangGui;
            $goods_data['jingPi'] = $jingPi;
            $goods_data['shangDao'] = $shangDao;
            $goods_data['daRunFa'] = $daRunFa;
            $data[] = $goods_data;
            unset($goods_data);
        }
//        echo 'end：'.count($sunYuShow_arr);
//        print_r($sunYuShow_arr);
        if(!empty(count($sunYuShow_arr))){
            foreach ($sunYuShow_arr as $one){
                $sunYuShow_new_arr[] = [
                    $one['id'],
                    $one['skuid'],
                    $one['title'],
                    $one['supplier_price'],
                ];
            }
        }
//        $this->exportExcel($title,$data,'家庭清洁');
        $this->exportExcel(['id','sku_id','商品名称','价格'],$sunYuShow_new_arr,'家庭清洁_孙宇自营');
    }

    public function sysLogTest(){
        $arr = array('32321','ceui');
        sys_log($arr,1);
        sys_log();
    }
    
    /**
     * 去掉购物车里的限制品
     */
    public function cancelGoodsArea(){
//        110115：大兴区区码
        $goods_cart = Db::connect('db_ydxq_test_xianshang')->table('ims_ewei_shop_member_cart')->where('goodsid = 61492')->field('id,openid')->select();
        $area_id = 0;
        if(!empty(count($goods_cart))){
            foreach ($goods_cart as $one_cart){
                //查询当前购买c端店铺的点位地址
                $user_area_id = Db::connect('db_btj_new')->table('potential_customer')->field('area_id,address')->where('is_validity = 1 and xcx_openid = "'.$one_cart['openid'].'"')->find();
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
                if($area_id != 110115){
                    //说明当前加入购物车的用户不在大兴区，需要把这个商品删除掉
                    Db::connect('db_ydxq_test_xianshang')->table('ims_ewei_shop_member_cart')->where('id = '.$one_cart['id'])->update(['deleted' => 1]);
                }
            }
        }
    }

    public function setPontential(){
        echo 3;exit;
        $sup_openid_arr = Db::connect('db_ydxq_test_xianshang')->query("SELECT id,openid from ims_yd_supplier WHERE id in(234,245,253,256,259,266,282,304,308,322,338,355,375,386,399,404,406,407,416,466,506)");
        $sup_openid_str = '';
        $sup_openid_id_arr = array();
        foreach ($sup_openid_arr as $one){
            $sup_openid_str .= ',"'.$one['openid'].'"';
            $sup_openid_id_arr[$one['openid']] = $one['id'];
        }
        $sup_openid_str = trim($sup_openid_str,',');
        $sup_pontential_arr = Db::connect('db_btj_new')->query("SELECT id,xcx_openid,user_name from potential_customer WHERE xcx_openid in({$sup_openid_str}) and  is_validity = 1 ORDER BY xcx_openid");
        foreach ($sup_pontential_arr as $one_pon){
            $sup_id = !empty($sup_openid_id_arr[$one_pon['xcx_openid']]) ? $sup_openid_id_arr[$one_pon['xcx_openid']] : 0;
            if(!empty($sup_id)){
                Db::connect('db_ydxq_test_xianshang')->execute("UPDATE ims_goods_code_info set potential_id = {$one_pon['id']},is_valid = 1,potential_name = '{$one_pon['user_name']}' where sup_id = {$sup_id}");
            }
        }
        echo "<pre>";
        print_r($sup_pontential_arr);
    }

    /**
     * 计算bd提成
     */
    public function bdMoney(){
        //确认时间
        $start_time = strtotime('2019-12');
        $end_time = strtotime(date('Y-m-01 00:00:00',strtotime('+1 month',$start_time)));
        $order_id_arr = [];
        $order_all_price = 0;
        $order_coupon_all_price = 0;
        $order_goods_all_price = 0;
        //查询订单商品
        $order_goods_list = Db::connect('db_ydxq_test_xianshang')->query("SELECT id,price,goodsid,orderid from ims_ewei_shop_order_goods WHERE orderid in(SELECT `id` FROM `ims_ewei_shop_order` WHERE ( supplier_id = 461 and (status = 1 or status = 3) and createtime >= {$start_time} and createtime < {$end_time} )) and total > 0");
        //查询商品分类
        $goods_list = Db::connect('db_ydxq_test_xianshang')->query("SELECT id,title,bb_cate1,bb_cate2 from ims_ewei_shop_goods where id in(SELECT goodsid from ims_ewei_shop_order_goods WHERE orderid in(SELECT `id` FROM `ims_ewei_shop_order` WHERE ( supplier_id = 461 and (status = 1 or status = 3) and createtime >= {$start_time} and createtime < {$end_time} )) and total > 0)");
//        echo Db::connect('db_ydxq_test_xianshang')->getLastSql();exit;
        $goods_cate_arr = [];
        foreach ($goods_list as $key => $value){
            $goods_cate_arr[$value['id']] = $value['bb_cate1'];
        }
        //计算提成
        $order_bd_money = [];
        $bd_money_count = 0;
        foreach ($order_goods_list as $key => $value){
            $one_order_bd_money = 0;
            if(!empty($order_bd_money[$value['orderid']])){
                $one_order_bd_money = $order_bd_money[$value['orderid']];
            }
            if((!empty($goods_cate_arr[$value['goodsid']])) && ($goods_cate_arr[$value['goodsid']] == 10)){
                $one_order_bd_money = $one_order_bd_money + ($value['price'] * 0.04);
            }else{
                $one_order_bd_money = $one_order_bd_money + ($value['price'] * 0.02);
            }
            $order_bd_money[$value['orderid']] = $one_order_bd_money;
//            echo '<br>-------------'.$bd_money_count;
        }
        echo '<br>订单总价：'.$order_all_price;
        echo '<br>优惠券总价：'.$order_coupon_all_price;
        echo '<br>订单商品总价：'.$order_goods_all_price;
        //更新订单提成
        foreach ($order_bd_money as $key_order_id => $value_bd_money){
            $value_bd_money = round($value_bd_money,2);
            Db::connect('db_ydxq_test_xianshang')->table('ims_ewei_shop_order')->where('id = '.$key_order_id)->update(['bd_money'=>$value_bd_money]);
            $bd_money_count = $bd_money_count + $value_bd_money;
        }
        echo '<br>12月份总提成：'.$bd_money_count;
        asort($order_bd_money);
        echo "<pre>";
        print_r($order_bd_money);
    }
}

?>