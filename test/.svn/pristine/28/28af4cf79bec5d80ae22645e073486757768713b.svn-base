<?php
namespace app\api\model\fixup;

use think\Model;

class BrandBd extends Model
{
    protected $connection = 'db_btj_new';
    protected $table = 'btj_brand_bd_log';
    protected function initialize()
    {
        parent::initialize();
    }
    public function customer()
    {
        return $this->belongsTo('app\api\model\fixup\PotentialCustomer','customer_id','id')->bind('user_name');
    }
}