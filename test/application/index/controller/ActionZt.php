<?php
/**
 * Created by originThink
 * Author: seaboyer@163.com
 * Date: 2019/7/26
 */

namespace app\index\controller;

use app\api\model\btjnew\Sign;
use think\facade\Cache;

//use think\Controller;
//use app\api\model\CommonBaseModel;
use app\api\model\btjnew\Sign as SignModel;
use app\api\model\ydhl\ParityProduct as ParityProductModel;
use app\api\model\ydxq\ShopGoods as ShopGoodsModel;
use think\Db;
use think\Exception;
use think\exception\DbException;

use splitWord\splitWord;

//use sameStr\sameStr;

class ActionZt extends BaseController
{
    /**
     * seaboyer的一些批量处理方法的入口
     *
     */
    public function index()
    {
        $act = $this->request->param("act");

        if (!empty($act) && stripos($act, 'index_') !== false) {

            if (method_exists($this, $act)) {
                $this->$act();
            } else {
                echo "<div align='center'>no function : " . $act . "</div>";
            }
        }
    }

    /**
     * index_test
     *
     */
    public function index_test()
    {

        echo "test<br>";
        echo $this->get_goods_spec("美年达青苹果550ml");
        echo $this->get_goods_spec("康师傅绿茶1L");
    }

    /**
     * index_f0
     */
    public function index_f0()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        echo "0 " . time() . "<br>";
//        $a = 0;
//        for ($i = 0; $i < 100000000; $i++) {
//            $a = 0;
//        }
//        echo "a " . time() . "<br>";
//        $a = 0;
//        for ($i = 0; $i < 100000000; $i++) {
//            if ($i == 1) {
//                $a++;
//            }
//        }
//        echo "b " . time() . "<br>";
//        $a = 0;
//        for ($i = 0; $i < 100000000; $i++) {
//            if ($i >= 0) {
//                $a++;
//            }
//        }
//        echo "c " . time() . "<br>";
//        $arr = array();
//        for ($i = 0; $i < 3000000; $i++) {
//            //if($i >= 0){
//            $arr[] = $i;
//            //}
//        }
//        echo count($arr) . "<br>";
//        echo "d " . time() . "<br>";
//
//        $a = 0;
//        for ($i = 0; $i < 100000000; $i++) {
//            if ($i % 13 == 0) {
//                $a++;
//            }
//        }
//        echo $a . "<br>";
//        echo "e " . time() . "<br>";

//        $n = 0;
//        for ($i = 1; $i <= 40000; $i++) {
//            $k = 0;
//            for ($j = 1; $j < $i; $j++) {
//                if ($i % $j == 0) {
//                    $k++;
//                }
//            }
//            if ($k == 1) {
//                echo $i."&nbsp;&nbsp;";
//                $n++;
//                if ($n % 20 == 0) {
//                    echo "<br>";
//                }
//                //echo "&nbsp;&nbsp;";
//            }
//        }
//        echo "<br>f " . time() . "<br>";
//
//        $n = 0;
//        for ($i = 1; $i <= 100000000; $i++) {
//            $n = $n+$i;
//        }
//        echo $n;
//        echo "<br>g " . time() . "<br>";
//        $split_word = new splitWord();
//        $dict = array('中华');
//        $split_word->set_dict($dict);
//        $arr_word = $split_word->split_word("中华人民共和国万岁");
//        print_r($arr_word);
//        $a = array("goods_id"=>3);
//        print_r(json_encode($a));

//        $m_ShopGoodsModel = new ShopGoodsModel();
//        for ($i = 1; $i <= 10000; $i++) {
//            //$sqlShopGoods = "select id from ims_bb_cate_bb where id = 1";
//            //$shopGoodsCodeList = $m_ShopGoodsModel->querySql($sqlShopGoods);
//            $shopGoodsCodeList = $m_ShopGoodsModel->executeSql("update ims_bb_cate_bb set fid = 0 where id = 1");
//            echo $shopGoodsCodeList . "<br>";
//        }
        echo "<br> " . time() . "<br>";
    }


    /**
     * //foreach实现
     * @return string
     */
    public function index_f1()
    {
        set_time_limit(0);
        $res = array();
        $m_RemoveModel = new ParityProductModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $st = time();
        echo "a " . time() . "<br>";
        // 查询goods_code 不为空 并且 skuid 不为空
        $sqlShopGoods = "SELECT id , goods_code ,skuid   FROM  ims_ewei_shop_goods   WHERE    goods_code !=''   AND  skuid ='' ORDER BY id desc  limit 500";
        $shopGoodsCodeList = $m_ShopGoodsModel->querySql($sqlShopGoods);
        echo "b " . time() . "<br>";
        $sqlProductAll = "SELECT  DISTINCT barcode  , id AS skuid  FROM  bsj_parity_product  ";
        $parityProductAll = $m_RemoveModel->querySql($sqlProductAll);
        echo "c " . time() . "<br>";
        foreach ($shopGoodsCodeList AS $k => $v) {
            foreach ($parityProductAll AS $parityProductK => $parityProductV) {
                if ($v['goods_code'] == $parityProductV['barcode'] AND $v['skuid'] == '') {
                    $upOne = array(
                        'skuid' => $parityProductV['skuid']
                    );
                    $m_ShopGoodsModel->updateInfo($v['id'], $upOne); //更新 shop_goods 表
                    //$res[$v['id']] = $parityProductV['skuid'];
                    break;
                }
            }
        }
        echo "e " . time() . "<br>";
        $en = time();
        echo $en - $st . "<br>";
        //var_dump($res);
        return "up  data ok !";
    }

    public function index_q()
    {
        set_time_limit(0);
        $res = array();

        $q = $this->request->param("q");
        $t = $this->request->param("t", '');
        $sq = check_pram($q);

        $m_ParityProduct = new ParityProductModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $m_SignModel = new SignModel();

        echo "s " . time() . "<br>";
        if ($t == 'btj') {
            $res = $m_SignModel->querySql($sq);
        } elseif ($t == 'ydhl') {
            $res = $m_ParityProduct->querySql($sq);
        } else {
            $res = $m_ShopGoodsModel->querySql($sq);
        }

        echo_html($res);

        echo "e " . time() . "<br>";
    }

    /**
     * //isset
     * @return string
     */
    public function index_f2()
    {
        set_time_limit(0);
        $res = array();
        $m_RemoveModel = new ParityProductModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $st = time();
        // 查询goods_code 不为空 并且 skuid 不为空
        echo "a " . time() . "<br>";
        $sqlShopGoods = "SELECT id , goods_code  FROM  ims_ewei_shop_goods   WHERE    goods_code !=''   AND  skuid ='' ORDER BY id desc ";
        $shopGoodsCodeList = $m_ShopGoodsModel->querySql($sqlShopGoods);
        echo "b " . time() . "<br>";
        $sqlProductAll = "SELECT barcode  , id AS skuid  FROM  bsj_parity_product ";
        $parityProductAll = $m_RemoveModel->querySql($sqlProductAll);
        $sku_list = array();
        echo "c " . time() . "<br>";
        foreach ($parityProductAll as $one) {
            if (!empty($one['barcode'])) {
                $arr = explode(',', $one['barcode']);
                $sku_id = $one['skuid'];
                foreach ($arr as $two) {
                    $sku_list[$two] = $sku_id;
                }
            }
        }
        //echo count($sku_list);
        echo "d " . time() . "<br>";
        foreach ($shopGoodsCodeList AS $k => $v) {
            if (isset($sku_list[$v['goods_code']])) {//array_key_exists
                $upOne = array(
                    'skuid' => $sku_list[$v['goods_code']]
                );
                $m_ShopGoodsModel->updateInfo($v['id'], $upOne); //更新 shop_goods 表
                //$res[$v['id']] = $sku_list[$v['goods_code']];
            }
        }
        echo "e " . time() . "<br>";
        $en = time();
        echo $en - $st . "<br>";
        //var_dump($res);
        return "up  data ok !";
    }


    /**
     * //array_key_exists,如果2、3的判断时间过长，就用redis，否则不用
     * @return string
     */
    public function index_f3()
    {
        set_time_limit(0);
        $res = array();
        $m_RemoveModel = new ParityProductModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $st = time();
        // 查询goods_code 不为空 并且 skuid 不为空
        echo "a " . time() . "<br>";
        $sqlShopGoods = "SELECT id , goods_code  FROM  ims_ewei_shop_goods   WHERE    goods_code !=''   AND  skuid ='' ORDER BY id desc ";
        $shopGoodsCodeList = $m_ShopGoodsModel->querySql($sqlShopGoods);
        echo "b " . time() . "<br>";
        $sqlProductAll = "SELECT barcode  , id AS skuid  FROM  bsj_parity_product ";
        $parityProductAll = $m_RemoveModel->querySql($sqlProductAll);
        $sku_list = array();
        echo "c " . time() . "<br>";
        foreach ($parityProductAll as $one) {
            if (!empty($one['barcode'])) {
                $arr = explode(',', $one['barcode']);
                $sku_id = $one['skuid'];
                foreach ($arr as $two) {
                    $sku_list[$two] = $sku_id;
                }
            }
        }
        //echo count($sku_list);
        echo "d " . time() . "<br>";
        foreach ($shopGoodsCodeList AS $k => $v) {
            if (array_key_exists($v['goods_code'], $sku_list)) {
                $upOne = array(
                    'skuid' => $sku_list[$v['goods_code']]
                );
                $m_ShopGoodsModel->updateInfo($v['id'], $upOne); //更新 shop_goods 表
                //$res[$v['id']] = $sku_list[$v['goods_code']];
            }
        }
        echo "e " . time() . "<br>";
        $en = time();
        echo $en - $st . "<br>";
        //var_dump($res);
        return "up  data ok !";
    }

    /**
     * //redis方法
     * @return string
     */
    public function index_f4()
    {
        set_time_limit(0);
        $res = array();
        $m_RemoveModel = new ParityProductModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $st = time();
        // 查询goods_code 不为空 并且 skuid 不为空
        echo "a " . time() . "<br>";
        $sqlShopGoods = "SELECT id , goods_code  FROM  ims_ewei_shop_goods   WHERE    goods_code !=''   AND  skuid ='' ORDER BY id desc limit 1000";
        $shopGoodsCodeList = $m_ShopGoodsModel->querySql($sqlShopGoods);
        echo "b " . time() . "<br>";
        $sqlProductAll = "SELECT barcode  , id AS skuid  FROM  bsj_parity_product ";
        $parityProductAll = $m_RemoveModel->querySql($sqlProductAll);
        $sku_list = array();
        echo "c " . time() . "<br>";
        foreach ($parityProductAll as $one) {
            if (!empty($one['barcode'])) {
                $arr = explode(',', $one['barcode']);
                $sku_id = $one['skuid'];
                foreach ($arr as $two) {
                    $sku_list[$two] = $sku_id;
                }
            }
        }
        //echo count($sku_list);
        echo "d " . time() . "<br>";
        foreach ($shopGoodsCodeList AS $k => $v) {
            if (isset($sku_list[$v['goods_code']])) {//array_key_exists
                $upOne = array(
                    'skuid' => $sku_list[$v['goods_code']]
                );
                //$m_ShopGoodsModel->updateInfo($v['id'],$upOne); //更新 shop_goods 表
                $res[$v['id']] = $sku_list[$v['goods_code']];
            }
        }
        echo "e " . time() . "<br>";
        $en = time();
        echo $en - $st . "<br>";
        var_dump($res);
        return "up  data ok !";
    }

    /**
     * 组合价格详情数据
     * @param $sku_id
     * @param $one_compare
     * @param $list_compare
     * @return array
     */
    function check_compare($sku_id, $one_compare, $list_compare)
    {
        $arr = array();
        if (!empty($one_compare)) {
            //$arr = array();
            $arr1 = array();
            if (isset($list_compare[$sku_id])) {
                $arr = $list_compare[$sku_id];
            }
            $arr['price_list'][] = $one_compare;
            $arr1 = $arr['price_list'];

            $arr['c_444'] = ''; //444   百世店加
            $arr['c_461'] = ''; //461   中商惠民
            $arr['c_1012'] = '';//1012  易久批
            $arr['c_460'] = ''; //460   链商优供
            $arr['c_967'] = ''; //967   美菜

            if (count($arr1) == 1) {
                $arr['price_min_money'] = $arr1[0]['totalPrice'];
                $arr['price_min_channel'] = $arr1[0]['channel'];
                $arr['price_more_money'] = $arr1[0]['totalPrice'];
                $arr['price_more_count'] = 1;

                if ($arr1[0]['channel'] == '百世店加') {
                    $arr['c_444'] = $arr1[0]['totalPrice'];
                } elseif ($arr1[0]['channel'] == '中商惠民') {
                    $arr['c_461'] = $arr1[0]['totalPrice'];
                } elseif ($arr1[0]['channel'] == '易久批') {
                    $arr['c_1012'] = $arr1[0]['totalPrice'];
                } elseif ($arr1[0]['channel'] == '链商优供') {
                    $arr['c_460'] = $arr1[0]['totalPrice'];
                } elseif ($arr1[0]['channel'] == '美菜') {
                    $arr['c_967'] = $arr1[0]['totalPrice'];
                }

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

                    if ($one_p['channel'] == '百世店加') {
                        $arr['c_444'] = $one_p['totalPrice'];
                    } elseif ($one_p['channel'] == '中商惠民') {
                        $arr['c_461'] = $one_p['totalPrice'];
                    } elseif ($one_p['channel'] == '易久批') {
                        $arr['c_1012'] = $one_p['totalPrice'];
                    } elseif ($one_p['channel'] == '链商优供') {
                        $arr['c_460'] = $one_p['totalPrice'];
                    } elseif ($one_p['channel'] == '美菜') {
                        $arr['c_967'] = $one_p['totalPrice'];
                    }
                }
                arsort($all_price);
                //$i = 1;
                foreach ($all_price as $k => $v) {
                    //if ($i == 1) {
                    $arr['price_more_money'] = $k;
                    $arr['price_more_count'] = $v;
                    break;
                    //}
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

    /**
     * @return string
     */
    public function index_m1()
    {
        set_time_limit(0);
        //$res = array();
        $m_RemoveModel = new ParityProductModel();
        $st = time();
        echo "a " . time() . "<br>";
        $sqlCompareAll = "SELECT skuId,totalPrice,channel FROM  bsj_parity_compare where id > 0 order by skuId asc ";//channelId
        $parityCompareAll = $m_RemoveModel->querySql($sqlCompareAll);
        //var_dump($parityCompareAll);
        echo "原始共" . count($parityCompareAll) . "条记录<br>";
        $sku_compare_list = array();
        $one_compare = array();
        echo "b " . time() . "<br>";
        foreach ($parityCompareAll as $one) {
            $one_compare = null;
            $sku_id = $one['skuId'];
            unset($one['skuId']);
            $one_compare = $one;
            $sku_compare_list[$sku_id] = $this->check_compare($sku_id, $one_compare, $sku_compare_list);
        }
        echo "c " . time() . "<br>";
        echo "整理后" . count($sku_compare_list) . "条记录<br>";
//        foreach ($sku_compare_list as $k => $v) {
//            //if ($v['price_more_count'] > 1) {
//                echo $k . ":";
//                var_dump($v);
//                echo "<br>";
//            //}
//        }

        set_time_limit(30);
        return "up  data ok !";
    }

    /**
     * @return string
     */
    public function index_flx()
    {
        set_time_limit(0);
        $m_RemoveModel = new ShopGoodsModel();
        $st = time();
        echo "a " . time() . "<br>";
        $sqlCompareAll = "SELECT id,sku_id,price FROM  ims_bb_price_list order by id asc ";
        $parityCompareAll = $m_RemoveModel->querySql($sqlCompareAll);

        $sku_compare_list = array();
        foreach ($parityCompareAll as $one) {
            $sku_compare_list[$one['sku_id']][] = $one['price'];
        }
        $sku_price_list = array();
        $one_price = array();
        foreach ($sku_compare_list as $k => $v) {
            $arr_one = $v;
            $one_price = null;
            if (count($arr_one) > 0) {
                $arr_one = array_unique($arr_one);
                sort($arr_one);
                $one_price['min'] = $arr_one[0];
                rsort($arr_one);
                $one_price['max'] = $arr_one[0];
                $sku_price_list[$k] = $one_price;
            }
        }

        foreach ($sku_compare_list as $k => $v) {
            $res = $m_RemoveModel->querySql("update ims_bb_city_sku set min_price={$v['mix']},max_price={$v['mix']},updatetime={$st} where sku_id = {$k}");
            if (!$res) {
                echo $k . "<br>";
            }
        }
        echo "c " . time() . "<br>";

        set_time_limit(30);
        return "up  data ok !";
    }

    /**
     * @return string
     */
    public function index_yk()
    {
        set_time_limit(0);
        $m_SignModel = new SignModel();

        //$sql_Sign = "SELECT id,admin_user_id,create_time FROM  btj_sign where ";//
        //$list_sign = $m_SignModel->querySql($sql_Sign);

        //$sign_day = array();
        $day = $this->request->param("day");
        if (empty($day)) {
            $today = strtotime(date('Y-m-d', time())) + 60 * 60 * 24;
        } else {
            $today = strtotime($day) + 60 * 60 * 24;
        }
        for ($i = 0; $i <= 32; $i++) {
            $day1 = $today - 60 * 60 * 24 * $i;
            $day2 = $day1 - 60 * 60 * 24;
            $sql_Sign1 = "SELECT id,admin_user_id FROM  btj_sign where create_time > {$day2} and create_time < {$day1} group  by admin_user_id";
            $list_sign1 = $m_SignModel->querySql($sql_Sign1);

            echo "<table border='1' align='center' width='80%'>";
            $j = 0;
            foreach ($list_sign1 as $one1) {
                $j++;
                $sql_Sign = "SELECT id,admin_user_id,FROM_UNIXTIME(create_time,'%T') as cc,b.`name` FROM  btj_sign a left join btj_admin_user b on a.admin_user_id=b.user_id where create_time > {$day2} and create_time < {$day1} and admin_user_id = {$one1['admin_user_id']} order by create_time asc ";
                $list_sign = $m_SignModel->querySql($sql_Sign);

                $i_start = 1;
                $i_end = count($list_sign);
                echo "<tr>";
                foreach ($list_sign as $one) {
                    if ($i_start == 1) {
                        echo "<td width='20%'>" . date('Y-m-d', $day2) . "</td><td width='10%'>" . $one["admin_user_id"] . " </td><td width='10%'>" . $one["name"] . "</td><td width='30%'> 首次： " . $one["cc"] . "</td>";
                    }
                    if ($i_end > 1) {
                        if ($i_start == $i_end) {
                            echo "<td> 末次： " . $one["cc"] . "</td>";
                        }
                    } else {
                        echo "<td> 末次：&nbsp;</td>";
                    }
                    $i_start++;
                }
                echo "</tr>";
            }
            if ($j == 0) {
                echo "<td width='20%'>" . date('Y-m-d', $day2) . "</td><td width='10%'>&nbsp; </td><td width='10%'>&nbsp;</td><td width='30%'> nbsp;</td><td>&nbsp;</td>";
            }
            echo "</table><br>";
        }
//        foreach ($list_sign as $one) {
//            $one_compare = null;
//            $sku_id = $one['skuId'];
//            unset($one['skuId']);
//            $one_compare = $one;
//            $sku_compare_list[$sku_id] = $this->check_compare($sku_id, $one_compare, $sku_compare_list);
//        }

        set_time_limit(30);
        return "up  data ok !";
    }

    /**
     * index_hk
     */
    public function index_hk()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        //获取全部品牌
        echo "a=" . time() . "<br>";
        $m_bb_brand = new ShopGoodsModel();
        $sql = "select id,hbsj_id from ims_bb_brand where 1=1 order by id asc";
        $list_brand = $m_bb_brand->querySql($sql);

        echo "b=" . time() . "<br>";
        $sql_bb_goods_item = "select id,brand_id from ims_bb_goods_item order by id asc";
        $list_goods_item = $m_bb_brand->querySql($sql_bb_goods_item);

        echo "c=" . time() . "<br>";
        $brand_arr = array();
        foreach ($list_goods_item as $k => $v) {
            $brand_arr[$v['brand_id']][] = $v['id'];
        }
        echo "d=" . time() . "<br>";

        $sql_bb_sku = "select id,goods_id,hbsj_sku_id,hbsj_item_id from ims_bb_sku order by id asc";
        $list_sku = $m_bb_brand->querySql($sql_bb_sku);
        echo "e=" . time() . "<br>";
        $sku_arr = array();
        foreach ($list_sku as $k => $v) {
            //$sku_arr[$v['hbsj_item_id']][] = $v['hbsj_sku_id'];
            $sku_arr[$v['goods_id']][] = $v['id'];
        }

        echo "f=" . time() . "<br>";
        $sql_bb_city_sku = "select id,sku_id,hbsj_sku_id,channel_count from ims_bb_city_sku order by id asc";
        $list_city_sku = $m_bb_brand->querySql($sql_bb_city_sku);
        echo "g=" . time() . "<br>";
        $city_sku_arr = array();
        foreach ($list_city_sku as $k => $v) {
            //$city_sku_arr[$v['hbsj_sku_id']][] = $v['channel_count'];
            $city_sku_arr[$v['sku_id']][] = $v['channel_count'];
        }
        echo "h=" . time() . "<br>";

        $i = 0;
        foreach ($list_brand as $k => $v) {

            $sku_count = 0;
            $price_num = 0;
            if (isset($brand_arr[$v['hbsj_id']])) {
                $arr_brand_one = $brand_arr[$v['hbsj_id']];
                foreach ($arr_brand_one as $one_item) {
                    if (isset($sku_arr[$one_item])) {
                        $one_item_arr = $sku_arr[$one_item];
                        $sku_count = $sku_count + count($one_item_arr);
                        foreach ($one_item_arr as $one_sku) {
                            if (isset($city_sku_arr[$one_sku])) {
                                foreach ($city_sku_arr[$one_sku] as $ttt) {
                                    $price_num = $price_num + $ttt;
                                }
                            }
                        }
                    }
                }
            }

            $sql = "UPDATE ims_bb_brand SET skuid_count = " . $sku_count . ",price_count = " . $price_num . ",update_time = " . time() . " WHERE hbsj_id = " . $v['hbsj_id'];
            //$temp_db->query($sql);
            echo $sql . "<br>";
            $i++;
            if ($i % 10 == 0) {
                echo $i . "<br>";
            }
        }
        echo "k=" . time() . "<br>";
    }

    /**
     * @param $array
     * @param $field
     * @param string $sort
     * @return mixed
     */
    function arraySequence($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }

    /**
     * index_pf
     */
    public function index_pf()
    {
//        Cache::set('arr_city_sku','',10);
//        Cache::set('list_goods_cache','',10);
//        Cache::set('arr_sku','',10);
//        set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');
        $page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 0;
        $all_count = !empty($_REQUEST['all_count']) ? $_REQUEST['all_count'] : 0;
        //获取全部品牌
        //echo "a=".time()."<br>";
        $m_bb_brand = new ShopGoodsModel();
        $str_ids = ' and a.id in (60455,60456,60458,60459,60460,60461,60462,60463,60464,60465,60495,60521,60522,60523,60525,60526,60527,60528,60529,60533,60534,60539,60540,60541,60542,60547,60548,60549,60551,60553,60555,60557,60558,60559,60560,60561,60563,60568,60570,60572,60573,60576,60578,60580,60582,60586,60588,60589,60591,60593,60594,60596,60600,60601,60602,60603,60604,60605,60606,60607,60608,60609,60610,60611,60612,60613,60614,60615,60616,60617,60618,60619,60620,60621,60622,60623,60624,60625,60626,60627,60628,60630,60631,60632,60633,60634,60635,60636,60641,60642,60644,60645,60646,60648,60649,60650,60651,60652,60653,60654,60655,60656,60657,60658,60659,60660,60661,60662,60664,60666,60667,60668,60669,60670,60671,60672,60673,60674,60675,60676,60677,60678,60679,60680,60681,60682,60684,60685,60686,60687,60688,60689,60690,60691,60692,60693,60694,60695,60696,60699,60700,60701,60702,60703,60704,60705,60706,60707,60708,60709,60710,60711,60712,60713,60714,60715,60716,60717,60718,60719,60720,60721,60722,60723,60726,60727,60728,60729,60730,60731,60732,60733,60734,60735,60736,60737,60738,60739,60740,60741,60742,60743,60744,60745,60746,60747,60748,60749,60750,60751,60752,60753,60754,60755,60756,60757,60758,60759,60760,60761,60762,60763,60764,60765,60766,60767,60768,60769,60772,60773,60774,60775,60776,60777,60778,60779,60780,60781,60782,60783,60784,60785,60786,60787,60788,60789,60790,60791,60792,60793,60794,60795,60796,60797,60798,60805,60806,60807,60808,60809,60810,60811,60812,60813,60814,60815,60816,60817,60818,60819,60820,60821,60822,60823,60824,60825,60826,60827,60828,60829,60830,60831,60832,60833,60834,60835,60836,60837,60838,60839,60840,60841,60842,60843,60844,60845,60846,60847,60848,60849,60851,60852,60853,60854,60855,60856,60857,60858,60859,60860,60861,60862,60863,60864,60865,60866,60867,60868,60869,60870,60871,60872,60873,60874,60875,60876,60877,60878,60879,60880,60881,60882,60883,60884,60885,60886,60888,60890,60891,60892,60893,60894,60895,60896,60897,60898,60899,60900,60906,60907,60908,60909,60910,60911,60912,60915,60919,60920,60921,60922,60923,60924,60933,60934,60935,60936,60937,60938,60939,60944,60945,60946,60948,60949,60950,60951,60952,60955,60956,60957,60958,60959,60960,60961,60962,60963,60964,60997,61060,61061,61062,61063,61064,61066,61069,61070,61071,61072,61073,61076,61078,61081,61082,61084,61111,61112,61113,61127,61128,61129,61152,61153,61154,61155,61156,61157,61158,61165,61166,61167,61294,61295,61347,61348,61349,61372,61398,61399,61401,61403,61404,61405,61406,61407,61408,61430,61431,61432,61433,61434,61435,61442,61446,61447,61495,61497,83142,83164,83165,83166,83167,83168,83169,83170,83171,83172,83173,83174,83175,83176,83177,83178,83179,83180,83181,83182,83184,83185,83187,83188,83189,83262,83263,83265,83266,83270,83271,83272,83273,83274,83276,83332,83335,83338,83339,83341,83355,84313)';
        //$str_ids = '';
        //计算总数
        if (empty($all_count)) {
            if (empty($str_ids)) {
                $sql_count = "select count(a.id) as num from ims_ewei_shop_goods as a left join ims_yd_supplier_goods as b on a.id = b.goods_id where a.sup_id = 461 and a.status = 1 and a.deleted = 0 and a.total > 0 and b.status = 1";
            } else {
                $sql_count = "select count(a.id) as num from ims_ewei_shop_goods as a left join ims_yd_supplier_goods as b on a.id = b.goods_id where a.sup_id = 461 {$str_ids}";
            }

            $sql_count_arr = $m_bb_brand->querySql($sql_count);
            $all_count = $sql_count_arr[0]['num'];
        }
        $pagesize = 5;
        $next_page = $page + 1;
        $pre_page = $page - 1;
        if ($pre_page < 1) {
            $pre_page = 0;
        }
        echo time() . "<a href='ShopGoods.html?page={$pre_page}&all_count={$all_count}'>上一页</a>----当前第{$page}页-------<a href='ShopGoods.html?page={$next_page}&all_count={$all_count}'>下一页</a><br>";

        $aa_g_id = !empty($_REQUEST['g_id']) ? $_REQUEST['g_id'] : 0;
        $aa_skuid = !empty($_REQUEST['skuid']) ? $_REQUEST['skuid'] : 0;
        $aa_hbsj_sku_id = !empty($_REQUEST['hbsj_sku_id']) ? $_REQUEST['hbsj_sku_id'] : 0;
        if (!empty($aa_g_id)) {
            //更新孙宇店铺商品skuid
            $time = time();
            $update_sql = "UPDATE ims_ewei_shop_goods set skuid = '{$aa_skuid}',hbsj_sku_id = '{$aa_hbsj_sku_id}',updatetime = '{$time}' where id = {$aa_g_id}";
            $m_bb_brand->executeSql($update_sql);
            echo "<script>alert('ok')</script>";
        }

        if (!empty($all_count)) {
            $count_page = ceil(($all_count / $pagesize));

            if ($page <= $count_page) {
                $limit = $pagesize * $page;
            } else {
                $limit = 0;
            }

            if (empty($str_ids)) {
                $sql_goods = "select a.id,a.title,a.goods_code_list,a.skuid,a.hbsj_sku_id from ims_ewei_shop_goods as a left join ims_yd_supplier_goods as b on a.id = b.goods_id where a.sup_id = 461 and a.status = 1 and a.deleted = 0 and a.total > 0 and b.status = 1 order by a.id asc limit {$limit},{$pagesize}";
            } else {
                $sql_goods = "select a.id,a.title,a.goods_code_list,a.skuid,a.hbsj_sku_id from ims_ewei_shop_goods as a left join ims_yd_supplier_goods as b on a.id = b.goods_id where a.sup_id = 461 {$str_ids} order by a.id asc limit {$limit},{$pagesize}";
            }
//            $sql_goods = "select a.id,a.title,a.goods_code_list,a.skuid from ims_ewei_shop_goods as a left join ims_yd_supplier_goods as b on a.id = b.goods_id where a.sup_id = 461 and a.status = 1 and a.deleted = 0 and a.total > 0 and b.status = 1 and a.id = 60454 order by a.id asc limit 1";
            $list_goods = $m_bb_brand->querySql($sql_goods);
//----------------------------------------------------------------------------------------
//            echo 'a：'.time().'<br>';
            $arr_city_sku = Cache::get('arr_city_sku');
            if (empty($arr_city_sku)) {
                echo 'cache_city_sku：' . time() . '<br>';
                $sql_city_sku = "select id,channel_count,hbsj_sku_id from ims_bb_city_sku";
                $list_city_sku = $m_bb_brand->querySql($sql_city_sku);
                $arr_city_sku = array();
                foreach ($list_city_sku as $one) {
                    //
                    $arr_city_sku[$one['hbsj_sku_id']] = $one;
                }
                Cache::set('arr_city_sku', $arr_city_sku, 3600 * 10);
            }
//            echo "<pre>";print_r($arr_city_sku);exit;
//----------------------------------------------------------------------------------------
//            echo 'b：'.time().'<br>';
            $list_goods_cache = Cache::get('list_goods_cache');
            if (empty($list_goods_cache)) {
                echo 'cache_goods：' . time() . '<br>';
                $sql_goods_once = "select a.id,a.title,a.goods_code_list,a.skuid from ims_ewei_shop_goods as a left join ims_yd_supplier_goods as b on a.id = b.goods_id where a.sup_id = 461 and a.status = 1 and a.deleted = 0 and a.total > 0 and b.status = 1 order by a.id asc ";
                $list_goods_once = $m_bb_brand->querySql($sql_goods_once);

//                echo 'b1：'.time().'<br>';
//                $sql_goods_mc = "select id,hbsj_item_id,content,code_list from ims_bb_goods_item";
//                $goods_mc = $m_bb_brand->querySql($sql_goods_mc);
//                $goods_mc_arr = array();
//                foreach ($goods_mc as $one){
////                    $goods_mc_arr[$one['code_list']] = $one;
//                    $goods_mc_arr[] = $one;
//                }
//                echo 'b2：'.time().'<br>';
                $list_goods_cache = array();
                $item_list_mc = array();
                foreach ($list_goods_once as $one) {
//                echo $one['id'] . " " . $one['title'] . "<br>";
                    $str_code = $one['goods_code_list'];
                    $arr_code = explode(',', $str_code);
                    $arr_item = null;
                    $array_sku = null;
                    foreach ($arr_code as $two) {
                        $two = trim($two);
                        if (!empty($two)) {
                            $sql_goods = "select id,hbsj_item_id,content from ims_bb_goods_item where code_list like '%{$two}%' order by id asc";
                            $list_item = $m_bb_brand->querySql($sql_goods);
//                            $list_item = array();
//                            foreach ($goods_mc_arr as $key => $value){
//                                if(strpos($value['code_list'],$two) !== false){
//                                    $list_item[] = $value;
//                                }
//                            }
                            $list_goods_cache[$two] = $list_item;
                            if (!empty($list_item)) {
                                foreach ($list_item as $item_one) {
                                    $item_list_mc[] = $item_one['hbsj_item_id'];
                                }
                            }
                        }
                    }
                }
                Cache::set('list_goods_cache', $list_goods_cache, 3600 * 10);
            }
//----------------------------------------------------------------------------------------
//            echo 'c：'.time().'<br>';
            //加载整体数组
//            $arr_sku = Cache::get('arr_sku');
//            if (empty($arr_sku)) {
//                echo 'cache_item：' . time() . '<br>';
//                $item_list_mc = array_unique($item_list_mc);
//                $item_list_mc_res = implode(',', $item_list_mc);
//
//                $sql_sku = "select id,sku_name,unit_count,unit_name,sku_score,hbsj_sku_id,hbsj_item_id from ims_bb_sku where hbsj_sku_id in({$item_list_mc_res})";
////                $sql_sku = "select id,sku_name,unit_count,unit_name,sku_score,hbsj_sku_id,hbsj_item_id from ims_bb_sku";
//                $list_sku = $m_bb_brand->querySql($sql_sku);
//                $arr_sku = array();
//                foreach ($list_sku as $one) {
//                    //
//                    $arr_sku[$one['hbsj_item_id']][] = $one;
//                }
//                Cache::set('arr_sku', $arr_sku, 3600 * 10);
//            }
//----------------------------------------------------------------------------------------
//            echo 'd：'.time().'<br>';
            $array_item = array();
            foreach ($list_goods AS $one) {
                echo $one['id'] . " " . $one['title'] . "<br>";
                $str_code = $one['goods_code_list'];
                $arr_code = explode(',', $str_code);
                $arr_item = null;
                $array_sku = null;
                foreach ($arr_code as $two) {
                    $two = trim($two);
                    if (!empty($two)) {
//                        $sql_goods = "select id,hbsj_item_id,content from ims_bb_goods_item where code_list like '%{$two}%' order by id asc";
//                        echo $sql_sku.';<br>';continue;
//                        $list_item = $m_bb_brand->querySql($sql_goods);
                        //echo " sku_id:<br>";
//                        $list_goods_cache[$two];
                        if (empty($list_goods_cache[$two])) {
                            continue;
                        }
                        $list_item = $list_goods_cache[$two];
//                        echo "<pre>";print_r($list_item);
                        foreach ($list_item as $one_goods_item) {
                            //$array_item[] = $one_goods_item['hbsj_item_id'];
                            $content = $one_goods_item['content'];
                            $one_item = $one_goods_item['hbsj_item_id'];
                            //echo " item_id:".$one_item.":";
                            $sql_sku = "select id,sku_name,unit_count,unit_name,sku_score,hbsj_sku_id from ims_bb_sku where  hbsj_item_id = {$one_item} order by id asc";//is_used = 1 and
//                            echo $sql_sku.';<br>';continue;
                            $list_sku = $m_bb_brand->querySql($sql_sku);
//                            echo "<pre>";print_r($list_sku);
//                            echo '----------------------------------------------';
                            //echo " sku_id:<br>";
//                            echo "<pre>";print_r($arr_sku[$one_item]);
//                            exit;
//                            if(empty($arr_sku[$one_item])){
//                                continue;
//                            }
//                            $list_sku = $arr_sku[$one_item];
//                            echo "<pre>";print_r($list_sku);
//                            exit;
                            $one_data = null;
                            foreach ($list_sku as $three) {
                                //$str_sql_items = implode(',',$arr_item);
//                                $sql_price = "select id,channel_count from ims_bb_city_sku where hbsj_sku_id = {$three['hbsj_sku_id']} order by id asc";
//                                echo $sql_price.';<br>';continue;
//                                $list_price = $m_bb_brand->querySql($sql_price);
                                if (empty($arr_city_sku[$three['hbsj_sku_id']])) {
                                    continue;
                                }
//                                echo $three['hbsj_sku_id'];
//                                echo "<pre>";
//                                print_r($arr_city_sku);
//                                exit;
                                $list_price_one = $arr_city_sku[$three['hbsj_sku_id']];
                                unset($list_price);
                                $list_price[] = $list_price_one;
                                $one_price = 0;
                                if (empty($list_price)) {
                                    //echo $three['hbsj_sku_id']."(".$three['unit_count'].$three['unit_name'].")"."[0] ";
                                } else {
                                    $one_price = $list_price[0]['channel_count'];
                                    //echo $three['hbsj_sku_id']."(".$three['unit_count'].$three['unit_name'].")"."[".$list_price[0]['channel_count']."] ";
                                }
                                $one_data['id'] = $three['id'];
                                $one_data['hbsj_sku_id'] = $three['hbsj_sku_id'];
                                $one_data['sku_unit'] = "(" . $three['sku_name'] . "+" . $three['unit_count'] . $three['unit_name'] . "+" . " " . $content . "+" . " :评分" . $three['sku_score'] . ")";
                                $one_data['channel_count'] = $one_price;
                                $one_data['channel_count_s'] = "[报价数:" . $one_price . "]";
                                //$one_data['content'] = $content;
                                if (!isset($array_sku[$three['hbsj_sku_id']])) {
                                    $array_sku[$three['hbsj_sku_id']] = $one_data;
                                }

                                //$array_sku[] = ;
                            }
                        }
                    }
                }
//                echo 3;exit;
                //print_r($array_sku);

                if (!empty($array_sku)) {
                    $fix_array_sku = $this->arraySequence($array_sku, 'channel_count');

                    //print_r($fix_array_sku);
                    foreach ($fix_array_sku as $thour) {
                        echo $thour['hbsj_sku_id'] . $thour['channel_count_s'] . $thour['sku_unit'];//.$one['skuid']
                        if ($one['hbsj_sku_id'] <> $thour['hbsj_sku_id']) {
                            echo "<a href='shopGoods.html?g_id={$one['id']}&skuid={$thour['id']}&hbsj_sku_id={$thour['hbsj_sku_id']}&page={$page}&all_count={$all_count}'>设置为商品SKUID</a>";
                        } else {
                            echo " √√√";
                        }
//                    echo " <a href='up_goods_skuid?g_id={$one['id']}&skuid={$thour['hbsj_sku_id']}'>删除此SKUID</a>";
                        echo "<br>";
                    }
                }
                echo "<hr>";
            }
//        if(!empty($array_item)){
//            $str_sql_items = implode(',',$array_item);
//            echo $str_sql_items."<br>";
//        }
            //echo "<br>";
            //$list_sku
            echo time() . "<br>";
        }
    }

    /**
     * index_pf2
     */
    public function index_pf2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_order = "SELECT openid,address,COUNT(id) as cc from ims_ewei_shop_order where supplier_id = 461 AND `status` >=0 and address is not null GROUP BY openid";
        $list_order = $m_bb_brand->querySql($sql_order);
        $arr_order = array();
        foreach ($list_order as $one) {
            $opendid = $one['openid'];
            $arr_address = unserialize($one['address']);
            $arr_order[$opendid]['cc'] = $one['cc'];
            $arr_order[$opendid]['addr'] = str_replace(' ', '', $arr_address['address']);
            $arr_order[$opendid]['phone'] = $arr_address['mobile'];
            $arr_order[$opendid]['name'] = $arr_address['realname'];
        }
        $sql_member = "SELECT openid,nickname from ims_ewei_shop_member where openid in (SELECT openid from ims_ewei_shop_order where supplier_id = 461 AND `status` >=0 GROUP BY openid) group  by openid";
        $list_member = $m_bb_brand->querySql($sql_member);
        foreach ($list_member as $one) {
            $opendid = $one['openid'];
            echo $opendid . "  ";
            //echo $one['nickname']."  ";
            //$arr_order[$opendid]['cc'] = $one['openid'];
            //$arr_order[$opendid]['addr'] = unserialize($one['address'])['address'];
            if (isset($arr_order[$opendid])) {
                echo $arr_order[$opendid]['name'] . "  ";
                echo $arr_order[$opendid]['addr'] . "  ";
                echo $arr_order[$opendid]['phone'] . "  ";
                echo $arr_order[$opendid]['cc'] . "  ";
            }
            echo "<br>";
        }

    }

    /**
     * index_lcy
     */
    public function index_lcy()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_goods_item = "SELECT id,cate_bb1,cate_bb2,hbsj_cate1,hbsj_cate2,brand_id from ims_bb_goods_item where cate_bb2 > 0";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);
        $arr_goods_item = array();
        foreach ($list_goods_item as $one) {
            $s_key = $one['cate_bb1'] . '_' . $one['cate_bb2'] . '_' . $one['brand_id'];
            if (!isset($arr_goods_item[$s_key])) {
                $arr_goods_item[$s_key] = $one;
            }

        }
        var_dump($arr_goods_item);
        exit;
