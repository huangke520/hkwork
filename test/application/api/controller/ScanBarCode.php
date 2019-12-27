<?php
/**
 * ceadr 2019-08-13
 */

namespace app\api\controller;

use think\Db;
use app\api\model\ydxq\BbCateBb;
use app\api\model\ydxq\BbGoodsItem;
use app\api\model\ydxq\BbGoodsItemExt;
use app\api\model\ydhl\BaseGoods;
use app\api\model\ydxq\BbBarCode;
use app\api\model\ydxq\BbSanBarCodeBuy;
use app\api\model\ydxq\BbSanBarCodeLog;
use app\api\model\ydxq\BbBrandKeyWord;
use app\api\model\ydxq\BbSanBarCodeJd;

use splitWord\splitWord;

class ScanBarCode extends BaseController
{

    public function __construct()
    {
        parent::__construct();

        //为了跨域访问的代码
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET");
    }

    /**
     * @param $str string
     * 字符串去除空格
     * @return string
     */
    function trim_s($str)
    {
        $str_s = empty($str) ? '' : trim($str);
        return $str_s;
    }

    /**
     * @param $url
     * @return bool|false|string
     */
    function curl_get($url)
    {
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

//    function check_barcode($str_bar_code)
//    {
//        $bar_code = $str_bar_code;
//
//        if (stripos($str_bar_code, ',') !== false) {
//            $arr_bar_code = explode(',', $str_bar_code);
//            if (count($arr_bar_code) == 2) {
//                $bar_code = $arr_bar_code[1];
//            }
//        }
//        return $bar_code;
//    }

    /**
     * @throws \think\Exception
     *  1.如果goods_item中找barcode找，找到干嘛
     *  2.goods_item没有，在京东60w找，找到干嘛？
     *  3.京东表60w没有，再接口京东查询，找到干嘛？
     *  4.郑州线下200w，查
     *  5.在线国条商品库，查
     *  6.也没有，干嘛
     */
    public function get_barcode_info()
    {
        $param = $this->request_param;
        $time = time();
        if (!isset($param['barcode'])) {
            sdk_return('', 0, '没有参数barcode');
        }

        $barcode = $param['barcode'];
        $barcode = check_barcode($barcode);
        if (empty($barcode) || strlen($barcode) <= 10 || strlen($barcode) > 16) {
            sdk_return('', 0, "无效的barcode[{$barcode}]");
        }

        $arr_barcode = array();

        //0.是否已经扫码过ims_bb_scan_bar_code_buy
        $m_BbSanBarCodeBuy = new BbSanBarCodeBuy();
        $bar_code_info = $m_BbSanBarCodeBuy->getInfo(['bar_code' => $barcode]);
        if (!empty($bar_code_info)) {
            $m_BbCateBb = new BbCateBb();

            $arr_barcode['goods_barcode'] = $barcode;
            $arr_barcode['goods_type'] = 0;//从ims_bb_scan_bar_code_buy查到
            $arr_barcode['goods_source'] = "商品[" . date('Y-m-d', $bar_code_info['create_time']) . "]已录入";
            $arr_barcode['goods_id'] = $bar_code_info['info'];//不同结果，不同ID
            $arr_barcode['goods_name'] = $bar_code_info['goods_name'];
            $arr_barcode['goods_img'] = $bar_code_info['goods_img'];
            $arr_barcode['goods_cate_bb1'] = $bar_code_info['cate_bb1'];
            $arr_barcode['goods_cate_bb2'] = $bar_code_info['cate_bb2'];
            if (!empty($bar_code_info['cate_bb1'])) {
                $one_cate = $m_BbCateBb->getInfo(['id' => $bar_code_info['cate_bb1']]);
                $arr_barcode['goods_cate_bb1_name'] = $one_cate['c_name'];
            } else {
                $arr_barcode['goods_cate_bb1_name'] = '';
            }

            if (!empty($bar_code_info['cate_bb2'])) {
                $one_cate = $m_BbCateBb->getInfo(['id' => $bar_code_info['cate_bb2']]);
                $arr_barcode['goods_cate_bb2_name'] = $one_cate['c_name'];
            } else {
                $arr_barcode['goods_cate_bb2_name'] = '';
            }
            $arr_barcode['goods_brand'] = 0;
            $arr_barcode['goods_brand_name'] = $bar_code_info['brand_name'];
            $arr_barcode['goods_base_word'] = $bar_code_info['base_word'];
            $arr_barcode['goods_kou_wei'] = $bar_code_info['kou_wei'];
            $arr_barcode['goods_spec_value'] = $bar_code_info['spec_value'];
            $arr_barcode['goods_base_word1'] = $bar_code_info['base_word1'];
            $arr_barcode['brand_list'] = [];
            $arr_barcode['brand_list_cate'] = [];
            $arr_barcode['sale_unit'] = $bar_code_info['sale_unit'];
            $arr_barcode['buy_price'] = $bar_code_info['buy_price'];
            $arr_barcode['buy_count'] = $bar_code_info['buy_count'];

            $this->save_barcode_log($barcode, 0, json_encode($bar_code_info));
            sdk_return($arr_barcode, 1, 'success');
        }

        //1.查基础goods_item表(code_list_str拆出到bb_bar_code)
        $m_BbBarCode = new BbBarCode();
        $bar_code_info = $m_BbBarCode->getInfo(['bar_code' => $barcode]);//bar_code

        if (!empty($bar_code_info) && $bar_code_info['come_from'] == 1) {
            $m_BbGoodsItem = new BbGoodsItem();
            //$one_goods_item = $m_BbGoodsItem->getInfo(['id' => $bar_code_info['come_id'], 'status' => 1]);
            $list_goods_item = $m_BbGoodsItem->querySql("select a.*,b.b_name as brand_name,c.c_name as cate_bb1_name,d.c_name as cate_bb2_name from ims_bb_goods_item a left join ims_bb_brand b on a.brand_id = b.id left join ims_bb_cate_bb c on a.cate_bb1 = c.id left join ims_bb_cate_bb d on a.cate_bb2 = d.id where a.id = {$bar_code_info['come_id']} and a.status = 1");
            if (!empty($list_goods_item)) {
                $one_goods_item = $list_goods_item[0];
                $arr_barcode['goods_barcode'] = $barcode;
                $arr_barcode['goods_type'] = 1;//从goods_item查到
                $arr_barcode['goods_source'] = "来源：1.B2B基础库数据";
                $arr_barcode['goods_id'] = $one_goods_item['id'];//不同结果，不同ID
                $arr_barcode['goods_name'] = $one_goods_item['goods_name'];
                $arr_barcode['goods_img'] = $one_goods_item['img'];
                $arr_barcode['goods_cate_bb1'] = $one_goods_item['cate_bb1'];
                $arr_barcode['goods_cate_bb1_name'] = $one_goods_item['cate_bb1_name'];
                $arr_barcode['goods_cate_bb2'] = $one_goods_item['cate_bb2'];
                $arr_barcode['goods_cate_bb2_name'] = $one_goods_item['cate_bb2_name'];
                $arr_barcode['goods_brand_name'] = $one_goods_item['brand_name'];
                $arr_barcode['goods_base_word'] = $one_goods_item['base_word_str'];
                $arr_barcode['goods_kou_wei'] = $one_goods_item['kou_wei'];
                $arr_barcode['goods_spec_value'] = $one_goods_item['content'];//spec_value
                $arr_barcode['goods_base_word1'] = '';//$one_goods_item['base_word1']
                $arr_barcode['brand_list'] = [];
                $arr_barcode['brand_list_cate'] = [];
                $arr_barcode['sale_unit'] = '';
                $arr_barcode['buy_price'] = '';
                $arr_barcode['buy_count'] = '';

                $this->save_barcode_log($barcode, 1, json_encode($one_goods_item));
                sdk_return($arr_barcode, 1, 'success');
            }
        }

        //2.查京东ims_yd_base_goods表(60w表数据)
        $m_BaseGoods = new BaseGoods();
        $bar_code_info = $m_BaseGoods->getInfo(['code' => $barcode]);//code
        if (!empty($bar_code_info)) {
            $arr_barcode['goods_barcode'] = $barcode;
            $arr_barcode['goods_type'] = 2;//从yd_base_goods查到
            $arr_barcode['goods_source'] = "来源：2.京东库数据";
            $arr_barcode['goods_id'] = $bar_code_info['id'];//不同结果，不同ID
            $arr_barcode['goods_name'] = $bar_code_info['goods_name'];
            $arr_barcode['goods_img'] = $bar_code_info['img'];

            $arr_barcode['goods_brand'] = '0';
            $arr_barcode['goods_brand_name'] = $bar_code_info['trademark'];
            $arr_barcode['goods_base_word'] = $this->get_base_word($bar_code_info['goods_name']);
            $arr_cate = $this->get_cate_by_base_word($arr_barcode['goods_base_word']);
            if (empty($arr_cate)) {
                $arr_barcode['goods_cate_bb1'] = '';
                $arr_barcode['goods_cate_bb2'] = '';
                $arr_barcode['goods_cate_bb1_name'] = '';
                $arr_barcode['goods_cate_bb2_name'] = '';
            } else {
                $arr_barcode['goods_cate_bb1'] = $arr_cate['cate_bb1'];
                $arr_barcode['goods_cate_bb2'] = $arr_cate['cate_bb2'];
                $arr_barcode['goods_cate_bb1_name'] = $arr_cate['cate_bb1_name'];
                $arr_barcode['goods_cate_bb2_name'] = $arr_cate['cate_bb2_name'];
            }

            $arr_barcode['goods_kou_wei'] = $this->get_kou_wei($bar_code_info['goods_name']);
            $arr_barcode['goods_spec_value'] = $bar_code_info['spec'];
            $arr_barcode['goods_base_word1'] = '';
            $arr_barcode['brand_list'] = [];
            $arr_barcode['brand_list_cate'] = [];
            $arr_barcode['sale_unit'] = '';
            $arr_barcode['buy_price'] = '';
            $arr_barcode['buy_count'] = '';

            $this->save_barcode_log($barcode, 2, json_encode($bar_code_info));
            sdk_return($arr_barcode, 1, 'success');
        }

        //$m_BbSanBarCodeJd = new BbSanBarCodeJd();
        //$bar_code_info_jd = $m_BbSanBarCodeJd->getInfo(['bar_code' => $barcode]);//code
        //3.查京东接口
        if (1 == 1) {

            //组织接口所需数据
            $jd_url = 'https://way.jd.com/showapi/barcode';
            $jd_appkey = '45255b8140311586cbd90b78171fe6d0';
            $jd_json_c = $this->curl_get($jd_url . '?code=' . $barcode . '&appkey=' . $jd_appkey);
            //print_r($jd_json_c);
            $jd_json_a = str_replace(array('/*', '*/', '#', '--'), '*', $jd_json_c);
            $jd_json = str_replace("'", '’', $jd_json_a);
            $goods_arr = json_decode($jd_json, true);

            if ($goods_arr['charge']) {

                $bar_code_info = $goods_arr['result']['showapi_res_body'];
                //拿出商品数据//有返回数据不一定有真实结果，再判断一层
                $this->save_barcode_log($barcode, 3, $jd_json);
                if (!empty($bar_code_info['goodsName'])) {
                    $arr_barcode['goods_barcode'] = $barcode;
                    $arr_barcode['goods_type'] = 3;//从jd_api查到
                    $arr_barcode['goods_source'] = "来源：3.京东接口数据";
                    $arr_barcode['goods_id'] = 1;//不同结果，不同ID
                    $arr_barcode['goods_name'] = $bar_code_info['goodsName'];
                    $arr_barcode['goods_img'] = $bar_code_info['sptmImg'];
                    $arr_barcode['goods_brand'] = '0';
                    $arr_barcode['goods_brand_name'] = $bar_code_info['trademark'];
                    $arr_barcode['goods_base_word'] = $this->get_base_word($bar_code_info['goodsName']);
                    $arr_cate = $this->get_cate_by_base_word($arr_barcode['goods_base_word']);
                    if (empty($arr_cate)) {
                        $arr_barcode['goods_cate_bb1'] = '';
                        $arr_barcode['goods_cate_bb2'] = '';
                        $arr_barcode['goods_cate_bb1_name'] = '';
                        $arr_barcode['goods_cate_bb2_name'] = '';
                    } else {
                        $arr_barcode['goods_cate_bb1'] = $arr_cate['cate_bb1'];
                        $arr_barcode['goods_cate_bb2'] = $arr_cate['cate_bb2'];
                        $arr_barcode['goods_cate_bb1_name'] = $arr_cate['cate_bb1_name'];
                        $arr_barcode['goods_cate_bb2_name'] = $arr_cate['cate_bb2_name'];
                    }
                    $arr_barcode['goods_kou_wei'] = $this->get_kou_wei($bar_code_info['goodsName']);
                    $arr_barcode['goods_spec_value'] = $bar_code_info['spec'];
                    $arr_barcode['goods_base_word1'] = '';
                    $arr_barcode['brand_list'] = [];
                    $arr_barcode['brand_list_cate'] = [];
                    $arr_barcode['sale_unit'] = '';
                    $arr_barcode['buy_price'] = '';
                    $arr_barcode['buy_count'] = '';

                    sdk_return($arr_barcode, 1, 'success');

                    if (1 == 1) {
                        $this->insert_jd_json($bar_code_info);
                    }
                }
            }
        }


        //4.查基础goods_item_ext表(郑州线下200w库)
        $m_BbGoodsItemExt = new BbGoodsItemExt();
        $bar_code_info = $m_BbGoodsItemExt->getInfo(['bar_code' => $barcode]);//bar_code
        if (!empty($bar_code_info)) {
            $arr_barcode['goods_barcode'] = $barcode;
            $arr_barcode['goods_type'] = 4;//从goods_item_ext查到
            $arr_barcode['goods_source'] = "来源：4.郑州交易数据";
            $arr_barcode['goods_id'] = $bar_code_info['id'];//不同结果，不同ID
            $arr_barcode['goods_name'] = $bar_code_info['goods_name'];
            $arr_barcode['goods_img'] = '';
            $arr_barcode['goods_brand'] = '0';
            $arr_barcode['goods_brand_name'] = $bar_code_info['brand_name'];
            $arr_barcode['goods_base_word'] = $this->get_base_word($bar_code_info['goods_name']);
            $arr_cate = $this->get_cate_by_base_word($arr_barcode['goods_base_word']);
            if (empty($arr_cate)) {
                $arr_barcode['goods_cate_bb1'] = '';
                $arr_barcode['goods_cate_bb2'] = '';
                $arr_barcode['goods_cate_bb1_name'] = '';
                $arr_barcode['goods_cate_bb2_name'] = '';
            } else {
                $arr_barcode['goods_cate_bb1'] = $arr_cate['cate_bb1'];
                $arr_barcode['goods_cate_bb2'] = $arr_cate['cate_bb2'];
                $arr_barcode['goods_cate_bb1_name'] = $arr_cate['cate_bb1_name'];
                $arr_barcode['goods_cate_bb2_name'] = $arr_cate['cate_bb2_name'];
            }
            $arr_barcode['goods_kou_wei'] = $this->get_kou_wei($bar_code_info['goods_name']);
            $arr_barcode['goods_spec_value'] = '';//$bar_code_info['spec'];
            $arr_barcode['goods_base_word1'] = '';
            $arr_barcode['brand_list'] = [];
            $arr_barcode['brand_list_cate'] = [];
            $arr_barcode['sale_unit'] = '';
            $arr_barcode['buy_price'] = '';
            $arr_barcode['buy_count'] = '';

            $this->save_barcode_log($barcode, 4, json_encode($bar_code_info));
            sdk_return($arr_barcode, 1, 'success');
        }

        //5.在线国条商品库实时查


        //6.以上都没有，截取barcode前9位，再查一下可能信息
        $m_BbBarCode = new BbBarCode();
        //$bar_code_info = $m_BbBarCode->getInfo(['bar_code' => $barcode]);//bar_code
        $barcode9 = substr($barcode, 0, 9);
        $list_bar_code_info = $m_BbBarCode->querySql("select id,come_from,come_id from ims_bb_bar_code where bar_code like '{$barcode9}%' limit 20 ");
        if (!empty($list_bar_code_info)) {

            $arr_barcode['goods_barcode'] = $barcode;
            $arr_barcode['goods_type'] = 5;//从goods_item模糊查询
            $arr_barcode['goods_source'] = "来源：5.模糊匹配库";
            $arr_barcode['goods_id'] = '';//不同结果，不同ID
            $arr_barcode['goods_name'] = '';
            $arr_barcode['goods_img'] = '';
            $arr_barcode['goods_cate_bb1'] = '';
            $arr_barcode['goods_cate_bb2'] = '';
            $arr_barcode['goods_cate_bb1_name'] = '';
            $arr_barcode['goods_cate_bb2_name'] = '';
            $arr_barcode['goods_brand'] = '0';
            $arr_barcode['goods_brand_name'] = '';
            $arr_barcode['goods_base_word'] = '';
            $arr_barcode['goods_kou_wei'] = '';
            $arr_barcode['goods_spec_value'] = '';
            $arr_barcode['goods_base_word1'] = '';

            $arr_goods_item = array();
            foreach ($list_bar_code_info as $one) {
                $arr_goods_item[] = $one['come_id'];
            }
            if (!empty($arr_goods_item)) {
                $str_goods_item = implode(',', $arr_goods_item);
                $list_goods_item = $m_BbBarCode->querySql("select a.id,brand_id,b.b_name as brand_name from ims_bb_goods_item a left join ims_bb_brand b on a.brand_id = b.id where a.id in ({$str_goods_item}) and a.status = 1 group by brand_id");
                $arr_barcode['brand_list'] = $list_goods_item;
                foreach ($list_goods_item as $three) {
                    $list_cate_bb2 = $m_BbBarCode->querySql("select a.cate_id,b.c_name,b.fid,c.c_name as fname from ims_bb_cate_brand a LEFT JOIN ims_bb_cate_bb b on a.cate_id = b.id LEFT JOIN ims_bb_cate_bb c on b.fid = c.id where a.brand_id = {$three['brand_id']} ORDER BY a.sku_count desc limit 10 ");
                    $arr_barcode['brand_list_cate'][$three['brand_id']] = $list_cate_bb2;
                }
            }
            $arr_barcode['sale_unit'] = '';
            $arr_barcode['buy_price'] = '';
            $arr_barcode['buy_count'] = '';

            $this->save_barcode_log($barcode, 5, json_encode($list_bar_code_info));
            sdk_return($arr_barcode, 1, 'success');
        }


        //7.都没有，返回完整结构
        if (empty($bar_code_info)) {
            $arr_barcode['goods_barcode'] = $barcode;
            $arr_barcode['goods_type'] = 9;//从goods_item_ext查到
            $arr_barcode['goods_source'] = "来源：9.暂无数据";
            $arr_barcode['goods_id'] = '';//不同结果，不同ID
            $arr_barcode['goods_name'] = '';
            $arr_barcode['goods_img'] = '';
            $arr_barcode['goods_cate_bb1'] = '';
            $arr_barcode['goods_cate_bb2'] = '';
            $arr_barcode['goods_cate_bb1_name'] = '';
            $arr_barcode['goods_cate_bb2_name'] = '';
            $arr_barcode['goods_brand'] = '0';
            $arr_barcode['goods_brand_name'] = '';
            $arr_barcode['goods_base_word'] = '';
            $arr_barcode['goods_kou_wei'] = '';
            $arr_barcode['goods_spec_value'] = '';
            $arr_barcode['goods_base_word1'] = '';
            $arr_barcode['brand_list'] = [];
            $arr_barcode['brand_list_cate'] = [];
            $arr_barcode['sale_unit'] = '';
            $arr_barcode['buy_price'] = '';
            $arr_barcode['buy_count'] = '';

            $this->save_barcode_log($barcode, 9, '');
            sdk_return($arr_barcode, 1, 'success');
        }
    }

    /**
     * 传入接口返回的json，插入品牌、厂商、和base_goods表
     */
    function insert_jd_json()
    {
//        //插入品牌
//        $brand_name_new = str_replace("'", '’', $goods_trademark);
//        if (empty($brand_name_new)) {
//            sdk_return('', 0, '京东api未获取到品牌信息');
//        }
//
//        //分类
//        if (!empty($goods_type)) {
//            $category_arr = explode('>>', $goods_type);
//            foreach ($category_arr as $cate_k => $cate_v) {
//                $cate_key = $cate_k + 1;
//                $category['category' . $cate_key] = $cate_v;
//            }
//        }
//        //关键字
//        $key_word = $this->getKeywork($goods_note);
//        $jd_category1 = !empty($category['category1']) ? $category['category1'] : '';//一级分类
//        $jd_category2 = !empty($category['category2']) ? $category['category2'] : '';//二级分类
//        $jd_category3 = !empty($category['category3']) ? $category['category3'] : '';//三级分类
//        $jd_category4 = !empty($category['category4']) ? $category['category4'] : '';//四级分类
//        //查询是否有国标四级分类与本地分类的关联
//        //$mall_category_arr = $this->setCategory($jd_category4);
//        $mall_category_arr = $this->setCategory();
//
//        $mall_category = $mall_category_arr['mall_category'];
//        $mall_category_id = $mall_category_arr['mall_category_id'];
//
//        //如果存在当前商品，更新品牌，如果不存在插入一条新数据
//        if (!count($base_good_info)) {
//            //插入商品数据
//            $goods_no_jd_insert = [
//                'code' => $barcode,
//                'success' => 2,
//                'jd_time' => $time,
//                'goods_name' => $goodsName,
//                'factory' => $goods_manuName,
//                'sptm_img' => $sptmImg,
//                'spec' => $goods_spec,
//                'img' => $goods_img,
//                'img_list' => $goods_imgList,
//                'price' => $goods_price,
//                'trademark' => $goods_trademark,
//                'manu_address' => $goods_manuAddress,
//                'note' => $goods_note,
//                'goods_type' => $goods_type,
//                'addtime' => $time,
//                'keywork' => $key_word,
//                'category1' => $jd_category1,
//                'category2' => $jd_category2,
//                'category3' => $jd_category3,
//                'category4' => $jd_category4,
//                'mall_category' => $mall_category,
//                'mall_category_id' => $mall_category_id,
//            ];
//
//            $base_goods_id = Db::connect($db_ydhl)->table('yd_base_goods')->insertGetId($goods_no_jd_insert);
//        } else {
//            $rst = Db::connect($db_ydhl)->table('yd_base_goods')->where(['id' => $base_good_info['id']])->update(['trademark' => $brand_name_new]);
//        }
//
//        //ims_yd_base_brand判断当前品牌是否存在品牌表中
//        $base_brand_info = Db::connect($db_ydhl)->table('yd_base_brand')->where(['brand_name' => $brand_name_new])->find();
//        if (empty($base_brand_info)) {
//            //插入一条新的数据到brand表
//            $brad_data = ['brand_name' => $brand_name_new];
//            $brad_id = Db::connect($db_ydhl)->table('yd_base_brand')->insertGetId($brad_data);
//        }

        //判断ims_yd_base_factory有没有，之前都没有处理，先这样，jd_josn有，需要时，统一批量洗一下
    }

    function save_barcode_log($bar_code, $res_type, $res_info)
    {
        $create_time = time();
        $arr_SanBarCodeBuy = [
            'res_type' => $res_type,
            'bar_code' => $bar_code,
            'res_info' => $res_info,
            'create_time' => $create_time,
        ];

        $m_BbSanBarCodeLog = new BbSanBarCodeLog();
        $res = $m_BbSanBarCodeLog->insertInfo($arr_SanBarCodeBuy);
    }

    /**
     * 保存扫完barcode后的输入信息(采购价格，采购起数等)
     */
    public function save_barcode_info()
    {
        $param = $this->request_param;

        if (!isset($param['barcode']) || empty($param['barcode']) || strlen($param['barcode']) <= 10 || strlen($param['barcode']) > 16) {
            sdk_return('', 0, '无效的参数barcode');
        }

        $barcode = $param['barcode'];
        $type = $param['type'];
        $goods_img = $param['goods_img'];
        $goods_name = $param['goods_name'];
        $goods_name_full = $param['goods_name_full'];
        $brand_name = $param['brand_name'];
        $kou_wei = $param['kou_wei'];
        $spec_value = $param['spec_value'];
        $base_word = $param['base_word'];
        $base_word1 = $param['base_word1'];
        $cate_bb1 = $param['cate_bb1'];
        $cate_bb2 = $param['cate_bb2'];
        $sale_unit = $param['sale_unit'];
        $buy_price = $param['buy_price'];
        $buy_count = $param['buy_count'];
        $create_time = time();
        $status = 1;
        $info = $param['info'];

        $arr_SanBarCodeBuy = [
            'type' => $type,
            'bar_code' => $barcode,
            'goods_img' => $goods_img,
            'goods_name' => $goods_name,
            'goods_name_full' => $goods_name_full,
            'brand_name' => $brand_name,
            'kou_wei' => $kou_wei,
            'spec_value' => $spec_value,
            'base_word' => $base_word,
            'base_word1' => $base_word1,
            'cate_bb1' => $cate_bb1,
            'cate_bb2' => $cate_bb2,
            'sale_unit' => $sale_unit,
            'buy_price' => $buy_price,
            'buy_count' => $buy_count,
            'create_time' => $create_time,
            'status' => $status,
            'info' => $info,
        ];

        $m_BbSanBarCodeBuy = new BbSanBarCodeBuy();
        $res = $m_BbSanBarCodeBuy->insertInfo($arr_SanBarCodeBuy);
        sdk_return($res, 1, 'success');
    }

//    public function edit_barcode_info()
//    {
//        $param = $this->request_param;
//
//        if (!isset($param['id']) || empty($param['id'])) {
//            sdk_return('', 0, '无效的参数id');
//        }
//        $id = $param['id'];
//        $m_BbSanBarCodeBuy = new BbSanBarCodeBuy();
//        $one_BbSanBarCodeBuy = $m_BbSanBarCodeBuy->getInfo(['id' => $id]);
//        sdk_return($one_BbSanBarCodeBuy, 1, 'success');
//    }

    /**
     * 获取分类可1、2级列表
     * @param cate_bb1
     */
    function get_cate($cate_bb1)
    {
        $m_BbCateBb = new BbCateBb();
        if (empty($cate_bb1)) {
            $cate_list = $m_BbCateBb->getAllList(['fid' => 0, 'status' => 1]);
        } else {
            $cate_list = $m_BbCateBb->getAllList(['fid' => $cate_bb1, 'status' => 1]);
        }
        sdk_return($cate_list, 1, 'success');
    }

    /**
     * 切换分类，重新拆分base_word1
     * @param $cate_bb2
     * @param $goods_name
     */
    function get_split_word($cate_bb2, $goods_name)
    {

        $arr_result = array();
        if (!empty($cate_bb2)) {
            $m_BbBrandKeyWord = new BbBrandKeyWord();
            $list_key_word = $m_BbBrandKeyWord->getAllList(['cate_bb2' => $cate_bb2, 'status' => 1]);

            $class_splitWord = new splitWord();
            $dict = $class_splitWord->get_dict($list_key_word);
            $dict = $class_splitWord->fix_dict($dict);
            $class_splitWord->set_dict($dict);
            $class_splitWord->split_word($goods_name);

            $arr_result['base_word1'] = $goods_name;
        }
        sdk_return($arr_result, 1, 'success');
    }


    function get_base_word($goods_name, $dict_base_word = [])
    {
        $str_base_word = '';
        if (!empty($cate_bb2)) {
            $m_BbBrandKeyWord = new BbBrandKeyWord();
            //货比三价-基础词
            $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 4 and status = 1  GROUP BY key_word order by key_weight desc";
            $list_key_word = $m_BbBrandKeyWord->querySql($sql_key_word);
            $arr_key_word4 = array();
            foreach ($list_key_word as $one) {
                $arr_key_word4[] = $one['key_word'];
            }

            foreach ($arr_key_word4 as $kk => $vv) {
                if (stripos($goods_name, $vv) !== false) {
                    $str_base_word = $vv;
                    break;
                }
            }
        }
        return $str_base_word;
    }

    function get_kou_wei($goods_name, $dict_kou_wei = [])
    {
        $str_kou_wei = '';
        if (!empty($cate_bb2)) {
            $m_BbBrandKeyWord = new BbBrandKeyWord();
            //货比三价-口味
            $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' GROUP BY key_word order by key_weight desc";
            $list_key_word = $m_BbBrandKeyWord->querySql($sql_key_word);
            $arr_key_word2 = array();
            foreach ($list_key_word as $one) {
                $arr_key_word2[] = $one['key_word'];
            }

            foreach ($arr_key_word2 as $kk => $vv) {
                if (stripos($goods_name, $vv) !== false) {
                    $str_kou_wei = $vv;
                    break;
                }
            }
        }
        return $str_kou_wei;
    }

    /**
     * 根据base_word返回权重最大的一个二级分类
     * @param $key_word
     * @param array $dict_key_word
     * @return mixed|string
     */
    function get_cate_by_base_word($key_word, $dict_key_word = [])
    {
        $arr_cate = array();
        if (!empty($key_word)) {
            $m_BbBrandKeyWord = new BbBrandKeyWord();
            //货比三价-基础词
            $sql_key_word = "SELECT	a.cate_bb2,	sum(a.weight) AS key_weight,b.c_name as cate_bb2_name,b.fid as cate_bb1,c.c_name as cate_bb1_name FROM ims_bb_brand_keyword a left join ims_bb_cate_bb b on a.cate_bb2=b.id left join ims_bb_cate_bb c on b.fid=c.id WHERE a.key_type = 4 AND a.`STATUS` = 1 AND a.key_word = '{$key_word}' ORDER BY key_weight DESC";
            $list_key_word = $m_BbBrandKeyWord->querySql($sql_key_word);

            foreach ($list_key_word as $one) {
                $arr_cate['cate_bb1'] = $one['fid'];
                $arr_cate['cate_bb2'] = $one['cate_bb2'];
                break;
            }

        }
        return $arr_cate;
    }


    public function get_barcode_cate()
    {
        $param = $this->request_param;
        if (!isset($param['barcode']) || empty($param['barcode']) || strlen($param['barcode']) <= 10 || strlen($param['barcode']) > 16) {
            sdk_return('', 0, '无效的参数barcode');
        }

        $barcode = $param['barcode'];
        $cate_bb1 = $param['cate_bb1'];

        return $this->get_cate($cate_bb1);

    }

    public function get_barcode_split_word()
    {
        $param = $this->request_param;
        if (!isset($param['barcode']) || empty($param['barcode']) || strlen($param['barcode']) <= 10 || strlen($param['barcode']) > 16) {
            sdk_return('', 0, '无效的参数barcode');
        }

        $barcode = $param['barcode'];
        $cate_bb2 = $param['cate_bb2'];
        $goods_name = $param['goods_name'];

        return $this->get_split_word($cate_bb2, $goods_name);
    }

}