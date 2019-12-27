<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-13
 * Time: 20:43
 */

namespace app\api\controller;

use app\api\model\ydxq\CaptainListModel;
use app\api\model\ydxq\GoodsCode;
use app\api\model\ydxq\GoodsFormal;
use app\api\model\ydxq\GoodsLhsdtj;
use app\api\model\ydxq\GoodsLq;
use app\api\model\ydxq\MemberListModel;
use app\api\model\ydxq\ShopGoods;
use library\Oss;

use OSS\Core\OssException;
use think\Db;
use think\facade\Config;
use think\Exception;

use app\api\model\ydxq\GoodsYtxc;

// 临时引用类
use app\api\model\ydxq\ServerOss;

use app\api\controller\BaseController;
use think\facade\Debug;

class OssUpload extends BaseController
{

    protected $aliyun_oss_config; // 阿里云OSS配置

    public function __construct()
    {
        parent::__construct();
        $this->aliyun_oss_config = Config::get('config.aliyun_oss');
    }


    public function index()
    {
        return 'index';
//        $res = $this->img_exists('https://btj.yundian168.com/images/goodsimg/02/04002/69032774110913.jpg');
//        dump($res);
    }

    public function captain_to_oss()
    {
        ini_set("max_execution_time", "3000");
        $page_size = $this->request->get('pagesize', 50);
        $captainModel = new CaptainListModel();
        Debug::remark('begin');
//        $list = $captainModel->getAllList([['c_oss_img','=',''],['c_img','<>','']]);
//        return json(count($list));
        $list = $captainModel->getPageListArr([['c_oss_img', '=', ''], ['c_img', '<>', '']], $page_size, ['id', 'c_img']);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $ben_img = null;
//                $host_url = 'https://btj.yundian168.com/';
//                $ben_img = $host_url.$v['c_headpic'];
                $oss_path = 'ydxq/img/system/captain/' . $_GET['page'];
                $ben_img = $v['c_img'];

                $file_exists = $this->img_exists($ben_img);

                if ($file_exists) {
                    $image_info = getimagesize($ben_img);
                    $base64 = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($ben_img)));
                    $res = null;
                    $res = $this->up_img($base64, $oss_path, md5(md5(uniqid())));
                    if (!empty($res)) {
                        $captainModel->updateInfo($v['id'], ['c_oss_img' => $res['relative_path']]);
                    }
                }

            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return([], 0, '数据为空');

    }

    // 最新团长头像上传OSS接口
    public function captain_to_oss_v2()
    {
        ini_set("max_execution_time", "3000");
        $captainModel = new CaptainListModel();
        $oss_path = 'ydxq/img/system/captain/' . $_GET['page'];
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];
        Debug::remark('begin');
        $list = $captainModel->getPageListArr([['c_oss_img', '=', ''], ['c_img', '<>', '']], 50, ['id', 'c_img', 'c_headpic', 'c_oss_img']);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                // 本地图片和外链图片都不存在，跳出循环
                if (empty($v['c_headpic']) && empty($v['c_img'])) {
                    continue;
                }

                // 本地图片有值，且能访问
                if (!empty($v['c_headpic']) && $this->img_exists('http://btj.yundian168.com' . $v['c_headpic'])) {
                    $local_temp_path = $this->saveImg('http://btj.yundian168.com' . $v['c_headpic']);
                    if (!is_array($local_temp_path)) {
                        continue;
                    }
                    try {
                        $fileName = $oss_path . '/' . md5(uniqid()) . $local_temp_path['ext']; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                        if ($res['info']['url']) {
                            $captainModel->updateInfo($v['id'], ['c_oss_img' => $fileName]);
                        }
                        unlink($local_temp_path['save_url']);
                    } catch (OssException $e) {
                        return sdk_return([], 0, $e->getMessage());
                    }
                } elseif (!empty($v['c_img']) && $this->img_exists($v['c_img'])) { // 没有本地图片，读取网络图片
                    $image_name = md5(uniqid());
                    // 保存微信头像到本地
                    $this->userIconSave($v['c_img'], $image_name);
                    $local_temp_path = './upload/images/' . $image_name . '.png';
                    if (!file_exists($local_temp_path)) {
                        continue;
                    }

                    try {
                        $fileName = $oss_path . '/' . $image_name . '.png'; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $result = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path);
                        if (!empty($result['info']['url'])) {
                            $captainModel->updateInfo($v['id'], ['c_oss_img' => $fileName]);
                        }
                        unlink($local_temp_path);
                    } catch (OssException $e) {
                        return sdk_return([], 0, $e->getMessage());
                    }

                } else {
                    continue;
                }
            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return('', 0, 'data is empty');
    }
    /*
    *  修改版
    *更新oss 图片
    */
    // 最新会员头像上传到OSS
    public function member_to_oss_v2()
    {
        ini_set("max_execution_time", "3000");
        $memberModel = new MemberListModel();
        $oss_path = 'ydxq/img/system/wxuser/' . $_GET['page'];
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];

        Debug::remark('begin');
        $list = $memberModel->getPageListArr([['m_oss_img', '=', ''], ['m_head_img', '<>', ''], ['modtime', '=', 0]], 5, ['id', 'm_head_img', 'm_oss_img']);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                // 本地图片和外链图片都不存在，跳出循环
                if (empty($v['m_oss_img']) AND empty($v['m_head_img'])) {
                    $memberModel->updateInfo($v['id'], ['modtime' => time()]);
                }
                $imgIsNull = $this->img_exists($v['m_head_img']);
                // 本地图片有值，且能访问
                if ($imgIsNull) {
                    $local_temp_path = $this->saveImg($v['m_head_img']);
                    if (!is_array($local_temp_path)) { // 不是数组，说明图片格式不在允许范围内
                        $memberModel->updateInfo($v['id'], ['modtime' => time()]);
                        continue;
                    }
                    try {
                        $fileName = $oss_path . '/' . md5(md5(uniqid())) . $local_temp_path['ext']; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                        if ($res['info']['url']) {
                            $memberModel->updateInfo($v['id'], ['m_oss_img' => $fileName, 'modtime' => time()]);
                        }
                        unlink($local_temp_path['save_url']);
                    } catch (OssException $e) {
                        return sdk_return([], 0, $e->getMessage());
                    }
                } else {
                    $memberModel->updateInfo($v['id'], ['modtime' => time()]);
                }


            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return('', 0, 'data is empty');
    }

    /*
     *
     *
     *
     */

    // 最新会员头像上传到OSS
    public function member_to_oss_v2_backUp()
    {
        ini_set("max_execution_time", "3000");
        $memberModel = new MemberListModel();
        $oss_path = 'ydxq/img/system/wxuser/' . $_GET['page'];
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];

        Debug::remark('begin');
        $list = $memberModel->getPageListArr([['m_oss_img', '=', ''], ['m_head_img', '<>', '']], 50, ['id', 'm_headpic', 'm_head_img', 'm_oss_img']);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                // 本地图片和外链图片都不存在，跳出循环
                if (empty($v['m_headpic']) && empty($v['m_head_img'])) {
                    continue;
                }

                // 本地图片有值，且能访问
                if (!empty($v['m_headpic']) && $this->img_exists('http://btj.yundian168.com/' . $v['m_headpic'])) {
                    $local_temp_path = $this->saveImg('http://btj.yundian168.com/' . $v['m_headpic']);
                    if (!is_array($local_temp_path)) { // 不是数组，说明图片格式不在允许范围内
                        continue;
                    }
                    try {
                        $fileName = $oss_path . '/' . md5(md5(uniqid())) . $local_temp_path['ext']; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                        if ($res['info']['url']) {
                            $memberModel->updateInfo($v['id'], ['m_oss_img' => $fileName]);
                        }
                        unlink($local_temp_path['save_url']);
                    } catch (OssException $e) {
                        return sdk_return([], 0, $e->getMessage());
                    }
                } elseif (!empty($v['m_headpic']) && $this->img_exists($v['m_head_img'])) { // 没有本地图片，读取网络图片
                    $image_name = md5(md5(uniqid()));
                    // 保存微信头像到本地
                    $this->userIconSave($v['m_head_img'], $image_name);
                    $local_temp_path = './upload/images/' . $image_name . '.png';
                    if (!file_exists($local_temp_path)) {
                        continue;
                    }
                    try {
                        $fileName = $oss_path . '/' . $image_name . '.png'; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $result = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path);
                        if (!empty($result['info']['url'])) {
                            $memberModel->updateInfo($v['id'], ['m_oss_img' => $fileName]);
                        }
                        unlink($local_temp_path);
                    } catch (OssException $e) {
                        return sdk_return([], 0, $e->getMessage());
                    }
                } else {
                    continue;
                }
            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return('', 0, 'data is empty');
    }

    public function member_to_oss_v3()
    {
        ini_set("max_execution_time", "3000");
        $memberModel = new MemberListModel();
        $oss_path = 'ydxq/img/system/wxuser/' . $_GET['page'];
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];

        Debug::remark('begin');
        $where = [
            ['modtime', '=', 0],
        ];
        $list = $memberModel->getPageListArr($where, 50, ['id', 'm_head_img', 'm_oss_img']);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $memberModel->updateInfo($v['id'], ['modtime' => time()]);
                if (!empty($v['m_oss_img'])) {
                    if ($this->img_exists('http://oss.yundian168.com/' . $v['m_oss_img'])) {
                        //表示可以访问到
                    } else {
                        //表示不可以访问到
                        $local_temp_path = $this->saveImg($v['m_head_img']);
                        if (!is_array($local_temp_path)) { // 不是数组，说明图片格式不在允许范围内
                            continue;
                        }
                        try {
                            $fileName = $oss_path . '/' . md5(md5(uniqid())) . $local_temp_path['ext']; // oss路径文件
                            $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                            $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                            if ($res['info']['url']) {
                                $memberModel->updateInfo($v['id'], ['m_oss_img' => $fileName, 'modtime' => time()]);
                            }
                            unlink($local_temp_path['save_url']);
                        } catch (OssException $e) {
//                            return sdk_return([],0,$e->getMessage());
                        }
                    }
                } else {
                    //表示oss字段为空，需要重新拉取图片进行上传
                    $local_temp_path = $this->saveImg($v['m_head_img']);
                    if (!is_array($local_temp_path)) { // 不是数组，说明图片格式不在允许范围内
                        continue;
                    }
                    try {
                        $fileName = $oss_path . '/' . md5(md5(uniqid())) . $local_temp_path['ext']; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                        if ($res['info']['url']) {
                            $memberModel->updateInfo($v['id'], ['m_oss_img' => $fileName, 'modtime' => time()]);
                        }
                        unlink($local_temp_path['save_url']);
                    } catch (OssException $e) {
//                            return sdk_return([],0,$e->getMessage());
                    }
                }
            }
            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }
        return sdk_return('', 0, 'data is empty');
    }

    public function member_to_oss_zt()
    {
        ini_set("max_execution_time", "300");
        $memberModel = new MemberListModel();
        $oss_path = 'ydxq/img/system/wx_user';//. $_GET['page']
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];

        Debug::remark('begin');
        //echo "a" . time() . "<br>";
        $list = $memberModel->getPageListArr([['m_oss_time_zt', '=', 0], ['m_head_img', '<>', '']], 50, ['id', 'm_head_img'], ['id asc']);
        //echo "b" . time() . "<br>";
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                //echo "c" . time() . "<br>";
                $imgIsNull = $this->img_exists($v['m_head_img']);
                //echo "d" . time() . "<br>";
                // 本地图片有值，且能访问
                if ($imgIsNull) {
                    $local_temp_path = $this->saveWxImg($v['id'], $v['m_head_img']);
                    //echo "e" . time() . "<br>";
//                    if (!is_array($local_temp_path)) { // 不是数组，说明图片格式不在允许范围内
//                        $memberModel->updateInfo($v['id'], ['m_oss_time_zt' => time()]);
//                        continue;
//                    }
                    try {
                        $str_id1 = ceil($v['id'] / 1000000);
                        if ($str_id1 < 10) {
                            $str_id1 = '0' . $str_id1;
                        }
                        $str_id2 = ceil($v['id'] / 1000);
                        if ($str_id2 < 10) {
                            $str_id2 = '00' . $str_id2;
                        } elseif ($str_id2 < 100) {
                            $str_id2 = '0' . $str_id2;
                        }

                        $fileName = $oss_path . '/' . $str_id1 . '/' . $str_id2 . '/' . md5('wx_user_'.$v['id']) . $local_temp_path['ext']; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                        //echo "f" . time() . "<br>";
                        if ($res['info']['url']) {
                            $memberModel->updateInfo($v['id'], ['m_oss_img_zt' => $fileName, 'm_oss_time_zt' => time()]);
                        }
                        //echo "g" . time() . "<br>";
                        unlink($local_temp_path['save_url']);
                    } catch (OssException $e) {
                        //echo "h" . time() . "<br>";
                        return sdk_return([], 0, $e->getMessage());
                    }
                } else {
                    $memberModel->updateInfo($v['id'], ['m_oss_time_zt' => time()]);
                }


            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return('', 0, 'data is empty');
    }


    // 本地图片上传到OSS对应表ims_ewei_shop_goods
    public function shop_goods_to_oss()
    {
        ini_set("max_execution_time", "3000");
        $shopGoodsModel = new ShopGoods();
        $pageSize = 10;
        $oss_path = 'ydxq/img/system/goods/' . intval($_GET['page']);
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];
        Debug::remark('begin');
        $sql = "select id,thumb from ims_ewei_shop_goods where thumb <> '' and oss_img = '' limit " . ((intval($_GET['page']) - 1) * $pageSize) . "," . $pageSize;
        $list = $shopGoodsModel->querySql($sql);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (!$this->img_exists(imgSrc($v['thumb']))) {
                    continue;
                } else {
                    $local_temp_path = $this->saveImg(imgSrc($v['thumb']));
                    if (!is_array($local_temp_path)) { // 下载临时文件失败
                        continue;
                    } else {
                        // 上传阿里云OSS
                        try {
                            $fileName = $oss_path . '/' . md5(md5(uniqid()) . 'goods') . $local_temp_path['ext']; // oss路径文件
                            $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                            $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                            if ($res['info']['url']) {
                                $shopGoodsModel->updateInfo($v['id'], ['oss_img' => $fileName]);
                            }
                            unlink($local_temp_path['save_url']);
                        } catch (OssException $e) {
                            return sdk_return([], 0, $e->getMessage());
                        }
                    }
                }
            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return('', 0, 'data is empty');

    }

    // 本地图片上传到OSS对应表ims_goods_formal_img
    public function goods_formal_to_oss()
    {
        ini_set("max_execution_time", "3000");
        $goodsFormalModel = new GoodsFormal();
        $pageSize = 50;
        $oss_path = 'ydxq/img/system/goods/' . intval($_GET['page']);
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];
        Debug::remark('begin');
        $sql = "select id,img,oss_img from ims_goods_formal_img where img <> '' limit " . ((intval($_GET['page']) - 1) * $pageSize) . "," . $pageSize;
        $list = $goodsFormalModel->querySql($sql);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if ($this->img_exists(imgSrc($v['img']))) {
                    $local_temp_path = $this->saveImg(imgSrc($v['img']));
                    if (!is_array($local_temp_path)) {
                        continue;
                    }
                    // 上传阿里云OSS
                    try {
                        $fileName = $oss_path . '/' . md5(md5(uniqid()) . 'formal') . $local_temp_path['ext']; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                        if ($res['info']['url']) {
                            $goodsFormalModel->updateInfo($v['id'], ['oss_img' => $fileName]);
                        }
                        unlink($local_temp_path['save_url']);
                    } catch (OssException $e) {
                        return sdk_return([], 0, $e->getMessage());
                    }

                } else {
                    continue;
                }
            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return('', 0, 'data is empty');
    }


    // 本地图片上传到OSS对应表ims_goods_code
    public function goods_code_to_oss()
    {
        ini_set("max_execution_time", "3000");
        $goodsCodeModel = new GoodsCode();
        $pageSize = 10;
        $oss_path = 'ydxq/img/system/goods/' . intval($_GET['page']);
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];

        Debug::remark('begin');
        $sql = "select id,img,oss_img from ims_goods_code where img is not null and img <> '' and oss_img = '' limit " . ((intval($_GET['page']) - 1) * $pageSize) . "," . $pageSize;
        $list = $goodsCodeModel->querySql($sql);

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if ($this->img_exists(imgSrc($v['img']))) {
                    $local_temp_path = $this->saveImg(imgSrc($v['img']));
                    if (!is_array($local_temp_path)) {
                        continue;
                    }
                    // 上传阿里云OSS
                    try {
                        $fileName = $oss_path . '/' . md5(md5(uniqid()) . 'img') . $local_temp_path['ext']; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                        if ($res['info']['url']) {
                            $goodsCodeModel->updateInfo($v['id'], ['oss_img' => $fileName]);
                        }
                        unlink($local_temp_path['save_url']);
                    } catch (OssException $e) {
                        return sdk_return([], 0, $e->getMessage());
                    }

                } else {
                    continue;
                }
            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return('', 0, 'data is empty');
    }

    /**
     * 保存网络图片
     * @param $imgUrl
     * @return array
     */
    private function saveImg($imgUrl)
    {
        $img_file = file_get_contents($imgUrl);
        $img_content = base64_encode($img_file);
        $ext = strrchr($imgUrl, '.');
        if (!in_array($ext, ['.jpg', '.png', '.jpeg', '.gif']))
            return $imgUrl;
        $baseName = basename($imgUrl);

        $saveUrl = "./upload/images/" . $baseName;
        //文件保存绝对路径
        $path = __DIR__ . '/../../../public/upload/images/' . $baseName;
        $img = file_get_contents($imgUrl);
        file_put_contents($path, $img);
        return [
            'save_url' => $saveUrl,
            'ext' => $ext
        ];
    }

    function dlfile1($file_url, $save_to)
    {
        $in = fopen($file_url, "rb");
        $out = fopen($save_to, "wb");
        while ($chunk = fread($in, 8192)) {
            fwrite($out, $chunk, 8192);
        }
        fclose($in);
        fclose($out);
    }

    function dlfile2($file_url, $save_to)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        curl_close($ch);
        $downloaded_file = fopen($save_to, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
    }


    private function saveWxImg($id, $imgUrl)
    {
        //echo "s1" . time() . "<br>";
        $s_id = sprintf("%07d", $id);
        //echo $s_id;
        //exit;
        //$s_id = sprintf("0%3d", $id);
//        if ($id < 10) {
//            $s_id = '000' . $id;
//        } elseif ($id < 100) {
//            $s_id = '00' . $id;
//        } elseif ($id < 1000) {
//            $s_id = '0' . $id;
//        }
        $baseName = "wx_" . $s_id . '.jpg';//basename($imgUrl);
        $saveUrl = "./upload/images/" . $baseName;
        //文件保存绝对路径
        $path = __DIR__ . '/../../../public/upload/images/' . $baseName;
        $this->dlfile2($imgUrl, $path);
        //echo "s2" . time() . "<br>";
        return [
            'save_url' => $saveUrl,
            'ext' => '.jpg'
        ];
    }

    /**
     * 保存微信头像到本地
     * @param $url [微信头像URL]
     * @param $name [图片名称]
     */
    private function userIconSave($url, $name)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        $resource = fopen($_SERVER['DOCUMENT_ROOT'] . "/upload/images/" . $name . ".png", 'a');
        fwrite($resource, $file);
        fclose($resource);
    }


    // 判断文件是否存在
    private function img_exists($url)
    {
        if (@file_get_contents($url, 0, null, 0, 1))
            return true;
        else
            return false;
//            $isNull = 0;
//           if (file_exists($url)){
//               $isNull =1;
//           }
//            return $isNull;
    }

    // 本地图片上传到阿里云OSS，后期没用可以删除
    public function ben_img_to_oss()
    {
        ini_set("max_execution_time", "3000");
        $page_size = 10;
        $oss_path = 'ydxq/img/system/goods/01/0007';
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];
        $serverOssModel = new ServerOss();
        Debug::remark('begin');
        $sql = "select id,ben_img,oss_img from yd_base_goods where oss_img = '' and ben_img != '' and ben_img is not null limit " . ((intval($_GET['page']) - 1) * $page_size) . "," . $page_size;
        $list = $serverOssModel->querySql($sql);