//        $sql_member = "SELECT openid,nickname from ims_ewei_shop_member where openid in (SELECT openid from ims_ewei_shop_order where supplier_id = 461 AND `status` >=0 GROUP BY openid) group  by openid";
//        $list_member = $m_bb_brand->querySql($sql_member);
//        foreach ($list_member as $one) {
//            $opendid = $one['openid'];
//            echo $opendid . "  ";
//            //echo $one['nickname']."  ";
//            //$arr_order[$opendid]['cc'] = $one['openid'];
//            //$arr_order[$opendid]['addr'] = unserialize($one['address'])['address'];
//            if (isset($arr_order[$opendid])) {
//                echo $arr_order[$opendid]['name'] . "  ";
//                echo $arr_order[$opendid]['addr'] . "  ";
//                echo $arr_order[$opendid]['phone'] . "  ";
//                echo $arr_order[$opendid]['cc'] . "  ";
//            }
//            echo "<br>";
//        }

    }

    /**
     * @param string $table
     * @param array $data
     * @return bool|string
     */
    function insertIntoSql($table = '', $data = array())
    {
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
     * @param $cate_bb2
     */
    private function check_key_word($cate_bb2)
    {
        $m_bb_brand = new ShopGoodsModel();
        $time = time();
        echo $cate_bb2 . ": " . time() . "<br>";

        //$cate_bb2 = 18;
        //词典那个你先把风味素食的跑了
        //18 10 风味素食
        $sql_goods_item_keyword = "SELECT a.id,a.kewords,b.id,b.brand_id,b.cate_bb2 from ims_bb_sku a left join ims_bb_goods_item b on a.goods_id=b.id where b.cate_bb2 = {$cate_bb2} and b.brand_id > 0 and a.kewords <> '' and a.kewords <> a.sku_name order by brand_id ";
        $list_goods_item_keyword = $m_bb_brand->querySql($sql_goods_item_keyword);
        $arr_brand = array();
        foreach ($list_goods_item_keyword as $one) {
            $arr_brand[$one['brand_id']][] = $one['kewords'];
        }
        //print_r($arr_brand);
        $arr_sql = array();
        $arr_list_brand = array();
        $arr_one_brand1 = array();
        $arr_one_brand2 = array();
        $arr_one_brand3 = array();
        $arr_one_brand4 = array();
        $arr_temp = array();
        $arr_one_temp = array();
        foreach ($arr_brand as $k => $v) {
            $arr_one_brand1 = null;
            $arr_one_brand2 = null;
            $arr_one_brand3 = null;
            $arr_one_brand4 = null;
            foreach ($v as $vv) {
                $arr_temp = explode('||', $vv);
                foreach ($arr_temp as $two) {
                    if (!empty(trim($two))) {
                        $arr_one_brand1[] = trim($two);
                    }
                }
            }
            $arr_one_brand2 = array_count_values($arr_one_brand1);
            $arr_one_brand3 = array_unique($arr_one_brand1);
            //print_r($arr_one_brand1);
            //print_r($arr_one_brand2);

            foreach ($arr_one_brand3 as $kk => $vv) {
                if (mb_strlen($vv) > 1) {
                    $arr_one_temp = null;
                    $arr_one_temp['cate_bb2'] = $cate_bb2;
                    $arr_one_temp['brand_id'] = $k;
                    $arr_one_temp['key_word'] = $vv;
                    $arr_one_temp['weight'] = $arr_one_brand2[$vv];
                    $arr_one_temp['status'] = 1;
                    $arr_one_temp['create_time'] = $time;
                    $arr_one_temp['come_form'] = 1;
                    $arr_one_brand4[] = $arr_one_temp;
                    $arr_sql[] = $arr_one_temp;
                    if (count($arr_sql) >= 200) {
                        $one_sql = $this->insertIntoSql('ims_bb_brand_keyword', $arr_sql);
                        $m_bb_brand->executeSql($one_sql);
                        //echo $one_sql."<br>";
                        $arr_sql = null;
                    }
                }
                //echo "insert into ims_bb_brand_keyword (cate_bb2,brand_id,key_word,weight,status,createtime) values({$arr_one_temp[0]},{$arr_one_temp[1]},'{$arr_one_temp[2]}',{$arr_one_temp[3]},1,{$time})<br>";
            }

            $arr_list_brand[$cate_bb2 . '_' . $k] = $arr_one_brand4;

            //$m_bb_brand->executeSql("insert into ims_bb_brand_keyword (cate_bb2,brand_id,key_word,weight,status,createtime) values($arr_one_brand4[0],$arr_one_brand4[1],'$arr_one_brand4[2]',$arr_one_brand4[3],1,$time)");
        }
        if (count($arr_sql) >= 1) {
            $one_sql = $this->insertIntoSql('ims_bb_brand_keyword', $arr_sql);
            $m_bb_brand->executeSql($one_sql);
            //echo $one_sql."<br>";
            $arr_sql = null;
        }
    }

    /**
     * @param $cate_bb2
     */
    private function check_key_word_v2($cate_bb2)
    {
        $m_bb_brand = new ShopGoodsModel();
        $time = time();
        echo $cate_bb2 . ": " . time() . "<br>";

        //$cate_bb2 = 18;
        //词典那个你先把风味素食的跑了
        //18 10 风味素食
        $sql_goods_item_keyword = "SELECT a.id,a.keywords_pro,b.id,b.brand_id,b.cate_bb2 from ims_bb_sku a left join ims_bb_goods_item b on a.goods_id=b.id where b.cate_bb2 = {$cate_bb2} and b.brand_id > 0 and a.keywords_pro <> '' order by brand_id ";
        $list_goods_item_keyword = $m_bb_brand->querySql($sql_goods_item_keyword);
        $arr_brand = array();
        foreach ($list_goods_item_keyword as $one) {
            $arr_brand[$one['brand_id']][] = $one['keywords_pro'];
        }
        //print_r($arr_brand);
        $arr_sql = array();
        $arr_list_brand = array();
        $arr_one_brand1 = array();
        $arr_one_brand2 = array();
        $arr_one_brand3 = array();
        $arr_one_brand4 = array();
        $arr_temp = array();
        $arr_one_temp = array();
        foreach ($arr_brand as $k => $v) {
            $arr_one_brand1 = null;
            $arr_one_brand2 = null;
            $arr_one_brand3 = null;
            $arr_one_brand4 = null;
            foreach ($v as $vv) {
                $arr_temp = explode(',', $vv);
                foreach ($arr_temp as $two) {
                    if (!empty(trim($two))) {
                        $arr_one_brand1[] = trim($two);
                    }
                }
            }
            $arr_one_brand2 = array_count_values($arr_one_brand1);
            $arr_one_brand3 = array_unique($arr_one_brand1);
            //print_r($arr_one_brand1);
            //print_r($arr_one_brand2);

            foreach ($arr_one_brand3 as $kk => $vv) {
                if (mb_strlen($vv) > 1 && !is_numeric($vv)) {
                    $arr_one_temp = null;
                    $arr_one_temp['cate_bb2'] = $cate_bb2;
                    $arr_one_temp['brand_id'] = $k;
                    $arr_one_temp['key_word'] = $vv;
                    $arr_one_temp['weight'] = $arr_one_brand2[$vv];
                    $arr_one_temp['status'] = 1;
                    $arr_one_temp['create_time'] = $time;
                    $arr_one_temp['come_form'] = 1;
                    $arr_one_brand4[] = $arr_one_temp;
                    $arr_sql[] = $arr_one_temp;
                    if (count($arr_sql) >= 200) {
                        $one_sql = $this->insertIntoSql('ims_bb_brand_keyword', $arr_sql);
                        $m_bb_brand->executeSql($one_sql);
                        //echo $one_sql."<br>";
                        $arr_sql = null;
                    }
                }
                //echo "insert into ims_bb_brand_keyword (cate_bb2,brand_id,key_word,weight,status,createtime) values({$arr_one_temp[0]},{$arr_one_temp[1]},'{$arr_one_temp[2]}',{$arr_one_temp[3]},1,{$time})<br>";
            }

            $arr_list_brand[$cate_bb2 . '_' . $k] = $arr_one_brand4;

            //$m_bb_brand->executeSql("insert into ims_bb_brand_keyword (cate_bb2,brand_id,key_word,weight,status,createtime) values($arr_one_brand4[0],$arr_one_brand4[1],'$arr_one_brand4[2]',$arr_one_brand4[3],1,$time)");
        }
        if (count($arr_sql) >= 1) {
            $one_sql = $this->insertIntoSql('ims_bb_brand_keyword', $arr_sql);
            $m_bb_brand->executeSql($one_sql);
            //echo $one_sql."<br>";
            $arr_sql = null;
        }
    }

    /**
     * @param $cate_bb2
     */
    private function check_key_word_v3($cate_bb2)
    {
        $m_bb_brand = new ShopGoodsModel();
        $time = time();
        echo $cate_bb2 . ": " . time() . "<br>";

        //$cate_bb2 = 18;
        //词典那个你先把风味素食的跑了
        //18 10 风味素食
//        $sql_goods_item_keyword = "SELECT a.id,a.keywords_pro,b.id,b.brand_id,b.cate_bb2 from ims_bb_sku a left join ims_bb_goods_item b on a.goods_id=b.id where b.cate_bb2 = {$cate_bb2} and b.brand_id > 0 and a.keywords_pro <> '' order by brand_id ";
        $sql_goods_item = "SELECT id,brand_id,cate_bb2,goods_name,key_word from ims_bb_goods_item where cate_bb2 = {$cate_bb2}";
        $list_goods_item_keyword = $m_bb_brand->querySql($sql_goods_item);
        $arr_brand = array();
        foreach ($list_goods_item_keyword as $one) {
            $arr_brand[$one['brand_id']][] = $one['key_word'];
        }
        //print_r($arr_brand);
        $arr_sql = array();
        $arr_list_brand = array();
        $arr_one_brand1 = array();
        $arr_one_brand2 = array();
        $arr_one_brand3 = array();
        $arr_one_brand4 = array();
        $arr_temp = array();
        $arr_one_temp = array();
        foreach ($arr_brand as $k => $v) {
            $arr_one_brand1 = null;
            $arr_one_brand2 = null;
            $arr_one_brand3 = null;
            $arr_one_brand4 = null;
            foreach ($v as $vv) {
                $arr_temp = explode(',', $vv);
                foreach ($arr_temp as $two) {
                    if (!empty(trim($two))) {
                        $arr_one_brand1[] = trim($two);
                    }
                }
            }
            $arr_one_brand2 = array_count_values($arr_one_brand1);
            $arr_one_brand3 = array_unique($arr_one_brand1);
            //print_r($arr_one_brand1);
            //print_r($arr_one_brand2);

            foreach ($arr_one_brand3 as $kk => $vv) {
                if (mb_strlen($vv) > 1) {
                    $arr_one_temp = null;
                    $arr_one_temp['cate_bb2'] = $cate_bb2;
                    $arr_one_temp['brand_id'] = $k;
                    $arr_one_temp['key_word'] = $vv;
                    $arr_one_temp['weight'] = $arr_one_brand2[$vv];
                    $arr_one_temp['status'] = 1;
                    $arr_one_temp['create_time'] = $time;
                    $arr_one_temp['come_form'] = 1;
                    $arr_one_brand4[] = $arr_one_temp;
                    $arr_sql[] = $arr_one_temp;
                    if (count($arr_sql) >= 200) {
                        $one_sql = $this->insertIntoSql('ims_bb_brand_keyword', $arr_sql);
                        $m_bb_brand->executeSql($one_sql);
                        //echo $one_sql."<br>";
                        $arr_sql = null;
                    }
                }
                //echo "insert into ims_bb_brand_keyword (cate_bb2,brand_id,key_word,weight,status,createtime) values({$arr_one_temp[0]},{$arr_one_temp[1]},'{$arr_one_temp[2]}',{$arr_one_temp[3]},1,{$time})<br>";
            }

            $arr_list_brand[$cate_bb2 . '_' . $k] = $arr_one_brand4;

            //$m_bb_brand->executeSql("insert into ims_bb_brand_keyword (cate_bb2,brand_id,key_word,weight,status,createtime) values($arr_one_brand4[0],$arr_one_brand4[1],'$arr_one_brand4[2]',$arr_one_brand4[3],1,$time)");
        }
        if (count($arr_sql) >= 1) {
            $one_sql = $this->insertIntoSql('ims_bb_brand_keyword', $arr_sql);
            $m_bb_brand->executeSql($one_sql);
            //echo $one_sql."<br>";
            $arr_sql = null;
        }
    }

    /**
     * index_zt1
     */
    public function index_zt1()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_cate = "SELECT id from ims_bb_cate_bb where fid <> 0 ";
        $list_cate = $m_bb_brand->querySql($sql_cate);

        foreach ($list_cate as $one) {
            $this->check_key_word($one['id']);
            //$this->check_key_word_v2($one['id']);
        }
        echo time() . "<br>";
    }


    /**
     * index_zt2
     */
    public function index_zt2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_cate = "SELECT id from ims_bb_cate_bb where fid <> 0";//fid <> 0
        $list_cate = $m_bb_brand->querySql($sql_cate);

        $split_word = new splitWord();

        foreach ($list_cate as $one_cate) {

            $cate_id = $one_cate['id'];
            echo $cate_id . ": " . time() . "<br>";
            $sql_brand_keyword = "SELECT id,cate_bb2,brand_id,key_word from ims_bb_brand_keyword_hbsj where cate_bb2 =  " . $cate_id . " order by brand_id ";
            $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);

            $sql_brand1 = "select id,b_name from ims_bb_brand where id in (SELECT brand_id from ims_bb_goods_item where cate_bb2 =  " . $cate_id . " group by brand_id order by brand_id )";
            $list_brand1 = $m_bb_brand->querySql($sql_brand1);
            //print_r($list_brand1);
            $arr_list_brand = array();
            foreach ($list_brand1 as $one) {
                $arr_list_brand[] = $one['b_name'];
            }
            //print_r($arr_list_brand);
            //exit;
            $arr_cate_bb2 = $arr_list_brand;
            $arr_brand_keyword = $arr_list_brand;
            foreach ($list_brand_keyword as $one_cate_brand) {
                $arr_cate_bb2[] = $one_cate_brand['key_word'];
                $arr_brand_keyword[$one_cate_brand['cate_bb2'] . "_" . $one_cate_brand['brand_id']][] = $one_cate_brand['key_word'];
            }

            $sql_sku_keyword = "SELECT a.id,a.kewords,b.brand_id,b.cate_bb2,a.sku_name from ims_bb_sku a left join ims_bb_goods_item b on a.goods_id=b.id where b.cate_bb2 = {$cate_id} and b.brand_id > 0 and kewords <> '' order by brand_id ";
            $list_sku_keyword = $m_bb_brand->querySql($sql_sku_keyword);

            $arr_one_sku_keyword = array();
            foreach ($list_sku_keyword as $one) {
                $arr_one_sku_keyword = null;
                if (!empty($one['kewords']) && $one['kewords'] <> $one['sku_name']) {
//                    if ($one['kewords'] == $one['sku_name']) {
//                        //拆
//                        $arr_one_sku_keyword = null;
//                    } else {
                    $arr_one_sku_keyword = explode('||', $one['kewords']);
                    //$arr_one_sku_keyword = implode(',',$arr_keywords);
//                    }
                } else {
                    //拆
                    //$dict = $arr_brand_keyword[$one['cate_bb2'].'_'.$one['brand_id']];
                    if (isset($arr_brand_keyword[$one['cate_bb2'] . '_' . $one['brand_id']])) {
                        $str = "";
                        $split_word->set_dict($arr_brand_keyword[$one['cate_bb2'] . '_' . $one['brand_id']]);
                        $arr_one_sku_keyword = $split_word->split_word($one['sku_name']);
                    } else {
                        $str = "&nbsp &nbsp ";
                        $split_word->set_dict($arr_cate_bb2);
                        //echo "skuid:".$one['id']."<br>";
                        //print_r($arr_cate_bb2);
                        $arr_one_sku_keyword = $split_word->split_word($one['sku_name']);
                        //print_r($split_word->split_word('华丰三鲜伊面'));
                        //exit;
                    }
                }
                $key_words_pro = implode(',', $arr_one_sku_keyword);
                $m_bb_brand->executeSql("update ims_bb_sku set keywords_pro = '{$key_words_pro}' where id = {$one['id']}");
                //echo $str."update ims_bb_sku set keywords_pro = '{$key_words_pro}' where id = {$one['id']}"."<br>";
            }

        }
        echo time() . "<br>";
    }

    /**
     * index_zt3
     */
    public function index_zt3()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_cate = "SELECT id from ims_bb_cate_bb where fid <> 0 ";
        $list_cate = $m_bb_brand->querySql($sql_cate);

        foreach ($list_cate as $one) {
            //$this->check_key_word($one['id']);
            $this->check_key_word_v2($one['id']);
        }
        echo time() . "<br>";
    }

    /**
     * 将sku的keyword移动到goods_item表中
     */
    public function index_zt4()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_goods_item = "SELECT id from ims_bb_goods_item order by id ";//limit 100
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        $sql_bb_sku = "SELECT goods_id,keywords_pro from ims_bb_sku ";
        $list_bb_sku = $m_bb_brand->querySql($sql_bb_sku);
        $arr_list_sku = array();
        foreach ($list_bb_sku as $one) {
            if ($one['keywords_pro'] <> '') {
                $arr_list_sku[$one['goods_id']] = $one['keywords_pro'];
            }
        }

        foreach ($list_goods_item as $one) {
            $one_key_word = isset($arr_list_sku[$one['id']]) ? $arr_list_sku[$one['id']] : '';
            if (substr($one_key_word, 0, 1) == ',') {
                $one_key_word = substr($one_key_word, 1);
            }
            if (!empty($one_key_word)) {
                $m_bb_brand->querySql("update ims_bb_goods_item set key_word = '{$one_key_word}' where id = {$one['id']}");
            }
        }

        echo time() . "<br>";
    }


    /**
     * 更新词根，品牌分类
     */
    public function index_zt5()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_brand = "SELECT id,b_name from ims_bb_brand ";
        $list_brand = $m_bb_brand->querySql($sql_brand);
        $arr_list_brand = array();
        foreach ($list_brand as $one) {
            $arr_list_brand[] = $one['b_name'];
        }

        $sql_brand_keyword = "SELECT id,key_word from ims_bb_brand_keyword ";
        $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);

        foreach ($list_brand_keyword as $one) {
            if (in_array($one['key_word'], $arr_list_brand)) {
                $m_bb_brand->querySql("update ims_bb_brand_keyword set key_type = 1 where id = {$one['id']}");
            }
        }
        echo time() . "<br>";
    }

    /**
     * 更新词根，口味分类
     */
    public function index_zt6()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_brand_keyword = "update ims_bb_brand_keyword set key_type = 2 where key_word like '%味'";
        $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);

