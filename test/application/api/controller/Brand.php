<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\controller;

use think\Db;
use think\Exception;
use app\api\model\btjnew\Customer as CustomerModel;
use app\api\model\ydxq\Supplier as SupplierModel;
use app\api\model\ydxq\BbBrand as BbBrandModel;
use app\api\model\ydxq\GoodsCodeInfo as GoodsCodeInfoModel;
use app\api\model\ydxq\CustomerBrandCode as CustomerBrandCodeModel;
use app\api\model\btjnew\SupplierBrand as SupplierBrandModel;

class Brand extends BaseController {

    private $customer_model;
    private $supplier_model;
    private $supplier_brand;
    private $bb_brand_model;
    private $goods_code_info_model;
    private $customer_brand_code_model;
    public function __construct() {
        parent::__construct();
        //解决跨域问题
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET");

        $this->customer_model = new CustomerModel();
        $this->supplier_model = new SupplierModel();
        $this->supplier_brand = new SupplierBrandModel();
        $this->bb_brand_model = new BbBrandModel();
        $this->goods_code_info_model = new GoodsCodeInfoModel();
        $this->customer_brand_code_model = new CustomerBrandCodeModel();
    }

    //获取供应商的品牌列表
    public function agentBrandLists(){
        $param = $this->request->param();
        /*if(!isset($param['sup_id']) || empty($sup_id = intval($param['sup_id']))){
            return sdk_return('', 0, '缺少参数sup_id');
        }
        //获取当前店铺的的openid
        $shop_info = $this->supplier_model->getInfoPro([['id', '=', $sup_id]], ['openid']);
        if(empty($shop_info) || empty($openid = $shop_info['openid'])){
            return sdk_return('', 0, '获取店铺信息错误');
        }

        //获取当前openid的供应商id
        $agent_info = $this->customer_model->getInfoPro([['xcx_openid', '=', $openid]], ['id', 'parent_id']);
        if(empty($agent_info)){
            return sdk_return('', 0, '获取供应商信息错误');
        }
        $customer_id = empty($agent_info['parent_id']) ? $agent_info['id'] : $agent_info['parent_id'];*/

        if(!isset($param['customer_id']) || empty($customer_id = intval($param['customer_id']))){
            return sdk_return('', 0, '缺少参数customer_id');
        }

        //获取当前供应商的所有标记品牌
        $supplier_brands = $this->supplier_brand->getAllList([['customer_id', '=', $customer_id], ['is_sale', '=', 1]]);
        $supplier_brand_ids = array_column($supplier_brands, 'brand_id');
        //获取所有的品牌
        $brands = $this->bb_brand_model->getAllListPro([['id', 'in', $supplier_brand_ids]], ['id','b_name','custom_repeat_num'],['custom_repeat_num'=>'desc']);

        //获取每个品牌下的商品总数量
        $brand_names = array_column($brands, 'b_name');
        $where = [
            ['brand', 'in', $brand_names],
            ['is_valid', '=', 1],
            ['goods_name', '<>', '']
        ];
        $brands_code = $this->goods_code_info_model->getCustomCodeByBrandCount($where);
        $brands_code_tmp = [];
        foreach ($brands_code as $k => $v){
            if(isset($brands_code_tmp[$v['brand']])){
                $brands_code_tmp[$v['brand']] += 1;
            }else{
                $brands_code_tmp[$v['brand']] = 1;
            }
        }

        //获取当前点位标记在售的所有商品
        $custom_brands_code = $this->customer_brand_code_model->getCustomerBrandCodes([['customer_id', '=', $customer_id]]);
        $custom_brands_code_tmp = [];
        foreach ($custom_brands_code as $k => $v){
            $custom_brands_code_tmp[$v['brand_id']] = $v['num'];
        }

        //把每个品牌的商品数量、当前店铺标记的商品数量写入数组
        foreach ($brands as $k => $v){
            if(isset($brands_code_tmp[$v['b_name']])){
                $brands[$k]['goods_nums'] = $brands_code_tmp[$v['b_name']];
            }else{
                $brands[$k]['goods_nums'] = 0;
            }

            //当前店铺标记的商品数量写入数组
            if(isset($custom_brands_code_tmp[$v['id']])){
                $brands[$k]['mark_goods_nums'] = $custom_brands_code_tmp[$v['id']];
            }else{
                $brands[$k]['mark_goods_nums'] = 0;
            }
        }

        return sdk_return($brands, 1, '获取成功');
    }

