<?php


namespace app\api\controller;

use app\api\model\ydxq\ShopGoods as ShopGoodsModel;
use app\api\model\ydxq\BbSku as BbSkuModel;
use app\api\model\ydxq\BbGoodsItem as BbGoodsItemModel;
use app\api\model\ydxq\BbBrand as BbBrandModel;
use app\api\model\ydxq\BbCateBb as BbCateBbdModel;
use app\api\model\ydxq\BbPriceList as BbPriceListModel;
use app\api\model\ydxq\BbChannel as BbChannelModel;
use app\api\model\ydxq\ShopMemberCart as ShopMemberCartModel;
use app\api\model\ydxq\SupplierGoods as SupplierGoodsModel;
use app\api\model\ydxq\ShopGoodsCate as ShopGoodsCateModel;
use app\api\model\ydhl\BaseGoods as BaseGoodsModel;
use think\Config;
use think\DB;

class BbSku extends BaseController
{
    private $shop_goods_model;
    private $bb_sku_model;
    private $bb_goods_item_model;
    private $bb_brand_model;
    private $bb_cate_bb_model;
    private $bb_price_list_model;
    private $bb_channel_model;
    private $bb_member_cart_model;
    private $supplier_goods_model;
    private $shop_goods_cate_model;
    private $ydhl_base_goods_model;

    public function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET");

        parent::__construct();