//        foreach ($list_brand_keyword as $one) {
//            if(in_array($one['key_word'],$arr_list_brand)){
//                $m_bb_brand->querySql("update ims_bb_brand_keyword set key_type = 1 where id = {$one['id']}");
//            }
//        }
        echo time() . "<br>";
    }

    /**
     * 修正goods_item表中brand_id=0
     */
    public function index_zt7()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        $m_ParityProduct = new ParityProductModel();
        echo time() . "<br>";

        $sql_brand = "SELECT id,hbsj_id,b_name from ims_bb_brand ";
        $list_brand = $m_bb_brand->querySql($sql_brand);
        $arr_list_brand = array();
        foreach ($list_brand as $one) {
            $arr_list_brand[$one['hbsj_id']] = $one;
        }

        $sql_goods_item = "SELECT id,hbsj_item_id from ims_bb_goods_item where brand_id = 0 order by id ";//limit 100
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        foreach ($list_goods_item as $one) {
            $sql_parity_product = "SELECT id,brandId,brandName from bsj_parity_product where itemId = '{$one['hbsj_item_id']}' order by id limit 1";//limit 100
            $list_parity_product = $m_ParityProduct->querySql($sql_parity_product);
            if ($list_parity_product) {
                if (isset($arr_list_brand[$list_parity_product[0]['brandId']])) {
                    //if(empty($list_bb_brand)){
                    //$m_bb_brand->querySql("insert into ims_bb_brand (b_name,hbsj_id,createtime) values ('{$list_parity_product[0]['brandName']}',{$list_parity_product[0]['brandId']},{$time})");

                    $brand_id = $arr_list_brand[$list_parity_product[0]['brandId']];
                    $m_bb_brand->querySql("update ims_bb_goods_item set brand_id = {$brand_id['id']} where id = {$one['id']}");

                    //echo "update ims_bb_goods_item set brand_id = {$brand_id['id']} where id = {$one['id']}"."<br>";
                }
            }


        }
        echo time() . "<br>";
    }

    /**
     * 处理goods_item中刚修复完的brand_id=0的没有key_word
     */
    public function index_zt8()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_cate = "SELECT id from ims_bb_cate_bb where fid <> 0";//
        $list_cate = $m_bb_brand->querySql($sql_cate);

        $split_word = new splitWord();

        foreach ($list_cate as $one_cate) {

            $cate_id = $one_cate['id'];
            echo $cate_id . ": " . time() . "<br>";
            $sql_brand_keyword = "SELECT id,cate_bb2,brand_id,key_word from ims_bb_brand_keyword_hbsj where cate_bb2 =  " . $cate_id . " order by brand_id ";
            $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);

            $sql_brand1 = "select id,b_name from ims_bb_brand where id in (SELECT brand_id from ims_bb_goods_item where cate_bb2 =  " . $cate_id . " group by brand_id order by brand_id )";
            $list_brand1 = $m_bb_brand->querySql($sql_brand1);

            $arr_list_brand = array();
            foreach ($list_brand1 as $one) {
                $arr_list_brand[] = $one['b_name'];
            }

            $arr_cate_bb2 = $arr_list_brand;

            $arr_brand_keyword = $arr_list_brand;
            foreach ($list_brand_keyword as $one_cate_brand) {
                if (!in_array($one_cate_brand['key_word'], $arr_cate_bb2)) {
                    $arr_cate_bb2[] = $one_cate_brand['key_word'];
                }

                $arr_brand_keyword[$one_cate_brand['cate_bb2'] . "_" . $one_cate_brand['brand_id']][] = $one_cate_brand['key_word'];
            }

            $sql_sku_keyword = "SELECT id,brand_id,cate_bb2,goods_name,key_word from ims_bb_goods_item where update_time > 0";
            $list_sku_keyword = $m_bb_brand->querySql($sql_sku_keyword);

            $arr_one_sku_keyword = array();
            foreach ($list_sku_keyword as $one_keyword) {
                $arr_one_sku_keyword = null;
                //if ($one['key_word'] <> $one['goods_name']) {
//                    if ($one['kewords'] == $one['sku_name']) {
//                        //拆
//                        $arr_one_sku_keyword = null;
//                    } else {
                //$arr_one_sku_keyword = explode('||',$one['key_word']);
                //$arr_one_sku_keyword = implode(',',$arr_keywords);
//                    }
                //} else {
                if (!empty($one_keyword['goods_name'])) {
                    //拆
                    //$dict = $arr_brand_keyword[$one['cate_bb2'].'_'.$one['brand_id']];
                    if (isset($arr_brand_keyword[$one_keyword['cate_bb2'] . '_' . $one_keyword['brand_id']])) {
                        $str = "";
                        $split_word->set_dict($arr_brand_keyword[$one_keyword['cate_bb2'] . '_' . $one_keyword['brand_id']]);
                        $arr_one_sku_keyword = $split_word->split_word($one_keyword['goods_name']);
                    } else {
                        $str = "&nbsp &nbsp ";
                        $split_word->set_dict($arr_cate_bb2);
                        $arr_one_sku_keyword = $split_word->split_word($one_keyword['goods_name']);

                    }
                }
                $key_words_pro = implode(',', $arr_one_sku_keyword);
                $m_bb_brand->executeSql("update ims_bb_goods_item set key_word = '{$key_words_pro}',update_time={$time} where id = {$one_keyword['id']}");

            }

        }
        echo time() . "<br>";
    }

    /**
     * 修复ims_ydhl_stock_xiangxi时间为时间戳
     */
    public function index_zt9()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_brand_keyword = "select id,ru_shi_jian from ims_ydhl_stock_xiangxi ";
        $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);

        foreach ($list_brand_keyword as $one) {
            $shi_jian = strtotime($one['ru_shi_jian']);
            //echo $shi_jian."<br>";
            $m_bb_brand->querySql("update ims_ydhl_stock_xiangxi set ru_shi_jian = '{$shi_jian}' where id = {$one['id']}");
        }
        echo time() . "<br>";
    }

    /**
     * index_zt10
     */
    public function index_zt10()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_cate = "SELECT id from ims_bb_cate_bb where fid <> 0 ";
        $list_cate = $m_bb_brand->querySql($sql_cate);

        foreach ($list_cate as $one) {
            //$this->check_key_word($one['id']);
            $this->check_key_word_v3($one['id']);
        }
        echo time() . "<br>";
    }

    /**
     * index_zt11
     */
    public function index_zt11()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_goods_item = "SELECT id,key_word from ims_bb_goods_item_1011";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        foreach ($list_goods_item as $one) {
            $m_bb_brand->querySql("update ims_bb_goods_item set key_word = '{$one['key_word']}',update_time = {$time} where id = {$one['id']}");
        }
        echo time() . "<br>";
    }

    /**
     * index_zt12
     */
    public function index_zt12()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        $cate_bb = 18;
        $sql_goods_item0 = "select tt.* from (select id,key_word from ims_bb_brand_keyword where cate_bb2 = {$cate_bb} and key_word <> '邬辣' order by weight desc) as tt group by tt.key_word order by tt.id";
        //$sql_goods_item0 ="SELECT id,key_word FROM `ims_bb_brand_keyword` where cate_bb2 = {} order by weight ";
        //$sql_goods_item0 = "SELECT id,key_word FROM `ims_bb_brand_keyword` where (cate_bb2='18') and key_type!='1' GROUP BY key_word ORDER BY weight desc";
        $list_goods_item0 = $m_bb_brand->querySql($sql_goods_item0);

        $sql_goods_item = "select tt.* from (select id,key_word from ims_bb_brand_keyword where cate_bb2 = {$cate_bb} and key_word <> '牛排' and key_word <> '邬辣' order by weight desc) as tt group by tt.key_word order by tt.id";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

//        $dict = array();
//        foreach($list_goods_item as $one){
//            $dict[] = $one['key_word'];
//        }
        $str_test = '(邬辣妈)蒜蓉素牛排[100g]';
        echo $str_test . "<br>";
        $split_word = new splitWord();
        //$arr_dict = array('正面', '和负面', '英文', '什么','负面');//,'正面和','和负面'
        //$arr_dict = array('邬辣妈', '蒜蓉', '牛排');//,'正面和','和负面'
        echo time() . "<br>";
        $dict = $split_word->get_dict($list_goods_item0);
        $dict = $split_word->fix_dict($dict);
        $split_word->set_dict($dict);
        $arr_one_keyword = $split_word->split_word($str_test);
        echo "<hr>";
        print_r($arr_one_keyword);

        $dict = $split_word->get_dict($list_goods_item);
        $dict = $split_word->fix_dict($dict);
        $split_word->set_dict($dict);
        $arr_one_keyword = $split_word->split_word($str_test);
        echo "<hr>";
        print_r($arr_one_keyword);
        //$arr_one_keyword2 = $split_word->split_word_asc('正面和负面英文是什么');
        //print_r($arr_one_keyword2);
        echo "<br>" . time() . "<br>";
    }

    /**
     * index_zt13
     */
    public function index_zt13()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $m_bb_brand->querySql("update ims_huiminwang_product set kou_wei = '' where kou_wei <> ''");
        $sql_key_word = "SELECT id,key_word from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' order by id asc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word = array();
        foreach ($list_key_word as $one) {
            $arr_key_word[] = $one['key_word'];
        }

        $sql_goods_item = "SELECT id,spec,`name` from ims_huiminwang_product";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        foreach ($list_goods_item as $one) {
//            $arr1 = explode('\\',$one['spec']);//260g\袋  600g*8\箱
//            if(!empty($arr1[0])){
//                $arr2 = explode('*',$arr1[0]);
//            }else{
//                $arr2 = array('');
//            }
//            $m_bb_brand->querySql("update ims_huiminwang_product set spec_value = '{$arr2[0]}' where pk_id = {$one['pk_id']}");
//
            $kou_wei = '';
            foreach ($arr_key_word as $two) {
                if (stripos($one['name'], $two) !== false) {
                    $kou_wei = $two;
                    break;
                }
            }

            if (!empty($kou_wei)) {
                $m_bb_brand->querySql("update ims_huiminwang_product set kou_wei = '{$kou_wei}' where id = {$one['id']}");
            }

        }
        echo time() . "<br>";
    }

    /**
     * 修复key_word表里的纯数字key_word
     */
    public function index_zt14()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_key_word = "SELECT id,key_word from ims_bb_brand_keyword where status = 1  order by id asc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);

        foreach ($list_key_word as $one) {
            if (is_numeric($one['key_word'])) {
                $m_bb_brand->querySql("update ims_bb_brand_keyword set status = 0 where id = {$one['id']}");
            }

        }
        echo time() . "<br>";
    }

    /**
     * 提取单位
     * @param $conent
     * @param int $to_upcase
     * @return string
     */
    function get_unit($conent, $to_upcase = 1)
    {
        $unit = $conent;
        $i_len = mb_strlen($conent);
        $i_pos = 0;
        for ($i = 0; $i < $i_len; $i++) {
            $one_char = mb_substr($conent, $i, 1);
            if (!is_numeric($one_char) && $one_char <> '.') {
                $i_pos = $i;
                break;
            }
        }
        if ($i_pos > 0) {
            $unit = mb_substr($conent, $i_pos);
        }
        if ($to_upcase) {
            $unit = mb_strtoupper($unit);
        }
        if (is_numeric($unit)) {
            $unit = '';
        }
        return $unit;
    }

    /**
     * 提取unit到goods_unit表
     */
    public function index_zt15()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $sql_cate = "SELECT id from ims_bb_cate_bb where fid <> 0";//
        $list_cate = $m_bb_brand->querySql($sql_cate);


        $arr_unit = array();
        foreach ($list_cate as $two) {
            $cate_bb2 = $two['id'];
            $sql_unit = "SELECT id,content from ims_bb_goods_item where cate_bb2 = {$cate_bb2}  order by id asc";
            $list_unit = $m_bb_brand->querySql($sql_unit);

            foreach ($list_unit as $one) {
                $unit = $this->get_unit($one['content']);
                if (!empty($unit)) {
                    if (isset($arr_unit[$cate_bb2])) {
                        if (in_array($unit, $arr_unit[$two['id']]) === false) {
                            $arr_unit[$cate_bb2][] = $unit;
                        }
                    } else {
                        $arr_unit[$cate_bb2][] = $unit;
                    }
                }
            }
        }
        if (!empty($arr_unit)) {
            $time = time();
            foreach ($arr_unit as $k => $v) {
                $str_sql = "insert into ims_bb_goods_unit (cate_bb2,unit,status,create_time) values ";
                $arr_sql = null;
                foreach ($v as $two) {
                    $arr_sql[] = "({$k},'{$two}',1,{$time})";
                }
                if (!empty($arr_sql)) {
                    $m_bb_brand->querySql($str_sql . implode(',', $arr_sql));
                }
            }
        }
        echo time() . "<br>";
    }

    /**
     * 用比三价的品牌词根数组，去拆中商惠民的key_word
     */
    public function index_zt16()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        //$time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_brand_name = "SELECT id,brand_name from ims_huiminwang_product where brand_name <> '' group by brand_name";//
        $list_brand_name = $m_bb_brand->querySql($sql_brand_name);

        $split_word = new splitWord();
        foreach ($list_brand_name as $one) {
            $sql_one = "SELECT id,b_name from ims_bb_brand where b_name ='{$one['brand_name']}' ";//
            $list_one = $m_bb_brand->querySql($sql_one);

            $list_brand_keyword = array();
            if (count($list_one) > 0) {
                $sql_brand_keyword = "SELECT id,key_word from ims_bb_brand_keyword where brand_id = {$list_one[0]['id']} and status = 1";//
                $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);

                if (count($list_brand_keyword) > 0) {
                    $dict = $split_word->get_dict($list_brand_keyword);
                    $dict = $split_word->fix_dict($dict);
                    $split_word->set_dict($dict);

                    $sql_product = "SELECT id,`name`,spec_value from ims_huiminwang_product where brand_name = '{$one['brand_name']}' order by id";//
                    $list_product = $m_bb_brand->querySql($sql_product);
                    foreach ($list_product as $two) {
                        //$one_product_name = str_replace($two['spec_value'], '', );
                        $one_product_name = trim(str_replace('*', '', $two['name']));
                        $i_pos = stripos($one_product_name, $two['spec_value']);
                        $one_product_name = substr($one_product_name, 0, $i_pos);
                        $arr_one_keyword = $split_word->split_word($one_product_name);
                        if (count($arr_one_keyword) > 0) {
                            $key_word_str = implode(',', $arr_one_keyword);
                            $m_bb_brand->querySql("update ims_huiminwang_product set key_word = '{$key_word_str}' where id = {$two['id']}");
                        }
                    }
                }
            }
        }
        echo time() . "<br>";

    }

    /**
     * 字符串相似度百分比
     * @param $str1
     * @param $str2
     * @return float
     */
    function str_same_percent($str1, $str2)
    {
        similar_text($str1, $str2, $percent);
        $percent = round($percent, 2);
        return $percent;
    }

    /**
     * str_same_percent_flx冯礼钦
     * @param $brand_name
     * @param $str1
     * @param $str2
     * @return float
     */
    function str_same_percent_flx($brand_name, $str1, $str2)
    {
        $str11 = str_replace(',', '', $str1);
        $str11 = str_replace($brand_name, '', $str11);

        $str22 = str_replace($brand_name, '', $str2);

        similar_text($str11, $str22, $percent);
        $percent = round($percent, 2);
        return $percent;
    }

    /**
     * index_zt17
     */
    public function index_zt17()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        //$time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $one_product_name0 = trim('  *奇多日式牛排味干杯脆油炸型膨化食品25g/6盒');
        $one_product_name = str_replace('*', '', $one_product_name0);
        $i_pos = stripos($one_product_name, '25g');
        $one_product_name = substr($one_product_name, 0, $i_pos);
        echo "|" . $one_product_name0 . "|<br>";
        echo "|" . $one_product_name . "|<br>";

        //echo $this->str_same_percent($one_product_name, $one_product_name0);
        echo $this->str_same_percent("吉林禽业公司火灾已致112人遇难", "吉林宝源丰禽业公司火灾已致112人遇难");
        echo "<br>";
        $str_same = new sameStr();
        echo $str_same->getStrSamePercent("吉林禽业公司火灾已致112人遇难", "吉林宝源丰禽业公司火灾已致112人遇难");
    }


    /**
     * 清洗potential_customer_data数据
     * 客户初步盘点
     * 将有开放供应链的超市划分为以下等级：
     * S ：25及以上，50%以上分类有经营,有雇员
     * A ：25及以上，50%以上分类有经营
     * B : 15-24  ，50%以上分类有经营
     * C : 10-14  ，50%及以上分类有经营
     * D : 5-9    ，30%及以上分类有经营
     * 0 :
     */
    public function index_zt18()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        //$time = time();
        $m_bb_brand = new SignModel();
        echo time() . "<br>";
        $time = time();
        $sql_customer_data = "SELECT * from potential_customer_data where id > 0";
        $list_customer_data = $m_bb_brand->querySql($sql_customer_data);
        $one_data = array();
        foreach ($list_customer_data as $one) {
            $has_employee = $one['employee'];
            $one_data = null;
            $k = 0;
            foreach ($one as $two) {
                if ($k > 0 && $k < 11) {
                    $one_data[] = $two;
                }
                $k++;
            }
            $cate_sum = array_sum($one_data);

            $cate_count = 0;
            foreach ($one_data as $three) {
                if (!empty($three)) {
                    $cate_count++;
                }
            }
            //print_r($one_data);
            $jing_ying = round($cate_count * 100 / 10, 1);
            if ($cate_sum >= 25 && $jing_ying >= 50 && $has_employee == 1) {
                $score_level = 'S';
            } elseif ($cate_sum >= 25 && $jing_ying >= 50) {
                $score_level = 'A';
            } elseif ($cate_sum >= 15 && $jing_ying >= 50) {
                $score_level = 'B';
            } elseif ($cate_sum >= 10 && $jing_ying >= 50) {
                $score_level = 'C';
            } elseif ($cate_sum >= 5 && $jing_ying >= 30) {
                $score_level = 'D';
            } else {
                $score_level = '0';
            }
            echo $one['id'] . " : " . $cate_sum . " + " . $jing_ying . " = " . $score_level . "<br>";
            //exit;
            $m_bb_brand->querySql("update potential_customer_data set score_level = '{$score_level}',update_time={$time} where id = {$one['id']}");

        }

    }

    /**
     * 修复ims_jingpiwang_product的price_per_unit等字段
     */
    public function index_zt19()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_key_word = "SELECT id,price_per_unit,per_piece,state from ims_jingpiwang_product where id >= 1  order by id asc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);

        foreach ($list_key_word as $one) {
            $fix_price_per_unit = str_replace('单价￥', '', $one['price_per_unit']);
            $fix_per_piece = str_replace('每件￥', '', $one['per_piece']);
            $fix_state = 0;
            if ($one['state'] == '立即订购') {
                $fix_state = 1;
            };
            //if (is_numeric($one['key_word'])) {
            $m_bb_brand->querySql("update ims_jingpiwang_product set price_per_unit='{$fix_price_per_unit}',per_piece='{$fix_per_piece}',state = '{$fix_state}' where id = {$one['id']}");
            //}

        }
        echo time() . "<br>";
    }

    /**
     * 匹配ims_jingpiwang_product的kou_wei等字段
     */
    public function index_zt20()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_key_word = "SELECT id,key_word from ims_bb_brand_keyword where key_type = 2  order by id asc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);

        $sql_product = "SELECT id,product_name from ims_jingpiwang_product where id >= 1  order by id asc";
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            $spec_value = $one['product_name'];
            $kou_wei = $one['product_name'];

            //$m_bb_brand->querySql("update ims_jingpiwang_product set spec_value='{$spec_value}',kou_wei='{$kou_wei}' where id = {$one['id']}");


        }
        echo time() . "<br>";
    }


    /**
     * 匹配ims_huiminwang_product的base_word1、base_word2字段,
     * 去掉品牌、基础词、规格后，按照权重高低来匹配确定修饰词1和修饰词2
     */
    public function index_zt21()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        //找出product所有品牌
        $sql_brand_name = "SELECT brand_name from ims_huiminwang_product where base_word <> '' GROUP BY brand_name";
        $list_brand_name = $m_bb_brand->querySql($sql_brand_name);

        //组合出所有品牌下标的对应所有key_word
        $arr_brand_key_word = array();
        foreach ($list_brand_name as $one) {
            $one_brand_name = trim($one['brand_name']);
            $sql_bb_brand = "SELECT id from ims_bb_brand where b_name = '{$one_brand_name}' and status = 1 limit 1";
            $list_bb_brand = $m_bb_brand->querySql($sql_bb_brand);

            if (count($list_bb_brand) > 0) {
                $sql_brand_keyword = "SELECT key_word, sum(weight) AS sum_weight FROM ims_bb_brand_keyword WHERE brand_id = {$list_bb_brand[0]['id']} AND `STATUS` = 1 GROUP BY key_word ORDER BY sum_weight DESC";
                $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);
                $arr_brand_key_word[$one_brand_name] = $list_brand_keyword;
            }
        }

        //列出product中有base_word的所有待清洗记录
        $sql_product = "SELECT id,`name`,key_word,base_word,brand_name from ims_huiminwang_product where base_word <> '' order by id asc";
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            echo $one['name'] . ":" . $one['brand_name'] . "+" . $one['base_word'];
            //$str_goods_key_word0 = str_replace(',', '', $one['key_word']);
            //$str_goods_key_word1 = str_replace($one['brand_name'], '', $str_goods_key_word0);
            //$str_goods_key_word2 = str_replace($one['base_word'], '', $str_goods_key_word1);

            //清洗掉品牌和基础词
            $arr_one_goods_key_word = explode(',', $one['key_word']);
            foreach ($arr_one_goods_key_word as $k => $v) {
                if ($v == $one['brand_name']) {
                    unset($arr_one_goods_key_word[$k]);
                }
                if ($v == $one['base_word']) {
                    unset($arr_one_goods_key_word[$k]);
                }
                if (mb_strlen($v) <= 1) {
                    unset($arr_one_goods_key_word[$k]);
                }
            }

            //某商品所剩余的词根
            $arr_one_goods_key_word = array_values($arr_one_goods_key_word);

            $base_word1 = '';
            $base_word2 = '';
            $arr_one = null;

            $key_count = count($arr_one_goods_key_word);
            if ($key_count > 0) {
                if ($key_count == 1) {
                    $base_word1 = $arr_one_goods_key_word[0];
                } else {
                    if (isset($arr_brand_key_word[$one['brand_name']])) {
                        $arr_temp1 = $arr_brand_key_word[$one['brand_name']];
                        foreach ($arr_one_goods_key_word as $three) {
                            foreach ($arr_temp1 as $abc) {
                                if ($three == $abc) {
                                    $arr_one[] = $three;
                                    break;
                                }
                            }
                        }
                        if (count($arr_one) > 0) {
                            //print_r($arr_one);
                            //exit;
                            $arr_one = $this->arraySequence($arr_one, 'sum_weight');
                            if (count($arr_one) == 1) {
                                $base_word1 = $arr_one[0]['key_word'];
                            } else {
                                $base_word1 = $arr_one[0]['key_word'];
                                $base_word2 = $arr_one[1]['key_word'];
                            }
                        } else {
                            $base_word1 = $arr_one_goods_key_word[0];
                            $base_word2 = $arr_one_goods_key_word[1];
                        }
                    }
                }
            }

            if (!empty($base_word1) || !empty($base_word2)) {
                $m_bb_brand->querySql("update ims_huiminwang_product set base_word1='{$base_word1}',base_word2='{$base_word2}' where id = {$one['id']}");

                echo "update ims_huiminwang_product set base_word1='{$base_word1}',base_word2='{$base_word2}' where id = {$one['id']}";
            }
            echo "<br>";


        }
        echo time() . "<br>";
    }

    /**
     * 规格
     * @param $conent
     * @param int $to_upcase
     * @return string
     */
    function get_spec_value($conent, $to_upcase = 0)
    {
        //echo "get_spec_value: " . $conent . "<br>";
        $unit = '';
        $i_len = mb_strlen($conent);
        $i_start = -1;
        $i_end = 0;
        for ($i = 0; $i < $i_len; $i++) {
            $one_char = mb_substr($conent, $i, 1);
            if (is_numeric($one_char)) {
                $i_start = $i;
                break;
            }
        }
        //echo $i_start . "<br>";
        if ($i_start > -1) {
            for ($i = $i_start; $i < $i_len; $i++) {
                $one_char = mb_substr($conent, $i, 1);
                if (!is_numeric($one_char)) {
                    $i_end = $i;
                    break;
                }
            }
            //echo $i_end . "<br>";
            $unit = mb_substr($conent, $i_start, $i_end - $i_start + 1);
            if ($to_upcase) {
                $unit = mb_strtoupper($unit);
            }
        }
        //echo $unit . "<br>";
        return $unit;
    }

    /**
     * index_zt22
     */
    public function index_zt22()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        //$m_bb_brand->querySql("update ims_huiminwang_product set kou_wei = '' where kou_wei <> ''");
        //货比三价-口味
        $sql_key_word = "SELECT id,key_word from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' order by id asc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word = array();
        foreach ($list_key_word as $one) {
            $arr_key_word[] = $one['key_word'];
        }

        //货比三价-品牌
        $sql_brand = "SELECT id,b_name from ims_bb_brand where `status` = 1 order by id asc";
        $list_brand = $m_bb_brand->querySql($sql_brand);
        $arr_brand = array();
        foreach ($list_brand as $one) {
            $arr_brand[] = $one['b_name'];
        }

        //货比三价-单位,暂时不用这方法

        //遍历记录，处理各个字段
        $sql_product = "SELECT id,product_name from ims_jingpiwang_product where id >= 1 order by id ";//limit 100
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            $brand_name0 = trim($one['product_name']);
            if (substr($one['product_name'], 0, 1) == '*') {
                $brand_name1 = substr($brand_name0, 1);
            } else {
                $brand_name1 = $brand_name0;
            }

            $brand_name2 = str_replace(' ', '__', $brand_name1);
            $arr1 = explode('*', $brand_name2);

            $brand_name1 = $arr1[0];
            $arr2 = explode('__', $brand_name1);
            $spec_value = '';//$this->get_spec_value($arr2);
            foreach ($arr2 as $four) {
                $unit_temp = $this->get_spec_value($four);
                if (!empty($unit_temp)) {
                    $spec_value = $unit_temp;
                    break;
                }
            }

            $kou_wei = '';
            foreach ($arr_key_word as $two) {
                if (stripos($brand_name2, $two) !== false) {
                    $kou_wei = $two;
                    break;
                }
            }
            $brand_name = '';
            foreach ($arr_brand as $three) {
                if (stripos($brand_name2, $three) !== false) {
                    $brand_name = $three;
                    break;
                }
            }

            //brand_name spec_value kou_wei
            if (!empty($brand_name) || !empty($kou_wei) || !empty($spec_value)) {
                $m_bb_brand->executeSql("update ims_jingpiwang_product set brand_name = '{$brand_name}', kou_wei = '{$kou_wei}',spec_value = '{$spec_value}' where id = {$one['id']}");
                echo "update ims_jingpiwang_product set brand_name = '{$brand_name}', kou_wei = '{$kou_wei}',spec_value = '{$spec_value}' where id = {$one['id']}";
                //break;
                echo "<br>";
            }

        }
        echo "<br>" . time() . "<br>";
    }

    /**
     * index_yk22
     */
    public function index_yk22()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_SignModel = new SignModel();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $day22 = date('Y-m-d', strtotime('-1 sunday', time()) + 60 * 60 * 24);
        $day11 = date('Y-m-d', strtotime($day22) - 60 * 60 * 24 * 7);

        $day2 = $this->request->param("day2");
        if (empty($day2)) {
            $day2 = $day22;
        }
        $day1 = $this->request->param("day1");
        if (empty($day1)) {
            $day1 = $day11;
        }
        echo $day1 . " " . $day2 . "<br>";
        $i_day1 = strtotime($day1);
        $i_day2 = strtotime($day2);

        $sql_potential_customer = "SELECT a.id, a.user_name, a.address, a.service_id, c.`name`, a.line_code, a.xcx_openid FROM potential_customer a LEFT JOIN btj_admin_user c ON a.service_id = c.user_id WHERE a.is_validity = 1 AND a.line_code > 0 ORDER BY a.id ASC";
        $list_potential_customer = $m_SignModel->querySql($sql_potential_customer);

        echo "<table border='1'>";
        echo "<tr><td>ID</td><td>点位名称</td><td>点位地址</td><td>服务人Id</td><td>负责人名</td><td>排线</td><td>openid</td><td>评分</td><td>订单数</td><td>点单天</td></tr>";
        foreach ($list_potential_customer as $k => $v) {
            echo "<tr>";
            foreach ($v as $one_r) {
                echo "<td>" . $one_r . "</td>";
            }
            //评分
            $sql_potential_customer_data = "SELECT id,score_level from potential_customer_data where customer_id = {$v['id']}  order by id desc limit 1";
            $list_potential_customer_data = $m_SignModel->querySql($sql_potential_customer_data);
            $list_potential_customer[$k]['score_level'] = '';
            if (count($list_potential_customer_data) > 0) {
                $list_potential_customer[$k]['score_level'] = $list_potential_customer_data[0]['score_level'];
            }
            echo "<td>" . $list_potential_customer[$k]['score_level'] . "</td>";

            if (!empty($v['xcx_openid'])) {
                //订单
                $sql_shop_order = "SELECT id,createtime from ims_ewei_shop_order where openid = '{$v['xcx_openid']}' and status >= 0 and createtime > {$i_day1} and createtime < {$i_day2}";
                //echo $sql_shop_order."<br>";
                $list_shop_order = $m_bb_brand->querySql($sql_shop_order);
                $list_potential_customer[$k]['order_count'] = count($list_shop_order);

                $arr_day = null;
                foreach ($list_shop_order as $one) {
                    $day_one = date('Y-m-d', $one['createtime']);
                    if (empty($arr_day)) {
                        $arr_day[] = $day_one;
                    } else {
                        if (!in_array($day_one, $arr_day)) {
                            $arr_day[] = $day_one;
                        }
                    }
                }
                $list_potential_customer[$k]['day_count'] = count($arr_day);
            } else {
                $list_potential_customer[$k]['order_count'] = 0;
                $list_potential_customer[$k]['day_count'] = 0;
            }
            echo "<td>" . $list_potential_customer[$k]['order_count'] . "</td>";
            echo "<td>" . $list_potential_customer[$k]['day_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo time() . "<br>";
    }

    /**
     * index_yk2
     * @throws Exception
     */
    public function index_yk2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $time = time();
        $m_SignModel = new SignModel();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $day22 = date('Y-m-d', strtotime('-1 sunday', time()) + 60 * 60 * 24);
        $day11 = date('Y-m-d', strtotime($day22) - 60 * 60 * 24 * 7);

        $day2 = $this->request->param("day2");
        if (empty($day2)) {
            $day2 = $day22;
        }
        $day1 = $this->request->param("day1");
        if (empty($day1)) {
            $day1 = $day11;
        }
        $page = $this->request->param("page");
        $pagestar = $page * 100;
        echo $day1 . " " . $day2 . "<br>";
        $i_day1 = strtotime($day1);
        $i_day2 = strtotime($day2);

        $sql_potential_customer = "SELECT a.id, a.user_name, a.address, a.service_id, c.`name`, a.line_code, a.xcx_openid FROM potential_customer a LEFT JOIN btj_admin_user c ON a.service_id = c.user_id WHERE a.is_validity = 1 AND a.line_code > 0 ORDER BY a.id ASC limit $pagestar,100";
        $list_potential_customer = $m_SignModel->querySql($sql_potential_customer);

        echo "<table border='1'>";
        echo "<tr><td>ID</td><td>点位名称</td><td>点位地址</td><td>服务人Id</td><td>负责人名</td><td>排线</td><td>openid</td><td>评分</td><td>订单数</td><td>订单天</td><td>拜访次数</td><td>浏览次数</td><td>浏览天数</td><td>最近一次浏览时间</td><td>最近一次下单时间</td><td>品牌盘点数</td><td>是否加购物车</td><td>是否填写地址</td></tr>";
        foreach ($list_potential_customer as $k => $v) {
            echo "<tr>";
            foreach ($v as $one_r) {
                echo "<td>" . $one_r . "</td>";
            }
            //评分
            $sql_potential_customer_data = "SELECT id,score_level from potential_customer_data where customer_id = {$v['id']}  order by id desc limit 1";
            $list_potential_customer_data = $m_SignModel->querySql($sql_potential_customer_data);
            $list_potential_customer[$k]['score_level'] = '';
            if (count($list_potential_customer_data) > 0) {
                $list_potential_customer[$k]['score_level'] = $list_potential_customer_data[0]['score_level'];
            }
            echo "<td>" . $list_potential_customer[$k]['score_level'] . "</td>";

            if (!empty($v['xcx_openid'])) {
                //订单
                $sql_shop_order = "SELECT id,createtime from ims_ewei_shop_order where openid = '{$v['xcx_openid']}' and status >= 0 and createtime > {$i_day1} and createtime < {$i_day2}";
                $list_shop_order = $m_bb_brand->querySql($sql_shop_order);
                $list_potential_customer[$k]['order_count'] = count($list_shop_order);

                $arr_day = null;
                foreach ($list_shop_order as $one) {
                    $day_one = date('Y-m-d', $one['createtime']);
                    if (empty($arr_day)) {
                        $arr_day[] = $day_one;
                    } else {
                        if (!in_array($day_one, $arr_day)) {
                            $arr_day[] = $day_one;
                        }
                    }
                }
                $list_potential_customer[$k]['day_count'] = count($arr_day);
            } else {
                $list_potential_customer[$k]['order_count'] = 0;
                $list_potential_customer[$k]['day_count'] = 0;
            }
            echo "<td>" . $list_potential_customer[$k]['order_count'] . "</td>";
            echo "<td>" . $list_potential_customer[$k]['day_count'] . "</td>";
            //拜访次数
            $signcount = Db::connect('db_btj_new')
                ->table('btj_sign')
                ->where('customer_id', $v['id'])
                ->where('create_time', 'between', [$i_day1, $i_day2])
                ->count();
            echo "<td>" . $signcount . "</td>";
            //浏览次数
            $openids = $this->getXcxOpenids($v['id']);
            $lius = Db::connect('db_mini_mall')
                ->table('ims_member_action_log')
                ->field("FROM_UNIXTIME(createtime,'%Y-%m-%d') as dates")
                ->where('log_type', 3)
                ->where('sup_id', 461)
                ->where('openid', 'IN', $openids)
                ->where('createtime', 'between', [$i_day1, $i_day2])
                ->select();
            echo "<td>" . count($lius) . "</td>";
            //浏览天数
            $liu_day = count(array_unique(array_column($lius, 'dates')));
            echo "<td>" . $liu_day . "</td>";
            //最近一次浏览时间
            $liu_time = Db::connect('db_mini_mall')
                ->table('ims_member_action_log')
                ->where(['log_type' => 3])
                ->where('sup_id', 461)
                ->where('openid', 'IN', $openids)
                ->MAX('createtime');
            $liutime = '';
            if ($liu_time) {
                $liutime = date('Y-m-d H:i:s', $liu_time);
            }
            echo "<td>" . $liutime . "</td>";
            //最近一次下单时间
            $last = Db::connect('db_mini_mall')
                ->table('ims_ewei_shop_order')
                ->where('status', '>', -1)
                ->where('openid', 'IN', $openids)
                ->MAX('createtime');
            $lasttime = '';
            if ($last) {
                $lasttime = date('Y-m-d H:i:s', $last);
            }

            echo "<td>" . $lasttime . "</td>";
            //品牌盘点数
            $brands = Db::connect('db_btj_new')
                ->table('btj_brand_bd_log')
                ->where('type', 1)
                ->where('customer_id', $v['id'])
                ->where('create_time', 'between', [$i_day1, $i_day2])
                ->column('brand_id');
            $brand_count = count(array_unique($brands));
            echo "<td>" . $brand_count . "</td>";
            //是否加入购物车
            $is_cart = Db::connect('db_mini_mall')
                ->table('ims_ewei_shop_member_cart')
                ->where('openid', 'IN', $openids)
                ->where('deleted', 0)
                ->count();
            $cart = $is_cart > 0 ? '是' : '否';
            echo "<td>" . $cart . "</td>";
            //是否填写地址
            $is_address = Db::connect('db_mini_mall')
                ->table('ims_ewei_shop_member_address')
                ->where('openid', 'IN', $openids)
                ->count();
            $address = $is_address > 0 ? '是' : '否';
            echo "<td>" . $address . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo time() . "<br>";
    }

    /**
     * getXcxOpenids
     * @param $user_id
     * @return array
     * @throws Exception
     */
    public function getXcxOpenids($user_id)
    {
        //获取所有客户openid
        $openids_arr = DB::connect('db_btj_new')
            ->table('potential_customer')
            ->field('xcx_openid,id')
            ->where('parent_id', 0)
            ->where('is_validity', 1)
            ->where('id', $user_id)
            ->select();
        $openids = array_filter(array_unique(array_column($openids_arr, 'xcx_openid')));
        $ids = array_filter(array_unique(array_column($openids_arr, 'id')));

        //获取所有客户子集openid
        $openidss = DB::connect('db_btj_new')
            ->table('potential_customer')
            ->where('is_validity', 1)
            ->where('parent_id', 'IN', $ids)
            ->column('xcx_openid');
        $openidss = array_filter(array_unique($openidss));

        $openids = array_keys(array_flip($openids) + array_flip($openidss));

        return $openids;
    }


    /**
     * 匹配ims_jingpiwang_product的base_word字段，
     * 用ims_bb_brand_keyword表的key_type=4去product_name匹配
     */
    public function index_zt23()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_base_word = "SELECT id,key_word from ims_bb_brand_keyword where key_type = 4  order by id asc";
        $list_base_word = $m_bb_brand->querySql($sql_base_word);

        $sql_product = "SELECT id,product_name,kou_wei from ims_jingpiwang_product where id >= 1  order by id asc";
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            $product_name = $one['product_name'];
            $base_word = '';
            foreach ($list_base_word as $two) {
                if (stripos($product_name, $two['key_word']) !== false && stripos($one['kou_wei'], $two['key_word']) === false) {
                    $base_word = $two['key_word'];
                    break;
                }
            }

            if (!empty($base_word)) {
                $m_bb_brand->querySql("update ims_jingpiwang_product set base_word='{$base_word}' where id = {$one['id']}");

                echo "update ims_jingpiwang_product set base_word='{$base_word}' where id = {$one['id']}<br>";
            }
        }
        echo time() . "<br>";
    }


    /**
     * zuofei:匹配bsdj的sku，用name去goods_item中对应品牌底下的所有item匹配度最高5个，然后手工确定
     * 列出bsdj中孙宇店可能有的（相似度）
     */
    public function index_zt24()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_bsdj_product = "SELECT id,brandName,skuName,specifications from ims_bsdj_product where id >= 1  order by id asc limit 10";
        $list_bsdj_product = $m_bb_brand->querySql($sql_bsdj_product);

        $sql_shop_goods = "SELECT a.id,a.brand_id,a.title,c.b_name from ims_ewei_shop_goods 
