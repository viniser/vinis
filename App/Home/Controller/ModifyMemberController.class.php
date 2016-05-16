<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 16-3-8
 * Time: 下午4:29
 */

namespace Home\Controller;

use Common\Controller\CommonController;

class ModifyMemberController extends CommonController {
    /**
     * 添加个人信息
     */
    public function modify(){
        //判断是否是已经完成reg基本注册
		 
				
       $login=$this->checkLogin();
       if(!$login){
      	 	$this->redirect('User/index');
       		return;
       }
       if(session('STATUS')!=0){
            $this->redirect('User/index');
            return;
        }
        if(IS_POST){
            $M_member = D('Member');
            $id = session('USER_KEY_ID');
            $_POST['member_id']=$id;
            $_POST['status'] = 1;//0=有效但未填写个人信息1=有效并且填写完个人信息2=禁用
            if (1==2){ // 创建数据对象 !$data=$M_member->create()
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $data['status'] = 0;
                $data['info'] = $M_member->getError();
                $this->ajaxReturn($data);
                return;
            }else {
				 $config=$this->config;
				$where['member_id'] = $id;
				
				$count2 = $M_member->where($where)->count();//根据分类查找数据数量
			 	if($count2==1){
				$Memb2 = $M_member->where($where)->find(); 
				$parentID['member_id']=intval($Memb2['pid']);
				$parentID['currency_id']=intval($config['BiID']); 
						
				if(intval($Memb2['pid'])>0){
				M('currency_user')->where($parentID)->setInc('forzen_num',intval($config['giveYao']));
				$this->addFinance($parentID['member_id'], 12, "推荐注册奖励",intval($config['giveYao']),1,intval($config['BiID']));
				$this->addCurrencyLog(12,$parentID['member_id'],intval($config['BiID']),intval($config['giveYao']));
 				}
				
				if((intval($config['giveDoor'])>0)&&(intval($config['BiID'])>0)){
				$cur_where['member_id']=$id;
				$cur_where['currency_id']=intval($config['BiID']);
				M('currency_user')->where($cur_where)->setInc('forzen_num',intval($config['giveDoor']));
				$this->addFinance($id,24,"新用户注册奖励",intval($config['giveDoor']),1,intval($config['BiID']));
				$this->addCurrencyLog(24,$id,intval($config['BiID']),intval($config['giveDoor']));
				} 
				
				
/*				$dat['nick']=$_POST['nick'];
				$dat['name']=$_POST['name'];
				$dat['nick']=$_POST['idcard'];
				$dat['phone']=$_POST['phone'];
				$dat['status']=$_POST['status'];
				$dat['cardtype']=$_POST['cardtype'];*/	 

				$r = $M_member->where($where)->save($_POST); 
				}
                if($r){
                    session('procedure',2);//SESSION 跟踪第二步
                    session('STATUS',1);
                    $data['status'] = 1;
                    $data['info'] = "恭喜注册成功!";
                    $this->ajaxReturn($data);
//                    $this->redirect('Reg/regSuccess');
                }else{
                    $data['status'] = 0;
                    $data['info'] = '服务器繁忙,请稍后重试'. $_POST['status'];
                    $this->ajaxReturn($data);
//                    $this->error('服务器繁忙,请稍后重试');
//                    return;
                }
            }
        }else{
            $this->display();
        }
    }
    /**
     * ajax验证昵称是否存在
     */
    public function ajaxCheckNick($nick){
        $nick = urldecode($nick);
        $data =array();
        $M_member = M('Member');
        $where['nick']  = $nick;
        $r = $M_member->where($where)->find();
        if($r){
            $data['msg'] = "昵称已被占用";
            $data['status'] = 0;
        }else{
            $data['msg'] = "";
            $data['status'] = 1;
        }
        $this->ajaxReturn($data);
    }
    /**
     * ajax手机验证
     */
    function ajaxCheckPhone($phone) {
		// $this->ajaxReturn($data);exit;
        $phone = urldecode($phone);
        $data = array();
        if(!checkMobile($phone)){
            $data['msg'] = "手机号不正确！";
            $data['status'] = 0;
        }else{
            $M_member = M('Member');
            $where['phone']  = $phone;
            $r = $M_member->where($where)->find();
            if($r){
                $data['msg'] = "此手机已经绑定过！请更换手机号";
                $data['status'] = 0;
            }else{
                $data['msg'] = "";
                $data['status'] = 1;
            }
        }
        $this->ajaxReturn($data);
    }
/**
添加冻结
***/
   public function addCurrencyLog($iid,$uid,$cid,$num){
    	  $arr['iid']=$iid;  //分类
		  $arr['uid']=$uid;  //用户
		  $arr['cid']=$cid;  //币种
		  $arr['num']=$num;  //数量
		  $arr['deal']=$num;
		  $arr['add_time']=time();
		  $arr['status']=0;
		  M('Currency_dong')->add($arr);
    	if($list){
    		return $list;
    	}else{
    		return false;
    	}
    }
    /**
     * ajax验证手机验证码
     */
    public function ajaxSandPhone(){
        $phone = urldecode(I('phone'));
		$uid=urldecode(I('t'));
        if(empty($phone)){
            $data['status']=0;
            $data['info'] = "手机号码不能为空";
            $this->ajaxReturn($data);
        }
        if(!preg_match("/^1[34578]{1}\d{9}$/",$phone)){  
            $data['status']=-1;
            $data['info'] = "手机号码不正确";
            $this->ajaxReturn($data);
        }
		
		if($uid==0){
        $user_phone=M("Member")->field('phone')->where("phone='$phone'")->find();
        if (!empty($user_phone)){
            $data['status']=-2;
            $data['info'] = "手机号码已经存在";
            $this->ajaxReturn($data);
        }
		}else{
        $user_phone=M("Member")->field('phone')->where("phone='$phone' and member_id='$uid'")->find();
        if (empty($user_phone)){
            $data['status']=-2;
            $data['info'] = "您输入的手机号与注册的不一致!";
            $this->ajaxReturn($data);
        }
			}
        $r = sandPhone($phone,$this->config['CODE_NAME'],$this->config['CODE_USER_NAME'],$this->config['CODE_USER_PASS']);
        if($r!="短信发送成功"){
            $data['status']=0;
            $data['info'] = $r;
            $this->ajaxReturn($data);
        }else{
            $data['status']=1;
            $data['info'] = $r;
            $this->ajaxReturn($data);
        }
    }
}