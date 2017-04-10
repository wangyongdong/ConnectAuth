<?php 
/**
 * QQ联合登陆功能
 * @author wangyongdong
 */
class OAuthQqClass {
	const APP_ID = '';
	const APP_SECRET = '';
	const RED_URL = 'http://localhost/connect/auth_login/qq';
	const PC_URI = 'https://graph.qq.com/oauth2.0';
	const WAP_URI = 'https://graph.z.qq.com/moc2';
	
	private $auth_code = null;
	private $access_token = null;
	private $openid = null;//openid是此网站上唯一对应用户身份的标识，网站可将此ID进行存储便于用户下次登录时辨识其身份，或将其与用户在网站上的原有账号进行绑定。
	private $state = null;
	private $uri = null;
	
	public function __construct() {
		if(!isset($_SESSION)) {
			session_start();
		}
		$this->auth_code = !empty($_REQUEST["code"]) ? $_REQUEST["code"] : null;
		if(!$this->is_mobile()) {
			$this->uri = self::PC_URI;
		} else {
			$this->uri = self::WAP_URI;
		}
	}
	
	public function oauth_login() {
		if(empty($this->auth_code)) {
			$this->getAuthCode();
		} 
		//获取access_token
		$ret = $this->getAccessToken();
		if(!empty($ret['error'])) {
			return array('error'=>$ret['error'], 'msg'=>$ret['msg']);
		}
		
		//获取openid
		$ret = $this->getOpenID();
		if(!empty($ret['error'])) {
			return array('error'=>$ret['error'], 'msg'=>$ret['msg']);
		}
		
		//获取用户信息
		$ret = $this->getUserInfo();
		if(!empty($ret['error'])) {
			return array('error'=>$ret['error'], 'msg'=>$ret['msg']);
		}
		$ret['openid'] = $this->openid;
		return array('success'=>'success', 'info'=>$ret);
	}
	
	//Step1：获取Authorization Code
	public function getAuthCode() {
		//state参数用于防止CSRF攻击，成功授权后回调时会原样带回
		$this->state = md5(uniqid(rand(), TRUE));
		$_SESSION['state'] = $this->state;
		
		$params = array(
			'response_type' => 'code',
			'client_id' => self::APP_ID,
			'redirect_uri' => self::RED_URL,
			'state' => $this->state,
		);
		if($this->is_mobile()) {
			$params['display'] = '';//仅PC网站接入时使用。 用于展示的样式。不传则默认展示为PC下的样式。如果传入“mobile”，则展示为mobile端下的样式。
			$params['g_ut'] = '';	//仅WAP网站接入时使用。 QQ登录页面版本（1：wml版本； 2：xhtml版本），默认值为1。
		}
		$url = self::PC_URI.'/authorize?'.http_build_query($params);
		$this->redirect($url);
	}
	
	//Step2：通过Authorization Code获取Access Token
	public function getAccessToken() {
		//csrf校验
		if($_REQUEST['state'] !== $_SESSION['state']) {
			return array(
				'error' => 'error',
				'msg' => 'The state does not match. You may be a victim of CSRF.',
			);
		}
		
		$params = array(
			'grant_type' => 'authorization_code',
			'client_id' => self::APP_ID,
			'client_secret' => self::APP_SECRET,
			'redirect_uri' => self::RED_URL,
			'code' => $this->auth_code,
		);
		$url = $this->uri.'/token';
		$response = $this->httpsSend($url, $params);
		
		if(!empty($response['error']) || empty($response['access_token'])) {
			return array(
				'error' => $response['error'],
				'msg' => $response['error_description'],
			);
		}
		
		$this->access_token = $response['access_token'];
		
		//判断是否续期
		if($response['expires_in'] <= 864000) {
			$this->refreshAccessToken($response['refresh_token']);
		}
	}
	
	//Step3：（可选）权限自动续期，获取Access Token
	public function refreshAccessToken($refresh_token) {
		$params = array(
			'grant_type' => 'refresh_token',
			'client_id' => self::APP_ID,
			'client_secret' => self::APP_SECRET,
			'refresh_token' => $refresh_token,
		);
		$url = $this->uri.'/token';
		$response = $this->httpsSend($url, $params);
		if(!empty($response['error']) || empty($response['access_token'])) {
			return array(
				'error' => $response['error'],
				'msg' => $response['error_description'],
			);
		}
		
		$this->access_token = $response['access_token'];
	}
	
	//Step4：使用Access Token来获取用户的OpenID
	public function getOpenID() {
		$params = array(
			'access_token' => $this->access_token,
		);
		$url = $this->uri."/me";
		$response = $this->httpsSend($url, $params);
		if (!empty($response['error'])) {
			return array(
				'error' => $response['error'],
				'msg' => $response['error_description'],
			);
		}
		$this->openid = $response['openid'];
	}
	
	//Step5：使用Access Token 和 OpenID 获取用户信息
	public function getUserInfo() {
		$params = array(
			'access_token' => $this->access_token,
			'oauth_consumer_key' => self::APP_ID,
			'openid' => $this->openid,
		);
		$url = 'https://graph.qq.com/user/get_user_info?'.http_build_query($params);
		$response = file_get_contents($url);
		$response = json_decode($response, true);
		if ($response['ret'] < 0) {
			return array(
				'error' => 'error',
				'msg' => $response['msg'],
			);
		}
		return $response;
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
		if (strpos($response, "callback") !== false) {
			$lpos = strpos($response, "(");
			$rpos = strrpos($response, ")");
			$response = substr($response, $lpos + 1, $rpos - $lpos -1);
			$response = json_decode($response, true);
		} else {
			$params = array();
			parse_str($response, $params);
			$response = $params;
		}
		return $response;
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