where FROM ims_ewei_shop_goods a
LEFT JOIN ims_yd_supplier_goods b ON a.id = b.goods_id
LEFT JOIN ims_bb_brand c ON a.brand_id = c.id
WHERE b.supplier_id = 461 AND a.total > 0 AND a.deleted = 0 AND a.`status` = 1 AND b.`status` = 1  order by a.id asc";
        $list_shop_goods = $m_bb_brand->querySql($sql_shop_goods);
        $arr_shop_goods = array();
        foreach ($list_shop_goods as $one) {
            $arr_shop_goods[$one['b_name']][] = $one;
        }

        foreach ($list_bsdj_product as $one) {
            $skuName = $one['skuName'];
            $arr_sku_name = explode(' ', $skuName);
            $sku_name = $arr_sku_name[0];
            $brandName = $one['brandName'];
            $str_spec = $one['specifications'];
            $arr_spec = explode('(', $str_spec);
            $spec = $arr_spec[0];
            if ($sku_name) {

            }
            //$sy_goods_list = null;
            if (isset($arr_shop_goods[$brandName])) {
                $arr_brand_goods = $arr_shop_goods[$brandName];
                $arr_same_goods = null;
                foreach ($arr_brand_goods as $two) {
                    $sy_goods_one = null;
                    $sy_goods_one['id'] = $two['id'];
                    $sy_goods_one['title'] = $two['title'];
                    $sy_goods_one['percent'] = $this->str_same_percent($skuName, $two['title']);
                    $arr_same_goods[] = $sy_goods_one;
//                    foreach($arr_same_goods as $three){
//
//                    }
                }
                if (count($arr_same_goods) > 3) {
                    $arr_same_goods = $this->arraySequence($arr_same_goods, 'percent');
                    array_splice($arr_same_goods, 3);
                }
                print_r($arr_same_goods);

            } else {
                echo "无匹配";
            }
            echo "<br>";


//            if (!empty($base_word)) {
//                $m_bb_brand->querySql("update ims_jingpiwang_product set base_word='{$base_word}' where id = {$one['id']}");
//
//                echo "update ims_jingpiwang_product set base_word='{$base_word}' where id = {$one['id']}<br>";
//            }
        }
        echo time() . "<br>";
    }


    /**
     * 匹配ims_bsdj_product的base_word等几个字段
     */
    public function index_zt25()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        //$m_bb_brand->querySql("update ims_huiminwang_product set kou_wei = '' where kou_wei <> ''");
        //货比三价-口味
        $sql_key_word = "SELECT id,key_word from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' order by id asc";
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word2 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word2[] = $one['key_word'];
        }

        //货比三价-基础词
        //$sql_key_word = "SELECT id,key_word from ims_bb_brand_keyword where key_type = 4 and status = 1 and key_word <> '口味' order by id asc";
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 4 and status = 1  GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word4 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word4[] = $one['key_word'];
        }

        //货比三价-品牌
        $sql_brand = "SELECT id,b_name from ims_bb_brand where `status` = 1 order by id asc";
        $list_brand = $m_bb_brand->querySql($sql_brand);
        $arr_brand = array();
        foreach ($list_brand as $one) {
            $arr_brand[] = $one['b_name'];
        }

        //货比三价-单位,暂时不用这方法

        //遍历记录，处理各个字段
        $sql_product = "SELECT id,brandName,skuName,specifications from ims_bsdj_product where id >= 1 order by id ";//limit 100
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            $skuName = str_replace("'", '’', $one['skuName']);
            $arr_sku_name = explode(' ', $skuName);
            $sku_name0 = $arr_sku_name[0];
            //$brand_Name = $one['brandName'];
            $str_spec = str_replace('（', '(', $one['specifications']);
            $arr_spec = explode('(', $str_spec);
            $spec = $arr_spec[0];
            $sku_name = str_replace($spec, '', $sku_name0);
            $arr_spec1 = explode('/', $spec);

            $spec_value0 = $arr_spec1[0];//$this->get_spec_value($arr2);
            $spec_value1 = explode('*', $spec_value0);
            $spec_value = $spec_value1[0];

            $brand_name = '';
            foreach ($arr_brand as $four) {
                if (stripos($sku_name, $four) !== false) {
                    $brand_name = $four;
                    break;
                }
            }

            $kou_wei = '';
            foreach ($arr_key_word2 as $two) {
                if (stripos($sku_name, $two) !== false) {
                    $kou_wei = $two;
                    break;
                }
            }

            $base_word = '';
            foreach ($arr_key_word4 as $three) {
                if (stripos($sku_name, $three) !== false) {
                    $base_word = $three;
                    break;
                }
            }

            //brand_name spec_value kou_wei
            if (!empty($kou_wei) || !empty($spec_value)) {
                $m_bb_brand->executeSql("update ims_bsdj_product set sku_name = '{$sku_name}',base_word = '{$base_word}', kou_wei = '{$kou_wei}',spec_value = '{$spec_value}',brand_name='{$brand_name}' where id = {$one['id']}");
                echo "update ims_bsdj_product set sku_name = '{$sku_name}',base_word = '{$base_word}', kou_wei = '{$kou_wei}',spec_value = '{$spec_value}',brand_name='{$brand_name}' where id = {$one['id']}";
                //break;
                echo "<br>";
            }

        }
        echo "<br>" . time() . "<br>";
    }

    /**
     * @param $goods_name
     * @return string
     */
    function get_goods_spec($goods_name)
    {
        //echo $goods_name . "<br>";
        $spec_value = '';
        $i_len = mb_strlen($goods_name);

        $sku_char_l1 = mb_substr($goods_name, -1);

        if (!is_numeric($sku_char_l1)) {
            $sku_char_l2 = mb_substr($goods_name, -2, 1);
            $sku_char_l3 = mb_substr($goods_name, -3, 1);

            if (is_numeric($sku_char_l2)) {
                $s_pos = $i_len;
                $e_pos = $i_len - 1;
                for ($i = $i_len - 2; $i > 0; $i--) {
                    $char = mb_substr($goods_name, $i, 1);
                    //echo $char . "<br>";
                    if (!is_numeric($char) && $char <> '.') {
                        $s_pos = $i;
                        break;
                    }
                }
                $spec_value = mb_substr($goods_name, $s_pos + 1, $e_pos - $s_pos + 1);
            } elseif (is_numeric($sku_char_l3)) {
                $s_pos = $i_len;
                $e_pos = $i_len - 2;
                for ($i = $i_len - 3; $i > 0; $i--) {
                    $char = mb_substr($goods_name, $i, 1);
                    //echo $char . "<br>";
                    if (!is_numeric($char) && $char <> '.') {
                        $s_pos = $i;
                        break;
                    }
                }
                $spec_value = mb_substr($goods_name, $s_pos + 1, $e_pos - $s_pos + 1);
            }
        }
        return $spec_value;
    }

    /**
     * 匹配ims_ewei_shop_goods的base_word等几个字段
     */
    public function index_zt26()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        //$m_bb_brand->querySql("update ims_huiminwang_product set kou_wei = '' where kou_wei <> ''");
        //货比三价-口味
        $sql_key_word = "SELECT id,key_word from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' order by id asc";
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word2 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word2[] = $one['key_word'];
        }

        //货比三价-基础词
        //$sql_key_word = "SELECT id,key_word from ims_bb_brand_keyword where key_type = 4 and status = 1 and key_word <> '口味' order by id asc";
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 4 and status = 1  GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word4 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word4[] = $one['key_word'];
        }

        //货比三价-品牌
//        $sql_brand = "SELECT id,b_name from ims_bb_brand where `status` = 1 order by id asc";
//        $list_brand = $m_bb_brand->querySql($sql_brand);
//        $arr_brand = array();
//        foreach ($list_brand as $one) {
//            $arr_brand[$one['id']] = $one['b_name'];
//        }

        //货比三价-单位,暂时不用这方法

        //遍历记录，处理各个字段
        $sql_shop_goods = "SELECT a.id,a.brand_id,a.title,c.b_name,a.skuid,c.b_name
