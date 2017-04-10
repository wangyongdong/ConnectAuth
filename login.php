<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>联合登陆--开发调试</title>
<script>
function toLogin()
{
   //以下为按钮点击事件的逻辑。注意这里要重新打开窗口
   //否则后面跳转到QQ登录，授权页面时会直接缩小当前浏览器的窗口，而不是打开新窗口
   var A = window.open("/connect/auth_login/qq","TencentLogin","width=450,height=320,menubar=0,scrollbars=1,resizable=1,status=1,titlebar=0,toolbar=0,location=1");
}

</script>
</head>
<body style="margin:20px;">
	<!-- <a href="#" onclick='toLogin()'> -->
	<a href="/connect/auth_login.php?type=qq">
  		QQ登陆
  	</a><br/>
  	<a href="/connect/auth_login.php?type=wechat">
  		微信登陆
  	</a><br/>
  	<a href="/connect/auth_login.php?type=weibo">
  		微博登陆
  	</a><br/>
</body>
</html>