        $this->shop_goods_model = new ShopGoodsModel();
        $this->bb_sku_model = new BbSkuModel();
        $this->bb_goods_item_model = new BbGoodsItemModel();
        $this->bb_brand_model = new BbBrandModel();
        $this->bb_cate_bb_model = new BbCateBbdModel();
        $this->bb_price_list_model = new BbPriceListModel();
        $this->bb_channel_model = new BbChannelModel();
        $this->bb_member_cart_model = new ShopMemberCartModel();
        $this->supplier_goods_model = new SupplierGoodsModel();
        $this->shop_goods_cate_model = new ShopGoodsCateModel();
        $this->ydhl_base_goods_model = new BaseGoodsModel();
    }

    //克隆商品
    public function clone_goods(){

        $data = $this->request_param;

        if(!isset($data['user_id']) || !in_array($data['user_id'], [297, 675])){
            sdk_return('', 0, '无克隆权限');
        }

        $sup_id = isset($data['sup_id']) ? intval($data['sup_id']) : 461;
        if(!isset($data['sale_pirce'])){
            sdk_return('', 0, '价格字段不能为空');
        }
        if(!isset($data['sku_id'])){
            sdk_return('', 0, 'sku_id不能为空');
        }
        $sku_id = $data['sku_id'];

        //获取商品名称
        if(!isset($data['goods_name'])){
            sdk_return('', 0, '商品名称不能为空');
        }

        //1:活动商品只给特定人看并分享
        $is_activity = isset($data['is_activity']) ? intval($data['is_activity']) : 0;

        //最大限购数
        $bb_end_count = isset($data['bb_end_count']) ? intval($data['bb_end_count']) : 0;
        //商品起购数
        $bb_start_count = isset($data['bb_start_count']) ? intval($data['bb_start_count']) : 1;
        $bb_start_count = $bb_start_count == 0 ? 1 : $bb_start_count;
        //下单时步长
        /*$bb_step = isset($data['bb_step']) ? intval($data['bb_step']) : 1;
        $bb_step = $bb_step == 0 ? 1 : $bb_step;*/

        $bb_step = $bb_start_count;//步时长等于商品起订数

        //验证当前sku在当前店铺商品是否已经存在
        $shop_goods_info = $this->shop_goods_model->getInfo([['skuid', '=', $sku_id], ['sup_id', '=', $sup_id]]);
        if(count($shop_goods_info)){
            //sdk_return('', 0, '店铺已创建当前商品，请勿重复创建');
            //更新数据
            $update_data = [
                'sale_pirce'        =>  $data['sale_pirce'],
                'title'             =>  trim($data['goods_name']),
                'bb_end_count'      =>  $bb_end_count,//最大限购数
                'bb_start_count'    =>  $bb_start_count,//最低购买数
                'deleted'           =>  0,
                'bb_step'           =>  $bb_step,//下单时步长
                'status'            =>  1,
                'updatetime'        =>  time(),
            ];

            $update_rst = $this->shop_goods_model->updateInfo($shop_goods_info['id'], $update_data);

            //修改supplier_goods价格
            $this->supplier_goods_model->updateInfoPro(['goods_id'=>$shop_goods_info['id']], ['supplier_price'=>$data['sale_pirce']]);

            if($update_rst){
                sdk_return('', 1, '克隆更新成功');
            }

            sdk_return('', 0, '克隆更新失败');
        }

        //获取sku信息
        $sku_info = $this->bb_sku_model->getInfo(['id'=>$sku_id]);

        if(!$sku_info){
            sdk_return('', 0, '获取sku商品信息错误');
        }

        //获取item详情
        $item_info = $this->bb_goods_item_model->getInfo(['hbsj_item_id'=>$sku_info['hbsj_item_id']]);
        if(!$item_info){
            sdk_return('', 0, '获取item详情错误');
        }

        //获取当前克隆商品的分类名称
        $bb_cate_info = $this->bb_cate_bb_model->getInfo(['id'=>$item_info['cate_bb1']]);
        //获取当前店铺是否存在当前分类
        $cate_where = [
            ['c_name', '=', $bb_cate_info['c_name']],
            ['platform_id', '=', $sup_id],
        ];
        $cate_info = $this->shop_goods_cate_model->getInfo($cate_where);
        if(!count($cate_info)){
            //如果不存在，创建一个新分类
            $cate_data = [
                'platform_id'       =>  $sup_id,
                'c_name'            =>  $bb_cate_info['c_name'],
                'status'            =>  1,
                'addtime'           =>  time(),
                'modtime'           =>  time(),
            ];
            $ccate = $this->shop_goods_cate_model->insertInfo($cate_data);
        }else{
            $ccate = $cate_info['id'];
        }

        //code单条码
        $goods_code_arr = explode(',', $sku_info['code_list']);
        $goods_code = empty($goods_code) ? '' : $goods_code_arr[ 0 ];

        //组装数据
        $shop_goods_data = [
            'sup_id'            =>  $sup_id,
            //'title'             =>  $sku_info['sku_name'],
            'title'             =>  trim($data['goods_name']),
            'ccate'             =>  $ccate,//分类id
            'uniacid'           =>  4,
            'goods_code_list'   =>  $sku_info['code_list'],
            'goods_code'        =>  $goods_code,
            'brand'             =>  $item_info['brand_id'],
            'thumb'             =>  $item_info['img'],
            'total'             =>  999999,
            'createtime'        =>  time(),
            'updatetime'        =>  time(),
            'skuid'             =>  $sku_info['id'],
            'hbsj_brand_id'     =>  $item_info['hbsj_brand_id'],
            'hbsj_sku_id'       =>  $sku_info['hbsj_sku_id'],
            'brand_id'          =>  $item_info['brand_id'],
            'bb_cate1'          =>  $item_info['cate_bb1'],
            'bb_cate2'          =>  $item_info['cate_bb2'],
            'sale_pirce'        =>  $data['sale_pirce'],
            'smg_price'         =>  $data['sale_pirce'],
            'smg_total'         =>  999999,
            'bb_end_count'      =>  $bb_end_count,//最大限购数
            'bb_start_count'    =>  $bb_start_count,//最低购买数
            'bb_step'           =>  $bb_step,//下单时步长
            'is_activity'       =>  $is_activity,//活动商品只给特定人看并分享
        ];

        //追加商品到goods表
        $goods_id = $this->shop_goods_model->insertInfo($shop_goods_data);

        if(!$goods_id){
            sdk_return('', 0, '克隆失败，请稍后重试,插入父表数据失败');
        }
        //追加到子表
        $supplier_goods = [
            'supplier_id'       =>  $sup_id,
            'goods_id'          =>  $goods_id,
            'supplier_price'    =>  $data['sale_pirce'],
            'status'            =>  1
        ];
        $rst = $this->supplier_goods_model->insertInfo($supplier_goods);
        if(!$rst){
            sdk_return('', 0, '克隆失败，请稍后重试。插入子表数据失败');
        }

        //写入erp
        $data=[
            'goods_code'    =>  $goods_id,
            'goods_name'    =>  trim($data['goods_name']),
            'goods_pic'     =>  empty(imgSrc($item_info['img'])) ?  'none.jpg' : imgSrc($item_info['img'])
        ];
        $postdata = http_build_query($data);
        $opts = array('http' =>
            array( 'method'  => 'POST','header'  => 'Content-type: application/form-data', 'content' => $postdata ) );
        $url='http://ydxqtptest.yundian168.com/api/erp/goods_upload';
        $context = stream_context_create($opts);
        $request_rst = file_get_contents($url, false, $context);
        $request_arr = json_decode($request_rst, true);
        if($request_arr['status'] == 0){//删除当前商品
            //删除商品
            $this->shop_goods_model->updateInfo($goods_id, ['status'=>0, 'skuid'=>'', 'deleted'=>1]);
            sdk_return('', 0, '克隆失败，同步到erp失败:'.$request_arr['msg']);
        }

        $return_data = [
            'goods_id'  =>  $goods_id
        ];
        sdk_return($return_data, 1, '克隆成功');
    }

    //创建一个新的sku
    public function create_sku(){
        $data = $this->request_param;
        if(!isset($data['item_id']) || empty(intval($data['item_id']))){
            sdk_return('', 0, '缺少参数item_id');
        }
        $item_id = intval($data['item_id']);

        if(!isset($data['unit_count']) || empty(intval($data['unit_count']))){
            sdk_return('', 0, '缺少参数unit_count');
        }
        $unit_count = intval($data['unit_count']);

        //获取item详情
        $item_info = $this->bb_goods_item_model->getInfo(['id'=>$item_id]);
        if(empty($item_info)){
            sdk_return('', 0, '无效的参数item_id');
        }

        //验证当前item是否存在当前要创建的规格
        $sku_info = $this->bb_sku_model->getInfo(['goods_id'=>$item_id, 'unit_count'=>$unit_count]);
        if(count($sku_info)){
            sdk_return('', 0, '当前规格已创建，请勿重复创建');
        }

        //获取当前item下的所有sku barcode
        $skus = $this->bb_sku_model->getAllListPro(['goods_id'=>$item_id], ['code_list_new', 'spec', 'unit_count']);
        $spec = '';
        $code_list_new = '';
        foreach ($skus as $k => $v){
            $code_list_new .= $v['code_list_new'] . ',';

            if($v['unit_count'] != 1){
                $spec = $v['spec'];
            }
        }
        $spec_group = explode('/', $spec);

        $code_list_new_str = implode(',', array_unique(explode(',', trim($code_list_new, ','))));

        $sku_data = [
            'goods_id'          =>  $item_id,
            'sku_name'          =>  $item_info['goods_name'],
            'sku_img'           =>  $item_info['img'],
            'unit_name'         =>  trim($item_info['unit'], '1' ),
            'unit_count'        =>  $unit_count,
            'comefrom'          =>  98,//自建sku
            'createtime'        =>  time(),
            'code_list_new'     =>  $code_list_new_str,
            'is_used'           =>  1,
            'spec'              =>  $item_info['content'] . '*' . $unit_count.trim($item_info['unit'], '1' ) . '/' . $spec_group[count($spec_group) - 1],
            'code_list_pro'     =>  $code_list_new_str
        ];

        //添加到sku表
        $sku_id = $this->bb_sku_model->insertInfo($sku_data);
        if(!$sku_id){
            sdk_return('', 0, '创建sku失败，请稍后重试');
        }

        sdk_return(['sku_id'=>$sku_id], 1, '创建成功');
    }

    //商品上下架
    public function switch_status_by_sku(){
        $data = $this->request_param;
        if(!isset($data['sku_id'])){
            sdk_return('', 0, '缺少参数sku_id');
        }

        if(!isset($data['user_id']) || $data['user_id'] != 297){
            sdk_return('', 0, '无克隆权限');
        }

        if(!isset($data['status'])){
            sdk_return('', 0, '缺少参数status');
        }
        $rst = $this->shop_goods_model->updateInfoPro(['skuid'=>$data['sku_id'], 'sup_id'=>461], ['status'=>intval($data['status'])]);
        sdk_return('', 1, '状态修改成功');
    }

    //通过barcode获取item
    public function get_item_by_barcode(){
        $data = $this->request_param;
        if(!isset($data['barcode'])){
            sdk_return('', 0, '缺少参数barcode');
        }
        $barcode = trim($data['barcode']);

        //获取当前barcode是否在sku中存在
        $skus = $this->bb_sku_model->getAllListPro([['code_list_new', '%like%', $barcode], ['is_users', '=', 1]], ['goods_id']);
        $item_ids = array_unique(array_column($skus, 'goods_id'));
        //如果存在itemids，获取所有的item
        if(count($item_ids)){
            $items = $this->bb_goods_item_model->getAllListPro([['id', 'in', $item_ids]]);
            sdk_return('', 1, $items);
        }

        //不存在item，调用京东60w词库
        $base_goods_info = $this->ydhl_base_goods_model->getInfoPro([['code', '=', $barcode], ['success', '=', 2]], ['category2', 'trademark', 'goods_name']);
        if(!count($base_goods_info)){
            //调用京东接口
            $jd_url = 'https://way.jd.com/showapi/barcode';
            $jd_appkey = '45255b8140311586cbd90b78171fe6d0';
            $jd_json_c = $this->curl_get($jd_url . '?code=' . $barcode . '&appkey=' . $jd_appkey);
            $jd_json_a = str_replace(array('/*','*/','#','--'), '*', $jd_json_c);
            $jd_json = str_replace("'", '’', $jd_json_a);
            $goods_arr = json_decode($jd_json, true);

            //如果请求京东接口失败
            if (!$goods_arr['charge']) {
                sdk_return('', 0, '京东api调用失败');
            }

            $goods_data = $goods_arr['result']['showapi_res_body'];
            $goods_trademark = str_replace("'", '’', trim($goods_data['trademark']));;//品牌名称
            $goods_name = $goods_data['goodsName'];//商品名称
            //插入品牌
            $brand_name_new = str_replace("'", '’', $goods_trademark);
            if(empty($brand_name_new)){
                sdk_return('', 0, '京东api未获取到品牌信息');
            }
            //获取二级分类
            if (!empty($goods_type)) {
                $category_arr = explode('>>', $goods_type);
                foreach ($category_arr as $cate_k => $cate_v) {
                    $cate_key = $cate_k + 1;
                    $category['category' . $cate_key] = $cate_v;
                }
            }
            $jd_category2 = !empty($category['category2']) ? $category['category2'] : '';//二级分类
            $base_goods_info = ['category2'=>$jd_category2, 'trademark'=>$goods_trademark, 'goods_name'=>$goods_name];
        }

        //调用分词接口

    }
}