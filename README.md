微信OAuth2.0网页授权接口
===========

微信OAuth2.0网页授权接口的thinkphp实现版本，主要实现了oauth网页受权，以及部分其他接口。  
[使用方法](#usage)

####为什么用OAuth2.0受权？  
通过OAuth2.0受权的网页将会获取到打开者的微信信息，甚至包括微信昵称、头像等有用的数据，开发者们可以凭此设计出更多更丰富的页面应用，比如最近一直很火爆的红包类活动。除此之外还有个额外的好处，就是可以控制页面在非微信浏览器中无法打开，可以减少代码被人窥窃的风险。   

####那么红包类活动是如何使用OAuth2.0受权接口的呢？    
1.首先用户打开页面连接（php页面），php接收到请求后将页面跳转到微信的OAuth2.0受权页面，在获取到受权后再次将页面跳转回php服务器；此次跳转中带有用户的各种信息，php服务器记录后返回用户所看到的页面。  
2.然后用户转发此页面，在转发的连接中带有自己的标识参数。当好友点击分享后的连接的时候也会重复第1步的步骤，当php服务器发现从微信OAuth2.0受权返回的数据中的用户数据与标识参数对应的分享者的用户数据不一致的时候，则可以判断出有好友打开了分享页面，给用户增加一个红包。  

====================
<a name="usage"></a>
###使用方法  

>AuthAction.class.php ---- 认证基类  
>IndexAction.class.php --- 测试类  
>index/Conf/config.php --- 设置  
>> wx_appID						微信公众账号的appid  
>> wx_appsecret    				微信公众账号的appsecret  
>> weixin_token    				微信公众账号接口配置信息的Token  
>> wx_webauth_callback_url  	OAuth2.0授权后跳转到的默认页面   
>> wx_webauth_expire  			OAuth2.0授权Token过期时间默认6500  

在AuthAction中的初始化函数```_initialize```中进行了OAuth2.0受权，所有基于AuthAction的控制器都将进行受权过程(除了微信API认证过程wechatInitAuth)。  
对于同一用户在受权过期时间内多次打开此控制器的页面，将不会进行多次受权，因为其受权信息记录在session中，以免重复受权，减慢访问速度。受权过期时间在```index/Conf/config.php```中```wx_webauth_expire```设置，建议不要大于微信的过期时间7200秒。  

====================

###测试  

>IndexAction.class.php --- 测试类  

######申请微信测试公众帐号  
微信提供测试用的公众账号，此帐号只能添加100个关注者且只有__已关注__的用户才可以进行OAuth2.0受权。  
[点此开通测试帐号](http://mp.weixin.qq.com/debug/cgi-bin/sandbox?t=sandbox/login)  

1.开通后将```appID```、```appsecret```、```Token```填入```index/Conf/config.php```中。然后将接口配置信息中的URL改至php服务器，将地址定位到```index.php/Index/wechatInitAuth```进行微信API认证，直到提示配置成功。  
![](https://raw.githubusercontent.com/uedtianji/weixin_auth/master/images/1.jpg)  
2.点击‘体验接口权限表--OAuth2.0网页授权（仅关注者才能授权）’中的修改，将授权回调页面域名改为php服务器地址。直到出现‘通过安全监测’。  
![](https://raw.githubusercontent.com/uedtianji/weixin_auth/master/images/2.jpg)  
![](https://raw.githubusercontent.com/uedtianji/weixin_auth/master/images/3.jpg)  

配置完微信测试号后，在微信中打开```http://项目目录/index.php```（例：```http://121.40.135.90/weixin_auth/index.php```）将会在页面中打印出session中的受权数据，表示测试受权成功。