FROM ims_ewei_shop_goods a
LEFT JOIN ims_yd_supplier_goods b ON a.id = b.goods_id
LEFT JOIN ims_bb_brand c ON a.brand_id = c.id
WHERE b.supplier_id = 461 AND a.total > 0 AND a.deleted = 0 AND a.`status` = 1 AND b.`status` = 1  order by a.id asc ";
        $list_product = $m_bb_brand->querySql($sql_shop_goods);

        foreach ($list_product as $one) {
            //$brand_name = b_name;
            $skuName = $one['title'];
            $arr_sku_name = explode('（', $skuName);
            $sku_name0 = $arr_sku_name[0];
            $sku_name1 = explode('/', $sku_name0);
            $sku_name2 = $sku_name1[0];
            $sku_name3 = explode('*', $sku_name2);
            $sku_name4 = $sku_name3[0];

            $spec_value = $this->get_goods_spec($sku_name4);

            $sku_name = substr($sku_name0, 0, stripos($sku_name0, $spec_value));

            $kou_wei = '';
            foreach ($arr_key_word2 as $two) {
                if (stripos($sku_name, $two) !== false) {
                    $kou_wei = $two;
                    break;
                }
            }

            $base_word = '';
            foreach ($arr_key_word4 as $three) {
                if (stripos($sku_name, $three) !== false && stripos($kou_wei, $three) === false) {
                    $base_word = $three;
                    break;
                }
            }

            //brand_name spec_value kou_wei
            if (!empty($kou_wei) || !empty($spec_value)) {
                $m_bb_brand->executeSql("insert into ims_ewei_shop_goods_pro (goods_id,sku_id,goods_name,sku_name,brand_name,kou_wei,base_word,spec_value)values({$one['id']},'{$one['skuid']}','{$one['title']}','{$sku_name}','{$one['b_name']}','{$kou_wei}','{$base_word}','{$spec_value}')");

                echo "insert into ims_ewei_shop_goods_pro (goods_id,sku_id,goods_name,sku_name,brand_name,kou_wei,base_word,spec_value)values({$one['id']},{$one['skuid']},'{$one['title']}','{$sku_name}','{$one['b_name']}','{$kou_wei}','{$base_word}','{$spec_value}')";
                echo "<br>";
            }

        }
        echo "<br>" . time() . "<br>";
    }

    /**
     * 匹配bsdj的base_word1,base_word2
     */
    public function index_zt27()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $split_word = new splitWord();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        //找出product所有品牌
        $sql_brand_name = "SELECT brand_name from ims_bsdj_product where base_word <> '' GROUP BY brand_name";
        $list_brand_name = $m_bb_brand->querySql($sql_brand_name);

        //组合出所有品牌下标的对应所有key_word
        $arr_brand_key_word = array();
        $arr_brand_dict = array();
        foreach ($list_brand_name as $one) {
            $one_brand_name = trim($one['brand_name']);
            $sql_bb_brand = "SELECT id from ims_bb_brand where b_name = '{$one_brand_name}' and status = 1 limit 1";
            $list_bb_brand = $m_bb_brand->querySql($sql_bb_brand);

            if (count($list_bb_brand) > 0) {
                $sql_brand_keyword = "SELECT key_word, sum(weight) AS sum_weight FROM ims_bb_brand_keyword WHERE brand_id = {$list_bb_brand[0]['id']} AND `STATUS` = 1 GROUP BY key_word ORDER BY sum_weight DESC";
                $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);
                $arr_brand_key_word[$one_brand_name] = $list_brand_keyword;
//                foreach ($list_brand_keyword as $two) {
                $arr_brand_dict[$one_brand_name] = $list_brand_keyword;//$two
//                }
            }
        }

        //列出product中有base_word的所有待清洗记录
        $sql_product = "SELECT id,sku_name,brand_name,base_word,kou_wei from ims_bsdj_product where base_word <> '' order by id asc";
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            //echo $one['id'].' '.$one['sku_name'] . ":" . $one['brand_name'] . "+" . $one['base_word'];

            $sku_name0 = $one['sku_name'];
            $sku_name1 = explode('*', $sku_name0);
            $sku_name2 = $sku_name1[0];
            $sku_name3 = explode('/', $sku_name2);
            $sku_name4 = $sku_name3[0];
            //echo "+".$sku_name2." ";
            $s_value = $this->get_goods_spec($sku_name4);
            if (!empty($s_value)) {
                $sku_name = substr($sku_name4, 0, stripos($sku_name4, $s_value));
            } else {
                $sku_name = $sku_name4;
            }

            //echo "+".$sku_name." ";

            //1.先分词
            $arr_goods_key_word = null;
            if (isset($arr_brand_dict[$one['brand_name']])) {
                $dict = $split_word->get_dict($arr_brand_dict[$one['brand_name']]);
                $dict = $split_word->fix_dict($dict);
                $split_word->set_dict($dict);
                $arr_goods_key_word = $split_word->split_word($sku_name);
            }
            $arr_one_goods_key_word = $arr_goods_key_word;
            $str_goods_key_word = implode(',', $arr_one_goods_key_word);

            //2.按照之前算法，找word1.word2

            //清洗掉品牌和基础词
            //$arr_one_goods_key_word = explode(',', $one['key_word']);
            foreach ($arr_one_goods_key_word as $k => $v) {
                if ($v == $one['brand_name']) {
                    unset($arr_one_goods_key_word[$k]);
                }
                if ($v == $one['base_word']) {
                    unset($arr_one_goods_key_word[$k]);
                }
                if ($v == $one['kou_wei']) {
                    unset($arr_one_goods_key_word[$k]);
                }
                if (mb_strlen($v) <= 1) {
                    unset($arr_one_goods_key_word[$k]);
                }
            }

            //某商品所剩余的词根
            $arr_one_goods_key_word = array_values($arr_one_goods_key_word);

            $base_word1 = '';
            $base_word2 = '';
            $arr_one = null;

            $key_count = count($arr_one_goods_key_word);
            if ($key_count > 0) {
                if ($key_count == 1) {
                    $base_word1 = $arr_one_goods_key_word[0];
                } else {
                    if (isset($arr_brand_key_word[$one['brand_name']])) {
                        $arr_temp1 = $arr_brand_key_word[$one['brand_name']];
                        foreach ($arr_one_goods_key_word as $three) {
                            foreach ($arr_temp1 as $abc) {
                                if ($three == $abc) {
                                    $arr_one[] = $three;
                                    break;
                                }
                            }
                        }
                        if (count($arr_one) > 0) {
                            $arr_one = $this->arraySequence($arr_one, 'sum_weight');
                            if (count($arr_one) == 1) {
                                $base_word1 = $arr_one[0]['key_word'];
                            } else {
                                $base_word1 = $arr_one[0]['key_word'];
                                $base_word2 = $arr_one[1]['key_word'];
                            }
                        } else {
                            $base_word1 = $arr_one_goods_key_word[0];
                            $base_word2 = $arr_one_goods_key_word[1];
                        }
                    }
                }
            }

            if (!empty($base_word1) || !empty($base_word2) || !empty($str_goods_key_word)) {
                $m_bb_brand->querySql("update ims_bsdj_product set base_word1='{$base_word1}',base_word2='{$base_word2}',sku_name='{$sku_name}',key_word='{$str_goods_key_word}' where id = {$one['id']}");

                echo "update ims_bsdj_product set base_word1='{$base_word1}',base_word2='{$base_word2}',sku_name='{$sku_name}',key_word='{$str_goods_key_word}' where id = {$one['id']}";
                echo "<br>";
            }

        }
        echo time() . "<br>";
    }

    /**
     * 匹配goods_item的base_word，kou_wei
     */
    public function index_zt28()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        //货比三价-口味
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word2 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word2[] = $one['key_word'];
        }

        //货比三价-基础词
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 4 and status = 1  GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word4 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word4[] = $one['key_word'];
        }


        //遍历记录，处理各个字段
        $sql_shop_goods = "SELECT id,brand_id,goods_name FROM ims_bb_goods_item where id >= 1  order by id asc ";
        $list_product = $m_bb_brand->querySql($sql_shop_goods);

        foreach ($list_product as $one) {
            $skuName = $one['goods_name'];

            $kou_wei = '';
            foreach ($arr_key_word2 as $two) {
                if (stripos($skuName, $two) !== false) {
                    $kou_wei = $two;
                    break;
                }
            }

            $base_word = '';
            foreach ($arr_key_word4 as $three) {
                if (stripos($skuName, $three) !== false) {
                    if (!empty($kou_wei)) {
                        if (stripos($kou_wei, $three) === false) {
                            $base_word = $three;
                            break;
                        }
                    }
                }
            }

            //brand_name spec_value kou_wei
            if (!empty($kou_wei) || !empty($base_word)) {
                $m_bb_brand->executeSql("update ims_bb_goods_item set base_word = '{$base_word}',kou_wei='{$kou_wei}' where id = {$one['id']} ");

                echo "update ims_bb_goods_item set base_word = '{$base_word}',kou_wei='{$kou_wei}' where id = {$one['id']} ";
                echo "<br>";
            }

        }
        echo "<br>" . time() . "<br>";
    }

    /**
     * 匹配goods_item的base_word1,base_word2
     */
    public function index_zt29()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $split_word = new splitWord();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        //找出product所有品牌
        $sql_bb_brand = "SELECT id from ims_bb_brand where id >= 1";
        $list_bb_brand = $m_bb_brand->querySql($sql_bb_brand);

        $arr_brand_key_word = array();
        foreach ($list_bb_brand as $one) {
            $sql_brand_keyword = "SELECT key_word, sum(weight) AS sum_weight FROM ims_bb_brand_keyword WHERE brand_id = {$one['id']} AND `STATUS` = 1 GROUP BY key_word ORDER BY sum_weight DESC";
            $list_brand_keyword = $m_bb_brand->querySql($sql_brand_keyword);
            $arr_brand_key_word[$one['id']] = $list_brand_keyword;
        }

        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 4 and status = 1  GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word4 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word4[] = $one['key_word'];
        }

        //列出product中有base_word的所有待清洗记录
        $sql_product = "SELECT id,sku_name,brand_name,base_word,kou_wei from ims_bb_goods_item where base_word <> '' order by id asc";
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            //echo $one['id'].' '.$one['sku_name'] . ":" . $one['brand_name'] . "+" . $one['base_word'];

            $sku_name0 = $one['sku_name'];
            $sku_name1 = explode('*', $sku_name0);
            $sku_name2 = $sku_name1[0];
            $sku_name3 = explode('/', $sku_name2);
            $sku_name4 = $sku_name3[0];
            //echo "+".$sku_name2." ";
            $s_value = $this->get_goods_spec($sku_name4);
            if (!empty($s_value)) {
                $sku_name = substr($sku_name4, 0, stripos($sku_name4, $s_value));
            } else {
                $sku_name = $sku_name4;
            }

            //echo "+".$sku_name." ";

            //1.先分词
            $arr_goods_key_word = null;
            if (isset($arr_brand_key_word[$one['brand_name']])) {
                $one_brand_dict = $arr_brand_key_word[$one['brand_name']];
                $dict = $split_word->get_dict($one_brand_dict);
                $dict = $split_word->fix_dict($dict);
                $split_word->set_dict($dict);
                $arr_goods_key_word = $split_word->split_word($sku_name);
            }
            $arr_one_goods_key_word = $arr_goods_key_word;
            $str_goods_key_word = implode(',', $arr_one_goods_key_word);

            //2.按照之前算法，找word1.word2

            //清洗掉品牌和基础词
            //$arr_one_goods_key_word = explode(',', $one['key_word']);
            foreach ($arr_one_goods_key_word as $k => $v) {
                if ($v == $one['brand_name']) {
                    unset($arr_one_goods_key_word[$k]);
                }
                if ($v == $one['base_word']) {
                    unset($arr_one_goods_key_word[$k]);
                }
                if ($v == $one['kou_wei']) {
                    unset($arr_one_goods_key_word[$k]);
                }
                if (mb_strlen($v) <= 1) {
                    unset($arr_one_goods_key_word[$k]);
                }
            }

            //某商品所剩余的词根
            $arr_one_goods_key_word = array_values($arr_one_goods_key_word);

            $base_word1 = '';
            $base_word2 = '';
            $arr_one = null;

            $key_count = count($arr_one_goods_key_word);
            if ($key_count > 0) {
                if ($key_count == 1) {
                    $base_word1 = $arr_one_goods_key_word[0];
                } else {
                    if (isset($arr_brand_key_word[$one['brand_name']])) {
                        $arr_temp1 = $arr_brand_key_word[$one['brand_name']];
                        foreach ($arr_one_goods_key_word as $three) {
                            foreach ($arr_temp1 as $abc) {
                                if ($three == $abc) {
                                    $arr_one[] = $three;
                                    break;
                                }
                            }
                        }
                        if (count($arr_one) > 0) {
                            $arr_one = $this->arraySequence($arr_one, 'sum_weight');
                            if (count($arr_one) == 1) {
                                $base_word1 = $arr_one[0]['key_word'];
                            } else {
                                $base_word1 = $arr_one[0]['key_word'];
                                $base_word2 = $arr_one[1]['key_word'];
                            }
                        } else {
                            $base_word1 = $arr_one_goods_key_word[0];
                            $base_word2 = $arr_one_goods_key_word[1];
                        }
                    }
                }
            }

            if (!empty($base_word1) || !empty($base_word2) || !empty($str_goods_key_word)) {
                $m_bb_brand->querySql("update ims_bb_goods_item set base_word1='{$base_word1}',base_word2='{$base_word2}',sku_name='{$sku_name}',key_word='{$str_goods_key_word}' where id = {$one['id']}");

                echo "update ims_bb_goods_item set base_word1='{$base_word1}',base_word2='{$base_word2}',sku_name='{$sku_name}',key_word='{$str_goods_key_word}' where id = {$one['id']}";
                echo "<br>";
            }

        }
        echo time() . "<br>";
    }


    /**
     * 清洗potential_customer_data数据//阿里云定时任务每5分钟运行
     * 这个表只是新增，所以跑过一遍的数据update_time>0不用再次运算，除非计算规则变化。
     *     1.1客户初步盘点
     * 将有开放供应链的超市划分为以下等级：
     * S ：25及以上，50%以上分类有经营,有雇员
     * A ：25及以上，50%以上分类有经营
     * B : 15-24  ，50%以上分类有经营
     * C : 10-14  ，50%及以上分类有经营
     * D : 5-9    ，30%及以上分类有经营
     * 0 :
     */
    public function index_yk3()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        //$time = time();
        $m_bb_brand = new SignModel();
        echo time() . "<br>";
        $time = time();
        $sql_customer_data = "SELECT * from potential_customer_data where update_time = 0";
        $list_customer_data = $m_bb_brand->querySql($sql_customer_data);
        $one_data = array();
        foreach ($list_customer_data as $one) {
            $has_employee = $one['employee'];
            $one_data = null;
            $k = 0;
            foreach ($one as $two) {
                if ($k > 0 && $k < 11) {
                    $one_data[] = $two;
                }
                $k++;
            }
            $cate_sum = array_sum($one_data);

            $cate_count = 0;
            foreach ($one_data as $three) {
                if (!empty($three)) {
                    $cate_count++;
                }
            }
            //print_r($one_data);
            $jing_ying = round($cate_count * 100 / 10, 1);
            if ($cate_sum >= 25 && $jing_ying >= 50 && $has_employee == 1) {
                $score_level = 'S';
            } elseif ($cate_sum >= 25 && $jing_ying >= 50) {
                $score_level = 'A';
            } elseif ($cate_sum >= 15 && $jing_ying >= 50) {
                $score_level = 'B';
            } elseif ($cate_sum >= 10 && $jing_ying >= 50) {
                $score_level = 'C';
            } elseif ($cate_sum >= 5 && $jing_ying >= 30) {
                $score_level = 'D';
            } else {
                $score_level = '0';
            }
            echo $one['id'] . " : " . $cate_sum . " + " . $jing_ying . " = " . $score_level . "<br>";
            //exit;
            $m_bb_brand->querySql("update potential_customer_data set score_level = '{$score_level}',update_time={$time} where id = {$one['id']}");

        }

    }


    /**
     * 将bsdj的baseword整理到key_word表
     */
    public function index_zt30()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_product = "SELECT a.id,a.sku_name,a.brandName,a.brand_name,a.base_word,a.cate2_temp,b.id as cate_bb2,c.id as brand_id from ims_bsdj_product a LEFT JOIN ims_bb_cate_bb b on a.cate2_temp=b.c_name LEFT JOIN ims_bb_brand c on c.b_name=a.brand_name where a.update_time > 0 and c.`status`=1";
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            echo $one['id'];
            $cate_bb2 = $one['cate_bb2'];
            $brand_id = $one['brand_id'];
            if (!empty($cate_bb2) && !empty($brand_id)) {
                $one_kw = $m_bb_brand->querySql("select id,come_form from ims_bb_brand_keyword where cate_bb2 = {$cate_bb2} and brand_id = {$brand_id} and key_word = '{$one['base_word']}' and status = 1 ");

                //echo "select id,come_form from ims_bb_brand_keyword where cate_bb2 = {$cate_bb2} and brand_id = {$brand_id} and key_word = '{$one['base_word']}' and status = 1 "."<br>";
                //continue;

                if (empty($one_kw)) {
                    $m_bb_brand->querySql("insert into ims_bb_brand_keyword (cate_bb2,brand_id,key_type,key_word,weight,come_form,status,create_time) values ($cate_bb2,$brand_id,4,'{$one['base_word']}',1,4,1,{$time})");

                    echo "insert into ims_bb_brand_keyword (cate_bb2,brand_id,key_type,key_word,weight,come_form,status,create_time) values ($cate_bb2,$brand_id,4,'{$one['base_word']}',1,4,1,{$time})<br>";
                } else {
                    if ($one_kw[0]['come_form'] == 4) {
                        $m_bb_brand->querySql("update ims_bb_brand_keyword set weight = weight + 1 where id = {$one_kw[0]['id']}");
                        echo "update ims_bb_brand_keyword set weight = weight + 1 where id = {$one_kw[0]['id']}<br>";
                    }

                }
            }
        }
        echo time() . "<br>";
    }

    /**
     * @return string
     */
    public function indexBackUp()
    {
        $m_RemoveModel = new ParityProductModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $sqlShopGoodsCount = "SELECT count(*) AS count  FROM  ims_ewei_shop_goods   WHERE    goods_code !=''  AND skuid =''  ";
        $shopGoodsCodeCount = $m_ShopGoodsModel->querySql($sqlShopGoodsCount);
        $countAll = $shopGoodsCodeCount[0]['count'];

        $amountEnd = 10;
        $amountForMax = ceil($countAll / $amountEnd); //取整数
        for ($i = 0; $i < $amountForMax; $i++) {
            $amountStart = $i * $amountEnd + 1;

            // 查询goods_code 不为空 并且 skuid 不为空
            $sqlShopGoods = "SELECT id , goods_code  FROM  ims_ewei_shop_goods   WHERE    goods_code !=''   AND  skuid =''   limit $amountStart ,$amountEnd ";
            $shopGoodsCodeList = $m_ShopGoodsModel->querySql($sqlShopGoods);
            $arrSkuidList = [];
            foreach ($shopGoodsCodeList AS $k => $v) {
                $sqlProductOne = "SELECT  DISTINCT id AS skuid  FROM  bsj_parity_product  WHERE   barcode =" . $v['goods_code'] . ' limit 1 ';
                $parityProductOne = $m_RemoveModel->querySql($sqlProductOne);
                if (!empty($parityProductOne)) {
                    foreach ($parityProductOne AS $parityProductK => $parityProductV) {
                        $upOne = array(
                            'skuid' => $parityProductV['skuid']
                        );
                        $m_ShopGoodsModel->updateInfo($v['id'], $upOne); //更新 shop_goods 表
                    }
                }
            }

        }
        return "up & ok !";
    }


    /**
     * 匹配bsdj的sku匹配
     */
    public function index_zt31()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";
        $count_all = 534;
        $page = $this->request->param("page", 1);
        $page_size = 10;
        if ($page < 1) {
            $page = 1;
        }
        if ($page > ceil($count_all / $page_size)) {
            $page = ceil($count_all / $page_size);
        }
        $page_pos = ($page - 1) * $page_size;
        $page1 = $page - 1;
        $page2 = $page + 1;

        echo " 当前{$page}页 ";
        echo " <a href='./index?act=index_zt31&page={$page1}'>上一页</a>";
        echo " <a href='./index?act=index_zt31&page={$page2}'>下一页</a>";
        echo " <br>";

        $sql_product = "SELECT a.id,a.skuName,a.base_word,a.kou_wei,a.brand_name,a.spec_value,b.id as brand_id,a.sku_id,a.salesPrice,a.sku_id,a.specifications,a.up_goods_item,a.no_goods_time from ims_bsdj_product a left join ims_bb_brand b on a.brand_name=b.b_name where a.brand_name <> '' and a.no_goods_item = 0 and sku_id = 0 order by a.id desc limit {$page_pos},10";
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            $product_name = $one['skuName'];
            echo $one['id'] . ":" . $product_name . '  ' . "[" . $one['brand_id'] . "+" . $one['base_word'] . "+" . $one['spec_value'] . "+" . $one['kou_wei'] . "] = {$one['salesPrice']}元";
            if (!empty($one['no_goods_time'])) {
                echo "&nbsp; √√√无item_id";
            } else {
                echo "&nbsp;  <a href='./index?act=index_zt31_no_item&id={$one['id']}'>无item_id</a>";
            }


            $sql_goods_item = "SELECT id,goods_name,content from ims_bb_goods_item where brand_id = {$one['brand_id']} and base_word = '{$one['base_word']}'  and kou_wei = '{$one['kou_wei']}'and content = '{$one['spec_value']}'  order by id asc ";//
            $list_goods_item = $m_bb_brand->querySql($sql_goods_item);
            foreach ($list_goods_item as $two) {
                echo "<br>item_id " . $two['id'] . ':' . $two['goods_name'] . '+' . $two['content'];
                if ($one['up_goods_item'] == $two['id']) {
                    echo "&nbsp; √√√关联item => ";
                } else {
                    echo " <a target='_blank' href='./index?act=index_zt31_up_item&id={$one['id']}&item_id={$two['id']}'>关联item</a> => ";
                }

                $sql_sku = "SELECT id,unit_count,unit_name from ims_bb_sku where goods_id = '{$two['id']}' and is_used = 1 order by id asc ";
                $list_sku = $m_bb_brand->querySql($sql_sku);
                foreach ($list_sku as $three) {
                    $sql_sku_price = "SELECT id,min_price,max_price from ims_bb_city_sku where sku_id = '{$three['id']}' ";
                    $list_sku_price = $m_bb_brand->querySql($sql_sku_price);
                    $sku_price = '';
                    if (!empty($list_sku_price)) {
                        $sku_price = $list_sku_price[0]['min_price'] . '-' . $list_sku_price[0]['max_price'] . "元";
                    }
                    if ($one['sku_id'] == $three['id']) {
                        echo "&nbsp; √√√" . $three['id'] . "(" . $three['unit_count'] . $three['unit_name'] . $sku_price . ")";
                    } else {
                        echo "&nbsp; &nbsp; <a target='_blank' href='./index?act=index_zt31_up_sku&id={$one['id']}&sku_id={$three['id']}'>" . $three['id'] . "(" . $three['unit_count'] . $three['unit_name'] . $sku_price . ")" . "<a>";
                    }

                }
                //echo "<br>";
            }
            //if(empty($list_goods_item))

            echo "<hr>";
        }
        echo " 当前{$page}页 ";
        echo " <a href='./index?act=index_zt31&page={$page1}'>上一页</a>";
        echo " <a href='./index?act=index_zt31&page={$page2}'>下一页</a>";
        echo " <br>";
        echo time() . "<br>";
    }

    /**
     * index_zt31_up_sku
     */
    public function index_zt31_up_sku()
    {
        $id = $this->request->param("id");
        $sku_id = $this->request->param("sku_id");

        if (!empty($id) && !empty($sku_id)) {
            $m_bb_brand = new ShopGoodsModel();
            $sql_product0 = "update ims_bsdj_product set sku_id = {$sku_id} where id = {$id} ";
            $r = $m_bb_brand->executeSql($sql_product0);
            echo "update ims_bsdj_product set sku_id = {$sku_id} where id = {$id} ";
            if ($r) {
                echo $id . " ok";
            } else {
                echo $id . " err";
            }

        }
    }

    /**
     * index_zt31_up_item
     */
    public function index_zt31_up_item()
    {
        $id = $this->request->param("id");
        $item_id = $this->request->param("item_id");

        if (!empty($id) && !empty($item_id)) {
            $m_bb_brand = new ShopGoodsModel();
            $sql_product0 = "update ims_bsdj_product set up_goods_item = {$item_id} where id = {$id} ";
            $r = $m_bb_brand->executeSql($sql_product0);
            echo "update ims_bsdj_product set up_goods_item = {$item_id} where id = {$id} ";
            if ($r) {
                echo $id . " ok";
            } else {
                echo $id . " err";
            }

        }
    }

    /**
     * index_zt31_no_item
     */
    public function index_zt31_no_item()
    {
        $id = $this->request->param("id");
        //$item_id = $this->request->param("item_id");

        if (!empty($id)) {
            $time = time();
            $m_bb_brand = new ShopGoodsModel();
            $sql_product0 = "update ims_bsdj_product set no_goods_time={$time} where id = {$id} ";
            $r = $m_bb_brand->executeSql($sql_product0);
            echo "update ims_bsdj_product set no_goods_time={$time} where id = {$id} ";
            if ($r) {
                echo $id . " ok";
            } else {
                echo $id . " err";
            }

        }
    }

    /**
     * 匹配bsdj的sku匹配
     */
    public function index_zt32()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_product = "SELECT a.id,a.sku_name,a.base_word,a.kou_wei,a.brand_name,a.spec_value,b.id as brand_id,a.sku_id,a.salesPrice from ims_bsdj_product a left join ims_bb_brand b on a.brand_name=b.b_name where a.brand_name <> ''  order by a.id asc";
        $list_product = $m_bb_brand->querySql($sql_product);

        foreach ($list_product as $one) {
            $sql_goods_item = "SELECT id,goods_name,content from ims_bb_goods_item where brand_id = {$one['brand_id']} and base_word = '{$one['base_word']}'  and kou_wei = '{$one['kou_wei']}'  order by id asc ";//and content = '{$one['spec_value']}'
            $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

            if (empty($list_goods_item)) {
                $sql_product0 = "update ims_bsdj_product set no_goods_item = 1 where id = {$one['id']} ";
                $res = $m_bb_brand->querySql($sql_product0);
            }
        }
        echo time() . "<br>";
    }


    /**
     * 匹配goods_item的item_name匹配
     */
    public function index_zt33()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT a.id,a.kou_wei,a.content,a.base_word,b.b_name from ims_bb_goods_item a left join ims_bb_brand b on a.brand_id = b.id where a.id > 0  order by a.id asc ";//and content = '{$one['spec_value']}'
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        foreach ($list_goods_item as $one) {
            if (!empty($one)) {
                $sql_product0 = "update ims_bb_goods_item set item_name = '{$one['b_name']}+{$one['kou_wei']}+{$one['base_word']}+{$one['content']}' where id = {$one['id']} ";
                $res = $m_bb_brand->querySql($sql_product0);
            }
        }

        echo time() . "<br>";
    }

    /**
     * @param $split_word
     * @param $goods_name
     * @return array
     */
    function get_explode_array($split_word, $goods_name)
    {
        $arr2 = array();
        $is_null = 0;
        $arr1 = explode($split_word, $goods_name);
        foreach ($arr1 as $k => $v) {
            if (strlen($v) == 0) {
                $is_null = 1;
                $arr2[] = $split_word;
            } else {
                $arr2[] = $v;
            }
        }
        if ($is_null == 0) {
            array_splice($arr2, 1, 0, array($split_word));
        }
        return $arr2;
    }

    /**
     * 按照扣品牌、基础词、口味后再用二记菜单当词典拆分剩余字符规则，匹配goods_item的key_word1匹配
     */
    public function index_zt34()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";


        //货比三价-口味
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word2 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word2[] = $one['key_word'];
        }
        echo "口味：" . count($arr_key_word2) . "<br>";

        //货比三价-品牌
        $sql_bb_brand = "SELECT id,b_name from ims_bb_brand where  status = 1 order by id asc";
        $list_bb_brand = $m_bb_brand->querySql($sql_bb_brand);
        $arr_bb_brand = array();
        foreach ($list_bb_brand as $one) {
            $arr_bb_brand[] = $one['b_name'];
        }
        echo "品牌：" . count($arr_bb_brand) . "<br>";

        //货比三价-基础词
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 4 and status = 1  GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word4 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word4[] = $one['key_word'];
        }
        echo "基础词：" . count($arr_key_word4) . "<br>";

        $sql_cate = "SELECT id from ims_bb_cate_bb where fid = 10";//一级：休闲食品，的二级分类
        $list_cate = $m_bb_brand->querySql($sql_cate);

        echo "分类：" . count($list_cate) . "<br>";
        $class_splitWord = new splitWord();
        foreach ($list_cate as $two) {
            $cate_bb2 = $two['id'];
            $sql_cate_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where cate_bb2 = {$cate_bb2} and status = 1;";
            $list_cate_key_word = $m_bb_brand->querySql($sql_cate_key_word);

            $dict_cate_key_word = null;
            foreach ($list_cate_key_word as $three) {
                $dict_cate_key_word[$cate_bb2][] = $three;
            }

            $sql_goods_item = "SELECT a.id,a.goods_name,b.b_name from ims_bb_goods_item a left join ims_bb_brand b on a.brand_id = b.id where a.cate_bb2 = {$cate_bb2} and a.key_word_str = '' and a.id = 100074029 order by a.id asc ";//and content = '{$one['spec_value']}'
            $list_goods_item = $m_bb_brand->querySql($sql_goods_item);


            $cate_dict = $class_splitWord->get_dict($dict_cate_key_word[$cate_bb2]);
            $cate_dict = $class_splitWord->fix_dict($cate_dict);
            $class_splitWord->set_dict($cate_dict);

            foreach ($list_goods_item as $one) {

                $arr_one_goods_name = null;
                $str_brand = $one['b_name'];
                $str_goods_name0 = $one['goods_name'];//'新版小猪佩奇超级棒棒糖水果味饼干';//;
                //$str_goods_name0 = str_replace(' ', '', $str_goods_name0);
                echo $str_goods_name0 . " ";
                //$str_goods_name1 = $str_goods_name0;
                //echo "brand:" . $str_brand . "<br>";
                //echo "goods_item:" . $str_goods_name0 . "<br>";
                //1.找出品牌
                $arr_goods_name_split = array($str_goods_name0);
                if (stripos($one['goods_name'], $str_brand) !== false) {
                    $arr_goods_name_split = $this->get_explode_array($str_brand, $str_goods_name0);
                }
                echo "pin_pai:";
                print_r($arr_goods_name_split);
                echo "<br>";
                exit;

                //2.找出base_word
                $str_base_word = '';
                foreach ($arr_goods_name_split as $k => $v) {
                    if ($v == $str_brand || empty($v)) {
                        continue;
                    } else {
                        //echo $k . ":" . $v . "<br>";
                        foreach ($arr_key_word4 as $kk => $vv) {
                            if (stripos($v, $vv) !== false) {
                                $str_base_word = $vv;
                                //echo $str_base_word;
                                $one_temp = null;
                                $one_temp = $this->get_explode_array($vv, $v);
                                //print_r($one_temp);
                                //echo "<br>";
                                array_splice($arr_goods_name_split, $k, 1, $one_temp);
                                //print_r($arr_goods_name_split);
                                //echo "<br>";
                                break;
                            }
                        }
                    }
                }
                //echo "base_wrod:";
                //print_r($arr_goods_name_split);
                //echo "<br>";

                $str_kou_wei = '';
                foreach ($arr_goods_name_split as $k1 => $v1) {
                    if ($v1 == $str_brand || empty($v1)) {
                        continue;
                    } elseif (!empty($str_base_word) && $v1 == $str_base_word) {
                        continue;
                    } else {
                        //echo $k1 . ":" . $v1 . "<br>";
                        foreach ($arr_key_word2 as $kk => $vv) {
                            if (stripos($v1, $vv) !== false) {
                                $str_kou_wei = $vv;
                                //echo $str_kou_wei;
                                $one_temp = null;
                                $one_temp = $this->get_explode_array($vv, $v1);
                                //print_r($one_temp);
                                //echo "<br>";
                                array_splice($arr_goods_name_split, $k1, 1, $one_temp);
                                //print_r($arr_goods_name_split);
                                //echo "<br>";
                                break;
                            }
                        }
                    }
                }

                //3.剩余的几个字符串，再用2级的词典，split
                foreach ($arr_goods_name_split as $k2 => $v2) {
                    if ($v2 == $str_brand) {
                        continue;
                    } elseif (!empty($str_kou_wei) && $v2 == $str_kou_wei) {
                        continue;
                    } elseif (!empty($str_base_word) && $v2 == $str_base_word) {
                        continue;
                    } else {
                        $one_temp = null;
                        $one_temp = $class_splitWord->split_word($v2);
                        //echo "split_wrod...";
                        //print_r($one_temp);
                        //echo "<br>";
                        array_splice($arr_goods_name_split, $k2, 1, $one_temp);
                    }
                }
                //echo "split_wrod:";
                //print_r($arr_goods_name_split);
                //echo "<br>";

                //4.组合所有词根为一个数组
                //$arr_one_goods_name = $arr_goods_name_split;

                //5.组合为字符串并保存入库
                $key_word_str = implode(',', $arr_goods_name_split);
                $sql_up_goods_item = "update ims_bb_goods_item set key_word_str = '{$key_word_str}',base_word_str = '{$str_base_word}',kou_wei_str='{$str_kou_wei}' where id = {$one['id']} ";
                echo $sql_up_goods_item . "<br>";
                $res = $m_bb_brand->querySql($sql_up_goods_item);

                exit;
            }
        }

        echo time() . "<br>";
    }

    /**
     * index_zt34_back
     */
    public function index_zt34_back()
    {
        /*
        //set_time_limit(0);
        ini_set('max_execution_time',0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";


        //货比三价-口味
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 2 and status = 1 and key_word <> '口味' GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word2 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word2[] = $one['key_word'];
        }
        echo count($arr_key_word2) . "<br>";

        //货比三价-品牌
        $sql_bb_brand = "SELECT id,b_name from ims_bb_brand where  status = 1 order by id asc";
        $list_bb_brand = $m_bb_brand->querySql($sql_bb_brand);
        $arr_bb_brand = array();
        foreach ($list_bb_brand as $one) {
            $arr_bb_brand[] = $one['b_name'];
        }
        echo count($arr_bb_brand) . "<br>";

        //货比三价-基础词
        $sql_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where key_type = 4 and status = 1  GROUP BY key_word order by key_weight desc";
        $list_key_word = $m_bb_brand->querySql($sql_key_word);
        $arr_key_word4 = array();
        foreach ($list_key_word as $one) {
            $arr_key_word4[] = $one['key_word'];
        }
        echo count($arr_key_word4) . "<br>";

        $sql_cate = "SELECT id from ims_bb_cate_bb where fid = 10 limit 1";//一级：休闲食品，的二级分类
        $list_cate = $m_bb_brand->querySql($sql_cate);

        echo count($list_cate) . "<br>";
        $class_splitWord = new splitWord();
        foreach ($list_cate as $two) {
            $cate_bb2 = $two['id'];
            $sql_cate_key_word = "SELECT key_word,sum(weight) as key_weight from ims_bb_brand_keyword where cate_bb2 = {$cate_bb2} and status = 1;";
            $list_cate_key_word = $m_bb_brand->querySql($sql_cate_key_word);

            $dict_cate_key_word = null;
            foreach ($list_cate_key_word as $three) {
                $dict_cate_key_word[$cate_bb2][] = $three;
            }

            $sql_goods_item = "SELECT a.id,a.goods_name,b.b_name from ims_bb_goods_item a left join ims_bb_brand b on a.brand_id = b.id where a.cate_bb2 = {$cate_bb2} order by a.id asc ";//and content = '{$one['spec_value']}'
            $list_goods_item = $m_bb_brand->querySql($sql_goods_item);


            $cate_dict = $class_splitWord->get_dict($dict_cate_key_word[$cate_bb2]);
            $cate_dict = $class_splitWord->fix_dict($cate_dict);
            $class_splitWord->set_dict($cate_dict);

            foreach ($list_goods_item as $one) {

                $arr_one_goods_name = null;
                $str_brand = $one['b_name'];
                $str_goods_name0 = '新版小猪佩奇超级棒棒糖水果味';//$one['goods_name'];
                //$str_goods_name1 = $str_goods_name0;
                echo "brand:" . $str_brand . "<br>";
                echo "goods_item:" . $str_goods_name0 . "<br>";
                //1.找出品牌
                $arr_goods_name_split = array($str_goods_name0);
                if (stripos($one['goods_name'], $str_brand) !== false) {
                    //$arr_name1 = explode("'".$str_brand."'", $str_goods_name0);
                    $arr_name1 = explode($str_brand, $str_goods_name0);
                    array_unshift($arr_name1, $str_brand);
                    $arr_goods_name_split = $arr_name1;
                    //$arr_one_goods_name[] = $str_brand;
                }
                echo "pin_pai:";
                print_r($arr_goods_name_split);
                echo "<br>";
                //$str_goods_name1 = str_replace($str_brand, '', $str_goods_name1);

                //2.找出base_word
                $str_base_word = '';
                foreach ($arr_goods_name_split as $k => $v) {
                    echo $k . ":" . $v . "<br>";
                    if ($v == $str_brand || empty($v)) {
                        continue;
                    } else {
                        foreach ($arr_key_word4 as $kk => $vv) {
                            if (stripos($v, $vv) !== false) {
                                $str_base_word = $vv;
                                $one_temp = null;
                                //$one_temp = explode("'".$vv."'", $v);
                                $one_temp = explode($vv, $v);
                                print_r($one_temp);
                                echo "<br>";
                                array_push($arr_goods_name_split, $vv);
                                unset($arr_goods_name_split[$k]);
                                print_r($arr_goods_name_split);
                                echo "<br>";
                                //$arr_goods_name_split = $arr_goods_name_split + $one_temp;
                                $arr_goods_name_split = array_merge($arr_goods_name_split, $one_temp);
                                print_r($arr_goods_name_split);
                                //array_splice();
                                echo "<br>";
                                break;
                            }
                        }
                    }
                }
                echo "base_wrod:";
                print_r($arr_goods_name_split);
                echo "<br>";

                //3.剩余的几个字符串，再用2级的词典，split
                foreach ($arr_goods_name_split as $k2 => $v2) {
                    if ($v2 == $str_brand) {
                        continue;
                    } elseif (!empty($str_base_word) && $v2 == $str_base_word) {
                        continue;
                    } else {
                        $one_temp = null;
                        $one_temp = $class_splitWord->split_word($v2);
                        unset($arr_goods_name_split[$k2]);
                        //$arr_goods_name_split = $arr_goods_name_split + $one_temp;
                        $arr_goods_name_split = array_merge($arr_goods_name_split, $one_temp);
                    }
                }
                echo "split_wrod:";
                print_r($arr_goods_name_split);
                echo "<br>";

                //4.组合所有词根为一个数组
                $arr_one_goods_name = $arr_goods_name_split;

                //5.组合为字符串并保存入库
                $key_word_str = implode(',', $arr_one_goods_name);
                $sql_up_goods_item = "update ims_bb_goods_item set key_word_str = '{$key_word_str}' where id = {$one['id']} ";
                echo $sql_up_goods_item . "<br>";
                //$res = $m_bb_brand->querySql($sql_up_goods_item);

                exit;
            }
        }

        echo time() . "<br>";
        */
    }

    /**
     * @param $goods_unit
     * @return string
     */
    function change_unit($goods_unit)
    {
        $new_unit = '';
        if (empty($goods_unit)) {
            $unit_old = array('毫克', '千克', '克', '毫升', '升');
            $unit_new = array('MG', 'KG', 'G', 'ML', 'L');
            foreach ($unit_old as $k => $v) {
                if ($goods_unit == $v) {
                    $new_unit = $unit_new[$k];
                    break;
                }
            }
        }
        return strtoupper($new_unit);
    }

    /**
     * 处理goods_item表的规格单位（克->G等等）
     */
    public function index_zt35()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT id,content from ims_bb_goods_item  where a.id > 0 order by id asc ";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        foreach ($list_goods_item as $one) {
            $spec_value_str = $this->change_unit($one['content']);
            $sql_product0 = "update ims_bb_goods_item set spec_value_str = '{$spec_value_str}' where id = {$one['id']} ";
            $res = $m_bb_brand->querySql($sql_product0);

        }

        echo time() . "<br>";
    }

    /**
     * 处理ims_bsdj_product表的规格单位（克->G等等）
     */
    public function index_zt36()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT id,spec_value from ims_bsdj_product where a.id > 0 order by id asc ";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        foreach ($list_goods_item as $one) {
            $spec_value_str = $this->change_unit($one['spec_value']);
            $sql_product0 = "update ims_bsdj_product set spec_value_str = '{$spec_value_str}' where id = {$one['id']} ";
            $res = $m_bb_brand->querySql($sql_product0);
        }

        echo time() . "<br>";
    }

    /**
     * 整理goods_item的code到sku_bar_code
     */
    public function index_zt37()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT id,code_list_str from ims_bb_goods_item where id > 0 order by id asc ";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        $arr_sql = array();
        foreach ($list_goods_item as $one) {
            $arr_code_list = explode(',', $one['code_list_str']);
            foreach ($arr_code_list as $two) {
                if (!empty($two)) {
                    $arr_one_temp = null;
                    $arr_one_temp['bar_code'] = $two;
                    $arr_one_temp['come_from'] = 1;
                    $arr_one_temp['come_id'] = $one['id'];
                    $arr_one_temp['status'] = 1;
                    $arr_one_temp['create_time'] = $time;

                    $arr_one_brand4[] = $arr_one_temp;
                    $arr_sql[] = $arr_one_temp;
                    if (count($arr_sql) >= 200) {
                        $one_sql = $this->insertIntoSql('ims_bb_bar_code', $arr_sql);
                        $m_bb_brand->executeSql($one_sql);
                        echo $one_sql . "<br>";
                        $arr_sql = null;
                    }
                }
            }
        }

        if (count($arr_sql) >= 1) {
            $one_sql = $this->insertIntoSql('ims_bb_bar_code', $arr_sql);
            $m_bb_brand->executeSql($one_sql);
            //echo $one_sql."<br>";
            $arr_sql = null;
        }

        echo time() . "<br>";
    }

    /**
     * 整理ims_yd_base_goods的code到sku_bar_code
     */
    public function index_zt38()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT id,code from ims_yd_base_goods where a.id > 0 order by id asc ";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        $arr_sql = array();
        foreach ($list_goods_item as $one) {
            if (!empty($one)) {
                $arr_one_temp = null;
                $arr_one_temp['bar_code'] = $one;
                $arr_one_temp['come_from'] = 2;
                $arr_one_temp['come_id'] = $one['id'];
                $arr_one_temp['status'] = 1;
                $arr_one_temp['create_time'] = $time;

                $arr_one_brand4[] = $arr_one_temp;
                $arr_sql[] = $arr_one_temp;
                if (count($arr_sql) >= 200) {
                    $one_sql = $this->insertIntoSql('ims_bb_bar_code', $arr_sql);
                    $m_bb_brand->executeSql($one_sql);
                    echo $one_sql . "<br>";
                    $arr_sql = null;
                }
            }
        }

        if (count($arr_sql) >= 1) {
            $one_sql = $this->insertIntoSql('ims_bb_bar_code', $arr_sql);
            $m_bb_brand->executeSql($one_sql);
            //echo $one_sql."<br>";
            $arr_sql = null;
        }

        echo time() . "<br>";
    }

    /**
     * 处理ims_bb_goods_item表的barcode,从sku表聚合去重code_list_str
     * 修改：除了所有sku再加上自己item本身code聚合
     */
    public function index_zt39()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT id,code_list from ims_bb_goods_item where id > 0 order by id asc ";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);
        $arr_code_list1 = array();
        foreach ($list_goods_item as $one) {
            $arr_code_list1[$one['id']] = trim($one['code_list']);
        }

        $sql_sku = "SELECT id,goods_id,code_list_pro from ims_bb_sku where id > 0 order by id asc ";
        $list_sku = $m_bb_brand->querySql($sql_sku);
        echo "list_sku " . time() . " " . count($list_sku) . "<br>";

        $arr_code_list = array();
        foreach ($list_sku as $one) {
            $arr_code_list[$one['goods_id']][] = trim($one['code_list_pro']);
        }
        echo "arr_code_list " . time() . " " . count($arr_code_list) . "<br>";

        $arr_code_list_str = array();
        foreach ($arr_code_list as $k => $v) {
            $arr2 = null;
            foreach ($v as $one2) {
                if (!empty($one2)) {
                    $arr1 = explode(',', $one2);
                    if (empty($arr2)) {
                        $arr2 = $arr1;
                    } else {
                        $arr2 = array_merge($arr1, $arr2);
                    }
                }
            }
            if (isset($arr_code_list1[$k])) {
                $str_item_self = $arr_code_list1[$k];

                $arr1 = explode(',', $str_item_self);
                if (empty($arr2)) {
                    $arr2 = $arr1;
                } else {
                    $arr2 = array_merge($arr1, $arr2);
                }
            }
            $str_list = '';
            if (!empty($arr2)) {
                $arr2 = array_unique($arr2);
                $arr3 = null;
                foreach ($arr2 as $kkk => $vvv) {
                    $one_v = trim($vvv);
                    if (is_numeric($one_v) && strlen($one_v) > 10) {
                        $arr3[] = $one_v;
                    } elseif (strlen($one_v) == 14 && substr($one_v, 1, 2) == '69') {
                        $arr3[] = substr($one_v, 1);
                    }
                }
                if (!empty($arr3)) {
                    sort($arr3);
                    $str_list = implode(',', $arr3);
                }
            }

            $arr_code_list_str[$k] = $str_list;
        }
        echo "arr_code_list_str " . time() . " " . count($arr_code_list_str) . "<br>";

        $sql_goods_item = "SELECT id from ims_bb_goods_item where id > 0 order by id asc ";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);

        foreach ($list_goods_item as $one) {
            if (isset($arr_code_list_str[$one['id']])) {
                $code_list_str = $arr_code_list_str[$one['id']];
                $sql_product0 = "update ims_bb_goods_item set code_list_str = '{$code_list_str}' where id = {$one['id']} ";
                $res = $m_bb_brand->querySql($sql_product0);
                //echo $sql_product0 . "<br>";
            }

        }

        echo time() . " " . count($list_goods_item) . "<br>";
    }

    /**
     * 已知201943返回周1-周日
     */
    public function index_zt40()
    {
        $arr1 = array(0, 0);
        $week_of_year = 201943;
        $s1 = substr($week_of_year, 0, 4);
        $s2 = substr($week_of_year, 4, 2);
        $s3 = ($s2 - 2) * 60 * 60 * 24 * 7;
        //strtotime($s1)
        $s4 = strtotime($s1 . '-01-01') + $s3;
        //cho $s4;
        $i_start = strtotime(date('Y-m-d', $s4));
        $arr0 = array();
        for ($i = 0; $i < 30; $i++) {
            $i_start = $i_start + 60 * 60 * 24;

            if (date('W', $i_start) == $s2) {
                echo date('Y-m-d', $i_start) . "<br>";
                $arr0[] = strtotime(date('Y-m-d', $i_start));
            }

        }
        $arr1 = array($arr0[0], $arr0[6]);
    }

    /**
     * index_zt41
     */
    public function index_zt41()
    {
        $arr1 = array(0, 0);
        $week_of_year = 201901;
        $s1 = substr($week_of_year, 0, 4);
        $s2 = substr($week_of_year, 4, 2);
        $s3 = ($s2 - 1) * 60 * 60 * 24 * 7;
        $s4 = strtotime($s1 . '-01-01') + $s3;

        $i_day = strtotime(date('Y-m-d', strtotime('-2 monday', $s4)));

        $i_week = 60 * 60 * 24 * 7;
        for ($i = 0; $i <= 3; $i++) {
            if (date('W', $i_day + $i_week * $i) == $s2) {
                echo date('Y-m-d', $i_day + $i_week * $i);
                break;
            }
        }

        //$arr1 = array($arr0[0], $arr0[6]);
    }


    /**
     * 整理ims_bb_goods_item_ext的bar_code，修复或者去掉各种特殊bar_code
     */
    public function index_zt42()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT id,bar_code1 from ims_bb_goods_item_ext where update_time = 0 order by id asc ";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);
        echo count($list_goods_item);
        foreach ($list_goods_item as $one) {
            if (!empty($one['bar_code1'])) {
                $bar_code0 = $one['bar_code1'];
                $bar_code = 0;
                if (is_numeric($bar_code0) && strlen($bar_code0) > 10 && intval($bar_code0) > 4000000000000) {
                    $bar_code = $bar_code0;
                } elseif (!is_numeric(substr($bar_code0, 0, 1)) && strlen($bar_code0) == 14 && is_numeric(substr($bar_code0, 1))) {
                    $bar_code = substr($bar_code0, 1);
                }
                if (!empty($bar_code)) {
                    $one_sql = "update ims_bb_goods_item_ext set bar_code = {$bar_code},update_time = {$time} where id = {$one['id']}";
                    $m_bb_brand->executeSql($one_sql);
                } else {
                    $one_sql = "update ims_bb_goods_item_ext set status = 0,update_time = {$time} where id = {$one['id']}";
                    $m_bb_brand->executeSql($one_sql);
                }
            }
        }

        echo time() . "<br>";
    }


    /**
     * 整理ims_bb_goods_item_ext的goods_name长度weight
     */
    public function index_zt43()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT id,goods_name from ims_bb_goods_item_ext where update_time >= 0 order by goods_name asc limit 10000";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);
        echo count($list_goods_item) . "<br>";
        foreach ($list_goods_item as $one) {
            if (!empty($one['goods_name'])) {
                $goods_name = trim($one['goods_name']);
//                $goods_name = trim($goods_name,'#');
//                $goods_name = trim($goods_name,'*');
//                $goods_name = trim($goods_name,'.');
//                $goods_name = trim($goods_name,'+');
//                $goods_name = trim($goods_name,'-');
//                $goods_name = trim($goods_name,'+-*/');
//                $goods_name = str_replace('（','(',$goods_name);
//                $goods_name = str_replace('）',')',$goods_name);
                $goods_name = str_replace('(买一送一)', '', $goods_name);
                $weight = strlen($goods_name);
                $one_sql = "update ims_bb_goods_item_ext set goods_name = '{$goods_name}',weight={$weight},update_time = {$time} where id = {$one['id']}";
                $m_bb_brand->executeSql($one_sql);

            }
        }

        echo time() . "<br>";
    }

    /**
     * index_zt44_update
     */
    public function index_zt44_update()
    {
        $p_type = $this->request->param("p_type");
        $p_id = $this->request->param("p_id");
        $goods_id = $this->request->param("goods_id");

        if (!empty($p_type) && !empty($p_id)) {
            $time = time();
            $m_GoodsModel = new ShopGoodsModel();

            $sql_product0 = "select id from ims_bb_goods_id_data where goods_id = {$goods_id} and status = 1";
            $list_goods_id = $m_GoodsModel->querySql($sql_product0);

            $sql_product0 = '';
            if (!empty($list_goods_id)) {
                $str_sql = '';
                if ($p_type == 1) {
                    $str_sql = " hbsj_id = '{$p_id}', ";
                } elseif ($p_type == 2) {
                    $str_sql = " jpw_id = '{$p_id}', ";
                } elseif ($p_type == 3) {
                    $str_sql = " hmw_id = '{$p_id}', ";
                } elseif ($p_type == 4) {
                    $str_sql = " zgb_id = '{$p_id}', ";
                } elseif ($p_type == 5) {
                    $str_sql = " bsdj_id = '{$p_id}', ";
                }
                if (!empty($str_sql)) {
                    $sql_product0 = "update ims_bb_goods_id_data set {$str_sql} update_time = {$time} where id = {$list_goods_id[0]['id']} ";
                }

            } else {
                $sql_shop_goods = "SELECT a.id,a.skuid,b.hbsj_sku_id from ims_ewei_shop_goods a left join ims_bb_sku b on a.skuid=b.id where a.id = {$goods_id} ";
                $list_shop_goods = $m_GoodsModel->querySql($sql_shop_goods);
                $hbsj_id = 0;
                if (count($list_shop_goods) > 0) $hbsj_id = $list_shop_goods[0]['hbsj_sku_id'];

                if ($p_type == 1) {
                    $sql_product0 = "insert into ims_bb_goods_id_data (goods_id,`status`,hbsj_id,create_time) values ($goods_id,1,'{$p_id}',{$time}) ";
                } elseif ($p_type == 2) {
                    $sql_product0 = "insert into ims_bb_goods_id_data (goods_id,hbsj_id,`status`,jpw_id,create_time) values ($goods_id,'{$hbsj_id}',1,'{$p_id}',{$time}) ";
                } elseif ($p_type == 3) {
                    $sql_product0 = "insert into ims_bb_goods_id_data (goods_id,hbsj_id,`status`,hmw_id,create_time) values ($goods_id,'{$hbsj_id}',1,'{$p_id}',{$time}) ";
                } elseif ($p_type == 4) {
                    $sql_product0 = "insert into ims_bb_goods_id_data (goods_id,hbsj_id,`status`,zgb_id,create_time) values ($goods_id,'{$hbsj_id}',1,'{$p_id}',{$time}) ";
                } elseif ($p_type == 5) {
                    $sql_product0 = "insert into ims_bb_goods_id_data (goods_id,hbsj_id,`status`,bsdj_id,create_time) values ($goods_id,'{$hbsj_id}',1,'{$p_id}',{$time}) ";
                } else {
                    //$sql_product0 = "insert into ims_bb_goods_id_data (goods_id,hbsj_id,`status`) values ($goods_id,1) ";
                }
            }
            $r = 0;
            if (!empty($sql_product0)) {
                $r = $m_GoodsModel->executeSql($sql_product0);
            }

            echo $sql_product0 . "<br>";
            if ($r) {
                echo $goods_id . " " . $r . " ok";
            } else {
                echo $goods_id . " " . $r . " err";
            }

        }
    }

    /**
     * index_zt44_update2
     */
    public function index_zt44_update2()
    {
        $p_type = $this->request->param("p_type");
        $p_id = $this->request->param("p_id");
        $goods_id = $this->request->param("goods_id");

        if (!empty($p_type) && !empty($p_id)) {
            $time = time();
            $m_GoodsModel = new ShopGoodsModel();

            $sql_product0 = "select id from ims_bb_goods_id_data where goods_id = {$goods_id} and status = 1";
            $list_goods_id = $m_GoodsModel->querySql($sql_product0);

            $sql_product0 = '';
            if (!empty($list_goods_id) && !empty($p_type)) {
                $str_sql = " platform{$p_type}_id = '{$p_id}', ";
                $sql_product0 = "update ims_bb_goods_id_data set {$str_sql} update_time = {$time} where id = {$list_goods_id[0]['id']} ";

            } else {
                $sql_shop_goods = "SELECT a.id,a.skuid,b.hbsj_sku_id from ims_ewei_shop_goods a left join ims_bb_sku b on a.skuid=b.id where a.id = {$goods_id} ";
                $list_shop_goods = $m_GoodsModel->querySql($sql_shop_goods);
                $hbsj_id = 0;
                if (count($list_shop_goods) > 0) $hbsj_id = $list_shop_goods[0]['hbsj_sku_id'];

                $sql_product0 = "insert into ims_bb_goods_id_data (goods_id,hbsj_id,`status`,platform{$p_type}_id,create_time) values ($goods_id,'{$hbsj_id}',1,'{$p_id}',{$time}) ";

            }
            $r = 0;
            if (!empty($sql_product0)) {
                $r = $m_GoodsModel->executeSql($sql_product0);
            }

            echo $sql_product0 . "<br>";
            if ($r) {
                echo $goods_id . " " . $r . " ok";
            } else {
                echo $goods_id . " " . $r . " err";
            }

        }
    }

    /**
     * @param $str_txt
     * @param $arr_word
     * @return mixed
     */
    function fix_html_str($str_txt, $arr_word)
    {
        $html_str = $str_txt;
        foreach ($arr_word as $one) {
            if (!empty($one)) {
                $html_str = str_replace($one, "<b style='color: red'>" . $one . '</b>', $html_str);
            }
        }
        return $html_str;
    }

    /**
     * shop_goods的匹配(1货比三价，2京批网，3惠民网，4掌柜宝，5百世店家，6...)平台的id，方便后续匹价格
     */
    public function index_zt44()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_GoodsModel = new ShopGoodsModel();
        //echo time() . "<br>";
        $all_count = $this->request->param("all_count", 0);
        if (empty($all_count)) {
            $sql0 = "SELECT count(id) as cc from ims_ewei_shop_goods where sup_id = 461 and skuid > 1 and status = 1 and total > 0 and deleted = 0";
            $list_shop_goods0 = $m_GoodsModel->querySql($sql0);
            $all_count = $list_shop_goods0[0]['cc'];
        }

        $page = $this->request->param("page", 1);
        $page_size = 3;
        if ($page < 1) {
            $page = 1;
        }
        $page_max = ceil($all_count / $page_size);
        if ($page > $page_max) {
            $page = $page_max;
        }
        $page_pos = ($page - 1) * $page_size;
        $page1 = $page - 1;
        $page2 = $page + 1;
        $url = "./index?act=index_zt44&all_count={$all_count}";
        $url2 = "./index?act=index_zt44_update";
        $url3 = "./index?act=index_zt44_update2";

        echo " 共{$all_count}条， 共{$page_max}页， 当前{$page}页， ";
        echo " <a href='{$url}&page={$page1}'>上一页</a>";
        echo "&nbsp;  <a href='{$url}&page={$page2}'>下一页</a>";
        echo "<hr>";
        //echo " <br>";

        $p_type = $this->request->param("p_type", 0);
        $g_id = $this->request->param("goods_id", 0);
        $s_type = $this->request->param("s_type", 3);

        $sql_shop_goods = "SELECT a.id,a.`title`,a.skuid,a.sale_pirce,c.base_word_str,c.kou_wei_str,c.content,d.b_name from ims_ewei_shop_goods a LEFT JOIN ims_bb_sku b ON a.skuid = b.id LEFT JOIN ims_bb_goods_item c ON b.goods_id = c.id left join ims_bb_brand d on d.id=c.brand_id where a.sup_id = 461 and a.skuid > 1 and a.`status` = 1 and a.total > 0 and a.deleted = 0  and is_activity = 0 order by a.id desc limit {$page_pos},{$page_size}";
        $list_shop_goods = $m_GoodsModel->querySql($sql_shop_goods);

        foreach ($list_shop_goods as $one) {
            $goods_name = $one['title'];
            $spec_value_str = $one['content'];
            $goods_id = $one['id'];
            $brand_name_str = $one['b_name'];
            $kou_wei_str = $one['kou_wei_str'];
            $base_word_str = $one['base_word_str'];
            echo $goods_id . '.' . $goods_name . "+<b style='color: red'>" . $spec_value_str . '</b>(孙宇报价：¥' . $one['sale_pirce'] . ")&nbsp; [匹配条件：<b style='color: red'>" . $one['b_name'] . '+' . $one['kou_wei_str'] . '+' . $one['base_word_str'] . "</b>]" . " == ";
            $arr_one_word = [$one['b_name'], $one['kou_wei_str'], $one['base_word_str'], $spec_value_str];
            //ims_bb_goods_id_data//ims_shop_goods_platform_price
            $sql_goods_id = "SELECT * from ims_bb_goods_id_data where goods_id = {$goods_id} ";
            $list_goods_id = $m_GoodsModel->querySql($sql_goods_id);

            $sql_price_list = "SELECT a.*,b.c_name from ims_bb_price_list a left join ims_bb_channel b on a.channel_id = b.id where sku_id = {$one['skuid']} ";
            $list_price_list = $m_GoodsModel->querySql($sql_price_list);
            echo "[平台报价：";
            foreach ($list_price_list as $three) {
                echo $three['c_name'] . ':¥' . $three['price'] . " ";
            }
            echo "] ==> [结果：";

            if (count($list_goods_id) > 0) {
                $one_goods_id = $list_goods_id[0];
                //print_r($one_goods_id);
                if (!empty($one_goods_id['hbsj_id'])) {
                    if ($one_goods_id['hbsj_id'] == 1) {
                        echo "1货比三价";
                    } else {
                        echo "√√√1货比三价";
                    }
                }
                if (!empty($one_goods_id['jpw_id'])) {
                    if ($one_goods_id['jpw_id'] == 1) {
                        echo "&nbsp; √2京批网";
                    } else {
                        echo "&nbsp; √√√2京批网";
                    }
                }
                if (!empty($one_goods_id['hmw_id'])) {
                    if ($one_goods_id['hmw_id'] == 1) {
                        echo "&nbsp; √3惠民网";
                    } else {
                        echo "&nbsp; √√√3惠民网";
                    }
                }
                if (!empty($one_goods_id['zgb_id'])) {
                    if ($one_goods_id['zgb_id'] == 1) {
                        echo "&nbsp; √4掌柜宝";
                    } else {
                        echo "&nbsp; √√√4掌柜宝";
                    }
                }
                if (!empty($one_goods_id['bsdj_id'])) {
                    if ($one_goods_id['bsdj_id'] == 1) {
                        echo "&nbsp; √5百世店家";
                    } else {
                        echo "&nbsp; √√√5百世店家";
                    }
                }
            } else {
                echo "";//"1货比三价，2京批网，3惠民网，4掌柜宝";
            }
            echo "]<br>";
            echo "1.货比三价:<br>";
            //echo "<a href='{$url}&page={$page}&goods_id={$goods_id}&p_type=1'>1货比三价</a>:<br>";
//            if ($g_id == $goods_id && $p_type == 1) {
//                $sql_product = "SELECT id,hbsj_sku_id,sku_name from ims_bb_sku where id >= 1 limit 10";
//                $list_product = $m_GoodsModel->querySql($sql_product);
//                foreach ($list_product as $two) {
//                    echo "&nbsp; &nbsp; &nbsp; &nbsp; <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type=1&p_id={$two['hbsj_sku_id']}'>" . $two['sku_name'] . "</a>" . "<br>";
//                }
//            }
            $s_str1 = '';
            $s_str2 = '';
            $s_str3 = '';
            echo "<a href='{$url}&page={$page}&goods_id={$goods_id}&p_type=2'>2.京批网</a>:";
            if ($g_id == $goods_id && $p_type == 2) {

                $sql_str = '';
                if ($s_type == 2) {
                    $s_str2 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and product_name like '%{$brand_name_str}%'";
                    if (!empty($kou_wei_str)) $sql_str = $sql_str . " and product_name like '%{$kou_wei_str}%'";
                } elseif ($s_type == 3) {
                    $s_str3 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and product_name like '%{$brand_name_str}%'";
                    if (!empty($kou_wei_str)) $sql_str = $sql_str . " and product_name like '%{$kou_wei_str}%'";
                    if (!empty($base_word_str)) $sql_str = $sql_str . " and product_name like '%{$base_word_str}%'";
                } else {
                    $s_str1 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and product_name like '%{$brand_name_str}%'";
                }

                echo "&nbsp; &nbsp; <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type=2&p_id=1'>没有匹配到</a>&nbsp; &nbsp; 筛选>> <a href='{$url}&goods_id={$goods_id}&p_type=2&page={$page}&s_type=1'>{$s_str1}只品牌</a>&nbsp; &nbsp; <a href='{$url}&goods_id={$goods_id}&p_type=2&page={$page}&s_type=2'>{$s_str2}品牌+口味</a>&nbsp; &nbsp; <a href='{$url}&goods_id={$goods_id}&p_type=2&page={$page}&s_type=3'>{$s_str3}品牌+口味+基础</a><br>";

                if (!empty($sql_str)) {
                    $sql_product = "SELECT id,jpw_id,product_name,price_per_unit from ims_jingpiwang_product where  id >= 1 {$sql_str} and jpw_id > 0 limit 50 ";
                    $k = 1;
                    $list_product = $m_GoodsModel->querySql($sql_product);
                    echo "<table border='1' align='center' width='60%'>";
                    foreach ($list_product as $two) {
                        $pp_name = $two['product_name'];
                        $pp_spec = '';
                        $pp_price = $two['price_per_unit'];
                        $pp_id = $two['jpw_id'];
                        //$pp_type = 2;
                        echo "<tr><td> " . $k . '</td><td>' . $this->fix_html_str($pp_name, $arr_one_word) . ' ' . $pp_spec . '</td><td>¥' . $pp_price . "</td><td> <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type={$p_type}&p_id={$pp_id}'>匹配</a></td><td> <a target='_blank' href='{$url3}&goods_id={$goods_id}&p_type={$p_type}&p_id={$pp_id}'>关联</a>" . "</td></tr>";
                        $k++;
                    }
                    echo "</table>";
                }
            } else {
                echo "<br>";
            }

            echo "<a href='{$url}&page={$page}&goods_id={$goods_id}&p_type=3'>3.惠民网</a>:";
            if ($g_id == $goods_id && $p_type == 3) {

                $sql_str = '';
                if ($s_type == 2) {
                    $s_str2 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and `name` like '%{$brand_name_str}%'";
                    if (!empty($kou_wei_str)) $sql_str = $sql_str . " and `name` like '%{$kou_wei_str}%'";
                } elseif ($s_type == 3) {
                    $s_str3 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and `name` like '%{$brand_name_str}%'";
                    if (!empty($kou_wei_str)) $sql_str = $sql_str . " and `name` like '%{$kou_wei_str}%'";
                    if (!empty($base_word_str)) $sql_str = $sql_str . " and `name` like '%{$base_word_str}%'";
                } else {
                    $s_str1 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and `name` like '%{$brand_name_str}%'";
                }

                echo "&nbsp; &nbsp; <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type=3&p_id=1'>没有匹配到</a>&nbsp; &nbsp; 筛选>> <a href='{$url}&goods_id={$goods_id}&p_type=3&page={$page}&s_type=1'>{$s_str1}只品牌</a>&nbsp; &nbsp; <a href='{$url}&goods_id={$goods_id}&p_type=3&page={$page}&s_type=2'>{$s_str2}品牌+口味</a>&nbsp; &nbsp; <a href='{$url}&goods_id={$goods_id}&p_type=3&page={$page}&s_type=3'>{$s_str3}品牌+口味+基础</a><br>";

                if (!empty($sql_str)) {
                    $sql_product = "SELECT id,hmw_id,`name`,curPrice,spec from ims_huiminwang_product where id >= 1 {$sql_str} and hmw_id > 0 limit 50 ";
                    $k = 1;
                    $list_product = $m_GoodsModel->querySql($sql_product);
                    echo "<table border='1' align='center' width='60%'>";
                    foreach ($list_product as $two) {
                        $pp_name = $two['name'];
                        $pp_spec = $two['spec'];
                        $pp_price = $two['curPrice'];
                        $pp_id = $two['hmw_id'];
                        //$pp_type = 3;
                        echo "<tr><td> " . $k . '</td><td>' . $this->fix_html_str($pp_name, $arr_one_word) . ' ' . $pp_spec . '</td><td>¥' . $pp_price . "</td><td> <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type={$p_type}&p_id={$pp_id}'>匹配</a></td><td> <a target='_blank' href='{$url3}&goods_id={$goods_id}&p_type={$p_type}&p_id={$pp_id}'>关联</a>" . "</td></tr>";
                        //echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (" . $k . ')' . $this->fix_html_str($two['name'], $arr_one_word) . ' ' . $two['spec'] . '(¥' . $two['curPrice'] . ") <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type=3&p_id={$two['hmw_id']}'>匹配" . "</a>&nbsp; <a target='_blank' href='{$url3}&goods_id={$goods_id}&p_type=3&p_id={$two['hmw_id']}'>关联</a>" . "<br>";
                        $k++;
                    }
                    echo "</table>";
                }
            } else {
                echo "<br>";
            }

            echo "<a href='{$url}&page={$page}&goods_id={$goods_id}&p_type=4'>4.掌柜宝</a>:";
            if ($g_id == $goods_id && $p_type == 4) {

                $sql_str = '';
                if ($s_type == 2) {
                    $s_str2 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and goods_name like '%{$brand_name_str}%'";
                    if (!empty($kou_wei_str)) $sql_str = $sql_str . " and goods_name like '%{$kou_wei_str}%'";
                } elseif ($s_type == 3) {
                    $s_str3 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and goods_name like '%{$brand_name_str}%'";
                    if (!empty($kou_wei_str)) $sql_str = $sql_str . " and goods_name like '%{$kou_wei_str}%'";
                    if (!empty($base_word_str)) $sql_str = $sql_str . " and goods_name like '%{$base_word_str}%'";
                } else {
                    $s_str1 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and goods_name like '%{$brand_name_str}%'";
                }

                echo "&nbsp; &nbsp; <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type=4&p_id=1'>没有匹配到</a>&nbsp; &nbsp; 筛选>> <a href='{$url}&goods_id={$goods_id}&p_type=4&page={$page}&s_type=1'>{$s_str1}只品牌</a>&nbsp; &nbsp; <a href='{$url}&goods_id={$goods_id}&p_type=4&page={$page}&s_type=2'>{$s_str2}品牌+口味</a>&nbsp; &nbsp; <a href='{$url}&goods_id={$goods_id}&p_type=4&page={$page}&s_type=3'>{$s_str3}品牌+口味+基础</a><br>";

                if (!empty($sql_str)) {
                    $sql_product = "SELECT id,skuId,goods_name,price,packageSize from ims_zhangguibao_product where id >= 1 {$sql_str} and skuId > 0 limit 50 ";
                    $k = 1;
                    $list_product = $m_GoodsModel->querySql($sql_product);
                    echo "<table border='1' align='center' width='60%'>";
                    foreach ($list_product as $two) {
                        //$goods_name4 = $two['goods_name'];
                        $pp_name = $two['goods_name'];
                        $pp_spec = $two['packageSize'];
                        $pp_price = $two['price'];
                        $pp_id = $two['skuId'];
                        //$pp_type = 4;
                        echo "<tr><td> " . $k . '</td><td>' . $this->fix_html_str($pp_name, $arr_one_word) . ' ' . $pp_spec . '</td><td>¥' . $pp_price . "</td><td> <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type={$p_type}&p_id={$pp_id}'>匹配</a></td><td> <a target='_blank' href='{$url3}&goods_id={$goods_id}&p_type={$p_type}&p_id={$pp_id}'>关联</a>" . "</td></tr>";
                        //echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (" . $k . ')' . $this->fix_html_str($goods_name4, $arr_one_word) . ' ' . $two['packageSize'] . '(¥' . $two['price'] . ") <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type=4&p_id={$two['skuId']}'>匹配" . "</a>&nbsp; <a target='_blank' href='{$url3}&goods_id={$goods_id}&p_type=4&p_id={$two['skuId']}'>关联</a>" . "<br>";
                        $k++;
                    }
                    echo "</table>";
                }
            } else {
                echo "<br>";
            }

            echo "<a href='{$url}&page={$page}&goods_id={$goods_id}&p_type=5'>5.百世店家</a>:";
            if ($g_id == $goods_id && $p_type == 5) {

                $sql_str = '';
                if ($s_type == 2) {
                    $s_str2 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and skuName like '%{$brand_name_str}%'";
                    if (!empty($kou_wei_str)) $sql_str = $sql_str . " and skuName like '%{$kou_wei_str}%'";
                } elseif ($s_type == 3) {
                    $s_str3 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and skuName like '%{$brand_name_str}%'";
                    if (!empty($kou_wei_str)) $sql_str = $sql_str . " and skuName like '%{$kou_wei_str}%'";
                    if (!empty($base_word_str)) $sql_str = $sql_str . " and skuName like '%{$base_word_str}%'";
                } else {
                    $s_str1 = '√√√';
                    if (!empty($brand_name_str)) $sql_str = " and skuName like '%{$brand_name_str}%'";
                }

                echo "&nbsp; &nbsp; <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type=5&p_id=1'>没有匹配到</a>&nbsp; &nbsp; 筛选>> <a href='{$url}&goods_id={$goods_id}&p_type=5&page={$page}&s_type=1'>{$s_str1}只品牌</a>&nbsp; &nbsp; <a  href='{$url}&goods_id={$goods_id}&p_type=5&page={$page}&s_type=2'>{$s_str2}品牌+口味</a>&nbsp; &nbsp; <a  href='{$url}&goods_id={$goods_id}&p_type=5&page={$page}&s_type=3'>{$s_str3}品牌+口味+基础</a><br>";

                if (!empty($sql_str)) {
                    $sql_product = "SELECT id,bsdj_id,skuName,salesPrice,specifications from ims_bsdj_product where id >= 1 {$sql_str} and bsdj_id > 0 limit 50 ";
                    $k = 1;
                    $list_product = $m_GoodsModel->querySql($sql_product);
                    echo "<table border='1' align='center' width='60%'>";
                    foreach ($list_product as $two) {
                        $pp_name = $two['skuName'];
                        $pp_spec = $two['specifications'];
                        $pp_price = $two['salesPrice'];
                        $pp_id = $two['bsdj_id'];
                        //$pp_type = 2;
                        echo "<tr><td> " . $k . '</td><td>' . $this->fix_html_str($pp_name, $arr_one_word) . ' ' . $pp_spec . '</td><td>¥' . $pp_price . "</td><td> <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type={$p_type}&p_id={$pp_id}'>匹配</a></td><td> <a target='_blank' href='{$url3}&goods_id={$goods_id}&p_type={$p_type}&p_id={$pp_id}'>关联</a>" . "</td></tr>";
                        //$goods_name5 = $two['skuName'];
                        //echo "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (" . $k . ')' . $this->fix_html_str($goods_name5, $arr_one_word) . ' ' . $two['specifications'] . '(¥' . $two['salesPrice'] . ") <a target='_blank' href='{$url2}&goods_id={$goods_id}&p_type=5&p_id={$two['bsdj_id']}'>匹配" . "</a>&nbsp; <a target='_blank' href='{$url3}&goods_id={$goods_id}&p_type=5&p_id={$two['bsdj_id']}'>关联</a>" . "<br>";
                        $k++;
                    }
                    echo "</table>";
                }
            } else {
                echo "<br>";
            }

            echo "<hr>";
        }
        echo " 共{$all_count}条， 共{$page_max}页， 当前{$page}页，";
        echo " <a href='{$url}&page={$page1}'>上一页</a>";
        echo "&nbsp;  <a href='{$url}&page={$page2}'>下一页</a>";
        echo " <br>";
        //echo time() . "<br>";
    }

    /**
     * index_hk2
     */
    function index_hk2()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        echo time() . "<br>";
        $m_SignModel = new SignModel();
        $m_bb_brand = new ShopGoodsModel();
        $sql = "select id,parent_id,xcx_openid from potential_customer where 1=1 ";
        $arr = $m_SignModel->querySql($sql);

        //$time_start = strtotime('2019-06-01');
        $sql0 = "select id,openid,createtime from ims_ewei_shop_order where supplier_id = 461";
        $list_order = $m_bb_brand->querySql($sql0);
        $arr_order = array();
        foreach ($list_order as $one) {
            if (isset($arr_order[$one['openid']])) {
                if ($arr_order[$one['openid']] < $one['createtime']) {
                    $arr_order[$one['openid']] = $one['createtime'];
                }
            } else {
                $arr_order[$one['openid']] = $one['createtime'];
            }
        }
        echo "arr_order=" . count($arr_order) . " " . time() . "<br>";

        $sql0 = "select id,createtime,openid from ims_member_action_log where sup_id = 461";
        $list_action = $m_bb_brand->querySql($sql0);
        $arr_action = array();
        foreach ($list_action as $one) {
            if (isset($arr_action[$one['openid']])) {
                if ($arr_action[$one['openid']] < $one['createtime']) {
                    $arr_action[$one['openid']] = $one['createtime'];
                }
            } else {
                $arr_action[$one['openid']] = $one['createtime'];
            }
        }
        echo "arr_action=" . count($arr_action) . " " . time() . "<br>";

        $sql0 = "select id,createtime,openid from ims_member_action_log where sup_id = 461 and log_type = 14";
        $list_action14 = $m_bb_brand->querySql($sql0);
        $arr_action14 = array();
        foreach ($list_action14 as $one) {
            if (isset($arr_action14[$one['openid']])) {
                if ($arr_action14[$one['openid']] < $one['createtime']) {
                    $arr_action14[$one['openid']] = $one['createtime'];
                }
            } else {
                $arr_action14[$one['openid']] = $one['createtime'];
            }
        }

        foreach ($arr as $k => $v) {
            if (isset($v['xcx_openid']) && !empty($v['xcx_openid'])) {
                echo $v['id'] . " ";
                $order_time = isset($arr_order[$v['xcx_openid']]) ? $arr_order[$v['xcx_openid']] : 0;
                if ($order_time > 0) {
                    if ($v['parent_id'] == 0) {
                        $sql1 = "update potential_customer SET order_time = {$order_time} where id = {$v['id']} and order_time < {$order_time}";
                    } else {
                        $sql1 = "update potential_customer SET order_time = {$order_time} where id = {$v['parent_id']} and order_time < {$order_time}";
                    }
                    $r1 = $m_SignModel->executeSql($sql1);
                    echo $r1 . " ";
                }

                $browse_time = isset($arr_action[$v['xcx_openid']]) ? $arr_action[$v['xcx_openid']] : 0;
                if ($browse_time > 0) {
                    if ($v['parent_id'] == 0) {
                        $sql2 = "update potential_customer SET browse_time = {$browse_time} where id = {$v['id']} and browse_time < {$browse_time}";
                    } else {
                        $sql2 = "update potential_customer SET browse_time = {$browse_time} where id = {$v['parent_id']} and browse_time < {$browse_time}";
                    }
                    $r2 = $m_SignModel->executeSql($sql2);
                    echo $r2 . " ";
                }

                $cart_time = isset($arr_action14[$v['xcx_openid']]) ? $arr_action14[$v['xcx_openid']] : 0;
                if ($cart_time > 0) {
                    if ($v['parent_id'] == 0) {
                        $sql3 = "update potential_customer SET cart_time = {$cart_time} where id = {$v['id']} and cart_time < {$cart_time}";
                    } else {
                        $sql3 = "update potential_customer SET cart_time = {$cart_time} where id = {$v['parent_id']} and cart_time < {$cart_time}";
                    }
                    $r3 = $m_SignModel->executeSql($sql3);
                    echo $r3 . " ";
                }
                echo "<br>";
            }
        }
        echo time() . "<br>";
    }

    /**
     * 整理ims_zhangguibao_product的name去掉类似【满200减20元】【京东超市】【生产日期2019年07月】等等
     */
    public function index_zt45()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_bb_brand = new ShopGoodsModel();
        echo time() . "<br>";

        $sql_goods_item = "SELECT id,`name` from ims_zhangguibao_product where update_time >= 0 order by goods_name asc ";
        $list_goods_item = $m_bb_brand->querySql($sql_goods_item);
        echo count($list_goods_item) . "<br>";
        foreach ($list_goods_item as $one) {
            if (!empty($one['name'])) {
                $goods_name = trim(preg_replace("/【(.*)】/U", "", $one['name']));
                $goods_name = str_replace("'", '', $goods_name);
                $one_sql = "update ims_zhangguibao_product set goods_name = '{$goods_name}',update_time = {$time} where id = {$one['id']}";
                $m_bb_brand->executeSql($one_sql);

            }
        }

        echo time() . "<br>";
    }

    /**
     * 清洗10月份ims_ewei_shop_order表中bd_money的值
     */
    public function index_zt46()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_ShopGoodsModel = new ShopGoodsModel();
        echo time() . "<br>";
        $day_start = strtotime('2019-10-01');

        $sql_shop_order = "SELECT id from ims_ewei_shop_order where supplier_id = 461 and createtime > {$day_start}  and price >=1 and status >=0 order by id asc ";
        $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);
        echo count($list_shop_order) . "<br>";
        foreach ($list_shop_order as $one) {
            $sql_shop_order_goods = "SELECT a.id,a.price,b.bb_cate1 from ims_ewei_shop_order_goods a left join ims_ewei_shop_goods b on a.goodsid = b.id where a.orderid = {$one['id']} order by id asc ";
            $list_shop_order_goods = $m_ShopGoodsModel->querySql($sql_shop_order_goods);

            $bd_money = 0;
            foreach ($list_shop_order_goods as $two) {
                if (!empty($two['bb_cate1'] == 10)) {
                    $bd_money = $bd_money + round($two['price'] * 0.04, 2);
                } else {
                    $bd_money = $bd_money + round($two['price'] * 0.02, 2);
                }
            }
            $one_sql = "update ims_ewei_shop_order set bd_money = '{$bd_money}' where id = {$one['id']}";
            $m_ShopGoodsModel->executeSql($one_sql);
            echo $one_sql . "<br>";
        }

        echo time() . "<br>";
    }

    /**
     * 通过openid洗
     * 昵称（shop-member写到种子用户）    //头像（shop-member写入种子用户）
     * 姓名（加字段，收货地址中的姓名写入种子用户表）    //电话（收货地址中的电话写入种子用户表）
     */
    public function index_yk4()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $m_SignModel = new SignModel();
        echo time() . "<br>";

        $sql_ShopGoodsModel = "SELECT id,xcx_openid from potential_customer where is_validity = 1 and xcx_openid <> '' order by id asc ";
        $list_potential_customer = $m_SignModel->querySql($sql_ShopGoodsModel);
        foreach ($list_potential_customer as $two) {
            $sql_ShopGoodsModel = "SELECT id,nickname,avatar from ims_ewei_shop_member where openid ='{$two['xcx_openid']}' ";
            $list_shop_member = $m_ShopGoodsModel->querySql($sql_ShopGoodsModel);
            if (count($list_shop_member) > 0) {
                $nickname = str_replace("'", '’', $list_shop_member[0]['nickname']);
                $nickname = str_replace("\\", '', $nickname);
                $avatar = $list_shop_member[0]['avatar'];

                $sql_shop_member_address = "SELECT id,realname,mobile from ims_ewei_shop_member_address where openid ='{$two['xcx_openid']}' order by id desc limit 1";
                $list_shop_member_address = $m_ShopGoodsModel->querySql($sql_shop_member_address);
                $sql_address = '';
                if (count($list_shop_member_address) > 0) {
                    $realname = $list_shop_member_address[0]['realname'];
                    $mobile = $list_shop_member_address[0]['mobile'];
                    $sql_address = ",real_name = '{$realname}' , telphone = '{$mobile}'";
                }

                if (!empty($sql_address) || !empty($nickname) || !empty($avatar)) {
                    $one_sql = "update potential_customer set header_url = '{$avatar}',wx_name = '{$nickname}'  {$sql_address} where id = {$two['id']}";
                    echo $one_sql . "<br>";
                    $m_SignModel->executeSql($one_sql);
                }
            }
        }
        echo time() . "<br>";
    }


    /**
     * 统计每天种子用户信息
     */
    public function index_zt47()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $s_time = date('Y-m-d', $time);
        $today = date('Ymd', $time);
        $time_s = strtotime($s_time) - 60 * 60 * 24 * 1;
        $time_e = $time_s + 60 * 60 * 24 * 1;
        $m_ShopGoodsModel = new ShopGoodsModel();
        $m_SignModel = new SignModel();
        //echo time() . "<br>";
        ec();

        //提前数组列出循环里需要的数据，减少sql查询时间//
        //1.今日订单记录(下单)
        $sql_shop_order = "SELECT id,openid,createtime,bd_money,status from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} order by id asc ";
        $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);
        $arr_shop_order = array();
        foreach ($list_shop_order as $one) {
            $openid = $one['openid'];
            unset($one['openid']);
            $arr_shop_order[$openid][] = $one;
        }
        unset($list_shop_order);
        ec($arr_shop_order);

        //2.今日订单记录(收款)
        $sql_shop_order2 = "SELECT id,openid,createtime,bd_money,status from ims_ewei_shop_order where paytime > {$time_s} and paytime < {$time_e} order by id asc ";
        $list_shop_order2 = $m_ShopGoodsModel->querySql($sql_shop_order2);
        $arr_shop_order2 = array();
        foreach ($list_shop_order2 as $one) {
            $openid = $one['openid'];
            unset($one['openid']);
            $arr_shop_order2[$openid][] = $one;
        }
        unset($list_shop_order2);
        ec($arr_shop_order2);

        //3.所有地址记录
        $sql_shop_member_address = "SELECT id,openid from ims_ewei_shop_member_address where  id > 0 order by id asc";
        $list_shop_member_address = $m_ShopGoodsModel->querySql($sql_shop_member_address);
        $arr_shop_member_address = array();
        foreach ($list_shop_member_address as $one) {
            $arr_shop_member_address[$one['openid']] = $one['id'];
        }
        unset($list_shop_member_address);
        ec($arr_shop_member_address);

        //4.今日已有记录
        $sql_supplier_day_count = "SELECT id,openid from ims_yd_supplier_day_count where today = {$today} ";
        $list_supplier_day_count = $m_ShopGoodsModel->querySql($sql_supplier_day_count);
        $arr_supplier_day_count = array();
        foreach ($list_supplier_day_count as $one) {
            $arr_supplier_day_count[$one['openid']] = $one['id'];
        }
        unset($list_supplier_day_count);
        ec($arr_supplier_day_count);

        //5.今日埋点记录
        $sql_member_action_log = "SELECT id,createtime,log_type,log_info,openid from ims_member_action_log where createtime > {$time_s} and createtime < {$time_e} order by id desc";
        $list_member_action_log = $m_ShopGoodsModel->querySql($sql_member_action_log);
        $arr_member_action_log = array();
        foreach ($list_member_action_log as $one) {
            $openid = $one['openid'];
            unset($one['openid']);
            $arr_member_action_log[$openid][] = $one;
        }
        unset($list_member_action_log);
        ec($arr_member_action_log);

        //6.所有点位数据
        $sql_potential_customer = "SELECT id,xcx_openid from potential_customer where is_validity = 1 and xcx_openid <> '' order by id asc ";//limit 1000
        $list_potential_customer = $m_SignModel->querySql($sql_potential_customer);
        ec($list_potential_customer);

        foreach ($list_potential_customer as $two) {
            $openid = $two['xcx_openid'];
            //echo $openid."<br>";
//            $sql_shop_member_address = "SELECT id from ims_ewei_shop_member_address where  openid ='{$openid}' order by id desc";
//            $list_shop_member_address = $m_ShopGoodsModel->querySql($sql_shop_member_address);
            $has_address = 0;
            if (isset($arr_shop_member_address[$openid])) {
                $has_address = $arr_shop_member_address[$openid];
            }
//            $sql_member_action_log = "SELECT id,createtime,log_type,log_info from ims_member_action_log where createtime > {$time_s} and openid ='{$openid}' order by createtime desc";
//            $list_member_action_log = $m_ShopGoodsModel->querySql($sql_member_action_log);
            if (isset($arr_member_action_log[$openid])) {
                $list_member_action_log = $arr_member_action_log[$openid];
            } else {
                $list_member_action_log = array();
            }
            $read_count = 0;//count($list_member_action_log);
            $read_time = 0;
            $cart_count = 0;
            $cart_time = 0;
            $arr_goods = array();
            $cart_goods = 0;
            foreach ($list_member_action_log as $one) {
                if ($read_count == 0) {
                    $read_time = $one['createtime'];
                }
                if ($one['log_type'] == 14) {
                    if ($cart_count == 0) {
                        $cart_time = $one['createtime'];
                    }
                    $cart_count++;
                    if (empty($arr_goods)) {
                        $arr_goods[] = $one['log_info'];
                    } else {
                        if (!in_array($one['log_info'], $arr_goods)) {
                            $arr_goods[] = $one['log_info'];
                        }
                    }
                }
                $read_count++;
            }
            $cart_goods = count($arr_goods);

//            $sql_shop_order = "SELECT id,createtime,bd_money,status from ims_ewei_shop_order where createtime > {$time_s} and openid ='{$openid}' order by createtime desc";
//            $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);

            if (isset($arr_shop_order[$openid])) {
                $list_shop_order = $arr_shop_order[$openid];
            } else {
                $list_shop_order = array();
            }
            $order_count = 0;
            $order_time = 0;
            $cancel_order_count = 0;
            $cancel_order_time = 0;
            $money_feature = 0;
            $money_ready = 0;
            foreach ($list_shop_order as $one) {
                //
                if ($one['status'] == -1) {
                    if ($cancel_order_count == 0) {
                        $cancel_order_time = $one['createtime'];
                    }
                    $cancel_order_count++;
                } else {
                    $order_time = $one['createtime'];
                    if ($one['status'] == 1) {
                        //$money_ready = $money_ready + $one['bd_money'];
                    } else {
                        $money_feature = $money_feature + $one['bd_money'];
                    }
                }
                $order_count++;
            }

            if (isset($arr_shop_order2[$openid])) {
                $list_shop_order2 = $arr_shop_order2[$openid];
            } else {
                $list_shop_order2 = array();
            }

            foreach ($list_shop_order2 as $one) {
                $money_ready = $money_ready + $one['bd_money'];
            }

            $str_all = "{$read_count},{$read_time},{$cart_count},{$cart_goods},{$order_count},{$cancel_order_count}";
            $arr_all = explode(',', $str_all);
            if (array_sum($arr_all) > 0) {
                //$sql_supplier_day_count = "SELECT id from ims_yd_supplier_day_count where openid ='{$openid}' and today = {$today} ";
                //$list_supplier_day_count = $m_ShopGoodsModel->querySql($sql_supplier_day_count);
                if (isset($arr_supplier_day_count[$openid])) {
                    $one_sql = "update ims_yd_supplier_day_count set has_address = {$has_address},read_count={$read_count},read_time = {$read_time},cart_count={$cart_count},cart_time={$cart_time},cart_goods={$cart_goods},order_count={$order_count},order_time={$order_time},cancel_order_count={$cancel_order_count},cancel_order_time={$cancel_order_time},money_feature={$money_feature},money_ready={$money_ready},update_time = {$time}  where id = {$arr_supplier_day_count[$openid]}";
                } else {
                    $one_sql = "insert into ims_yd_supplier_day_count (type,sup_id,today,openid,has_address,read_count,read_time,cart_count,cart_time,cart_goods,order_count,order_time,cancel_order_count,cancel_order_time,money_feature,money_ready,create_time) values(1,461,{$today},'{$openid}',{$has_address},{$read_count},{$read_time},{$cart_count},{$cart_time},{$cart_goods},{$order_count},{$order_time},{$cancel_order_count},{$cancel_order_time},{$money_feature},{$money_ready},{$time})";
                }
                echo $one_sql . "<br>";
                $m_ShopGoodsModel->executeSql($one_sql);
            }

        }

        ec();
    }

    /**
     * 统计每日划单信息
     */
    public function index_zt48()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $day = $this->request->param("day", 0);

        $time = time();
        $s_time = date('Y-m-d', $time);
        if (empty($day)) {
            $day = $s_time;
        }
        //$today = date('Ymd', $time);
        $time_s = strtotime($day);
        $time_e = $time_s + 60 * 60 * 24 * 1;
        $m_ShopGoodsModel = new ShopGoodsModel();
        $m_SignModel = new SignModel();
        echo "日期" . $day . "<br>";

        //1.今日订单记录
        $sql_shop_order = "SELECT id,ordersn,status from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 order by id desc";
        $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);
        $shop_order_count_all = count($list_shop_order);
        echo "今日订单记录" . $shop_order_count_all . "<br>";

        //2.今日变更记录
