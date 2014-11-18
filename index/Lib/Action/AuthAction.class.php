<?php
class AuthAction extends Action {
	/**
     * Initialize Method
     * 
     * @access public
     * @return void
     */
    public function _initialize () {
		$current_url = $this->get_url();
        //如果action为wechatInitAuth（微信API接入是使用），则不需要做OAuth2.0认证
        if(ACTION_NAME =="wechatInitAuth"){
            //微信初始化认证
            $this->wechatInitAuth();
            exit;
        }
        /*
         * OAuth2.0受权
         */
		if(isset($_REQUEST["code"])){
            //如果连接参数中不带有code参数,说明受权成功
            $code = htmlspecialchars($_REQUEST["code"]);
            //通过code换取网页授权access_token和用户信息
            $res = $this->wxGetTokenWithCode($code);
            if($res === false || empty($res["access_token"])){
                $this->error("受权失败");
            }
            //将用户信息存入session，以免对同一用户反复要求受权
            session('access_token',$res["access_token"]);
            session('refresh_token',$res["refresh_token"]);
            session('openid',$res["openid"]);
            session('scope',$res["scope"]);
            session('openidtime',time());
            
        }else{
            /*
             * 如果连接参数中不带有code参数，说明不是来自微信OAuth2.0受权返回的页面
             * 判断session的信息是否过期，如果没有信息或者过期，说明需要重新受权
             */
            if(session("?openidtime")){
                $dtime = time()-session("openidtime");
                if($dtime > C("wx_webauth_expire")){
                    //受权过期，删除session中的信息
                    $refresh_token = session('refresh_token');
                    session('access_token',null);
                    session('refresh_token',null);
                    session('openid',null);
                    session('scope',null);
                    session('openidtime',null);
                    //根据refresh_token刷新受权信息
                    $res = $this->refreshToken($refresh_token);
                    if($res === false){
                        $this->error("受权失败");
                    }else{
                        //受权信息更新完毕
                        session('access_token',$res["access_token"]);
                        session('refresh_token',$res["refresh_token"]);
                        session('openid',$res["openid"]);
                        session('scope',$res["scope"]);
                        session('openidtime',time());
                    }
                }else{
                    //受权信息过期，且保存在session中
                }
            }else{
                //session没有受权信息，进行OAuth2.0受权
                $this->wechatWebAuth($current_url);
            }
            
        }
    }
    /**
    * 微信初始化认证
    **/
    private function wechatInitAuth(){
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    
        $token = C("weixin_token");
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr == $signature ){
            echo $echoStr;
            exit;
        }else{
            exit;
        }
    }
    /**
   	* 微信auth2.0 受权
   	* @param string $redirct_url 授权后返回url
   	* @param string $scope 应用授权作用域，snsapi_base （不弹出授权页面，直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
   	**/
    private function wechatWebAuth($redirct_url = "",$scope = "snsapi_base"){
    	$redirct_url = $redirct_url === ""?C("wx_webauth_callback_url"):urlencode($redirct_url);
    	$wxurl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".C("wx_appID")."&redirect_uri=".$redirct_url."&response_type=code&scope=".$scope."&state=STATE#wechat_redirect";
    	header('Location:'.$wxurl);
    }
    /**
    * 刷新Token
    * @param string $code refresh_token refresh_token
    **/
    private function refreshToken($refresh_token) {
        if(empty($refresh_token)){
            return false;
        }
        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=' .C("wx_appID"). '&grant_type=refresh_token&refresh_token=' . $refresh_token;
        $Token = $this->curlGetInfo($url);
        $data = json_decode($Token, true);
        switch ($data['errcode']) {
            case '40029':
                $this->error('验证失败');
                return false;
                break;
        }
        return $data;
    }
    /**
   	* 通过code换取网页授权access_token和用户信息(微信auth2.0 受权)
   	* @param string $code wechatWebAuth 返回的code
   	**/
    private function wxGetTokenWithCode($code){
    	if(!isset($code)){
    		return false;
    	}
    	$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".C("wx_appID")."&secret=".C("wx_appsecret")."&code=".$code."&grant_type=authorization_code";
    	$Token = $this->curlGetInfo($url);
        $data = json_decode($Token, true);
        switch ($data['errcode']) {
            case '40029':
                $this->error('验证失败');
                return false;
                break;
        }
        return $data;
    }



    /**
     * get weixin access_token
     * @param wxid
     * @return array
     */ 
    protected function get_weixin_access_token() {
        if(S("wx_access_token")){
            return S("wx_access_token");
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".C("wx_appID")."&secret=".C("wx_appsecret");
        $Token = $this->curlGetInfo($url);
        $data = json_decode($Token, true);
        if($data['errcode']){
            Log::write('平台获取access_token错误'.$Token);
            return false;
        }
        S('wx_access_token',$data["access_token"],6000);
        return $data["access_token"];

    }

    //curl抓取网页
    private function curlGetInfo($url){
        $ch = curl_init();
         
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         
        $info = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Errno'.curl_error($ch);
        }
        
        return $info;
    }
    /**
     * 获取当前页面完整URL地址
     */
    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }
}