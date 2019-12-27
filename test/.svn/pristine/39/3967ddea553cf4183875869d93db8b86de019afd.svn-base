<?php

/**
 * Author: seaboyer@163.com
 * Date: 2019-08-08
 */

namespace app\api\controller;

use app\api\model\ydxq\Feedback as FeedbackModel;

class Feedback extends BaseController {
    protected $m_feedback;

    public function __construct() {
        parent::__construct();
        $this->m_feedback = new FeedbackModel();
    }

    /**
     * 反馈接口
     */
    public function addFeedback() {
        $request = $this->request_param;
        $type = !empty($request['type']) ? $request['type'] : sdk_return('', 6, '参数错误');//反馈类别
        //1：价格贵了，2：图片和商品信息不符合，3：其他
        $openid = !empty($request['openid']) ? $request['openid'] : sdk_return('', 6, '参数错误');//反馈人的openID
        $sup_id = !empty($request['sup_id']) ? $request['sup_id'] : sdk_return('', 6, '参数错误');//店铺ID
        $goods_id = !empty($request['goods_id']) ? $request['goods_id'] : sdk_return('', 6, '参数错误');//商品ID
        $content = !empty($request['content']) ? $request['content'] : '';//内容
        $img = !empty($request['img']) ? $request['img'] : '';//反馈图片

        //查询用户的昵称
        $user_nickname = $this->m_feedback->querySql("SELECT nickname,unionid from ims_ewei_shop_member where openid = '{$openid}' order by id desc limit 1");
        $nickname = !empty($user_nickname[0]['nickname']) ? $user_nickname[0]['nickname'] : '';//用户昵称
        $unionid = !empty($user_nickname[0]['unionid']) ? $user_nickname[0]['unionid'] : '';//用户unionid

        //查询店铺名称
        $shop_name_arr = $this->m_feedback->querySql("SELECT `name` from ims_yd_supplier where id = '{$sup_id}' and is_c = 1 limit 1");
        $shop_name = !empty($shop_name_arr[0]['name']) ? $shop_name_arr[0]['name'] : '';

        //查询商品名称
        $goods_name_arr = $this->m_feedback->querySql("SELECT title from ims_ewei_shop_goods where id = '{$goods_id}' limit 1");
        $goods_name = !empty($goods_name_arr[0]['title']) ? $goods_name_arr[0]['title'] : '';

        //插入反馈内容
        $parm = [
            'type' => $type,
            'openid' => $openid,
            'unionid' => $unionid,
            'nickname' => $nickname,
            'sup_id' => $sup_id,
            'sup_name' => $shop_name,
            'goods_id' => $goods_id,
            'goods_name' => $goods_name,
            'img' => $img,
            'content' => $content,
            'createtime' => time(),
        ];
        $res = $this->m_feedback->insertInfo($parm);
        if (!empty($res)) {
            sdk_return('', 1, '反馈成功');
        } else {
            sdk_return('', 6, '反馈失败');
        }
    }
}