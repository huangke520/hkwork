<?php
/**
 * ceadr 2019-08-13
 */

namespace app\api\controller;

use think\Db;

class JdBaseGoods extends BaseController {

    public function __construct() {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET");
    }

    //根据barcode获取品牌
    public function get_brand_by_barcode(){
        $param = $this->request_param;

        $time = time();

        if(!isset($param['barcode']) || empty($param['barcode'])){
            sdk_return('', 0, '无效的参数barcode');
        }
        $barcode = $param['barcode'];

        if(!isset($param['customer_id']) || empty(intval($param['customer_id']))){
            sdk_return('', 0, '无效的参数customer_id');
        }
        $customer_id = $param['customer_id'];

        if(strstr($barcode, ',')  !== false){
            $barcode_arr = explode(',', $barcode);
            $barcode = $barcode_arr[ count($barcode_arr) - 1 ];
        }

        //查询京东三十万数据
        $db_ydhl = 'db_ydhl';

        $base_good_info = Db::connect($db_ydhl)->table('yd_base_goods')->where(['code'=>$barcode])->find();

        //如果不存在，请求京东接口
        if(!count($base_good_info) || empty($base_good_info['trademark'])){
            //查询品牌表
            $jd_url = 'https://way.jd.com/showapi/barcode';
            $jd_appkey = '45255b8140311586cbd90b78171fe6d0';
            $jd_json_c = $this->curl_get($jd_url . '?code=' . $barcode . '&appkey=' . $jd_appkey);
            $jd_json_a = str_replace(array('/*','*/','#','--'), '*', $jd_json_c);
            $jd_json = str_replace("'", '’', $jd_json_a);
            $goods_arr = json_decode($jd_json, true);

            //如果请求京东接口失败
            if (!$goods_arr['charge']) {
                sdk_return('', 0, '京东api未获取到商品信息');
            }

            $goods_data = $goods_arr['result']['showapi_res_body'];
            //拿出商品数据
            $sptmImg = $this->trim_s($goods_data['sptmImg']);//条码图片
            $goodsName = $this->trim_s($goods_data['goodsName']);//名称
            $goods_price = $this->trim_s($goods_data['price']);//价格
            $goods_spec = $this->trim_s($goods_data['spec']);//商品规格
            $goods_img = $this->trim_s($goods_data['img']);//商品图片
            $goods_imgList = !empty($goods_data['imgList']) ? implode(';', $goods_data['imgList']) : '';//商品图片列表。好多图片
            $goods_manuName = str_replace("'", '’', $this->trim_s($goods_data['manuName']));//生产厂商
            $goods_manuAddress = $this->trim_s($goods_data['manuAddress']);//厂商地址
            $goods_trademark = str_replace("'", '’', $this->trim_s($goods_data['trademark']));;//品牌名称
            $goods_note = $this->trim_s($goods_data['note']);//品牌备注
            $goods_type = $this->trim_s($goods_data['goodsType']);//品牌四级分类

            //插入品牌
            $brand_name_new = str_replace("'", '’', $goods_trademark);
            if(empty($brand_name_new)){
                sdk_return('', 0, '京东api未获取到品牌信息');
            }

            //分类
            if (!empty($goods_type)) {
                $category_arr = explode('>>', $goods_type);
                foreach ($category_arr as $cate_k => $cate_v) {
                    $cate_key = $cate_k + 1;
                    $category['category' . $cate_key] = $cate_v;
                }
            }
            //关键字
            $key_word = $this->getKeywork($goods_note);
            $jd_category1 = !empty($category['category1']) ? $category['category1'] : '';//一级分类
            $jd_category2 = !empty($category['category2']) ? $category['category2'] : '';//二级分类
            $jd_category3 = !empty($category['category3']) ? $category['category3'] : '';//三级分类
            $jd_category4 = !empty($category['category4']) ? $category['category4'] : '';//四级分类
            //查询是否有国标四级分类与本地分类的关联
            //$mall_category_arr = $this->setCategory($jd_category4);
            $mall_category_arr = $this->setCategory();

            $mall_category = $mall_category_arr['mall_category'];
            $mall_category_id = $mall_category_arr['mall_category_id'];

            //如果存在当前商品，更新品牌，如果不存在插入一条新数据
            if(!count($base_good_info)){
                //插入商品数据
                $goods_no_jd_insert = [
                    'code' => $barcode,
                    'success' => 2,
                    'jd_time' => $time,
                    'goods_name' => $goodsName,
                    'factory' => $goods_manuName,
                    'sptm_img' => $sptmImg,
                    'spec' => $goods_spec,
                    'img' => $goods_img,
                    'img_list' => $goods_imgList,
                    'price' => $goods_price,
                    'trademark' => $goods_trademark,
                    'manu_address' => $goods_manuAddress,
                    'note' => $goods_note,
                    'goods_type' => $goods_type,
                    'addtime' => $time,
                    'keywork' => $key_word,
                    'category1' => $jd_category1,
                    'category2' => $jd_category2,
                    'category3' => $jd_category3,
                    'category4' => $jd_category4,
                    'mall_category' => $mall_category,
                    'mall_category_id' => $mall_category_id,
                ];
                
                $base_goods_id = Db::connect($db_ydhl)->table('yd_base_goods')->insertGetId($goods_no_jd_insert);
            }else{
                $rst = Db::connect($db_ydhl)->table('yd_base_goods')->where(['id'=>$base_good_info['id']])->update(['trademark'=>$brand_name_new]);
            }

        }else{
            $brand_name_new = $base_good_info['trademark'];
        }

        //判断当前品牌是否存在品牌表中
        $base_brand_info = Db::connect($db_ydhl)->table('yd_base_brand')->where(['brand_name'=>$brand_name_new])->find();

        if(empty($base_brand_info)){
            //插入一条新的数据到brand表
            $brad_data = [
                'brand_name'        =>  $brand_name_new
            ];

            $brad_id = Db::connect($db_ydhl)->table('yd_base_brand')->insertGetId($brad_data);
        }else{
            $brad_id = $base_brand_info['id'];
        }

        //验证当前品牌是否在售
        $db_btj_new = 'db_btj_new';
        $bd_log = Db::connect($db_btj_new)->table('btj_brand_bd_log')->where([['customer_id', '=', $customer_id], ['brand_id', '=', $brad_id]])->order('id', 'desc')->find();
        $type = empty($bd_log) ? 0 : $bd_log['type'];

        //返回品牌信息
        $return_data[] = [
            'brand_id'      =>  $brad_id,//品牌id
            'brand_name'    =>  $brand_name_new,//品牌名称
            'type'          =>  $type
        ];

        sdk_return($return_data, 1, '获取品牌成功');
    }

