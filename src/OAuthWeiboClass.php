<?php
namespace ContentAuth\OAuthWeiboClass;
/**
 * 微博联合登陆功能
 * @author wangyongdong
 */
class OAuthWeiboClass {
    private $config = array();
	private $code = null;
	private $access_token = null;//用户授权的唯一票据，用于调用微博的开放接口，同时也是第三方应用验证微博用户登录的唯一票据，第三方应用应该用该票据和自己应用内的用户建立唯一影射关系，来识别登录状态，不能使用本返回值里的UID字段来做登录识别。 
	private $state = null;
	private $uid = null;
	
	public function __construct() {
		if(!isset($_SESSION)) { 
			session_start();
		}
		$this->code = !empty($_REQUEST["code"]) ? $_REQUEST["code"] : null;
	}
	
	public function oauth_login() {
		if(empty($this->code)) {
			$this->getAuthCode();
		}

        $this->config = $config['weibo'];
		//获取access_token
		$ret = $this->getAccessToken();
		if(!empty($ret['error'])) {
			return array('error'=>$ret['error'], 'msg'=>$ret['msg']);
		}
		
		//获取用户信息
		$ret = $this->getUserInfo();
		if(!empty($ret['error'])) {
			return array('error'=>$ret['error'], 'msg'=>$ret['msg']);
		}
		
		$ret['uid'] = $this->uid;
		return array('success'=>'success', 'info'=>$ret);
	}
	
	public function getAuthCode() {
		//state参数用于防止CSRF攻击，成功授权后回调时会原样带回
		$this->state = md5(uniqid(rand(), TRUE));
		$_SESSION['state'] = $this->state;
		
		$params = array(
			'client_id' => $this->config['APP_ID'],
			'response_type' => 'code',
			'state' => $this->state,
			'redirect_uri' => $this->config['RED_URL'],
		);
		//判断终端类型
		if($this->is_mobile()) {
			$params['display'] = 'wap';
		}
		$url = 'https://api.weibo.com/oauth2/authorize?'.http_build_query($params);
		$this->redirect($url);
	}
	
	public function getAccessToken() {
		//csrf校验
		if($_REQUEST['state'] !== $_SESSION['state']) {
			return array(
				'error' => 'error',
				'msg' => 'The state does not match. You may be a victim of CSRF.',
			);
		}
		$params = array(
			'client_id' => $this->config['APP_ID'],
			'client_secret' => $this->config['APP_SECRET'],
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->config['RED_URL'],
			'code' => $this->code,
		);
		$url = 'https://api.weibo.com/oauth2/access_token';
		
		$response = $this->httpsSend($url, $params, 'post');
		if(!empty($response['error_code']) || empty($response['access_token'])) {
			return array(
				'error' => $response['error_code'],
				'msg' => $response['error_description'],
			);
		}
		
		$this->access_token = $response['access_token'];
		$this->uid = $response['uid'];
		
// 		//判断是否续期
// 		if($response['expires_in'] <= 864000) {
// 			$this->refreshAccessToken($response['refresh_token']);
// 		}
	}
	
	public function refreshAccessToken($refresh_token) {
		$params = array(
				'client_id' => $this->config['APP_ID'],
				'client_secret' => $this->config['APP_SECRET'],
				'grant_type' => 'refresh_token',
				'redirect_uri' => $this->config['RED_URL'],
				'refresh_token' => '',
		);
		$url = 'https://api.weibo.com/oauth2/access_token';
		$response = $this->httpsSend($url, $params, 'post');
		if(!empty($response['error_code']) || empty($response['access_token'])) {
			return array(
				'error' => $response['error_code'],
				'msg' => $response['error_description'],
			);
		}
		
		$this->access_token = $response['access_token'];
		$this->uid = $response['uid'];
	}
	
	public function getUserInfo() {
		$params = array(
			'access_token' => $this->access_token,
			'uid' => $this->uid,
		);
		$url = 'https://api.weibo.com/2/users/show.json';
		return $this->httpsSend($url, $params);
	}
	
	
	public function httpsSend($url, $params = array(), $method = 'get') {
		$ch = curl_init();
		if($method == 'get') {
			$sParm = http_build_query($params);
			if(!empty($sParm)) {
				$url = $url.'?'.$sParm;
			}
		} else {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		}
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		
		try {
			$response = curl_exec($ch);
			if(!$response) {
				$response = json_encode(array(
					'error'=>curl_errno($ch),
					'error_description'=>curl_error($ch),
				));
			}
			curl_close($ch);
		} catch (Exception $e) {
			$response = json_encode(array());
		}
		
		return json_decode($response, true);
	}

	public function redirect($uri = '/', $params = array(), $bPermanent = TRUE) {
		if (headers_sent()) {
			exit();
		}
	
		if ($bPermanent) {
			header('HTTP/1.0 301 Moved Permanently');
		}
		if($params) {
			$build_query = http_build_query($params);
			$uri = $uri . '?' . $build_query;
		}
	
		header('Location: ' . $uri);
		flush();
		exit();
	}
	
	public function is_mobile() {
		$sUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$sUserAgent = strtolower($sUserAgent);
		if(preg_match('/iphone|android|windows phone|micromessenger/i', $sUserAgent)) {
			return true;
		}
		return false;
	}
}