//        $list = $serverOssModel->getPageListArr([['oss_img','=',''],['ben_img','<>','null'],['ben_img','<>','']],$page_size,['id','ben_img']);
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (!$this->img_exists(imgSrc($v['ben_img']))) { // 网络图片不存在404，跳过
                    continue;
                } else {
                    $local_temp_path = $this->saveImg(imgSrc($v['ben_img']));
                    if (!is_array($local_temp_path)) { // 下载临时文件失败，跳过
                        continue;
                    } else {
                        // 上传阿里云服务器 oss
                        try {
                            $fileName = $oss_path . '/' . md5(md5(uniqid()) . 'goods') . $local_temp_path['ext']; // oss路径文件
                            $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                            $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                            if ($res['info']['url']) {
                                $serverOssModel->updateInfo($v['id'], ['oss_img' => $fileName]);
                            }
                            unlink($local_temp_path['save_url']);
                        } catch (OssException $e) {
                            return sdk_return([], 0, $e->getMessage());
                        }
                    }
                }
            }
            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }
        return sdk_return([], 0, 'data is empty');
    }

    public function wai_img_to_oss()
    {
        ini_set("max_execution_time", "3000");
        $page_size = $this->request->get('pagesize', 50);
        $serverOssModel = new ServerOss();
        Debug::remark('begin');

        $list = $serverOssModel->getPageListArr([['oss_img', '=', ''], ['img', '<>', ''], ['img', '<>', 'null']], $page_size, ['id', 'img']);

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $oss_path = 'ydxq/img/system/goods/01/0008';
                $file_exists = $this->img_exists($v['img']);

                if ($file_exists) {
                    $image_info = getimagesize($v['img']);
                    $base64 = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($v['img'])));
                    $res = null;
                    $res = $this->up_img($base64, $oss_path);
                    if (!empty($res)) {
                        $serverOssModel->updateInfo($v['id'], ['oss_img' => $res['relative_path']]);
                    }
                }
            }

            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }

        return sdk_return([], 0, '数据为空');

    }

    private function getImgSrc($src)
    {
        if (strstr($src, 'goodsimg') && !strstr($src, 'http')) {
            return 'https://btj.yundian168.com/' . $src;
        }
        if (strstr($src, 'kuaikuimg') && !strstr($src, 'http')) {
            return 'https://btj.yundian168.com/' . $src;
        }
        if (strstr($src, 'attachment') && !strstr($src, 'http')) {
            return 'https://mallm.yundian168.com/' . $src;
        }
        if (!strstr($src, 'http')) {
            return 'https://mallm.yundian168.com/attachment/' . $src;
        }

        return $src;
    }


    public function local_to_oss()
    {

//        $path = 'ydxq/img/system/goods/ytxc';
        $path = $this->request->param('path');
        // table = longhushidaitianjie
        $table = $this->request->param('table');
        if (empty($path)) return sdk_return([], 0, 'path不能为空');
        if (empty($table)) return sdk_return([], 0, 'table不能为空');
        switch ($table) {
            case 'yatangxiaochao':
                $modelObj = new GoodsYtxc();
                break;
            case 'longhushidaitianjie':
                $modelObj = new GoodsLhsdtj();
                break;
            case 'lianqiang':
                $modelObj = new GoodsLq();
                break;
            default:
                $modelObj = false;
        }

        ini_set("max_execution_time", "3000");
        if (empty($modelObj)) {
            return sdk_return([], 0, 'error');
        }

        $list = $modelObj->getPageListArr([], 100, ['id', 'img']);
        if (!empty($list)) {
            Debug::remark('begin');
            foreach ($list as $k => $v) {
                $image_info = getimagesize($v['img']);
                $base64 = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($v['img'])));
                $res = null;
                $res = $this->up_img($base64, $path);
                if (!empty($res)) {
                    $modelObj->updateInfo($v['id'], ['img_path' => $res['relative_path']]);
                }
            }
            Debug::remark('end');
            echo Debug::getRangeTime('begin', 'end') . 's';
        }
        return sdk_return([], 0, '数据为空');
    }

    /**
     * 上传美团饿了么的商品图片到OSS
     * @throws Exception
     */
    public function localToOssV2()
    {
//        echo 3;exit;
//        $path = 'ydxq/img/system/goods/ytxc';//云店星球
//        $path = 'sqp/img/system/goods/ytxc';社区派
        $database_title = 'db_ydxq_test_xianshang';
        $path = $this->request->param('path');
        // table = longhushidaitianjie
        $table = $this->request->param('table');
        if (empty($path)) return sdk_return([], 0, 'path不能为空');
        if (empty($table)) return sdk_return([], 0, 'table不能为空');

        ini_set("max_execution_time", "3000");

//        $list = $modelObj->getPageListArr([],100,['id','img']);
        $list = Db::connect($database_title)->table($table)->field('id,img')->whereNull('img_path')->select();
        if (!empty($list)) {
            Debug::remark('begin');
            foreach ($list as $k => $v) {
                $image_info = getimagesize($v['img']);
                $base64 = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($v['img'])));
                $res = null;
                $res = $this->up_img($base64, $path);
                if (!empty($res)) {
//                    $modelObj->updateInfo($v['id'],['img_path'=>$res['relative_path']]);
                    Db::connect($database_title)->table($table)->where('id', $v['id'])->update(['img_path' => $res['relative_path']]);
                }
            }
            Debug::remark('end');
            echo Debug::getRangeTime('begin', 'end') . 's';
        }
        return sdk_return([], 0, '数据为空');
    }

    // 上传图片，支持本地图片和base64
    public function upload_image()
    {
        header('Access-Control-Allow-Origin:*');

        // 阿里云OSS配置
        $aliyun_oss_config = $this->aliyun_oss_config;
        $bucket = $aliyun_oss_config['Bucket'];
        if ($this->request->has('img_url', 'post')) { // 图片URL上传
            $img_url = $this->request->post('img_url');
            $img_path = $this->request->post('img_path');
            if (empty($img_url)) return sdk_return([], 0, 'img_url不能为空');
            if (empty($img_path)) return sdk_return([], 0, 'img_path不能为空');
            // 网络图片转Base64图片
            $image_info = getimagesize($img_url);
            $base64 = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img_url)));

            $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
            // $path指定路径
            $result = $oss::process_base64($base64);
            if ($result['status'] == 1) {
                $fileResult = &$result['data'];
                $filePath = $fileResult['path'] . $fileResult['name'];
                $ossFileName = implode('/', [$img_path, date('Ymd'), $fileResult['name']]);

                // 文件上传
                try {
                    $result = $oss::ali_oss()->uploadFile($bucket, $ossFileName, $filePath);
                    $arr = [
                        'oss_url' => $result['info']['url'],    // 上传资源地址
                        'relative_path' => $ossFileName         // 数据库保存名称(相对路径)
                    ];
                } catch (OssException $e) {
                    return $e->getMessage();
                } finally {
                    unlink($filePath);
                }

                return sdk_return($arr, 1, 'success');
            }

            return sdk_return([], 0, $result['msg']);

        } else {
            // 获取上传图片
            $file_object = $this->request->file('file');
            $img_path = $this->request->post('img_path');
            if (empty($file_object)) return sdk_return([], 0, '请选择上传图片');
            if (empty($img_path)) return sdk_return([], 0, 'img_path不能为空');
            $file = $file_object->getInfo();
            if ($file) {
                $name = $file['name'];
                $format = strrchr($name, '.');  // 截取文件后缀名如 (.jpg)
                // 判断图片格式
                $allow_type = ['.jpg', '.jpeg', '.gif', '.bmp', '.png'];
                if (!in_array($format, $allow_type)) {
                    return sdk_return([], 0, '文件格式不在允许范围内');
                }

//                $fileName = 'upload/image/' . date("Ymd") . '/' . sha1(date('YmdHis', time()) . uniqid()) . $format;
                $fileName = $img_path . '/' . date("Ymd") . '/' . $name;
                // 执行上传
                try {
                    $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                    $result = $oss::ali_oss()->uploadFile($bucket, $fileName, $file['tmp_name']);
                    /*组合返回数据*/
                    $arr = [
                        'oss_url' => $result['info']['url'],    // 上传资源地址
                        'relative_path' => $fileName            // 数据库保存名称(相对路径)
                    ];
                } catch (OssException $e) {
                    return $e->getMessage();
                }

                return sdk_return($arr, 1, 'success');
            }

            return sdk_return([], 0, '文件不存在');
        }

    }

    /**
     * @param $base64 [base64图片数据]
     * @param string $oss_path [OSS存储路径]
     * @param string $img_name [图片名字,不含（.格式）]
     * @return array|bool|string|null
     */
    private function up_img($base64, $oss_path, $img_name = '')
    {
        // 阿里云OSS配置
        $aliyun_oss_config = $this->aliyun_oss_config;
        $bucket = $aliyun_oss_config['Bucket'];
        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
        // $path指定本地路径
        $result = $oss::process_base64($base64, $img_name);
        $arr = null;
        if ($result['status'] == 1) {
            $fileResult = &$result['data'];
            $filePath = $fileResult['path'] . $fileResult['name'];
            $ossFileName = implode('/', [$oss_path, $fileResult['name']]);
            // 文件上传
            try {
                $result = $oss::ali_oss()->uploadFile($bucket, $ossFileName, $filePath);
                $arr = [
                    'oss_url' => $result['info']['url'],    // 上传资源地址
                    'relative_path' => $ossFileName         // 数据库保存名称(相对路径)
                ];
            } catch (OssException $e) {
                return $e->getMessage();
            } finally {
                unlink($filePath);
            }

            return $arr;

        }

        return false;
    }

    /**
     * 上传本地图片
     * @return string|void
     */
    public function upLocalhostToOss()
    {
        exit('456');
        echo "<pre>";
        ini_set("max_execution_time", "3000");
        $page_size = 10;
        $oss_path = 'sqp/img/system/goods/13/0006';
        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];
