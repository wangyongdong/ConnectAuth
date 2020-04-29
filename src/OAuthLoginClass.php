<?php
namespace ContentAuth\OAuthLoginClass;

use ContentAuth\OAuthQqClass;
use ContentAuth\OAuthWechatClass;
use ContentAuth\OAuthWeiboClass;
/**
 * 第三方登陆调用和绑定
 * @author wangyongdong
 */
class OAuthLoginClass {
	
	/**
	 * 执行登陆，获取用户信息
	 * @param string $type
	 * @return boolean
	 */
	public static function authLogin($type = '') {
		if(!in_array($type, array('qq', 'wechat', 'weibo'))) {
			return false;
		}
		$ret = self::ConnectOAuth($type);
		if(!empty($ret['error'])) {
			return $ret;
		}
		return $ret['info'];
	}
	
	/**
	 * 根据类型 调用联合登陆实现
	 * @param string $type
	 * @return array
	 */
	public static function ConnectOAuth($type) {
		$ret = null;
		switch ($type) {
			case 'qq':
				$oauth = new OAuthQqClass\OAuthQqClass();
				break;
			case 'wechat':
				$oauth = new OAuthWechatClass\OAuthWechatClass();
				break;
			case 'weibo':
				$oauth = new OAuthWeiboClass\OAuthWeiboClass();
				break;
		}
		$ret = $oauth->oauth_login();
		return $ret;
	}
	
	/**
	 * aes加密
	 * @param $input 待加密字符串
	 */
	public static function aesEncrypt($input) {
		$key = self::random(16,'1234567890abcdefghijklmnopqrstuvwxyz');//随机生成16位key
	
		$size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);//128位时为16
		$pad = $size - (strlen($input) % $size);//取得补码的长度
		$input = $input . str_repeat(chr($pad), $pad); //用ASCII码为补码长度的字符， 补足最后一段
			
		$data = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $input , MCRYPT_MODE_ECB);
		$data = base64_encode($data);
		//key拼在字符串前
		$data = $key.$data;
		return $data;
	}
	
	/**
	 * aes 解密
	 * @param $sStr 待解密字符串
	 */
	public static function aesDecrypt($sStr) {
		//取出前16位为key
		$sKey = substr($sStr,  0, 16);
		$sStr = substr($sStr,16);
	
		$decrypted= mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$sKey,base64_decode($sStr),MCRYPT_MODE_ECB);
		$dec_s = strlen($decrypted);
		$padding = ord($decrypted[$dec_s-1]);
		$decrypted = substr($decrypted, 0, -$padding);
		return $decrypted;
	}
	
	/**
	 * 随机生成key
	 * @param int $length
	 * @param string $chars
	 * @return string
	 */
	public static function random($length, $chars = '1234567890') {
		$hash = '';
		$max = strlen($chars) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $chars[mt_rand(0, $max)];
		}
		return $hash;
	}	
}