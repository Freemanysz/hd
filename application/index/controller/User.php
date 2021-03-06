<?php
namespace app\index\controller;

use app\model\Apply;
use app\model\Collect;
use think\Controller;
use app\model\User as UserModel;
use think\Session;

class User extends MyController
{

    public static $roleName=[
        1=>"管理员",
        2=>"认证用户",
        3=>"普通用户"
    ];
    public function index()
    {
        return $this->fetch('index');
    }

    public function login()
    {
        $return_url = $this->request->header('referer');
        return $this->fetch('login',['return_url'=>$return_url]);
    }

    public function doLogin()
    {
        $user =$this->request->post("user");
        $pass = $this->request->post('password');

        if(!$user || !$pass){
            Session::flash("login-error","请输入用户名和密码");
            $this->redirect(url('index/user/login'));
        }

        $return  = $this->request->param('return_url');
        if(!$return)
            $return = url('index/index/index');
        if(preg_match("/\/user\/login/",$return)){
            $return = url('index/index/index');
        }

        $Model = new UserModel();

        if($User = $Model->getByUserAndPass($user,md5($pass))){
            Session::set('user_id',$User->id);
            Session::set('user_name',$User->name);

            Session::set('role',$User->role);

            $this->success("登录成功",'index/index/index',null,2);
        }else{
            $this->error("用户名或密码错误",url('index/user/login'));
        }
    }

    public function register()
    {
        $return_url = $this->request->header('referer');
        return $this->fetch('register',['return_url'=>$return_url]);
    }

    public function doRegister()
    {
        $user = $this->request->param('user');
        $email = $this->request->param('email');
        $pwd = $this->request->param('password');
        $repwd = $this->request->param('repassword');
        $role = 3;
        if(!$user||!$email||!$pwd){
            $this->error("表单信息不完整");
        }
        if($pwd !==$repwd){
            $this->error("两次输入密码不一致");
        }
        $return  = $this->request->param('return_url');
        if(!$return)
            $return = url('index/index/index');
        if(preg_match("/\/user\/register/",$return)){
            $return = url('index/index/index');
        }
        $User = new \app\model\User();
        $User->role = $role;
        $User->name = $user;
        $User->password = md5($pwd);
        $User->email = $email;
        $User->register_at = time();
        if($User->save()){
            Session::set('user_id',$User->id);
            Session::set('user_name',$User->name);
            Session::set('role',3);

            $this->redirect($return);
        }else{
            $this->error("注册失败");
        }
    }

    public function profile()
    {
        $User = \app\model\User::get(Session::get('user_id'));
        if($User){
            $User->roleName = self::$roleName[$User->role];
            return $this->fetch('profile',['user'=>$User]);
        }
    }

    public function updateEmail()
    {
        $id = Session::get('user_id');
        $email = $this->request->param('email');

        if(!$email){
            echo json_encode(['code'=>0,'msg'=>"请填写邮箱"]);
            return ;
        }
        $User = \app\model\User::get($id);
        $User->email = $email;
        $User->save();
        echo json_encode(['code'=>1,'msg'=>"修改成功"]);
        return ;
    }

    public function updatePass()
    {
        $id = Session::get('user_id');
        $old = $this->request->param('old');
        $new = $this->request->param('new');
        $re = $this->request->param('re');

        if(!$old ||!$new || !$re ||$re!==$new){
            echo json_encode(['code'=>0,'msg'=>"请确认修改信息！"]);
            return ;
        }
        $User = \app\model\User::get($id);
        $User->password = md5($new);
        $User->save();
        echo json_encode(['code'=>1,'msg'=>"修改成功"]);
        return ;
    }

    public function apply()
    {
        $apply = Apply::get(['user_id'=>Session::get('user_id')]);

        $return_url = $this->request->header('referer');
        return $this->fetch('apply',['return_url'=>$return_url,'apply'=>$apply]);
    }

    public function doApply()
    {
        $reason = $this->request->param('reason');
        $mobile = $this->request->param('mobile');

        if(!$reason){
            $this->error("请填写申请理由");
        }
        if(!$mobile){
            $this->error("请填写手机号");
        }

        $return  = $this->request->param('return_url');
        if(!$return)
            $return = url('index/index/index');
        if(preg_match("/\/user\/apply/",$return)){
            $return = url('index/index/index');
        }
        $apply = new Apply();
        $apply->user_id = Session::get('user_id');
        $apply->mobile = $mobile;
        $apply->reason = $reason;
        $apply->status = 0;
        if($apply->save()){
            $this->success("申请成功，请等待审核",$return);
        }else{
            $this->error("申请失败");
        }
    }

    public function logout()
    {
        Session::delete('user_id');
        Session::delete('user_name');
        Session::delete('role');
        $this->redirect(url('index/index/index'));
    }
}