//        $sql_order_change_log = "SELECT * from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 order by id desc";
//        $list_order_change_log = $m_ShopGoodsModel->querySql($sql_order_change_log);
//
//        $arr_modify = array();
//        $arr_delete = array();
//        foreach ($list_order_change_log as $two) {
//            if (empty($arr_modify)) {
//                $arr_modify[] = $two['opendid'];
//            } else {
//                if (!in_array($two['opendid'], $arr_modify)) {
//                    $arr_modify[] = $two['opendid'];
//                }
//            }
//
//            if (empty($arr_delete)) {
//                $arr_delete[] = $two['opendid'];
//            } else {
//                if (!in_array($two['opendid'], $arr_delete)) {
//                    $arr_delete[] = $two['opendid'];
//                }
//            }
//        }

        //划单订单
        $sql_order_change_log1 = "SELECT id,ordersn from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 group by ordersn order by id desc";
        $list_order_change_log1 = $m_ShopGoodsModel->querySql($sql_order_change_log1);
        echo "划单订单" . count($list_order_change_log1) . "<br>";

        //全划订单
        $sql_order_change_log2 = "SELECT id,ordersn from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 and count_new = 0 group by ordersn order by id desc";
        $list_order_change_log2 = $m_ShopGoodsModel->querySql($sql_order_change_log2);
        echo "全划订单:" . count($list_order_change_log2) . "<br>";

        //订单最高划品数
        //$sql_order_change_log3 = "SELECT goods_id,count(id) from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 group by ordersn order by id desc";
        //$list_order_change_log3 = $m_ShopGoodsModel->querySql($sql_order_change_log3);
        $sql_order_change_log3 = "SELECT * from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 and count_old > count_new order by id desc";
        $list_order_change_log3 = $m_ShopGoodsModel->querySql($sql_order_change_log3);
        $arr_goods = array();
        foreach ($list_order_change_log3 as $one) {
            if (isset($arr_goods[$one['goods_id']]['old'])) {
                if ($arr_goods[$one['goods_id']]['old'] < $one['count_old']) {
                    $arr_goods[$one['goods_id']]['old'] = $one['count_old'];
                }

            } else {
                $arr_goods[$one['goods_id']]['old'] = $one['count_old'];
            }
        }
        foreach ($list_order_change_log3 as $one) {
            if (isset($arr_goods[$one['goods_id']]['new'])) {
                if ($arr_goods[$one['goods_id']]['new'] > $one['count_new']) {
                    $arr_goods[$one['goods_id']]['new'] = $one['count_new'];
                }

            } else {
                $arr_goods[$one['goods_id']]['new'] = $one['count_new'];
            }
        }

        $cha_max = 0;
        foreach ($arr_goods as $k => $v) {
            $cha = $v['old'] - $v['new'];
            $arr_goods[$k]['cha'] = $cha;
            if ($cha_max < $cha) {
                $cha_max = $cha;
            }
        }
        echo "订单最高划品数:" . $cha_max . "<br>";
        //print_r($arr_goods);

        //划单商品数
        $sql_order_change_log5 = "SELECT goods_id from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 group by goods_id order by id desc";
        $list_order_change_log5 = $m_ShopGoodsModel->querySql($sql_order_change_log5);
        echo "划单商品数:" . count($list_order_change_log5) . "<br>";

        echo time() . "<br>";
    }


    /**
     * 统计每日划单以及商品信息
     */
    public function index_zt49()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $day = $this->request->param("day", 0);
        $show = $this->request->param("show", 0);

        $time = time();
        $s_time = date('Y-m-d', $time);
        if (empty($day)) {
            $day = $s_time;
        }

        $time_s = strtotime($day);
        $time_s1 = strtotime($day) - 60 * 60 * 24 * 1;
        $time_e = $time_s + 60 * 60 * 24 * 1;
        $m_ShopGoodsModel = new ShopGoodsModel();

        $day_s1 = date('Y-m-d', $time_s1);
        $day_e1 = date('Y-m-d', $time_e);
        echo "<div align='center'>云店批发每日划单统计(已除郭、吴数据)&nbsp; &nbsp; &nbsp; &nbsp; <a href='./index?act=index_zt49&day={$day_s1}'>上一日</a>&nbsp; " . $day . "&nbsp; <a href='./index?act=index_zt49&day={$day_e1}'>下一日</a></div><br>";

        //1.今日订单记录
        $sql_shop_order = "SELECT id,ordersn,status from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 and openid not in ('sns_wa_ogrIh0YvHd06DMI60jb7BpG-zgvY','sns_wa_ogrIh0V5T8eGtUINOheRKs4_bIgs','sns_wa_ogrIh0V7PbyY7V24pwRCtdWds3Ww') order by id desc";
        $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);
        $shop_order_count_all = count($list_shop_order);
        //echo "当日订单记录" . $shop_order_count_all . "<br>";

        $max_count = array();
        $arr_hua_dan = array();
        $count_goods = 0;
        $count_order = 0;
        foreach ($list_shop_order as $one) {
            $sql_shop_order0 = "SELECT id,goodsid from ims_ewei_shop_order_goods where orderid = {$one['id']}  order by id desc";
            $list_shop_order0 = $m_ShopGoodsModel->querySql($sql_shop_order0);
            $kk = 0;
            $arr1 = null;
            foreach ($list_shop_order0 as $two) {
                $sql_shop_order1 = "SELECT id,goods_id from ims_member_order_change_log where ordersn = '{$one['ordersn']}' and goods_id = {$two['goodsid']} and count_old > count_new order by id desc";
                $list_shop_order1 = $m_ShopGoodsModel->querySql($sql_shop_order1);
                if (count($list_shop_order1) > 0) {
                    $count_goods++;
                    $kk++;
                    $arr1[] = $two['goodsid'];
                }
            }
            //
            if ($kk > 0) {
                //echo $one['ordersn']." ".$kk."<br>";
                $max_count[] = $kk;
                $arr_hua_dan[$one['ordersn']] = $arr1;
                $count_order++;
            }
        }

        if (!empty($max_count)) {
            arsort($arr_hua_dan);
            rsort($max_count);
            $max_hua_dan = $max_count[0];
            $avg_hua_dan = round($count_goods / $count_order, 2);
        } else {
            $max_hua_dan = 0;
            $avg_hua_dan = 0;
        }

        //2.划单订单
        $sql_order_change_log1 = "SELECT id,ordersn from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) group by ordersn order by id desc";
        //echo $sql_order_change_log1."<br>";
        $list_order_change_log1 = $m_ShopGoodsModel->querySql($sql_order_change_log1);
        //echo "划单订单"
        $count1 = count($list_order_change_log1);

        //3.全划订单
