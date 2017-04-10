# connect_auth 
* 本代码将微信、QQ、微博第三方登陆整合，每个登陆逻辑对应一个class，可以通过调用实现对不同类型的登陆。

## 代码说明 ##
* login.php 
  主要是登陆按钮的显示
* auth_login.php 
  点击登陆按钮，访问的代码，根据不同类型去调用不同的联合登陆
* auth/OAuthLoginClass.php
  登陆实现，并返回info信息
* auth/OAuthQqClass.php
  QQ auth
* auth/OAuthWechatClass.php
  微信 auth
* auth/OAuthWeiboClass.php
  微博 auth
## 使用方法
* 在不同的auth登陆代码中，填写对应的app_id，app_secret，和回调url。回调url需在开发平台设置；
* 在执行登陆代码处，引入auth/OAuthLoginClass.php 文件，调用authLogin方法，返回联合登陆信息；
* 获取信息后，根据unionid，查询是否绑定网站，若绑定则执行登陆；若未绑定账号，1）如果用户在网站上有帐号，则输入网站帐号和密码成功登录网站后，并将该帐号与unionid绑定。2）如果用户在该网站上没有帐号，则用户自行注册，注册成功后与auth登陆的unionid绑定.