    //获取品牌下的code
    public function getBrandCodes(){
        $param = $this->request->param();
        if(!isset($param['brand_id']) || empty($brand_id = intval($param['brand_id']))){
            return sdk_return('', 0, '缺少参数brand_id');
        }

        //获取供应商id
        /*if(!isset($param['sup_id']) || empty($sup_id = intval($param['sup_id']))){
            return sdk_return('', 0, '缺少参数sup_id');
        }
        //获取当前店铺的的openid
        $shop_info = $this->supplier_model->getInfoPro([['id', '=', $sup_id]], ['openid']);
        if(empty($shop_info) || empty($openid = $shop_info['openid'])){
            return sdk_return('', 0, '获取店铺信息错误');
        }
        //获取当前openid的供应商id
        $agent_info = $this->customer_model->getInfoPro([['xcx_openid', '=', $openid]], ['id', 'parent_id']);
        if(empty($agent_info)){
            return sdk_return('', 0, '获取供应商信息错误');
        }
        $customer_id = empty($agent_info['parent_id']) ? $agent_info['id'] : $agent_info['parent_id'];*/

        if(!isset($param['customer_id']) || empty($customer_id = intval($param['customer_id']))){
            return sdk_return('', 0, '缺少参数customer_id');
        }

        //获取当前品牌的品牌名
        $brand_info = $this->bb_brand_model->getInfoPro([['id', '=', $brand_id]], ['b_name']);
        if(empty($brand_info) || empty($brand_name = $brand_info['b_name'])){
            return sdk_return([], 1, '获取成功');
        }

        //分页的参数
        $page = isset($param['page']) ? $param['page'] : 1;//分页的参数 页码
        $page_num = isset($param['page_num']) ? $param['page_num'] : 4;//本页条数
        $limit_start = ($page - 1) * $page_num;

        $where = [
            ['brand', '=', $brand_name],
            ['is_valid', '=', 1],
            ['goods_name', '<>', '']
        ];

        //获取当前品牌下所有的code
        $code_goods = $this->goods_code_info_model->getCustomCodeByBrand($where, $limit_start, $page_num);

        //获取总数量
        $count = $this->goods_code_info_model->getCustomCodeByBrandCount($where);

        //获取当前code，当前店铺是否已标记为在售
        $codes = array_column($code_goods, 'code');
        $agent_codes_goods = $this->customer_brand_code_model->getAllList([['code', 'in', $codes], ['customer_id', '=', $customer_id]]);
        //dump($custom_codes);
        $agent_codes = array_column($agent_codes_goods, 'code');

        foreach ($code_goods as $k => $v){
            if(in_array($v['code'], $agent_codes)){
                $code_goods[$k]['is_sale'] = 1;
            }else{
                $code_goods[$k]['is_sale'] = 0;
            }
        }

        $return_data = [
            'count'     =>  count($count),
            'goods'     =>  $code_goods
        ];

        return sdk_return($return_data, 1, '获取成功');
    }

    //供应商标记品牌下的商品为在售
    public function custom_mark_sale(){
        $param = $this->request->param();
        if(!isset($param['code']) || empty($code = $param['code'])){
            return sdk_return('', 0, '缺少参数code');
        }

        if(!isset($param['brand_id']) || empty($brand_id = intval($param['brand_id']))){
            return sdk_return('', 0, '缺少参数brand_id');
        }

        if(!isset($param['status']) || empty($status = intval($param['status']))){
            return sdk_return('', 0, '缺少参数status');
        }

        //获取供应商id
        /*if(!isset($param['sup_id']) || empty($sup_id = intval($param['sup_id']))){
            return sdk_return('', 0, '缺少参数sup_id');
        }
        //获取当前店铺的的openid
        $shop_info = $this->supplier_model->getInfoPro([['id', '=', $sup_id]], ['openid']);
        if(empty($shop_info) || empty($openid = $shop_info['openid'])){
            return sdk_return('', 0, '获取店铺信息错误');
        }
        //获取当前openid的供应商id
        $agent_info = $this->customer_model->getInfoPro([['xcx_openid', '=', $openid]], ['id', 'parent_id']);
        if(empty($agent_info)){
            return sdk_return('', 0, '获取供应商信息错误');
        }
        $customer_id = empty($agent_info['parent_id']) ? $agent_info['id'] : $agent_info['parent_id'];*/

        if(!isset($param['customer_id']) || empty($customer_id = intval($param['customer_id']))){
            return sdk_return('', 0, '缺少参数customer_id');
        }

        //获取code信息
        $code_info = $this->goods_code_info_model->getInfo([['code', '=', $code]]);

        $custom_info = $this->customer_brand_code_model->getInfo([['code', '=', $code], ['customer_id', '=', $customer_id]]);

        //标记为在售
        if($status == 1){
            if(!empty($custom_info)){
                return sdk_return('', 1, '已标记为在售');
            }
            $data = [
                'code'          =>  $code,
                'goods_name'    =>  $code_info['goods_name'],
                'brand_id'      =>  $brand_id,
                'customer_id'   =>  $customer_id,
                'price'         =>  isset($param['price']) ? $param['price'] : 0,
                'create_time'   =>  time(),
                'update_time'   =>  time()
            ];

            //更新到在售信息表
            $id = $this->customer_brand_code_model->insertInfo($data);

            return sdk_return($id, 1, '已标记为在售');

        }else{//标记为未售
            if(empty($custom_info)){
                return sdk_return('', 1, '已取消标记');
            }

            $this->customer_brand_code_model->deleteMark([['code', '=', $code], ['customer_id', '=', $customer_id]]);
            return sdk_return('', 1, '已取消标记');
        }
    }
}