//        $sql_order_change_log2 = "SELECT id,ordersn from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) and count_new = 0 group by ordersn order by id desc";
//        //echo $sql_order_change_log2."<br>";
//        $list_order_change_log2 = $m_ShopGoodsModel->querySql($sql_order_change_log2);
//        $count2 = count($list_order_change_log2);
        $count2 = count($arr_hua_dan);
        $html0 = '';
        if ($show == 1) {
            $html0 .= "<table border='1' width='80%' align='center'>";
            $html0 .= "<tr><td>序号</td><td>划单单号</td><td>划单商品</td></tr>";
            $hh = 0;
            foreach ($arr_hua_dan as $kkk => $vvv) {
                $hh++;
                $html0 .= "<tr><td>" . $hh . "</td><td>" . $kkk . "</td><td>" . implode(',', $vvv) . "</td></tr>";
            }
            $html0 .= "</table>" . "<br>";
        }

        $sql_order_change_log5 = "SELECT goods_id from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) group by goods_id order by id desc";
        $list_order_change_log5 = $m_ShopGoodsModel->querySql($sql_order_change_log5);
        $count5 = count($list_order_change_log5) . "<br>";

        $arr_goods_id = array();
        foreach ($list_order_change_log5 as $one) {
            $arr_goods_id[] = $one['goods_id'];
        }
        $str_goods_id = implode(',', $arr_goods_id);
        $money_all = 0;
        $html2 = '';
        if (count($arr_goods_id) > 0) {
            $sql_shop_goods = "SELECT * from ims_ewei_shop_goods where id in ({$str_goods_id}) order by id desc";
            $list_shop_goods = $m_ShopGoodsModel->querySql($sql_shop_goods);
            $html2 .= "<table border='1' width='80%' align='center'>";
            $html2 .= "<tr><td>ID</td><td>商品</td><td>售价</td><td>订单(鼠标放上显示单号)</td><td>划单数量</td><td>划单总额</td></tr>";

            foreach ($list_shop_goods as $one) {
                $sql_order_change_log6 = "SELECT ordersn,count_old,count_new from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) and goods_id = {$one['id']} and count_old > count_new order by id asc";
                $list_order_change_log6 = $m_ShopGoodsModel->querySql($sql_order_change_log6);
                $count_cha = 0;
                $sn = '';
                foreach ($list_order_change_log6 as $three) {
                    $sn = $sn . $three['ordersn'] . ",";
                    $sql_order_change_log7 = "SELECT ordersn,count_old,count_new from ims_member_order_change_log where ordersn = '{$three['ordersn']}' and goods_id = {$one['id']} order by id asc";
                    $list_order_change_log7 = $m_ShopGoodsModel->querySql($sql_order_change_log7);

                    $count_cha = $count_cha + ($list_order_change_log7[0]['count_old'] - $list_order_change_log7[count($list_order_change_log7) - 1]['count_new']);
                }
                $money = $one['sale_pirce'] * $count_cha;
                $money_all = $money_all + $money;

                $html2 .= "<tr><td>" . $one['id'] . "</td><td>" . $one['title'] . "</td><td>" . $one['sale_pirce'] . "</td><td title='{$sn}'>" . count($list_order_change_log6) . "</td><td>{$count_cha}</td><td>{$money}</td></tr>";//
            }
            $html2 .= "</table>";
        }

        $html1 = "<table border='1' width='80%' align='center'>";
        $html1 .= "<tr><td><a href='./index?act=index_zt49&day={$day}&show=0'>统计日期</a></td><td>订单总数</td><td><a href='./index?act=index_zt49&day={$day}&show=1'>划单订单数</a></td><td>全划订单数</td><td>一单最大划品数</td><td>划单平均划品数</td><td>划单商品数</td><td>划单总金额</td></tr>";
        $html1 .= "<tr><td>{$day}</td><td>{$shop_order_count_all}</td><td>{$count1}</td><td>{$count2}</td><td>{$max_hua_dan}</td><td>{$avg_hua_dan}</td><td>{$count5}</td><td>{$money_all}</td></tr>";
        $html1 .= "</table>";

        echo $html0;
        echo $html1 . "<br>";
        echo $html2 . "<br>";
        //echo time() . "<br>";
    }

    /**
     * 统计每日划单以及商品信息
     */
    public function index_zt49_old()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $day = $this->request->param("day", 0);
        $show = $this->request->param("show", 0);

        $time = time();
        $s_time = date('Y-m-d', $time);
        if (empty($day)) {
            $day = $s_time;
        }
        //$today = date('Ymd', $time);
        $time_s = strtotime($day);
        $time_s1 = strtotime($day) - 60 * 60 * 24 * 1;
        $time_e = $time_s + 60 * 60 * 24 * 1;
        $m_ShopGoodsModel = new ShopGoodsModel();
        $m_SignModel = new SignModel();
        $day_s1 = date('Y-m-d', $time_s1);
        $day_e1 = date('Y-m-d', $time_e);
        echo "<div align='center'><a href='./index?act=index_zt49&day={$day_s1}'>上一日</a>  " . "当日" . "  <a href='./index?act=index_zt49&day={$day_e1}'>下一日</a></div><br>";

        //1.今日订单记录
        $sql_shop_order = "SELECT id,ordersn,status from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 and openid not in ('sns_wa_ogrIh0YvHd06DMI60jb7BpG-zgvY','sns_wa_ogrIh0V5T8eGtUINOheRKs4_bIgs') order by id desc";
        $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);
        $shop_order_count_all = count($list_shop_order);
        //echo "当日订单记录" . $shop_order_count_all . "<br>";

        $max_count = array();
        $count_goods = 0;
        $count_order = 0;
        foreach ($list_shop_order as $one) {
            $sql_shop_order0 = "SELECT id,goodsid from ims_ewei_shop_order_goods where orderid = {$one['id']}  order by id desc";
            $list_shop_order0 = $m_ShopGoodsModel->querySql($sql_shop_order0);
            $kk = 0;
            foreach ($list_shop_order0 as $two) {
                $sql_shop_order1 = "SELECT id,goods_id from ims_member_order_change_log where ordersn = '{$one['ordersn']}' and goods_id = {$two['goodsid']} and count_old > count_new order by id desc";
                $list_shop_order1 = $m_ShopGoodsModel->querySql($sql_shop_order1);
                if (count($list_shop_order1) > 0) {
                    $count_goods++;
                    $kk++;
                }
            }
            //
            if ($kk > 0) {
                //echo $one['ordersn']." ".$kk."<br>";
                $max_count[] = $kk;
                $count_order++;
            }
        }

        if (!empty($max_count)) {
            rsort($max_count);
            $max_hua_dan = $max_count[0];
            $avg_hua_dan = round($count_goods / $count_order, 2);
        } else {
            $max_hua_dan = 0;
            $avg_hua_dan = 0;
        }


        //2.今日变更记录
