<?php
/**
 * News(新闻管理)
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class ImgController extends AdminbaseController {

    protected $news_model;
    protected $images_model;
    protected $auth_rule_model;

    public function _initialize() {
        parent::_initialize();
        $this->news_model = D("Common/News");
        $this->images_model = D("Common/Images");
        $this->auth_rule_model = D("Common/AuthRule");
    }

    // 后台图片列表
    public function index() {
        $host = $_SERVER['HTTP_HOST'];
        $keyword = I('post.keyword');
        $downloadDir= http_type().$host.__ROOT__.'/uploads/images/';

        $where['title']  = array('like','%'.$keyword.'%');
        $where['type']  = array('eq',2);
        //$where['_logic'] = 'OR';
        $count=$this->news_model->where(array('type'=>2))->count();
        $page = $this->page($count, 5);

        $this->news_model->join('LEFT JOIN __IMAGES__ ON __IMAGES__.nid = __NEWS__.nid');
        $this->news_model->where($where);
        $this->news_model->limit($page->firstRow , $page->listRows);

        $news = $this->news_model->order('id desc')->select();
//        echo $this->attachment_model->getLastSql();die;
//print_r($news);die;
        foreach ($news as $item => $val){
           $renews[ $news[$item]['nid']][] = $val;
        };
//        print_r($renews);die;
        $this->assign("page", $page->show('Admin'));
        $this->assign('news',array_values($renews));
        $this->assign('keyword',$keyword);
        $this->assign('downloadDir',$downloadDir);
        $this->display();
    }
    

    // 后台菜单添加
    public function add() {
        $this->display();
    }
    
    // 后台菜单添加提交
    public function upload() {
        $config = array(
            'maxSize'    =>    314572800,
            'rootPath'   =>    './uploads/images/',
            'savePath'   =>    '',
            'saveName'   =>    array('uniqid',''),
            'exts'       =>    array('jpg', 'gif', 'png', 'jpeg','doc','docx','pdf','zip','txt','tif','bmp'),
            'autoSub'    =>    true,
            'subName'    =>    array('date','Ymd'),
        );
        $upload = new \Think\Upload($config);// 实例化上传类
        // 上传文件
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $failReason = $upload->getError();
            $msg = array(
                'message'=> $failReason,
                'code'=> -1,
                'status'=> 0,
            );
            //将错误信息写入到.TXT文件中
            $failinfos = $_FILES['file']['name'].'------'.$failReason.'------'.date('Y-m-d H:i:s',time());
            $filepath = 'D:\/wamp\/'.__ROOT__.'/uploads/log/imagefail.txt';
            chmod($filepath,0644);
            file_put_contents($filepath, $failinfos.PHP_EOL, FILE_APPEND);
            $this->error($msg);
        }else{// 上传成功
            $attachment['img_name'] = $info['file']['name'];
            $attachment['save_name'] = $info['file']['savename'];
            $attachment['img_path'] = $info['file']['savepath'];
            $attachment['created_at'] = time();
            //创建附件数据
            $this->images_model->create($attachment);
            if($this->images_model->create()){
                $result = $this->images_model->add(); // 写入数据到数据库
            }else{
                $result = $this->images_model->add($attachment);
            }
            if($result){
                // 如果主键是自动增长型 成功后返回值就是最新插入的值
                $insertId = $result;
            }
            $msg = array('message'=>'success','code'=>200,'aid'=>$insertId,'status'=>1);
            return $this->ajaxReturn($msg,'json');
        }

    }

    public function add_post(){
        if (IS_POST) {
            $data['title'] = I('post.title');
            $aid = I('post.aids');
            $aids = explode(',',$aid);
            $data['created_at'] =time();
            $data['type'] =2;
//            $res = $this->news_model->addAll($dataList);
            if ($this->news_model->create($data)) {
                $res = $this->news_model->add();
                foreach($aids as $id => $v){
                    $updata['nid'] = $res;
                    $this->images_model->where(array('id'=>$v))->save($updata);
                }
                $this->success("添加成功！", U('Img/index'));
            } else {
                $this->error("添加失败！");
//                $this->error($this->news_model->getError());
            }


        }
    }

    public function delete_attach(){
        $error = array();
        $allids = I('get.allids');
        $allids = explode(',',$allids);
        $undeleids = I('get.undeleids');
        $undeleids = explode(',',$undeleids);
        $delids = array_diff($allids,$undeleids);
        //删除数据库数据1.删除附件表2,这时新闻表还没有插入数据,无须修改,3.删除上传目录的文件

        if(!empty($delids)){
            $map['id']  = array('IN',$delids);
            //1.首先查找所删id的附件名称
            $attachInfo = $this->images_model->where($map)->select();
//            echo $this->attachment_model->getLastSql();

            //2删除数据库信息
            $attach = $this->images_model->where($map)->delete();
            if($attach){
                //3.删除服务器存在的文件
                $dir =$_SERVER['DOCUMENT_ROOT']. __ROOT__.'/uploads/images/';
                foreach ($attachInfo as $k => $v ){
                    $delfile = $dir.$v['img_path'].$v['save_name'];
                    if(is_file($delfile)){
                        if(!unlink($delfile)){
                           $error['msg'] = '删除文件出错!';
                           $error['code'] = '-1';
                        }else {
                            $error['msg'] = '文件删除成功!';
                            $error['code'] = '0';
                        }
                    }else{
                        $error['msg'] = '文件不存在!';
                        $error['code'] = '-2';
                    }
                }
            }else{
                $error['msg'] = '删除失败!';
                $error['code'] = '-3';
            }

        }
        echo json_encode($error);

    }


    public function preview(){
        $this->display();
    }

    /*
     * 删除文件
     * */
    public function del(){
        $nid = I('get.nid');
        $where = array('nid'=>$nid);
        //开启事务
        $this->news_model->startTrans();
        $delnews = $this->news_model->where($where)->delete();
        $attach = $this->images_model->where(array('nid'=>$nid))->select();
        if($attach){
            //根据nid查找对应的附件
            foreach ($attach as $v){
                $delattach = $this->images_model->where(array('nid'=>$v['nid']))->delete();
                //删除本地的附件
                $dir =$_SERVER['DOCUMENT_ROOT']. __ROOT__.'/uploads/images/';
                $delfile = $dir.$v['img_path'].$v['save_name'];
                if(is_file($delfile)){
                    if(!unlink($delfile)){
                        $error['msg'] = '删除文件出错!';
                        $error['code'] = '-1';
                    }else {
                        if($delnews){
                            $this->news_model->commit();
                        }else{
                            $this->news_model->rollback();
                        }
                        $error['msg'] = '文件删除成功!';
                        $error['code'] = '0';
                    }
                }else{
                    $error['msg'] = '文件不存在!';
                    $error['code'] = '-2';
                }
            }
        }else{
            $error['msg'] = '删除失败!';
            $error['code'] = '-3';
        }
        echo json_encode($error);
    }

    public function edit(){
        $nid = I ('get.nid');
        $news = $this->news_model->where(array('nid'=>$nid))->find();
        //相应的附件信息
        $attaches = $this->images_model->where(array('nid'=>$news['nid']))->select();
        $this->assign('news',$news);
        $this->assign('attaches',$attaches);
        $this->display();
    }

    public function edit_del(){
        $id = I('get.id');
        $savename = I('get.savename');
        $filepath = I('get.filepath');

        //开启数据库事务
        $this->images_model->startTrans();
        $res = $this->images_model->where(array('id'=>$id))->delete();

        //删除服务器存储的文件
        $dir =$_SERVER['DOCUMENT_ROOT']. __ROOT__.'/uploads/images/';
        $delfile = $dir.$filepath.$savename;
        if(is_file($delfile)){
            if(!unlink($delfile)){
                $error['msg'] = '删除文件出错!';
                $error['code'] = '-1';
            }else {
                if($res){
                    $this->images_model->commit();
                }else{
                    $this->images_model->rollback();
                }
                $error['msg'] = '文件删除成功!';
                $error['code'] = '0';
            }
        }else{
            $error['msg'] = '文件不存在!';
            $error['code'] = '-2';
        }

        echo json_encode($error);
    }

    public function edit_post(){
        if(IS_POST){
            $data['title'] = I('post.title');
            $nid = I('post.nid');
            $aids = I('post.aids');
            //1.修改lxj_news表
            $this->news_model->where(array('nid'=>$nid))->save($data);
            //2,修改附件表
            if(!empty($aids)){
                $aids = explode(',',$aids);
                foreach($aids as $id => $v){
                    $updata['nid'] = $nid;
                    $upres = $this->images_model->where(array('id'=>$v))->save($updata);
                }
            }
            $this->success('更新成功!',U('img/index'));
        }


    }




























    

    



    


}
