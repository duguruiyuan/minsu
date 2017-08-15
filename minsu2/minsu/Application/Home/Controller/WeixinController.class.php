<?php 
namespace Home\Controller;
use Think\Controller;
/**
* 众筹
*/
class WeixinController extends Controller{
	const  APP_ID='wx8abf422f863923b3';
	const  SECRET='7976d4dfdaee9f2db765c5f3cc48ffb9';
	const  PAY_KEY='EF52668B6FFF6DBE9AD16488E756E22E';
	const  TOKEN_CACHE_KEY = 'weixin_token';
	const  TICKET_CACHE_KEY = 'weixin_ticket';
	const  MCH_ID=1406051302;	
		
	public function ajaxConfig($u) {
		$apiTicket=$this->cacheTicket();
		$noncestr='suijizifuchuan';		
		$timestamp=time(true);
		$sign = sha1('jsapi_ticket='.$apiTicket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$u);
//		echo $apiTicket;
//		echo '<br/><br/><br/>';
//		echo 'jsapi_ticket='.$apiTicket.'&noncestr='.$noncestr.'&timestamp='.$timestamp.'&url='.$url;
		$data['appId']=self::APP_ID;
		$data['timestamp']=$timestamp;
		$data['nonceStr']=$noncestr;
		$data['signature']=$sign;
		$data['jsapi_ticket']=$apiTicket;
		$this->ajaxReturn($data);
	}
	
	public function weixin() {
		$echoStr = $_GET["echostr"];
		if(is_null($echoStr)) {
			$postStr = file_get_contents('php://input');
			if(!empty($postStr)) {
        		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        		$fromUsername = $postObj->FromUserName;
        		$toUsername = $postObj->ToUserName;
        		$context=null;
				if($postObj->MsgType=='event' && $postObj->Event=='subscribe') {
					$context='美丽新乡村致力于为您找到属于您的那一所乡村美丽居所，您可以点击自定义菜单中的“乡村安家”寻找合适您的乡村民宅，或拔电客服电话13718138279咨询。';
				} else if($postObj->MsgType=='text') {
					$c =$postObj->Content;
					$reply = M('wx_msg_reply')->where("msg='{$c}'")->find();
					$context=$reply['reply'];
				} else if($postObj->MsgType=='event' && $postObj->Event=='LOCATION') {
//					$thirdpartyInfo = array('openid'=>$fromUsername,'latitude'=>($postObj->Latitude),'longitude'=>($postObj->Latitude));
//					M('thirdparty_info')->add($thirdpartyInfo);
				}
				if(!is_null($context)) {
					$msg=array('FromUserName'=>$toUsername,'ToUserName'=>$fromUsername,'CreateTime'=>time(),'MsgType'=>'text','Content'=>$context);
					echo arrayToXml($msg);
				}
			}
		} else {
			echo $echoStr;
		}
	}
	
