<?php
/**
 * News(新闻管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class ImgsortController extends AdminbaseController {

    protected $imgsort_model;

    public function _initialize() {
        parent::_initialize();
        $this->imgsort_model = D("Common/Imgsort");
//        $this->attachment_model = D("Common/Attachment");
        $this->auth_rule_model = D("Common/AuthRule");
    }

    // 后台图片分类列表
    public function index() {
        //分页
        $count=$this->imgsort_model->where(array('state'=> 1))->count();
        $page = $this->page($count, 5);
        $this->imgsort_model->where(array('state'=> 1))->limit($page->firstRow , $page->listRows);
        $imgsort = $this->imgsort_model->select();
//       echo $this->imgsort_model->getLastSql();die;
        $this->assign('imgsort',$imgsort);
        $this->assign("page", $page->show('Admin'));
        $this->display();
    }
    

    // 添加表单
    public function add() {
        $this->display();
    }


    //添加图片分类
    public function add_post(){
        $data['name'] = I('post.name');
        $data['state'] = I('post.state');
        $data['created_at'] = time();
        if(IS_POST){
            if($this->imgsort_model->create($data)){
                $insres = $this->imgsort_model->add();
                if($insres){
                    $this->success('添加成功!',U('imgsort/index'));
                }else{
                    $this->error('添加失败!');
                }
            }

        }
    }


    public function edit(){
        $id = I('get.id');
        $imgsort = $this->imgsort_model->where(array('id'=>$id,'state'=>1))->find();
        $this->assign('imgsort',$imgsort);
        $this->display();
    }

    //保存编辑
    public function edit_post(){
        $id  = I('post.id');
        $data['name'] = I('post.name');
        $data['state'] = I('post.state');
        if(IS_POST){
            $upres = $this->imgsort_model->where(array('id'=>$id))->save($data);
            if($upres){
                $this->success('修改成功!',U('imgsort/index'));
            }else{
                $this->error('修改失败!');
            }
        }
    }



























    

    



    


}
