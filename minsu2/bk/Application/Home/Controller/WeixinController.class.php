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
		$data['appId']=self::APP_ID;
		$data['timestamp']=$timestamp;
		$data['nonceStr']=$noncestr;
		$data['signature']=$sign;
		$data['jsapi_ticket']=$apiTicket;
		$this->ajaxReturn($data);
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
		
	public function payResult() {
		$postStr = file_get_contents('php://input');
		echo $postStr;
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
}
 ?>