    /**
     * @return string
     * 字符串去除空格
     * @return string
     */
    function trim_s($str) {
        $str_s = empty($str) ? '' : trim($str);
        return $str_s;
    }

    /**
     * 查询关键字
     */
    function getKeywork($goods_note = '') {
        if (empty($goods_note)) {
            return '';
        }
        //关键字
        $fen = strpos($goods_note, '；');
        $key_word = '';
        if ($fen !== false) {
            $key_work_str = str_replace(' ', '&&', $goods_note);
            $key_work = explode('；', $key_work_str);
            foreach ($key_work as $k => $v) {
                if (mb_substr($v, 0, 3) == '关键字') {
                    $arr1 = explode('：', $v);
                    $key_word = $arr1[1];
                }
            }
        } else {
            $key_work = explode(' ', $goods_note);
            foreach ($key_work as $k => $v) {
                if (mb_substr($v, 0, 3) == '关键字') {
                    $arr1 = explode('：', $v);
                    $key_word = $arr1[1];
                }
            }
        }//关键词
        return $key_word;
    }

    /**
     * 查询是否有国标四级分类与本地分类的关联
     */
    public function setCategory($jd_category4 = '') {

        $db_ydhl = 'db_ydhl';

        if (empty($jd_category4)) {
            $mall_category_return = [
                'mall_category' => '',
                'mall_category_id' => '',
            ];
            return $mall_category_return;
        }
        //查询是否有国标四级分类与本地分类的关联
        $jd_category4 = trim($jd_category4);
        //$mall_category_arr = pdo_fetch("SELECT mall_category,mall_category_id from ims_yd_category_inter where inter_cate_name = '{$jd_category4}'");
        $mall_category_arr = Db::connect($db_ydhl)->field('mall_category,mall_category_id')->table('ims_yd_category_inter')->where(['inter_cate_name'=>$jd_category4])->find();
        $mall_category = '';
        $mall_category_id = 0;
        if (!empty($mall_category_arr)) {
            //表示存在
            $mall_category = $mall_category_arr['mall_category'];
            $mall_category_id = $mall_category_arr['mall_category_id'];
        } else {
            //表示不存在，
            //查询四级分类ID
            //$inter_category_arr = $this->db_goods->fetch("SELECT id,title from base_unspsc where title = '{$jd_category4}' and `level` = 4");
            $inter_category_arr = Db::connect($db_ydhl)->field('id,title')->table('base_unspsc')->where(['title'=>$jd_category4])->find();
            $inter_cate_name = $jd_category4;
            $inter_category_id = 0;
            if (!empty($inter_category_arr)) {
                $inter_cate_name = $inter_category_arr['title'];
                $inter_category_id = $inter_category_arr['id'];
            }
            //需要插入关联表，进行人工关联
            pdo_query("INSERT INTO ims_yd_category_inter(inter_cate_name,inter_category_id) value ('{$inter_cate_name}','{$inter_category_id}')");
        }
        $mall_category_return = [
            'mall_category' => $mall_category,
            'mall_category_id' => $mall_category_id,
        ];
        return $mall_category_return;
    }

    /**
     * @cc curl_get请求
     * @param $url
     */
    function curl_get($url) {
        if (extension_loaded('curl')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, false);
            $res = curl_exec($curl);
            curl_close($curl);
        } else {
            $res = file_get_contents($url);
        }
        return $res;
    }
}