	public function callOpenid($u) {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($user_agent, 'MicroMessenger')) {
			$ruri = urlencode('http://mei.vshijie.cn/minsu/index.php/Home/Weixin/openid?u='.urlencode($u));
			$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.self::APP_ID.'&redirect_uri='.$ruri.'&response_type=code&scope=snsapi_base&state=1#wechat_redirect';
		} else {
			$url = $u;
			cookie('oid',randomStr());
		}
		header('Location: '.$url);
	}

	public function openid() {
		$u=$_GET["u"];
		$code=$_GET["code"];
		$url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.self::APP_ID.'&secret='.self::SECRET.'&code='.$code.'&grant_type=authorization_code';
		$dataJson = file_get_contents($url);
		$data=json_decode($dataJson);
		$openid=$data->openid;
		
		if(!is_null($openid)) {
			cookie('oid',$openid);
		}
		header('Location: '.$u);
	}
		
	public function ajaxPayConfig($openid,$hid,$business) {
		$body='美丽新乡村-查看联系方式支付';
		if($business<=2) {
			$unlockContact = M('unlock_contact')->where("hid={$hid} AND openid='{$openid}' AND business={$business}")->find();
			if(is_null($unlockContact)) {
				$contact=array('openid'=>$openid,'hid'=>$hid,'type'=>1,'status'=>0, 'business'=>$business);
				$id = M('unlock_contact')->add($contact);
			} else {
				$id = $unlockContact['id'];
			}
			$unifiedorder['total_fee']=100;
		} else if($business==3) {
			$fee = M('house_fee')->where("hid={$hid}")->find();
			$hsign = M('house_sign')->WHERE('hid='.$_POST['hid'].' AND openid="'.$_POST['openid'].'"')->find();
			if(IS_NULL($hsign['paid'])) {
				$sign=array('openid'=>$openid,'hid'=>$hid,'paid'=>1,'fee'=>$fee['fee']);
				$id = M('house_sign')->add($sign);
			} else {
				$id=$hsign[id];
			}
			$unifiedorder['total_fee']=$fee['fee']*100;
			$body='美丽新乡村-预约看房支付';
		} else if($business==4) {
			$fee = M('funding')->where("funid={$hid}")->find();
			$id=time();
			session('fee',$fee['sign_fee']);
			$unifiedorder['total_fee']=$fee['sign_fee']*100;
			$body='美丽新乡村-预约看房支付';
		}
		$unifiedorder['appid']=self::APP_ID;
		$unifiedorder['mch_id']=self::MCH_ID;
		$unifiedorder['nonce_str']='suijizifuchuanhao';
		$unifiedorder['body']=$body;
		$unifiedorder['out_trade_no']=$business.$id;
		$unifiedorder['spbill_create_ip']=getIP();
		$unifiedorder['notify_url']='http://mei.vshijie.cn/minsu/index.php/Home/Weixin/payResult';
		$unifiedorder['trade_type']='JSAPI';
		$unifiedorder['openid']=$openid;
		ksort($unifiedorder);
		$sign="";
		foreach ($unifiedorder as $key => $val) {
		    $sign=($sign.$key.'='.$val.'&');
		}
		$sign=$sign.'key='.self::PAY_KEY;
		$sign=md5($sign);
		$unifiedorder['sign']=strtoupper($sign);
		$prepayIdXml = send_post('https://api.mch.weixin.qq.com/pay/unifiedorder', arrayToXml($unifiedorder));
		if(strpos($prepayIdXml,'prepay_id')) {
			$prepayIdObj = simplexml_load_string($prepayIdXml, 'SimpleXMLElement', LIBXML_NOCDATA);
			$prepayId=$prepayIdObj->xpath('prepay_id');
			if(!is_null($prepayId)) {
				$data['appId']=self::APP_ID;
				$data['nonceStr']='digitSignRandom';
				$data['timeStamp']=time(true);
				$data['package']='prepay_id='.$prepayId[0];
				$data['signType']='MD5';
				ksort($data);
				$paySign="";
				foreach ($data as $key => $val) {
				    $paySign=($paySign.$key.'='.$val.'&');
				}
				$paySign=$paySign.'key='.self::PAY_KEY;
				$data['paySign']=strtoupper(md5($paySign));
				$data['status']=1;
				$data['id']=$id;
				$this->ajaxReturn(json_encode($data));
				exit;
			}
		}
		$data['status']=0;
		$this->ajaxReturn(array('data'=>$data));
	}
	
	public function payResult() {
		$postStr = file_get_contents('php://input');
		echo $postStr;
	}
	public function createMenu() {
		$accessToken = $this->cacheToken();
		if(!is_null($accessToken)) {
			$mkHomeBtn=array('type'=>'view','name'=>'乡村安家','url'=>'http://mei.vshijie.cn/minsu/');
			$xiangjuBtn=array('type'=>'view','name'=>'乡居短租','url'=>'http://mei.vshijie.cn/bk/index.php/Home/House/home');
			$fundSubBtn=array('type'=>'view','name'=>'项目众筹','url'=>'http://mei.vshijie.cn/minsu/index.php/Home/Funding/index.html');
			$houseSubBtn=array('type'=>'view','name'=>'民宅合作','url'=>'http://mei.vshijie.cn/minsu/index.php/Home/Index/coop.html');
			$landSubBtn=array('type'=>'view','name'=>'土地合作','url'=>'http://mei.vshijie.cn/minsu/index.php/Home/Land/index.html');
			$projectBtn=array('name'=>'项目合作','sub_button'=>array($fundSubBtn,$houseSubBtn,$landSubBtn));
			$json_data = array ('button'=>array($mkHomeBtn,$xiangjuBtn,$projectBtn)); 
			$post_data=$this->JSON($json_data);
			echo send_post('https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$accessToken, $post_data);
		}	
	}

	public function ajaxTimelineUnlock($openid,$hid,$u,$business) {
		$data = M('unlock_contact')->where("hid={$hid} AND openid='{$openid}' AND business={$business}")->find();
		$contact=array('openid'=>$openid,'hid'=>$hid,'type'=>0,'status'=>1,business=>$business);
		if(is_null($data['id'])) {
			M('unlock_contact')->add($contact);
		} else {
			M('unlock_contact')->where("id={$data['id']}")->save($contact);
		}
		header('Location: '.$u);
	}
	
	public function ajaxPayUnlock($openid,$hid,$u,$business) {
		$contact=array('type'=>1,'status'=>1);
		M('unlock_contact')->where("hid={$hid} AND openid='{$openid}' AND business={$business}")->save($contact);
		header('Location: '.$u);
	}
	
	public function callLogin($u) {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($user_agent, 'MicroMessenger')) {
			$ruri = urlencode('http://mei.vshijie.cn/minsu/index.php/Home/Weixin/openid?u='.urlencode($u));
			$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.self::APP_ID.'&redirect_uri='.$ruri.'&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect';
		} else {
			$url = $u;
			cookie('oid',randomStr());
		}
		header('Location: '.$url);
	}

	public function reply($meid, $content) {
		$z=M('message')->where("meid={$meid}")->find();
		if (!is_null($z['openid'])) {
			M('message')->where("meid={$meid}")->setField('reply',$content);
			$accessToken = $this->cacheToken();
			if(!is_null($accessToken)) {
				$text=array('content'=>$content);
				$msg=array('touser'=>$z['openid'],'msgtype'=>'text','text'=>$text);
				$post_data=$this->JSON($msg);
				$r = send_post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$accessToken, $post_data);
				$this->success('回复成功','http://mei.vshijie.cn/minsu/index.php/Admin/Message/index');
				return;
			}	
		}
		$this->error('回复失败','http://mei.vshijie.cn/minsu/index.php/Admin/Message/index');
	}

	//审核未通过理由
	public function auditReason() {
		$state = I('post.status');
		$hid = I('post.hid');
		$houseinfo = M('houseinfo');
		$z = $houseinfo->where("hid={$hid}")->save(array('status'=>3,'not_reason'=>$_POST['not_reason']));
		if($z){
			$fee = M('house_fee')->where("hid={$hid}")->find();
			if(!is_null($fee['agent'])) {
				$user = M('user')->where("uid={$fee['agent']}")->find();
				if(!is_null($user['openid']) && strlen($user['openid'])>12){
					$accessToken = $this->cacheToken();
					if(!is_null($accessToken)) {
						$text=array('content'=>$_POST['not_reason']);
						$msg=array('touser'=>$z['openid'],'msgtype'=>'text','text'=>$text);
						$post_data=$this->JSON($msg);
						send_post('https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$accessToken, $post_data);
					}	
				}
			}
			$this->success('操作成功','http://mei.vshijie.cn/minsu/index.php/Admin/House/houseAudit');
		}
	}

	public function ajaxAppoint($id,$u) {
		$z = array('paid'=>2);
		M('house_sign')->where("id={$id}")->save($z);
		header('Location: '.$u);
	}
	
	private function cacheToken() {
		$accessToken=s(TOKEN_CACHE_KEY);
		if(is_null($accessToken)||!$accessToken) {
			//获取access token
			$url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.self::APP_ID.'&secret='.self::SECRET;
			$accessTokenJson = file_get_contents($url);
			$accessTokenObj=json_decode($accessTokenJson); 
			$accessToken=$accessTokenObj->access_token;
			s(TOKEN_CACHE_KEY,$accessToken,116);
		}
		return $accessToken;
    }
    
	private function cacheTicket() {
		$apiTicket=s(TICKET_CACHE_KEY);
		if(is_null($apiTicket)||!$apiTicket) {
			$accessToken=$this->cacheToken();
			//获取api_ticket
			$url='https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$accessToken.'&type=jsapi';
			$apiTicketJson = file_get_contents($url);
			$apiTicketObj=json_decode($apiTicketJson); 
			$apiTicket=$apiTicketObj->ticket;
			s(TICKET_CACHE_KEY,$apiTicket,116);
		}
		return $apiTicket;
    }
    
     /**************************************************************
     *
     *    将数组转换为JSON字符串（兼容中文）
     *    @param  array   $array      要转换的数组
     *    @return string      转换得到的json字符串
     *    @access public
     *
     *************************************************************/
    function JSON($array) {
        $this->arrayRecursive($array, 'urlencode', true);
        $json = json_encode($array);
        return urldecode($json);
    }
    
    /**************************************************************
       *
       *    使用特定function对数组中所有元素做处理
       *    @param  string  &$array     要处理的字符串
       *    @param  string  $function   要执行的函数
       *    @return boolean $apply_to_keys_also     是否也应用到key上
       *    @access public
       *
     *************************************************************/
    function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
    {
        static $recursive_counter = 0;
        if (++$recursive_counter > 1000) {
            die('possible deep recursion attack');
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->arrayRecursive($array[$key], $function, $apply_to_keys_also);
            } else {
                $array[$key] = $function($value);
            }
     
            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
        $recursive_counter--;
    }
}
 ?>