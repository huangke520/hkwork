<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-12
 * Time: 12:02
 */
namespace app\api\validate;

use think\Validate;

class YunPrint extends Validate
{
    protected $rule = [
        'sup_id'    => 'require',
        'order_id'  => 'require',
        'dev_id'    => 'require',
        'dev_pw'   => 'require',
        'dev_ver'   => 'require',
    ];

    protected $message = [
        'sup_id.require'    => '店铺ID不能为空',
        'order_id.require'  => '订单ID不能为空',
        'dev_id.require'    => '设备编号不能为空',
        'dev_pw.require'   => '设备密钥不能为空',
        'dev_ver.require'   => '设备版本不能为空',
    ];

    protected $scene = [
        'text'                  =>  ['sup_id','order_id'],
        'voice'                 =>  ['sup_id'],
        'get_shop_printer'      =>  ['sup_id'],
        'shop_add_printer'      =>  ['sup_id','dev_id'],
        'admin_add_printer'     =>  ['dev_id','dev_pw','dev_ver'],
        'shop_update_printer'   =>  ['sup_id'],
        'shop_unbind_printer'   =>  ['sup_id','dev_id'],
        'get_shop_printer_status'   =>  ['sup_id'],
    ];
}


