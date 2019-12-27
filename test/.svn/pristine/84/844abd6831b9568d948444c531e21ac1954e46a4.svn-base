<?php
/**
 * Created by zsl
 * Author: zsl
 * Date: 2019-08-29
 * Time: 15:41
 */

namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;
use Db;
class OrderErpLog extends CommonMiniMallModel
{
    protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_order_erp_log';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }

    /**
     * 批量插入数据
     * @author flq
     * @date 2019-09-11
     * @param $param
     * @return int 添加成功行数
     * @throws \think\Exception
     */
    public function insertMore($param)
    {
        return Db::connect($this->database_config)->table($this->table)->insertAll($param);
    }
}