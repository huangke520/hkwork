<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-26
 * Time: 19:53
 */

namespace app\api\controller;

use app\api\controller\BaseController;

use app\api\model\ydxq\Supplier;
use app\api\model\ydxq\SupplierCreateImage;

use library\think\Image;

use library\Oss;
use library\Spreadcode;
use OSS\Core\OssException;
use think\facade\Config;

class Share extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // 创建小程序海报
    public function create_poster()
    {
        $sup_id = $this->request->post('sup_id');
        if (empty($sup_id)) {
            return sdk_return([],0,'sup_id不能为空');
        }
//        $sup_id = 461;
        $supplierModel = new Supplier();
        $supplierCreateModel = new SupplierCreateImage();

        // 店铺信息
        $info = $supplierModel->getInfoPro(['id'=>$sup_id],['name','phone','address','village','purpose_server']);
        $name = !empty($info['name']) ? $info['name'] : ''; // 店铺名称
        $business_scope = '酒水香烟 烟酒糖茶 日常百货 海外优品 引用好水 文具工具';                                     // 经营范围
        $purpose_server = !empty($info['$purpose_server']) ? $info['$purpose_server'] : '购物送到家 服务你我他';;  // 服务宗旨
        $village = !empty($info['village']) ? $info['village'] : '';;   // 小区名称
        $phone = !empty($info['phone']) ? $info['phone'] : '';;         // 电话
        $address = !empty($info['address']) ? $info['address'] : '';;   // 地址

        $create_data['info'] = md5($name.$business_scope.$purpose_server.$village.$phone.$address);

        // 生成小程序海报路径
        $image_local_path = './upload/images/'.'poster_'.$sup_id.'.png';
        // 背景图路径
        $img = __DIR__ . '/../../../public/static/images/poster_bg.png';


            // 字体路径
        $font_path = __DIR__ . '/../../../public/static/font/msyh.ttf';

        $sup_create_img_res = $supplierCreateModel->getInfoPro(['sup_id'=>$sup_id,'type'=>1]);

        // 从未生成过
        if (empty($sup_create_img_res)) {
            // 小程序二维码路径
            $weixin_code = $this->get_wexin_app_code($sup_id);
            $this->saveImg($weixin_code['data']);
            $QRcode = __DIR__ . '/../../../public/upload/images/spreadcode_'.$sup_id.'.png';
            // 创建图片
            $image = Image::open($img);
            $image->water($QRcode,[220,700]);                                                             // 小程序二维码
            $image->text($name,$font_path,25,'#ffffff',2,[0,25]);                       // 店铺名称
            $image->text($business_scope,$font_path,17,'#515366',2,[0,158]);            // 经营范围
            $image->text($purpose_server,$font_path,25,'#ffffff',5,[0,118]);            // 服务宗旨
            $image->text('社区：'.$village.' 电话：'.$phone,$font_path,16,'#4b4b4b',5,[0,400]); // 小区+电话
            $image->text('地址：'.$address,$font_path,16,'#4b4b4b',5,[0,435]);     // 地址
            $image->save($image_local_path);

            // 上传到阿里云OSS
            $aliyun_oss_config = Config::get('config.aliyun_oss');
            $bucket = $aliyun_oss_config['Bucket'];
            $fileName = 'ydxq/img/system/share/share/'.date("Ymd").'/'.'poster_'.$sup_id.'.png';
            try{
                $oss = new Oss($aliyun_oss_config['KeyId'],$aliyun_oss_config['KeySecret'],$aliyun_oss_config['Endpoint'],true);
                $result = $oss::ali_oss()->uploadFile($bucket,$fileName,$image_local_path);
                /*组合返回数据*/
                $arr = [
                    'oss_url' => $result['info']['url'],    // 上传资源地址
                    'relative_path' => $fileName            // 数据库保存名称(相对路径)
                ];
            } catch (OssException $e){
                return sdk_return([],0,$e->getMessage());
            }
            $add_data['sup_id'] = $sup_id; // 店铺ID
            $add_data['type'] = 1; // 1：海报；2：店铺
            $add_data['info'] = $create_data['info'];
            $add_data['oss_img'] = $fileName;
            $add_data['createtime'] = get_time();
            $add_data['updatetime'] = get_time();
            // 更新数据
            $res = $supplierCreateModel->insertInfo($add_data);
            if ($res) {
                unlink($image_local_path);
                unlink('./upload/images/spreadcode_'.$sup_id.'.png');
                return sdk_return(imgSrc($fileName),1,'success');
            }
        }


        // 已经生成过，且生成内容一致，直接返回图片
        if (!empty($sup_create_img_res) && $sup_create_img_res['info'] == $create_data['info']) {
            return sdk_return(imgSrc($sup_create_img_res['oss_img']),1,'success');
        }

        // 已经生成过，内容不一致，重新生成
        if(!empty($sup_create_img_res) && $sup_create_img_res['info'] != $create_data['info']) {
            //2019-09-18 flq
            // 小程序二维码路径
            $weixin_code = $this->get_wexin_app_code($sup_id);
            $this->saveImg($weixin_code['data']);
            $QRcode = __DIR__ . '/../../../public/upload/images/spreadcode_'.$sup_id.'.png';
            // 创建图片
            $image = Image::open($img);
            $image->water($QRcode,[220,700]);                                                             // 小程序二维码
            $image->text($name,$font_path,25,'#ffffff',2,[0,25]);                       // 店铺名称
            $image->text($business_scope,$font_path,17,'#515366',2,[0,158]);            // 经营范围
            $image->text($purpose_server,$font_path,25,'#ffffff',5,[0,118]);            // 服务宗旨
            $image->text('社区：'.$village.' 电话：'.$phone,$font_path,16,'#4b4b4b',5,[0,400]); // 小区+电话
            $image->text('地址：'.$address,$font_path,16,'#4b4b4b',5,[0,435]);     // 地址
            $image->save($image_local_path);

            // 上传到阿里云OSS
            $aliyun_oss_config = Config::get('config.aliyun_oss');
            $bucket = $aliyun_oss_config['Bucket'];
            $fileName = 'ydxq/img/system/share/share/'.date("Ymd").'/'.'poster_'.$sup_id.'.png';
            try{
                $oss = new Oss($aliyun_oss_config['KeyId'],$aliyun_oss_config['KeySecret'],$aliyun_oss_config['Endpoint'],true);
                $result = $oss::ali_oss()->uploadFile($bucket,$fileName,$image_local_path);
                /*组合返回数据*/
                $arr = [
                    'oss_url' => $result['info']['url'],    // 上传资源地址
                    'relative_path' => $fileName            // 数据库保存名称(相对路径)
                ];
            } catch (OssException $e){
                return sdk_return([],0,$e->getMessage());
            }
            $update_data['info']        = $create_data['info'];
            $update_data['oss_img']     = $fileName;
            $update_data['updatetime']  = get_time();
            // 更新数据
            $res = $supplierCreateModel->updateInfo($sup_create_img_res['id'],$update_data);
            if ($res !== false) {
                unlink($image_local_path);
                return sdk_return(imgSrc($update_data['oss_img']),1,'success');
            }
        }


        return sdk_return([],0,'error');
//        return sdk_return('http://oss.yundian168.com/ydxq/img/system/share/share/20190826/poster.png',1,'success');

    }


    // 创建店铺分享
    public function create_share_shop()
    {
        $sup_id = $this->request->post('sup_id');
        if (empty($sup_id)) {
            return sdk_return([],0,'sup_id不能为空');
        }
//        $sup_id = 419;
        $supplierModel = new Supplier();
        $supplierCreateModel = new SupplierCreateImage();

        // 店铺信息
        $info = $supplierModel->getInfoPro(['id'=>$sup_id],['name','avatar','phone','address','village','purpose_server']);
        $name = !empty($info['name']) ? $info['name'] : ''; // 店铺名称
        $purpose_server = !empty($info['$purpose_server']) ? $info['$purpose_server'] : '购物送到家 服务你我他1';;  // 服务宗旨
        $village = !empty($info['village']) ? $info['village'] : '';;   // 小区名称
        $phone = !empty($info['phone']) ? $info['phone'] : '';;         // 电话

        $create_data['info'] = md5($name.$purpose_server.$village.$phone);

        // 生成店铺本地路径
        $image_local_path = './upload/images/'.'shop_share_'.$sup_id.'.png';
        // 背景图路径
        $img = __DIR__ . '/../../../public/static/images/share_shop_bg.png';

        // 右侧图片
//        $left_img = __DIR__ . '/../../../public/static/images/right_img.png';
        // 字体路径
        $font_path = __DIR__ . '/../../../public/static/font/msyh.ttf';

        $sup_create_img_res = $supplierCreateModel->getInfoPro(['sup_id'=>$sup_id,'type'=>2]);

        // 从未生成过
        if (empty($sup_create_img_res)) {
            // 处理店主头像
            $this->userIconSave($info['avatar'],$sup_id);
            // 店主头像
            $header_img_path = __DIR__ . '/../../../public/upload/images/avatar_original_'.$sup_id.'.png';
            if(!file_exists($header_img_path)) {
                return sdk_return('',0,'用户头像获取失败');
            }
            $header_save_path = './upload/images/'.'avatar_thumb_'.$sup_id.'.png';
            // 处理头像
            $this->get_thumb_img(38,38,$header_img_path,$header_save_path);
            // 创建图片
            $image = Image::open($img);
            $image->water($header_save_path,[23,48]);  // 头像
//        $image->water($left_img,[300,120]);         // 右侧图
            $image->text($name,$font_path,20,'#ffffff',1,[65,55]);
            $image->text('烟酒茶糖 冷饮批发 零食百货',$font_path,16,'#515366',1,[20,165]);
            $image->text('热销果品 日杂百货 文教用品',$font_path,16,'#515366',1,[20,195]);
            $image->text($purpose_server,$font_path,20,'#ffffff',2,[0,270]);
            $image->text($phone,$font_path,17,'#8B8C96',1,[55,340]);
            $image->text($village,$font_path,17,'#8B8C96',1,[270,338]);
            $image->save($image_local_path);

            // 上传到阿里云OSS
            $aliyun_oss_config = Config::get('config.aliyun_oss');
            $bucket = $aliyun_oss_config['Bucket'];
            $fileName = 'ydxq/img/system/share/share/'.date("Ymd").'/'.'shop_share_'.$sup_id.'.png';
            try{
                $oss = new Oss($aliyun_oss_config['KeyId'],$aliyun_oss_config['KeySecret'],$aliyun_oss_config['Endpoint'],true);
                $result = $oss::ali_oss()->uploadFile($bucket,$fileName,$image_local_path);
                /*组合返回数据*/
                $arr = [
                    'oss_url'       => $result['info']['url'],      // 上传资源地址
                    'relative_path' => $fileName                    // 数据库保存名称(相对路径)
                ];
            } catch (OssException $e){
                return sdk_return([],0,$e->getMessage());
            }
            $add_data['sup_id'] = $sup_id; // 店铺ID
            $add_data['type'] = 2; // 1：海报；2：店铺
            $add_data['info'] = $create_data['info'];
            $add_data['oss_img'] = $fileName;
            $add_data['createtime'] = get_time();
            $add_data['updatetime'] = get_time();
            // 更新数据
            $res = $supplierCreateModel->insertInfo($add_data);
            if ($res) {
                unlink($header_img_path);
                unlink($image_local_path);
                unlink($header_save_path);
                return sdk_return(imgSrc($fileName),1,'success');
            }
        }

        // 已经生成过，且生成内容一致，直接返回图片
        if (!empty($sup_create_img_res) && $sup_create_img_res['info'] == $create_data['info']) {
            return sdk_return(imgSrc($sup_create_img_res['oss_img']),1,'success');
        }

        // 已经生成过，内容不一致，重新生成
        if(!empty($sup_create_img_res) && $sup_create_img_res['info'] != $create_data['info']) {
            // 处理店主头像
            $this->userIconSave($info['avatar'],$sup_id);
            // 店主头像
            $header_img_path = __DIR__ . '/../../../public/upload/images/avatar_original_'.$sup_id.'.png';
            if(!file_exists($header_img_path)) {
                return sdk_return('',0,'用户头像获取失败');
            }
            $header_save_path = './upload/images/'.'avatar_thumb_'.$sup_id.'.png';
            // 处理头像
            $this->get_thumb_img(38,38,$header_img_path,$header_save_path);
            // 创建图片
            $image = Image::open($img);
            $image->water($header_save_path,[23,48]);  // 头像
//        $image->water($left_img,[300,120]);         // 右侧图
            $image->text($name,$font_path,20,'#ffffff',1,[65,55]);
            $image->text('烟酒茶糖 冷饮批发 零食百货',$font_path,16,'#515366',1,[20,165]);
            $image->text('热销果品 日杂百货 文教用品',$font_path,16,'#515366',1,[20,195]);
            $image->text($purpose_server,$font_path,20,'#ffffff',2,[0,270]);
            $image->text($phone,$font_path,17,'#8B8C96',1,[55,340]);
            $image->text($village,$font_path,17,'#8B8C96',1,[270,338]);
            $image->save($image_local_path);

            // 上传到阿里云OSS
            $aliyun_oss_config = Config::get('config.aliyun_oss');
            $bucket = $aliyun_oss_config['Bucket'];
            $fileName = 'ydxq/img/system/share/share/'.date("Ymd").'/'.'shop_share_'.$sup_id.'.png';
            try{
                $oss = new Oss($aliyun_oss_config['KeyId'],$aliyun_oss_config['KeySecret'],$aliyun_oss_config['Endpoint'],true);
                $result = $oss::ali_oss()->uploadFile($bucket,$fileName,$image_local_path);
                /*组合返回数据*/
                $arr = [
                    'oss_url'       => $result['info']['url'],      // 上传资源地址
                    'relative_path' => $fileName                    // 数据库保存名称(相对路径)
                ];
            } catch (OssException $e){
                return sdk_return([],0,$e->getMessage());
            }

            $update_data['info'] = $create_data['info'];
            $update_data['oss_img'] = $fileName;
            $update_data['updatetime'] = get_time();
            // 更新数据
            $res = $supplierCreateModel->updateInfo($sup_create_img_res['id'],$update_data);
            if ($res) {
                unlink($header_img_path);
                unlink($image_local_path);
                unlink($header_save_path);
                return sdk_return(imgSrc($fileName),1,'success');
            }
        }

        return sdk_return([],0,'error');

//        return sdk_return(['http://oss.yundian168.com/ydxq/img/system/share/share/20190826/poster.png'],1,'success');
    }

    /**
     * 生成缩略图
     * @param $w [缩略图宽]
     * @param $h [缩略图高]
     * @param $img_path [原图路径]
     * @param $save_path [处理完之后的路径]
     * @param bool $is_round [是否变成圆形]
     * @return string
     */
    private function get_thumb_img($w,$h,$img_path,$save_path,$is_round = false)
    {
        $image = Image::open($img_path);
        if ($is_round) {
            $image->thumb($w,$h)->round();
        } else {
            $image->thumb($w,$h);
        }
        $image->save($save_path);
        return __DIR__ . '/../../../public/'.$save_path;

    }


    /**
     * 保存网络图片
     * @param $imgUrl [网络图片地址]
     * @return string [返回本地图片路径]
     */
    private function saveImg($imgUrl){
        $img_file = file_get_contents($imgUrl);
        $img_content= base64_encode($img_file);
        $ext=strrchr($imgUrl,'.');
        if(!in_array($ext,['.jpg','.png','.jpeg','.gif']))
            return $imgUrl;
        $baseName=basename($imgUrl);

        $saveUrl="/upload/img/".$baseName;
        //文件保存绝对路径
        $path = __DIR__ .'/../../../public/upload/images/'.$baseName;
        $img = file_get_contents($imgUrl);
        file_put_contents($path, $img);
        return $saveUrl;
    }

    /**
     * 保存微信头像到本地
     * @param $url [微信头像URL]
     * @param $sup_id [店主ID]
     * @param string $image_name
     */
    private function userIconSave($url,$sup_id,$image_name = 'avatar_original_'){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        $resource = fopen($_SERVER['DOCUMENT_ROOT']."/upload/images/". $image_name . $sup_id.".png" ,'a');
        fwrite($resource, $file);
        fclose($resource);
    }


    /**
     * 获取/创建带头像微信小程序码
     * @param $sup_id [店铺ID]
     * @param int $width [图像宽度]
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function get_wexin_app_code($sup_id,$width = 150)
    {
        $supplierModel = new Supplier();
        $info = $supplierModel->getInfoPro(['id'=>$sup_id],['avatar','oss_store_code_img']);
        if (!empty($info['oss_store_code_img'])) { // 有则返回
            return ['status'=>1,'msg'=>'success','data'=>imgSrc($info['oss_store_code_img'])];
        } else { // 无则生成
            $weixin_config = Config::get('config.weixin_app');
            $spreadCode = new Spreadcode();
            $access_token = $spreadCode::get_token($weixin_config['appID'],$weixin_config['appSecret']);
            // 生成二维码
            $path = 'pages/shop/home/index';
            $data = [
                'scene'=>'sid=' . $sup_id,
                'path'      => $path,
                "width"     => $width,
                'auto_color'=> false,
                //'line_color'=>$line_color,
            ];
            $post_data= json_encode($data,true);
            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=" . $access_token;
            $result_code = $spreadCode::http_curl($url,$post_data);
            $result = json_decode($result_code,true);
            if(!empty($result['errcode'])) {
                return ['status'=>0,'msg'=>$spreadCode::error_code($result['errcode'])];
            }

            $img_name = 'temp_'.$sup_id.".jpg";
            $temp_local_path = './upload/images/'.$img_name;// 创建完删除
            $avatar_code_path = './upload/images/avatar_code_'.$sup_id.'.png';// 创建完删除
            $new_temp_path = './upload/images/'.'avatar_code_new_'.$sup_id.'.png';
            if(file_put_contents($temp_local_path, $result_code)){// 缓存临时文件成功
                // 保存头像到本地
                $this->userIconSave($info['avatar'],$sup_id,'avatar_code_');
                // 处理头像成圆形
                $this->get_thumb_img(110,110,$avatar_code_path,$avatar_code_path,true);
                // 创建图片
                $image = Image::open('./upload/images/'.$img_name);
                $image->water($avatar_code_path,[85,85]);
                $image->thumb(170,170);
                $image->save($new_temp_path);
                // 上传到阿里云OSS
                $aliyun_oss_config = Config::get('config.aliyun_oss');
                $bucket = $aliyun_oss_config['Bucket'];
                $fileName = 'ydxq/img/system/share/share/'.date("Ymd").'/'.'spreadcode_'.$sup_id.'.png';
                try{
                    $oss = new Oss($aliyun_oss_config['KeyId'],$aliyun_oss_config['KeySecret'],$aliyun_oss_config['Endpoint'],true);
                    $result = $oss::ali_oss()->uploadFile($bucket,$fileName,$new_temp_path);
                    /*组合返回数据*/
                    $arr = [
                        'oss_url'       => $result['info']['url'],      // 上传资源地址
                        'relative_path' => $fileName                    // 数据库保存名称(相对路径)
                    ];
                } catch (OssException $e){
                    return sdk_return([],0,$e->getMessage());
                }

                $update_data['oss_store_code_img'] = $fileName;
                // 更新数据
                $res = $supplierModel->updateInfo($sup_id,$update_data);
                if ($res !== false) {
                    unlink($temp_local_path);
                    unlink($avatar_code_path);
                    unlink($new_temp_path);
                    return ['status'=>1,'msg'=>'success','data'=>imgSrc($fileName)];
                }

            }

        }

        return ['status'=>0,'msg'=>'店铺二维码获取失败'];
    }

}