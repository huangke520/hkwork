<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\controller;

use app\api\model\ydxq\Supplier as SupplierModel;
use app\api\model\btjnew\Sign as btjModel;

use think\Db;
use think\Exception;

class Code extends BaseController {
    protected $m_SupplierModel;
    protected $m_btjModel;

    public function __construct() {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET");

        $this->m_SupplierModel = new SupplierModel();
        $this->m_btjModel = new btjModel();
    }

    /**
     * 单码扫描
     */
    public function singleCodeScan(){
        $param = $this->request_param;
        $codes = !empty($param['code']) ? $param['code'] : sdk_return('', 6, '缺少参数1');
        $codes = rtrim($codes, ',');
        $custom_id = !empty($param['custom_id']) ? $param['custom_id'] : sdk_return('', 6, '缺少参数2');
        $custom_name = !empty($param['custom_name']) ? $param['custom_name'] : sdk_return('', 6, '缺少参数3');
        $upload_type = !empty($param['upload_type']) ? $param['upload_type'] : sdk_return('', 6, '缺少参数6');
        $bat_no = !empty($param['bat_no']) ? $param['bat_no'] : sdk_return('', 6, '缺少参数4');
        $user = !empty($param['user']) ? $param['user'] : sdk_return('', 6, '缺少参数5');
        $bat_no = strtotime(date('Y-m-d H:i:s', $bat_no));
        $now_time = time();
        $now_date = date('Y-m-d');

        /*if($upload_type == 'upload'){
            $file = fopen($_SERVER['DOCUMENT_ROOT'] . "/upload/code/". ($custom_id . date('YmdHis')) .".txt","a+");
            fwrite($file, $codes);
            fwrite($file, "======================\r\n");
            fclose($file);
        }*/
        $codelist = explode(',', $codes);
        foreach ($codelist as $code){
            /*写入扫描日志*/
            $allowScan = 1;
            if($upload_type == 'upload'){//全部提交
                $code_scan = Db::connect('db_mini_mall')->name('ims_goods_code_scan')->where('user', $user)->where('code', $code)->where('potential_id',$custom_id)->where('bat_no',$bat_no)->find();
                if($code_scan){
                    $allowScan = 0;
                }
            }

            if($allowScan == 1){
                $param = ['user' => $user, 'code' => $code, 'potential_id' => $custom_id,  'potential_name' => $custom_name, 'bat_no' => $bat_no,
                    'scan_time' => $bat_no, 'scan_date' => $now_date, 'create_time' => $now_time, 'is_vaild' => 0
                ];
                $scan_id = Db::connect('db_mini_mall')->name('ims_goods_code_scan')->insertGetId($param);
            }
            /*写入扫描日志*/

            $code_info = Db::connect('db_mini_mall')->name('ims_goods_code_info')->where('code', $code)->where('potential_id',$custom_id)->find();
            if($code_info){
                continue;
                //sdk_return('', 0, 'success');
            }

            $this->_curl_get("https://ydxqtp.yundian168.com/api/JdBaseGoods/get_brand_by_barcode");
            $base_goods = Db::connect('db_mini_mall')->name('ims_yd_base_goods')->where('code', $code)->find();
            if(empty($base_goods)){
                continue;
                //sdk_return('', 0, 'success');
            }

            $this->m_btjModel->executeSql("update potential_customer set code_nums = code_nums + 1 where id = {$custom_id} ");

            if($allowScan == 1) {
                $this->m_SupplierModel->executeSql("update ims_goods_code_scan set is_vaild = 1 where id = {$scan_id} ");
            }

            $param = ['code' => $code, 'add_time' => date('Y-m-d'), 'c_time' => time(), 'potential_id' => $custom_id, 'is_valid' => 1, 'potential_name' => $custom_name,
                'goods_name' => $base_goods['goods_name'], 'brand' => $base_goods['trademark']];
            Db::connect('db_mini_mall')->name('ims_goods_code_info')->insert($param);

            $goods_code = Db::connect('db_mini_mall')->name('ims_goods_code')->where('code', $code)->find();
            if($goods_code){
                continue;
                //sdk_return('', 0, 'success');
            }

            $param = ['code' => $code, 'type' => $base_goods['type'], 'status' => 0,  'success' => 0, 'num' => 0, 'jd_name' => $base_goods['goods_name'],
                'factory' => $base_goods['factory'], 'spec' => $base_goods['spec'], 'price' => $base_goods['price'], 'brand' => $base_goods['trademark'],
                'img' => $base_goods['img'], 'is_img' => empty($base_goods['img']) ? 0 : 1, 'inter_category' => $base_goods['category4'],
                'mall_category' => $base_goods['mall_category'], 'mall_category_id' => $base_goods['mall_category_id'], 'addtime' => time(),
            ];
            Db::connect('db_mini_mall')->name('ims_goods_code')->insert($param);
        }

        sdk_return('', 1, 'success');
    }

    private function _curl_get($url) {
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