//        $serverOssModel = new ServerOss();
//        Debug::remark('begin');
//        $sql = "select id,ben_img,oss_img from yd_base_goods where oss_img = '' and ben_img != '' and ben_img is not null limit " . ((intval($_GET['page']) - 1) * $page_size) . "," . $page_size;
//        $list = $serverOssModel->querySql($sql);
//        $list = $serverOssModel->getPageListArr([['oss_img','=',''],['ben_img','<>','null'],['ben_img','<>','']],$page_size,['id','ben_img']);
//        SELECT * FROM `ims_ewei_shop_goods` WHERE id in(SELECT goods_id from ims_yd_supplier_goods WHERE supplier_id = 1022);
//        Db::table('think_user')
//    ->where('id', 'IN', function ($query) {
//        $query->table('think_profile')->where('status', 1)->field('id');
//    })
//    ->select();
        $list = Db::connect('db_sqp')->table('ims_ewei_shop_goods')->where('id','IN',function ($query) {
            $query->table('ims_yd_supplier_goods')->where('supplier_id', 1022)->field('goods_id');
        })->select();
        if (!empty($list)) {
            foreach ($list as $k => $v) {
//                if(empty($v['thumb'])){
                    $img_url = './hrshm/'.$v['goods_code'].'.png';
                    if (!$this->img_exists($img_url)) { // 网络图片不存在404，跳过
                        $img_url = './hrshm/'.$v['goods_code'].'.jpg';
                        if (!$this->img_exists($img_url)) { // 网络图片不存在404，跳过
                            continue;
                        }
                    }
                    $local_temp_path = $this->saveImg($img_url);
                    if (!is_array($local_temp_path)) { // 下载临时文件失败，跳过
                        continue;
                    }
                    // 上传阿里云服务器 oss
                    try {
                        $fileName = $oss_path . '/' . md5(md5(uniqid()) . 'goods') . $local_temp_path['ext']; // oss路径文件
                        $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
                        $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
                        if ($res['info']['url']) {
//                            $shop_goods_model->updateInfo($v['id'], ['oss_img' => $fileName,'thumb' => $fileName]);
                            Db::connect('db_sqp')->table('ims_ewei_shop_goods')->where([['id','=',$v['id']]])->update(['oss_img' => $fileName,'thumb' => $fileName]);
                        }
                        unlink($local_temp_path['save_url']);
                    } catch (OssException $e) {
                        return sdk_return([], 0, $e->getMessage());
                    }
//                }
            }
            Debug::remark('end');
            return Debug::getRangeTime('begin', 'end') . 's';
        }
        return sdk_return([], 0, 'data is empty');
    }

    public function upOneImg(){
        ini_set("max_execution_time", "3000");
        $oss_path = $this->request->post('oss_path');
        $img_name = $this->request->post('img_name');//不加后缀
        $img_url = $this->request->post('img_url');

//        $oss_path = 'ydxq/img/system/wxuser';
//        $img_url = './morenavatar.png';

        $aliyun_oss_config = Config::get('config.aliyun_oss');
        $bucket = $aliyun_oss_config['Bucket'];
        if (!$this->img_exists($img_url)) { // 网络图片不存在404，跳过
            echo '网络图片不存在';exit;
        }
        $local_temp_path = $this->saveImg($img_url);
        if (!is_array($local_temp_path)) { // 下载临时文件失败，跳过
            return sdk_return([], 1, '上传失败');
        }
        // 上传阿里云服务器 oss
        try {
            $fileName = $oss_path . '/' . $img_name . $local_temp_path['ext']; // oss路径文件
            $oss = new Oss($aliyun_oss_config['KeyId'], $aliyun_oss_config['KeySecret'], $aliyun_oss_config['Endpoint'], true);
            $res = $oss::ali_oss()->uploadFile($bucket, $fileName, $local_temp_path['save_url']);
            $res['oss_path'] = $fileName;
            unlink($local_temp_path['save_url']);
            return sdk_return($res, 0, '上传成功');
        } catch (OssException $e) {
            return sdk_return([], 1, $e->getMessage());
        }
    }
}