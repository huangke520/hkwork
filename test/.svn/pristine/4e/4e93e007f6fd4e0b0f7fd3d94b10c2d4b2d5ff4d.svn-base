<?php

namespace app\api\controller;

use app\api\model\ydxq\Supplier;
use app\api\model\ydxq\ShopGoods as ShopGoodsModel;
use app\api\model\ydxq\Wxa as WxaModel;

class Wxa extends BaseController
{
    private $appid = 'wxc197083f4a7158ec';
    private $secret = 'daed15af5db5f3e740af3a8ccc5c7dd8';

    private $supplier_model;
    private $shop_shop_goods_model;
    private $wxa_model;
    public function __construct()
    {
        parent::__construct();
        $this->supplier_model = new Supplier();
        $this->shop_shop_goods_model = new ShopGoodsModel();
        $this->wxa_model = new WxaModel();
    }

    //微信检测
    public function sec_check(){
        //获取商品详情
        $goods = $this->shop_shop_goods_model->randSec([['thumb', '<>', '']]);

        foreach ($goods as $k => $v){
            //内容同步
            $content_request_data = $this->curl_msg_sec_check($v['title']);

            //图片同步
            $img_request_data = $this->curl_img_sec_check(imgSrc($v['thumb']));

            //查询是否已经创建同步日志
            $wxa_content_log = $this->wxa_model->getInfo(['type'=>1, 'relation_id'=>$v['id'], 'check_type'=>1]);
            $wxa_img_log = $this->wxa_model->getInfo(['type'=>1, 'relation_id'=>$v['id'], 'check_type'=>2]);

            if(!$wxa_content_log){
                $content_reuqest_log = [
                    'type'          =>  1,
                    'relation_id'   =>  $v['id'],
                    'check_type'    =>  1,
                    'auth_content'  =>  $v['title'],
                    'status'        =>  $content_request_data['status'],
                    'return_content'=>  json_encode($content_request_data['return_arr']),
                    'creattime'     =>  time()
                ];
                $this->wxa_model->insertInfo($content_reuqest_log);

                $img_reuqest_log = [
                    'type'          =>  1,
                    'relation_id'   =>  $v['id'],
                    'check_type'    =>  2,
                    'auth_content'  =>  imgSrc($v['thumb']),
                    'status'        =>  $img_request_data['status'],
                    'return_content'=>  json_encode($img_request_data['return_arr']),
                    'creattime'     =>  time()
                ];
                $this->wxa_model->insertInfo($img_reuqest_log);
            }else{
                $content_reuqest_log = [
                    'auth_content'  =>  $v['title'],
                    'status'        =>  $content_request_data['status'],
                    'return_content'=>  json_encode($content_request_data['return_arr']),
                    'updatetime'    =>  time(),
                ];
                $this->wxa_model->updateInfo($wxa_content_log['id'], $content_reuqest_log);

                $img_reuqest_log = [
                    'auth_content'  =>  imgSrc($v['thumb']),
                    'status'        =>  $img_request_data['status'],
                    'return_content'=>  json_encode($img_request_data['return_arr']),
                    'updatetime'    =>  time()
                ];
                $this->wxa_model->updateInfo($wxa_img_log['id'], $img_reuqest_log);
            }

        }
    }

    //微信文字敏感内容检测
    public function curl_msg_sec_check($content = ''){
        /*global $_GPC;
        if(!isset($_GPC['content'])){
            return ['error'=>'1','msg'=>'invalid params content'];
        }
        $content =
        */

        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=' . $access_token;

        $return_json = $this->http_request($url, json_encode(['content'=>$content]));

        $return_arr = json_decode($return_json, true);

        if($return_arr['errcode'] == 0){
            return ['error'=>'0','msg'=>'内容通过监测','status'=>1, 'return_arr'=>$return_arr];
        }else if($return_arr['errcode'] == 87014){
            return ['error'=>87014, 'msg'=>'内容含有违法违规内容','status'=>2, 'return_arr'=>$return_arr];
        }else{
            return ['error'=>$return_arr['errcode'], 'msg'=>'微信请求错误:'.$return_arr['errmsg'], 'status'=>3, 'return_arr'=>$return_arr];
        }
    }

    //校验一张图片是否含有违法违规内容。
    public function curl_img_sec_check($img = ''){
        //$img = 'http://oss.yundian168.com/ydxq/img/system/goods/933/5d67e01c176a6c9e7b070adcb9aad53d.jpg';
        //ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; GreenBrowser)');
        //$img = file_get_contents($img);
        $img = $this->http_request($img);
        $filePath = '../runtime/log/'.date('Ymd').rand(1000, 9999).'.jpg';
        file_put_contents($filePath, $img);
        $obj = new \CURLFile(realpath($filePath));
        $obj->setMimeType("image/jpeg");
        $file['media'] = $obj;
        //var_dump($file);die;
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/wxa/img_sec_check?access_token=' . $access_token;
        $return_json = $this->http_request($url, $file);
        $return_arr = json_decode($return_json, true);
        //unlink($filePath);//删除临时图片
        if($return_arr['errcode'] == 0){
            return ['error'=>'0','msg'=>'图片通过微信监测', 'status'=>1, 'return_arr'=>$return_arr];
        }else if($return_arr['errcode'] == 87014){
            return ['error'=>87014, 'msg'=>'图片未通过审核，含有违法违规内容','status'=>2, 'return_arr'=>$return_arr];
        }else{
            return ['error'=>$return_arr['errcode'], 'msg'=>'微信请求错误:'.$return_arr['errmsg'],'status'=>3, 'return_arr'=>$return_arr];
        }
    }

    public function getAccessToken(){

        $cacheKey = 'wxa:access_token';
        $cacheData = cache($cacheKey);
        $cacheData = json_decode($cacheData, true);
        if (!empty($cacheData) && !empty($cacheData['token']) && time() < $cacheData['expire']) {
            $access_token = $cacheData['token'];
        }
        if(isset($access_token)){
            //判断access_token是否已失效
            $url = 'https://api.weixin.qq.com/datacube/getweanalysisappidmonthlyretaininfo?access_token=' . $access_token;
            $param = [
                'begin_date'    =>  20190901,
                'end_date'      =>  20190927
            ];
            $check_json = $this->http_request($url, json_encode($param));
            $check_arr = json_decode($check_json, true);

            //如果token没有失效
            if(isset($check_arr['errcode']) && $check_arr['errcode'] != 40001){
                return $access_token;
            }
        }

        //重新获取access_token
        $request_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret;
        $return_json = $this->http_request($request_token_url);
        $request_arr = json_decode($return_json, true);
        $access_token = $request_arr['access_token'];

        $record['token'] = $access_token;
        $record['expire'] = time() + $request_arr['expires_in'] - 200;
        cache($cacheKey, json_encode($record));
        return $access_token;
    }

    private function http_request($url, $data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}