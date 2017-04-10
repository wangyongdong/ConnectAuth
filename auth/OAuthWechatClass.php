<?php
class OAuthWechatClass {
	const APP_ID = '';
	const APP_SECRET = '';
	const RED_URL = 'http://localhost/connect/auth_login/wechat';
	
	private $code = null;
	private $access_token = null;
	private $openid = null;
	private $state = null;
	private $unionid = null;//用户统一标识。针对一个微信开放平台帐号下的应用，同一用户的unionid是唯一的。开发者最好保存用户unionID信息，以便以后在不同应用中进行用户信息互通。
	//请注意，在用户修改微信头像后，旧的微信头像URL将会失效，因此开发者应该自己在获取用户信息后，将头像图片保存下来，避免微信头像URL失效后的异常情况。
	
	public function __construct() {
		if(!isset($_SESSION)) {
			session_start();
		}
		$this->code = !empty($_REQUEST['code']) ? $_REQUEST['code'] : null;
	}
	
	public function oauth_login() {
		if(empty($this->code)) {
			$this->getAuthCode();
		}
		
		$ret = $this->getAccessToken();
		if(!empty($ret['error'])) {
			return array('error'=>$ret['error'], 'msg'=>$ret['errmsg']);
		}
		$check = $this->checkAccessToken();
		if(!empty($check['error'])) {
			return array('error'=>$ret['error'], 'msg'=>$ret['errmsg']);
		}
		$ret = $this->getUserInfo();
		if(!empty($ret['error'])) {
			return array('error'=>$ret['error'], 'msg'=>$ret['errmsg']);
		}
		$ret['unionid'] = $this->unionid;
		return array('success'=>'success', 'info'=>$ret);
	}
	
	public function getAuthCode() {
		//state参数用于防止CSRF攻击，成功授权后回调时会原样带回
		$this->state = md5(uniqid(rand(), TRUE));
		$_SESSION['state'] = $this->state;
		
		$params = array(
			'appid' => self::APP_ID,
			'redirect_uri' => self::RED_URL,
			'response_type' => 'code',
			'scope' => 'snsapi_login',
			'state' => $this->state,
		);
		$url = 'https://open.weixin.qq.com/connect/qrconnect?'.http_build_query($params).'#wechat_redirect';
		$this->redirect($url);
	}
	
	public function getAccessToken() {
		if($_REQUEST['state'] !== $_SESSION['state']) {
			return array(
				'error' => 'error',
				'errmsg' => 'The state does not match. You may be a victim of CSRF.',
			);
		}
		$params = array(
			'appid' => self::APP_ID,
			'secret' => self::APP_SECRET,
			'code' => $this->code,
			'grant_type' => 'authorization_code',
		);
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token';
		$ret = $this->httpsSend($url, $params);
		if(!empty($ret['errcode']) || empty($ret['access_token']) || empty($ret['openid']) || empty($ret['unionid'])) {
			return array('error'=>$ret['errcode'], 'errmsg'=>$ret['errmsg']);
		}
		$this->access_token = $ret['access_token'];
		$this->openid = $ret['openid'];
		$this->unionid = $ret['unionid'];
		
		if($ret['expires_in'] <= 3600) {
			$ret = $this->refreshAccessToken($ret['refresh_token']);
			if(!empty($ret['error'])) {
				return array('error'=>$ret['error'], 'errmsg'=>$ret['errmsg']);
			}
		}
	}
	
	public function refreshAccessToken($refresh_token) {
		$params = array(
			'appid' => self::APP_ID,
			'grant_type' => 'refresh_token',
			'refresh_token' => $refresh_token,
		);
		$url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
		$ret = $this->httpsSend($url, $params);
		if(!empty($ret['errcode']) || empty($ret['access_token']) || empty($ret['openid'])) {
			return array('error'=>$ret['errcode'], 'errmsg'=>$ret['errmsg']);
		}
		$this->access_token = $ret['access_token'];
		$this->openid = $ret['openid'];
	}
	
	public function checkAccessToken() {
		$params = array(
			'access_token' => $this->access_token,
			'openid' => $this->openid,
		);
		$url = 'https://api.weixin.qq.com/sns/auth';
		$ret = $this->httpsSend($url, $params);
		if(!empty($ret['errcode']) || !empty($ret['errmsg'])) {
			return array('error'=>$ret['errcode'], 'errmsg'=>$ret['errmsg']);
		}
	}
	
	public function getUserInfo() {
		$params = array(
			'access_token' => $this->access_token,
			'openid' => $this->openid,
		);
		$url = 'https://api.weixin.qq.com/sns/userinfo';
		$ret = $this->httpsSend($url, $params);
		if(!empty($ret['errcode']) || !empty($ret['errmsg'])) {
			return array('error'=>$ret['errcode'], 'errmsg'=>$ret['errmsg']);
		}
		return $ret;
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
					'errcode'=>curl_errno($ch),
					'errmsg'=>curl_error($ch),
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