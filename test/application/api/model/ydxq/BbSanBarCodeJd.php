<?php
/**
 * Created by seaboyer
 * Author: seaboyer@163.com
 * Date: 2019-08-29
 */

namespace app\api\model\ydxq;

use app\api\model\CommonMiniMallModel;

class BbSanBarCodeJd extends CommonMiniMallModel
{
    //protected $autoWriteTimestamp = true;
    protected $tableName = 'ims_bb_scan_bar_code_jd';

    protected function initialize()
    {
        parent::initialize();
        $this->setTableName($this->tableName);
    }
}