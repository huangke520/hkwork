<?php
/**
 * Created by seaboyer
 * Author: seaboyer@163.com
 * Date: 2019-08-29
 */

namespace app\api\model\ydxq;
use think\Db;

use app\api\model\CommonMiniMallModel;

class ShopMemberCoupon extends CommonMiniMallModel
{
    //protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_ewei_shop_member_coupon';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
    
    //获取用户可用优惠券
    public function getCanuseCoupon($openid){

        $where = [
            ['a.coupon_status', '=', 2],
            ['a.openid', '=', $openid]
        ];
        $lists = Db::connect($this->database_config)
                ->field(['a.id', 'a.coupon_id', 'a.time_end', 'c.limit_money', 'c.money_value'])
                ->table($this->tableName)
                ->alias('a')
                ->join('ims_bb_base_coupon c', 'c.id = a.coupon_id')
                ->where($where)
                ->order(['a.time_end'=>'asc','c.money_value'=>'desc','c.limit_money'=>'asc'])
                ->select();
        return $lists;
    }
}