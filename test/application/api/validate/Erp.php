<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-12
 * Time: 12:02
 */
namespace app\api\validate;

use think\Validate;

class Erp extends Validate
{
    protected $rule = [
        'goods_code'    => 'require',
        'goods_name'    => 'require',
        'goods_pic'     => 'require',
    ];

    protected $message = [
        'goods_code.require'    => '商品条码不能为空',
        'goods_name.require'    => '商品名称不能为空',
        'goods_pic.require'     => '商品图片地址不能为空'
    ];

    protected $scene = [
        'goods_upload'          =>  ['goods_code','goods_name','goods_pic']
    ];
}


