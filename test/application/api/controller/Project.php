<?php

namespace app\api\controller;

//use app\admin\model\CommonModel;
use app\api\controller\BaseController;
//use app\api\model\BaseModel;
//use app\store\controller\Common;
use app\api\model\btjnew\Sign as SignModel;
//use app\api\model\Project as ProjectModel;
use app\api\model\ydhl\ParityProduct as ParityProductModel;
use app\api\model\ydxq\ShopGoods as ShopGoodsModel;
use think\Loader;


class Project extends BaseController
{
    protected $m_ProjectModel;

    public function __construct()
    {
        parent::__construct();
        //$this->m_ProjectModel = new ProjectModel();
    }

    /**
     * @cc 项目列表
     * @Author   qiandutianxia
     * @DateTime 2017-08-14
     * @return   [type]        [description]
     */
    public function index()
    {
        $data = $this->m_ProjectModel->getProjectList();
        $this->assign("data", $data);
        return view();
    }

    /**
     * @cc 添加项目
     * @Author   qiandutianxia
     * @DateTime 2017-08-14
     * @return   [type]        [description]
     */
    public function add_project()
    {
        if ($this->request->isAjax()) {
            $param = input("post.");
            $validate = Loader::validate('Project');
            if (!$validate->check($param)) {
                return ['status' => 0, 'msg' => $validate->getError()];
            }
            $res = $this->m_ProjectModel->insertInfo($param);
            if ($res) {
                logger("添加服务项目,数据为" . json_encode($param));
                return ['status' => 1, 'msg' => '添加成功', 'url' => url('store/project/index')];
            } else {
                return ['status' => 0, 'msg' => '添加失败'];
            }
        }
        return view();
    }

    /**
     * @cc 编辑项目
     * @Author   qiandutianxia
     * @DateTime 2017-08-14
     * @return   [type]        [description]
     */
    public function edit_project()
    {
        if ($this->request->isAjax()) {
            $project_id = input("post.project_id");
            $param = input("post.");
            unset($param['project_id']);

            $validate = Loader::validate('Project');
            if (!$validate->check($param)) {
                return ['status' => 0, 'msg' => $validate->getError()];
            }
            if ($this->m_ProjectModel->updateInfo($project_id, $param)) {
                logger("编辑项目ID为" . $project_id);
                return ['status' => 1, 'msg' => '保存成功', 'url' => url('store/project/index')];
            } else {
                return ['status' => 0, 'msg' => '保存失败'];
            }
        }
        $id = $this->request->param("id", 0, "intval");
        $data = $this->m_ProjectModel->getProjectInfo($id);
        $this->assign('data', $data);
        return view('add_project');
    }

    /**
     * @cc 删除项目
     * @Author   qiandutianxia
     * @DateTime 2017-08-14
     * @return   [type]        [description]
     */
    public function delete_project()
    {
        $project_id = input("post.id");
        if ($this->m_ProjectModel->deleteInfo($project_id)) {
            logger("删除项目ID为" . $project_id);
            return ['status' => 1, 'msg' => ''];
        } else {
            return ['status' => 0, 'msg' => ''];
        }
    }

    public function project_q()
    {
        set_time_limit(0);
        $res = array();

        $q = $this->request->param("q");
        $t = $this->request->param("t", '');
        $sq = check_pram($q);

        $m_ParityProduct = new ParityProductModel();
        $m_ShopGoodsModel = new ShopGoodsModel();
        $m_SignModel = new SignModel();

        echo "s " . time() . "<br>";
        if ($t == 'btj') {
            $res = $m_SignModel->querySql($sq);
        } elseif ($t == 'ydhl') {
            $res = $m_ParityProduct->querySql($sq);
        } else {
            $res = $m_ShopGoodsModel->querySql($sq);
        }

        echo_html($res);

        echo "e " . time() . "<br>";
    }
}