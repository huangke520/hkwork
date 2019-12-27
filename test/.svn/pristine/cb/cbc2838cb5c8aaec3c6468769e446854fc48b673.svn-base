<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\controller;

use think\Db;

class Timer extends BaseController {

    public function __construct() {
        parent::__construct();
    }

    //清洗potential_customer_data数据
    /*
    1.1客户初步盘点
    将有开放供应链的超市划分为以下等级：
    S ：25及以上，50%以上分类有经营,有雇员
    A ：25及以上，50%以上分类有经营
    B : 15-24  ，50%以上分类有经营
    C : 10-14  ，50%及以上分类有经营
    D : 5-9    ，30%及以上分类有经营
    0 :
    */
    public function index_zt18(){
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $db_btj_config = 'db_btj_new';
        $time = time();

        $sql_customer_data = "SELECT * from potential_customer_data where id > 0";
        $list_customer_data = DB::connect($db_btj_config)->query($sql_customer_data);
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
            print_r($one_data);
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
            Db::connect($db_btj_config)->query("update potential_customer_data set score_level = '{$score_level}',update_time={$time} where id = {$one['id']}");

        }
    }
}