//        $sql_order_change_log = "SELECT * from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 order by id desc";
//        $list_order_change_log = $m_ShopGoodsModel->querySql($sql_order_change_log);
//
//        $arr_modify = array();
//        $arr_delete = array();
//        foreach ($list_order_change_log as $two) {
//            if (empty($arr_modify)) {
//                $arr_modify[] = $two['opendid'];
//            } else {
//                if (!in_array($two['opendid'], $arr_modify)) {
//                    $arr_modify[] = $two['opendid'];
//                }
//            }
//
//            if (empty($arr_delete)) {
//                $arr_delete[] = $two['opendid'];
//            } else {
//                if (!in_array($two['opendid'], $arr_delete)) {
//                    $arr_delete[] = $two['opendid'];
//                }
//            }
//        }

        //划单订单
        //$sql_order_change_log1 = "SELECT id,ordersn from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 group by ordersn order by id desc";
        $sql_order_change_log1 = "SELECT id,ordersn from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) group by ordersn order by id desc";
        //echo $sql_order_change_log1."<br>";
        $list_order_change_log1 = $m_ShopGoodsModel->querySql($sql_order_change_log1);
        //echo "划单订单"
        $count1 = count($list_order_change_log1);

        //全划订单
        //$sql_order_change_log2 = "SELECT id,ordersn from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 and count_new = 0 group by ordersn order by id desc";
        $sql_order_change_log2 = "SELECT id,ordersn from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) and count_new = 0 group by ordersn order by id desc";
        //echo $sql_order_change_log2."<br>";
        $list_order_change_log2 = $m_ShopGoodsModel->querySql($sql_order_change_log2);
        //echo "全划订单:"
        $count2 = count($list_order_change_log2);
        if ($show == 1) {
            echo "划单单号<br>";
            foreach ($list_order_change_log2 as $one) {
                echo $one['ordersn'] . "<br>";
            }

        }

        //订单最高划品数
        //$sql_order_change_log3 = "SELECT goods_id,count(id) from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 group by ordersn order by id desc";
        //$list_order_change_log3 = $m_ShopGoodsModel->querySql($sql_order_change_log3);
        //$sql_order_change_log3 = "SELECT * from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 and count_old > count_new order by id desc";
//        $sql_order_change_log3 = "SELECT * from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) and count_old > count_new order by id desc";
//        $list_order_change_log3 = $m_ShopGoodsModel->querySql($sql_order_change_log3);
//        $arr_ordersn_goods = array();
////        foreach ($list_order_change_log3 as $one) {
////            if (isset($arr_ordersn_goods[$one['ordersn']]['old'])) {
////            }
////        }
//        $arr_goods = array();
//        foreach ($list_order_change_log3 as $one) {
//            if (isset($arr_goods[$one['goods_id']]['old'])) {
//                if ($arr_goods[$one['goods_id']]['old'] < $one['count_old']) {
//                    $arr_goods[$one['goods_id']]['old'] = $one['count_old'];
//                }
//
//            } else {
//                $arr_goods[$one['goods_id']]['old'] = $one['count_old'];
//            }
//        }
//        foreach ($list_order_change_log3 as $one) {
//            if (isset($arr_goods[$one['goods_id']]['new'])) {
//                if ($arr_goods[$one['goods_id']]['new'] > $one['count_new']) {
//                    $arr_goods[$one['goods_id']]['new'] = $one['count_new'];
//                }
//
//            } else {
//                $arr_goods[$one['goods_id']]['new'] = $one['count_new'];
//            }
//        }
//
//        $cha_max = 0;
//        foreach ($arr_goods as $k => $v) {
//            $cha = $v['old'] - $v['new'];
//            $arr_goods[$k]['cha'] = $cha;
//            if ($cha_max < $cha) {
//                $cha_max = $cha;
//            }
//        }
//        echo "订单最高划品数:" . $cha_max . "<br>";
//        print_r($arr_goods);

        //划单商品数
        //$sql_order_change_log5 = "SELECT goods_id from ims_member_order_change_log where createtime > {$time_s} and createtime < {$time_e} and sup_id = 461 group by goods_id order by id desc";
        $sql_order_change_log5 = "SELECT goods_id from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) group by goods_id order by id desc";
        $list_order_change_log5 = $m_ShopGoodsModel->querySql($sql_order_change_log5);
        //echo "划单商品数:" .
        $count5 = count($list_order_change_log5) . "<br>";


        $arr_goods_id = array();
        foreach ($list_order_change_log5 as $one) {
            $arr_goods_id[] = $one['goods_id'];
        }
        $str_goods_id = implode(',', $arr_goods_id);
        $money_all = 0;
        if (count($arr_goods_id) > 0) {
            $sql_shop_goods = "SELECT * from ims_ewei_shop_goods where id in ({$str_goods_id}) order by id desc";
            $list_shop_goods = $m_ShopGoodsModel->querySql($sql_shop_goods);
            $html = "<table border='1' width='80%' align='center'>";
            $html = $html . "<tr><td>ID</td><td>商品</td><td>价格</td><td>所在订单数</td><td>数量</td><td>金额</td></tr>";

            foreach ($list_shop_goods as $one) {
                $sql_order_change_log6 = "SELECT ordersn,count_old,count_new from ims_member_order_change_log where ordersn in (SELECT ordersn from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 order by id desc) and goods_id = {$one['id']} and count_old > count_new order by id asc";
                $list_order_change_log6 = $m_ShopGoodsModel->querySql($sql_order_change_log6);
                //echo "划单商品数:" . count($list_order_change_log5) . "<br>";
                $count_cha = 0;
                $sn = '';
                foreach ($list_order_change_log6 as $three) {
                    $sn = $sn . $three['ordersn'] . ",";
                    $sql_order_change_log7 = "SELECT ordersn,count_old,count_new from ims_member_order_change_log where ordersn = '{$three['ordersn']}' and goods_id = {$one['id']} order by id asc";
                    $list_order_change_log7 = $m_ShopGoodsModel->querySql($sql_order_change_log7);

                    $count_cha = $count_cha + ($list_order_change_log7[0]['count_old'] - $list_order_change_log7[count($list_order_change_log7) - 1]['count_new']);
                }
                $money = $one['sale_pirce'] * $count_cha;
                $money_all = $money_all + $money;

                $html .= "<tr><td>" . $one['id'] . "</td><td>" . $one['title'] . "</td><td>" . $one['sale_pirce'] . "</td><td title='{$sn}'>" . count($list_order_change_log6) . "</td><td>{$count_cha}</td><td>{$money}</td></tr>";//
            }
            $html .= "</table>";
        }


        //划单金额
//        $sql_shop_order = "SELECT id,goods_id,realprice from ims_ewei_shop_order_goods where createtime > {$time_s} and createtime < {$time_e} order by id desc";
//        $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);
//        $arr_goods_price = array();
//        foreach ($arr_goods as $one) {
//            $arr_goods_price[$one['goods_id']] = $one['realprice'];
//        }
        $html0 = "<table border='1' width='80%' align='center'>";
        $html0 .= "<tr><td><a href='./index?act=index_zt49&day={$day}&show=0'>日期</a></td><td>订单总数</td><td><a href='./index?act=index_zt49&day={$day}&show=1'>划单订单数</a></td><td>全划订单数</td><td>一单最大划品数</td><td>划单平均划品数</td><td>划单商品数</td><td>划单总金额</td></tr>";
        $html0 .= "<tr><td>{$day}</td><td>{$shop_order_count_all}</td><td>{$count1}</td><td>{$count2}</td><td>{$max_hua_dan}</td><td>{$avg_hua_dan}</td><td>{$count5}</td><td>{$money_all}</td></tr>";
        $html0 .= "</table>";

        echo $html0 . "<br>";
        echo $html . "<br>";
        //echo time() . "<br>";
    }

    /**
     * 统计每日划单以及商品信息
     * @throws Exception
     */
    public function index_zt50()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $m_ShopGoodsModel = new ShopGoodsModel();
        $m_SignModel = new SignModel();
        //ec();

        $sql = "SELECT l.date,c_openid,c,o.sum_price,o.count_order,o.count_openid from (SELECT date_format(from_unixtime(createtime),'%y-%m-%d') date,count(DISTINCT(openid)) c_openid,count(*) c  FROM `ims_member_action_log` where sup_id='461' GROUP BY date ORDER BY date desc) l LEFT JOIN (SELECT date_format(from_unixtime(createtime),'%y-%m-%d') date,sum(price) sum_price,count(*) count_order,count(DISTINCT(openid)) count_openid  from ims_ewei_shop_order where `status`!='-1' and supplier_id='461' GROUP BY date) o on l.date=o.date where 1 limit 5";
        $list_riBao = Db::connect('db_mini_mall')->query($sql);
        //ec($list_riBao);
        foreach ($list_riBao as $key => $value) {
            $list_riBao[$key]['avg_dianji'] = !empty($value['c']) ? round($value['sum_price'] / $value['c'], 2) : 0;
            $list_riBao[$key]['avg_openid'] = !empty($value['c_openid']) ? round($value['sum_price'] / $value['c_openid'], 2) : 0;
            $list_riBao[$key]['avg_openid_dianji'] = !empty($value['c_openid']) ? round($value['c'] / $value['c_openid'], 0) : 0;
            $list_riBao[$key]['avg_sum_price'] = !empty($value['count_openid']) ? round($value['sum_price'] / $value['count_openid'], 0) : 0;
            $list_riBao[$key]['count_openid_openid_rate'] = !empty($value['c_openid']) ? round($value['count_openid'] / $value['c_openid'], 2) * 100 : 0;

            $start_time = strtotime($value['date']);
            $end_time = $start_time + 60 * 60 * 24 * 1;
            ec();
            //查询新增的用户
            //$new_user = Db::connect('db_mini_mall')->table('ims_member_action_log')->alias('a')->leftJoin('ims_ewei_shop_member b', 'a.openid = b.openid')->where('b.createtime > ' . $start_time . ' and b.createtime <= ' . $end_time)->group('a.openid')->count();
            $new_user = Db::connect('db_mini_mall')->table('ims_ewei_shop_member b')->where('b.createtime > ' . $start_time . ' and b.createtime <= ' . $end_time)->count();
            $list_riBao[$key]['new_user'] = $new_user;
            ec();
            //查询新增订单客户数
            $new_order_user = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->alias('a')->leftJoin('ims_ewei_shop_member b', 'a.openid = b.openid')->where('b.createtime > ' . $start_time . ' and b.createtime <= ' . $end_time)->group('a.openid')->count();
            $list_riBao[$key]['new_order_user'] = $new_order_user;
            ec();
            //查询当日浏览量
            $liulanliang = Db::connect('db_mini_mall')->table('ims_member_action_log')->where('createtime > ' . $start_time . ' and createtime <= ' . $end_time)->group('openid')->count();
            $list_riBao[$key]['liulanliang'] = $liulanliang;
            ec();
            //查询当日划单数量
            $huadan = Db::connect('db_mini_mall')->table('ims_member_order_change_log')->where(' sup_id = 461 and createtime > ' . $start_time . ' and createtime <= ' . $end_time)->field('ordersn,goods_id,count_old,count_new')->order('createtime', 'asc')->select();
            $huadan_arr = null;
            if (!empty(count($huadan))) {
                foreach ($huadan as $h_key => $h_value) {
                    $huadan_arr[$h_value['ordersn']][] = $h_value;
                }
            }
            $huadan_count = 0;
            if (!empty(count($huadan_arr))) {
                foreach ($huadan_arr as $h_key => $h_value) {
                    $one_start = array_shift($h_value);
                    $one_end = end($h_value);
                    if ($one_start['count_new'] < $one_end['count_new']) {
                        $huadan_count = $huadan_count + 1;
                    }
                }
            }
            ec();
            //在线商品数
            $goods_count = Db::connect('db_mini_mall')->table('ims_ewei_shop_goods')->alias('a')->leftJoin('ims_yd_supplier_goods b', 'a.id = b.goods_id')->where('a.status = 1 and a.deleted = 0 and b.status = 1 and a.total > 0')->count();
            $list_riBao[$key]['goods_count'] = $goods_count;
            ec();
            //动销商品数
            $dong_goods_count = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where(' createtime > ' . $start_time . ' and createtime <= ' . $end_time . ' and supplier_id = 461')->field('sum(goods_count) as goods_count')->find();
            $list_riBao[$key]['dong_goods_count'] = $dong_goods_count['goods_count'];
            ec();
            //动销单品贡献
            $list_riBao[$key]['dong_goods_gongxian'] = !empty($dong_goods_count['goods_count']) ? round($value['sum_price'] / $dong_goods_count['goods_count'], 2) : 0;
            ec();
            //平均订单商品款数
            $list_riBao[$key]['avg_order_goods'] = !empty($dong_goods_count['goods_count']) ? round($value['count_order'] / $dong_goods_count['goods_count'], 2) : 0;
            ec();
            //在线供应商数
            $online_supplier_count = Db::connect('db_btj_new')->table('potential_customer')->where('identity = 2')->count();
            $list_riBao[$key]['online_supplier_count'] = $online_supplier_count;
            ec();
            //采购供应商数
            $buy_supplier_count = Db::connect('db_mini_mall')->table('ims_ydhl_stock')->group('gong_yin_shang')->count();
            $list_riBao[$key]['buy_supplier_count'] = $buy_supplier_count;
            ec();
            //领取金币客户数
            $get_gold_openid = Db::connect('db_mini_mall')->table('ims_member_gold_list')->where('addtime > ' . $start_time . ' and addtime <= ' . $end_time . ' and sup_id = 461')->group('openid')->count();//
            $list_riBao[$key]['get_gold_openid'] = $get_gold_openid;
            ec();
            //领取金币总数
            $get_gold_count = Db::connect('db_mini_mall')->query("select sum(gold_value) as cc from ims_member_gold_list where addtime > " . $start_time . ' and addtime <= ' . $end_time . ' and sup_id = 461');//
            $list_riBao[$key]['get_gold_count'] = $get_gold_count[0]['cc'];
            ec();
            //纯水乐兑换客户数/总量
            $chun_shui_le = Db::connect('db_mini_mall')->table('ims_ewei_shop_exchange_order')->where('createtime > ' . $start_time . ' and createtime <= ' . $end_time . ' and supplier_id = 461')->field('count(*) as order_count,sum(total) as order_total')->find();
            $list_riBao[$key]['chun_shui_le_order'] = !empty($chun_shui_le['order_count']) ? $chun_shui_le['order_count'] : 0;
            $list_riBao[$key]['chun_shui_le_order_total'] = !empty($chun_shui_le['order_total']) ? $chun_shui_le['order_total'] : 0;
            ec();
            //优惠券领取客户数
            $get_coupon_user = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_coupon')->where('create_time > ' . $start_time . ' and create_time <= ' . $end_time . ' and coupon_status = 1 and coupon_status = 2')->group('openid')->count();
            $list_riBao[$key]['get_coupon_user'] = $get_coupon_user;
            ec();
            //优惠券使用客户数
            $use_coupon_user = Db::connect('db_mini_mall')->table('ims_ewei_shop_member_coupon')->where('create_time > ' . $start_time . ' and create_time <= ' . $end_time . ' and coupon_status = 3')->group('openid')->count();
            $list_riBao[$key]['use_coupon_user'] = $use_coupon_user;
            ec();
            //优惠券领取面额总额
            $all_money_value = Db::connect('db_mini_mall')->table('ims_bb_base_coupon')->alias('a')->leftJoin('ims_ewei_shop_member_coupon b', 'a.id = b.coupon_id')->where('b.create_time > ' . $start_time . ' and b.create_time <= ' . $end_time . ' and coupon_status = 1 and coupon_status = 2')->field('sum(money_value) as money_value')->find();
            $list_riBao[$key]['all_money_value'] = !empty($all_money_value['money_value']) ? $all_money_value['money_value'] : 0;
            ec();
            //优惠券领取面额使用总额
            $all_coupon_money = Db::connect('db_mini_mall')->table('ims_ewei_shop_order')->where('createtime > ' . $start_time . ' and createtime <= ' . $end_time)->field('sum(coupon_money) as coupon_money')->find();
            $list_riBao[$key]['all_coupon_money'] = !empty($all_coupon_money['coupon_money']) ? $all_coupon_money['coupon_money'] : 0;
            ec();
        }
        print_r($list_riBao);
    }


    /**
     * potential_customer_data每个db的每天的第一时间和最后时间
     */
    public function index_yk5()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $time1 = strtotime(date('Y-m-d', $time));
        $m_ShopGoodsModel = new ShopGoodsModel();
        $m_SignModel = new SignModel();
        //echo time() . "<br>";
        ec();
        //$time7 = $time1 - 60 * 60 * 24 * 35;
        $time7 = strtotime('2019-10-01');

        $sql_ShopGoodsModel = "SELECT user_id,name from btj_admin_user where user_id > 0 order by user_id asc ";
        $list_admin_user = $m_SignModel->querySql($sql_ShopGoodsModel);
        //ec($list_admin_user);
        $arr_admin_user = array();
        foreach ($list_admin_user as $one) {
            $arr_admin_user[$one['user_id']] = $one['name'];
        }

        $sql_ShopGoodsModel = "SELECT id,user_id,create_time from potential_customer_data where create_time > {$time7} order by id desc ";
        $list_potential_customer_data = $m_SignModel->querySql($sql_ShopGoodsModel);
        //ec($list_potential_customer_data);
        $arr_customer_day = array();
        foreach ($list_potential_customer_data as $one) {
            $day = date('Y-m-d', $one['create_time']);
            $arr_customer_day[$day][] = $one;
        }
        //ec($arr_customer_day);
        //$arr_customer_data = array();
        foreach ($arr_customer_day as $kk => $vv) {
            $arr_customer_data = null;
            foreach ($vv as $two) {
                $arr_customer_data[$two['user_id']][] = $two['create_time'];
            }

            foreach ($arr_customer_data as $k => $v) {
                if (!empty($v)) {
                    $arr1 = $v;
                    $admin_name = isset($arr_admin_user[$k]) ? $arr_admin_user[$k] : $k;
                    if (count($v) == 1) {
                        $time1 = $arr1[0];
                        $time2 = '-';
                        echo $kk . ' : ' . $admin_name . " 开始" . date('Y-m-d H:i:s', $time1) . ' => 结束' . $time2 . "<br>";
                    } else {
                        sort($arr1);
                        $time1 = $arr1[0];
                        rsort($arr1);
                        $time2 = $arr1[0];
                        echo $kk . ' : ' . $admin_name . " 开始" . date('Y-m-d H:i:s', $time1) . ' => 结束' . date('Y-m-d H:i:s', $time2) . "<br>";
                    }
                }
            }
            echo "<hr>";
        }
        ec();
    }


    /**
     * 品牌，孙宇+3家bb平台商品数统计
     */
    public function index_zt51()
    {
        //set_time_limit(0);
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');

        $time = time();
        $m_ShopGoodsModel = new ShopGoodsModel();
        ec();

        $arr_brand_all = array();
        //1.孙宇-品牌商品
        $sql_ShopGoodsModel = "SELECT a.brand_id, b.b_name, count(a.id) as cc FROM ims_jingpiwang_product a LEFT JOIN ims_bb_brand b ON a.brand_id = b.id WHERE a.sup_id = 461 AND a.`STATUS` = 1 AND a.total > 0 AND a.deleted = 0 AND a.is_activity = 0 group by a.brand_id";
        $list_shop_goods = $m_ShopGoodsModel->querySql($sql_ShopGoodsModel);
        ec($list_shop_goods);
        foreach ($list_shop_goods as $one) {
            $arr_brand_all[$one['b_name']]['sy'] = $one['cc'];
        }

        //2.京批网-品牌商品
        $sql_ShopGoodsModel = "SELECT brand_name, count(id) as cc FROM ims_jingpiwang_product group by brand_name and brand_name <> ''";
        $list_jingpiwang_product = $m_ShopGoodsModel->querySql($sql_ShopGoodsModel);
        ec($list_jingpiwang_product);
        foreach ($list_jingpiwang_product as $one) {
            $arr_brand_all[$one['brand_name']]['jpw'] = $one['cc'];
        }

        //3.惠民网-品牌商品
        $sql_ShopGoodsModel = "SELECT brand_name, count(id) as cc FROM ims_huiminwang_product group by brand_name and brand_name <> ''";
        $list_jingpiwang_product = $m_ShopGoodsModel->querySql($sql_ShopGoodsModel);
        ec($list_jingpiwang_product);
        foreach ($list_jingpiwang_product as $one) {
            $arr_brand_all[$one['brand_name']]['hmw'] = $one['cc'];
        }

        //4.百世店家-品牌商品
        $sql_ShopGoodsModel = "SELECT brandName, count(id) as cc FROM ims_bsdj_product group by brand_name and brandName <> ''";
        $list_jingpiwang_product = $m_ShopGoodsModel->querySql($sql_ShopGoodsModel);
        ec($list_jingpiwang_product);
        foreach ($list_jingpiwang_product as $one) {
            $arr_brand_all[$one['brandName']]['bsdj'] = $one['cc'];
        }

//        $sql_ShopGoodsModel = "SELECT id,user_id,create_time from potential_customer_data where create_time > {$time7} order by id desc ";
//        $list_potential_customer_data = $m_SignModel->querySql($sql_ShopGoodsModel);
//        //ec($list_potential_customer_data);
//        $arr_customer_day = array();
//        foreach ($list_potential_customer_data as $one) {
//            $day = date('Y-m-d', $one['create_time']);
//            $arr_customer_day[$day][] = $one;
//        }


        ec();
    }


    /**
     * 李晓要，11.4-12号所有划单商品的详情
     */
    public function index_zt52()
    {
        $m_SignModel = new SignModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $day_s = strtotime('2019-11-04');
        for ($i = 0; $i < 10; $i++) {
            $time_s = $day_s + 60 * 60 * 24 * $i;
            if ($time_s > time()) continue;
            $time_e = $time_s + 60 * 60 * 24 * 1;
            $str_day = date('Y-m-d', $time_s);
            $sql_shop_order = "SELECT id,ordersn,status,openid from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 and openid not in ('sns_wa_ogrIh0YvHd06DMI60jb7BpG-zgvY','sns_wa_ogrIh0V5T8eGtUINOheRKs4_bIgs','sns_wa_ogrIh0V7PbyY7V24pwRCtdWds3Ww') order by id desc";
            //echo $sql_shop_order;
            $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);

            $max_count = array();
            $arr_hua_dan = array();
            $count_goods = 0;
            $count_order = 0;
            $html0 = "<table border='1' width='90%' align='center'>";
            $html0 .= "<tr><td>序号</td><td>序号</td><td>划单单号</td><td>划单商品</td><td>点位名称</td><td>客户地址</td><td>客户电话</td><td>线路</td><td>BD</td></tr>";
            foreach ($list_shop_order as $one) {
                //$sql_shop_order0 = "SELECT id,goodsid from ims_ewei_shop_order_goods where orderid = {$one['id']}  order by id desc";
                $sql_shop_order0 = "SELECT a.id,a.goodsid,b.title from ims_ewei_shop_order_goods a left join ims_ewei_shop_goods b on a.goodsid=b.id where a.orderid = {$one['id']}  order by a.id desc";
                $list_shop_order0 = $m_ShopGoodsModel->querySql($sql_shop_order0);
                $kk = 0;
                $arr1 = null;
                foreach ($list_shop_order0 as $two) {
                    $sql_shop_order1 = "SELECT id,goods_id from ims_member_order_change_log where ordersn = '{$one['ordersn']}' and goods_id = {$two['goodsid']} and count_old > count_new order by id desc";
                    $list_shop_order1 = $m_ShopGoodsModel->querySql($sql_shop_order1);
                    if (count($list_shop_order1) > 0) {
                        $count_goods++;
                        $kk++;
                        $arr1[] = $two['title'];
                    }
                }
                //
                if ($kk > 0) {
                    $max_count[] = $kk;
                    $arr_hua_dan[$one['ordersn']]['a'] = $arr1;
                    $arr_hua_dan[$one['ordersn']]['b'] = $one;
                    $count_order++;
                }
            }

            $hh = 0;
            //print_r($arr_hua_dan);exit;
            foreach ($arr_hua_dan as $kkk => $vvv) {
                //echo $one['b']['openid']."<br>";
                $hh++;
                $sql_potential_customer = "SELECT a.user_name,a.address,a.telphone,a.line_code,b.`name` from potential_customer a left join btj_admin_user b on a.service_id = b.user_id where xcx_openid = '{$vvv['b']['openid']}'  order by id desc";
                $list_potential_customer = $m_SignModel->querySql($sql_potential_customer);

                if (!empty($list_potential_customer)) {

                    $html0 .= "<tr><td>" . $str_day . "</td><td>" . $hh . "</td><td>" . $kkk . "</td><td>" . implode(',', $vvv['a']) . "</td><td>" . $list_potential_customer[0]['user_name'] . "</td><td>" . $list_potential_customer[0]['address'] . "</td><td>" . $list_potential_customer[0]['telphone'] . "</td><td>" . $list_potential_customer[0]['line_code'] . "</td><td>" . $list_potential_customer[0]['name'] . "</td></tr>";
                } else {
                    $html0 .= "<tr><td>" . $str_day . "</td><td>" . $hh . "</td><td>" . $kkk . "</td><td>" . implode(',', $vvv['a']) . "</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td></tr>";
                }

            }
            $html0 .= "</table>" . "<br>";
            echo $html0;
        }

    }

    /**
     * 于凯要，11.1号开始每日新增下单客户列表
     */
    public function index_yk6()
    {
        $m_SignModel = new SignModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $day_s = strtotime('2019-11-01');

        $sql_shop_order_all = "SELECT id,ordersn,openid,createtime from ims_ewei_shop_order where supplier_id = 461 and status > -1 group by openid order by id asc";
        $list_shop_order_all = $m_ShopGoodsModel->querySql($sql_shop_order_all);
        $arr_shop_order_all = array();
        foreach ($list_shop_order_all as $one) {
            if (!isset($arr_shop_order_all[$one['openid']])) {
                $arr_shop_order_all[$one['openid']] = $one['createtime'];
            }
        }

        $html0 = "<div align='center'>每日新增订单用户统计</div><br>";
        for ($i = 59; $i > -1; $i--) {
            $time_s = $day_s + 60 * 60 * 24 * $i;
            if ($time_s > time()) continue;

            $time_e = $time_s + 60 * 60 * 24 * 1;
            $str_day = date('Y-m-d', $time_s);
            $sql_shop_order = "SELECT id,ordersn,openid,createtime,address,status from ims_ewei_shop_order where createtime > {$time_s} and createtime < {$time_e} and supplier_id = 461 and status > -1 group by openid order by id desc";
            $list_shop_order = $m_ShopGoodsModel->querySql($sql_shop_order);

            $html0 .= "<table border='1' width='95%' align='center'>";
            $html0 .= "<tr><td>日期</td><td>客户类型</td><td>订单单号</td><td>订单状态</td><td>openid</td><td>点位名称</td><td>客户地址</td><td>客户电话</td><td>线路</td><td>BD</td></tr>";

            foreach ($list_shop_order as $kkk => $vvv) {
                if (isset($arr_shop_order_all[$vvv['openid']]) && $arr_shop_order_all[$vvv['openid']] == $vvv['createtime']) {
                    //获取此点位其他xcx_openid
                    $openids = $this->getOpenids($vvv['openid']);
                    $is_order = true;
                    if(count($openids) > 0){
                        $ordercount = Db::connect('db_mini_mall')
                            ->table('ims_ewei_shop_order')
                            ->where('createtime','<',$time_s)
                            ->where('openid','IN',$openids)
                            ->where('supplier_id',461)
                            ->where('status','>',-1)
                            ->count();
                        if($ordercount > 0) $is_order = false;
                    }
                    if($is_order){
                        $sql_potential_customer = "SELECT a.id,a.parent_id,a.user_name,a.address,a.telphone,a.line_code,b.`name`,a.service_id from potential_customer a left join btj_admin_user b on a.service_id = b.user_id where xcx_openid = '{$vvv['openid']}'  order by id desc limit 1";
                        $list_potential_customer = $m_SignModel->querySql($sql_potential_customer);
                        if($vvv['status'] == -1){
                            $status = '取消';
                        }elseif($vvv['status'] == 0){
                            $status = '待支付';
                        }elseif($vvv['status'] == 1){
                            $status = '已支付';
                        }else{
                            $status = '';
                        }
                        if (!empty($list_potential_customer)) {
                            $id_type = 0;
                            $str_html = '';
                            if ($list_potential_customer[0]['parent_id'] > 0) {
                                $sql_potential_customer1 = "SELECT a.id,a.parent_id,a.user_name,a.address,a.telphone,a.line_code,b.`name`,a.service_id from potential_customer a left join btj_admin_user b on a.service_id = b.user_id where id = '{$list_potential_customer[0]['parent_id']}' order by id desc";
                                $list_potential_customer1 = $m_SignModel->querySql($sql_potential_customer1);
                                $list_potential_customer = $list_potential_customer1;
                                $id_type = 1;
                                $str_html = " style='color: #60F060'";
                            }
                            $customer_url = 'https://btj.yundian168.com/biz/bd1/index.html#/particulars?id='.$list_potential_customer[0]['id'].'&user_id='.$list_potential_customer[0]['service_id'];
                            $html0 .= "<tr><td>" . $str_day . "</td><td>" . $id_type . "</td><td>" . $vvv['ordersn'] . "</td><td>".$status."</td><td{$str_html}>" . $vvv['openid'] . "</td><td><a href=".$customer_url." target='_blank'>" . $list_potential_customer[0]['user_name'] . "</a></td><td>" . $list_potential_customer[0]['address'] . "</td><td>" . $list_potential_customer[0]['telphone'] . "</td><td>" . $list_potential_customer[0]['line_code'] . "</td><td>" . $list_potential_customer[0]['name'] . "</td></tr>";
                        } else {
                            $address_fix = unserialize($vvv['address']);
                            $str_html = " style='color: #D06030'";
                            $html0 .= "<tr><td>" . $str_day . "</td><td>-1</td><td>" . $vvv['ordersn'] . "</td><td>".$status."</td><td{$str_html}>" . $vvv['openid'] . "</td><td>---</td><td>{$address_fix['address']}</td><td>{$address_fix['mobile']}</td><td>-</td><td>---</td></tr>";
                        }
                    }
                }
            }
            $html0 .= "</table>" . "<br>";

        }
        echo $html0;

    }

    //获取点位所有openid
    public function getOpenids($openid){

        $arr = Db::connect('db_mall_erp')
            ->table('potential_customer')
            ->field('id,parent_id')
            ->where('xcx_openid',$openid)
            ->find();
        $id = $arr['parent_id'] > 0 ? $arr['parent_id'] : $arr['id'];
        $openids = Db::connect('db_mall_erp')
            ->table('potential_customer')
            ->where('id',$id)
            ->whereOr('parent_id',$id)
            ->column('xcx_openid');
        $openidss = [];
        if($openids){
            $openidss = array_unique(array_filter($openids));
        }
        return $openidss;
    }

}
