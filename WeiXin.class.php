<?php
/**
* 作者：Yang
* 参数：appId & secret & testId（该公众号下任意一个已关注用户的openId） & deBug （是否打开调试输出 true/false/"print" "print"时打印从数据库中获取token时的情况）
* 功能：1.实例化即自动获取\刷新access_token并存入数据库中
*       2.获取用户头像和昵称等信息
*       3.获取微信网页开发的ticket
*       4.配置微信服务器
*       5.微信服务器上的临时、永久文件操作
* 方法：1.getAccessToken()-------------------------------------------------获取access_token
*       2.getUserInfoByAT($openId)---------------------------------------------------通过access_token和openId获取用户信息
*       3.getUserInfoByCode()-----------------------------------------------网页授权认证是获取用户信息
*       4.getSignature()---------------------------------------------------获取使用JDK的凭据（JDK签名）、时间戳、随机字符串、appid
*       5.makeUrl($url, $type = "base"/"info")------------------------------参数为认证端的首页（包括http协议头），返回可在微信端认证获取用户信息的页面链接，参数为base时为无感验证（仅可用于关注用户），为info是为有感验证
*       6.checkSignature($token)-------------------------------------------参数为你在页面设置的token，验证身份，绑定微信公众号与服务器
*       7.tempUpload($type, $filePath)-----------------------------上传临时文件，type参数图片（image）、语音（voice）、视频（video）和缩略图（thumb）为文件类型，filePath参数为文件在本地的绝对路径
*       8.foreUpload($type, $filePath , $title='', $intro='')-------上传永久文件，前两个和上传临时文件一样，但是当type为video时，需设置第三、第四个参数
*       9.tempDownload($media_id)---------------------下载临时文件，参数为文件上传时获取的media_id，文件保存在此文件上层目录的tempFiles中
*       10.foreDownload($media_id)---------------------同临时下载
*
*/
include_once 'DataBase.class.php';

class WeiXin
{
    public    $access_token;
    public    $userInfo;
    public    $deBug;
    protected $jsapi_ticket;
    protected $db;
    protected $appId;
    protected $secret;
    protected $testId;
    function __construct($appId="wx35d217408956f8tt", $secret="143ac50a4abb8a47c9ac8f330fc1977t", $testId="oYeDBjpSeFbpwbZiKuJKZXqSNo60", $deBug = false)
    {
        $this->appId  = $appId;
        $this->secret = $secret; 
        $this->testId = $testId; 
        $this->deBug  = $deBug; 
        $this->getAccessToken();
    }

    /*配置服务器*/
    public function checkSignature($token){
        // 1）将token、timestamp、nonce三个参数进行字典序排序
        $timestamp = $_GET['timestamp'];
        $nonce     = $_GET['nonce'];
        $signature = $_GET['signature'];
        $temArr    = array($timestamp, $nonce, $token);
        sort($temArr);

        //2）将三个参数字符串拼接成一个字符串进行sha1加密
        $temStr = implode( $temArr ); 
        $temStr = sha1($temStr); 

        //3）开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
        if ($temStr == $signature && isset($_GET['echostr'])) {
            $echostr = $_GET['echostr'];
            echo $echostr;
            exit;
        }
        else{
            return '{"errcode":-1,"errmsg":"失败"}';
        }
    }

    /**
    * 获取普通access_token(单值)
    * 开发者提供openID(析构函数传入)用获取信息接口检验access_token的可用性
    */
    public function getAccessToken(){
        //从数据库中获取
        $db = new DataBase('wechat', $this->deBug);
        $db->query('SELECT * FROM access_token WHERE id = 0');
        //判断是否有
        if ($db->results) {
            $data = $db->results;
            $access_token = $data[0]['token'];
            $expires_in   = $data[0]['expires_in']; //有效时间
            $expires_time = $data[0]['expires_time']; //录入时间 
            if (time() >= ($expires_time + $expires_in) || $access_token == "") {
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->secret;
                $res = file_get_contents($url);
                $result = json_decode($res, true);
                $access_token = $result["access_token"];
                $expires_in   = $result['expires_in']; //有效时间
                $expires_time = time(); //录入时间
                $date = date("Y-m-d H:i:s"); //可观时间
                $db->query("UPDATE access_token SET token = '$access_token', expires_in = '$expires_in', expires_time = '$expires_time', date = '$date' WHERE id = 0");
            }else{
                $url  = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=". $access_token ."&openid=". $this->testId ."&lang=zh_CN";
                $res  = file_get_contents($url);
                $data = json_decode($res, 1);
                if (!isset($data['nickname'])) {
                    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->secret;
                    $res = file_get_contents($url);
                    $result = json_decode($res, true);
                    $access_token = $result["access_token"];
                    $expires_in   = $result['expires_in']; //有效时间
                    $expires_time = time(); //录入时间
                    $date = date("Y-m-d H:i:s"); //可观时间
                    $db->query("UPDATE access_token SET token = '$access_token', expires_in = '$expires_in', expires_time = '$expires_time', date = '$date' WHERE id = 0");
                }
            }
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->secret;
            $res = file_get_contents($url);
            $result = json_decode($res, true);
            $access_token = $result["access_token"];
            $expires_in   = $result['expires_in']; //有效时间
            $expires_time = time(); //录入时间
            $date = date("Y-m-d H:i:s"); //可观时间
            $db->query("INSERT INTO access_token (token,expires_in,expires_time,date) VALUES ('$access_token','$expires_in','$expires_time','$date') ");
        }
        $this->access_token = $access_token;   //传入类全局变量
        $this->db = $db;
        return $access_token;
    }

    /*通过access_token获取用户信息*/
    public function getUserInfoByAT($openId){
        $url  = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=". $this->access_token ."&openid=". $openId ."&lang=zh_CN";
        $res  = file_get_contents($url);
        return $res;
    }

    /*通过网页授权认证时的code获取用户信息*/
    public function getUserInfoByCode(){
        // 获取认证授权code
        preg_match_all('/code=(.*?)&state=(.*?)$/', $_SERVER["QUERY_STRING"], $data);
        $code = $data[1][0];
        $type = $data[2][0];
        // 用code获取网页认证授权access_token和openid
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appId."&secret=".$this->secret."&code=".$code."&grant_type=authorization_code";
        $res = file_get_contents($url);       
        $result = json_decode($res, true);
        if ($type == "base") {
            if (isset($_COOKIE["userInfo"])) {
                $userInfo  = $_COOKIE["userInfo"];
            }else{
                $openId   = $result["openid"];
                $userInfo = $this->getUserInfoByAT($openId);
                setcookie("userInfo", $userInfo);
            }
        }else{
            if (isset($_COOKIE["userInfo"])) {
                $userInfo  = $_COOKIE["userInfo"];
            }else{
                $web_token = $result["access_token"];
                $openId    = $result["openid"];
                $url       = "https://api.weixin.qq.com/sns/userinfo?access_token=".$web_token."&openid=".$openId."&lang=zh_CN";
                $userInfo  = file_get_contents($url);
                setcookie("userInfo", $userInfo);
            }
        }
        return $userInfo;
    }

    /*获取JDk签名，和时间戳、随机字符串、appid一并以json形式返回*/
    public function getSignature(){
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; //获取地址栏完整url（带参数）
        $jsTicket  = $this->_getJsTicket();
        var_dump($jsTicket);
        $timestamp = time();
        $noncestr  = "JiaoWoSuiJiZiFuChuan";     //可随机定义的字符串
        $string    = "jsapi_ticket=".$jsTicket."&noncestr=".$noncestr."&timestamp=".$timestamp."&url=".$url;
        $signature = sha1($string);
        $signArray = array("timestamp"=>"$timestamp", "noncestr"=>"$noncestr", "signature"=>"$signature", "appid"=>"$this->appId");
        $signJson  = json_encode($signArray);
        return $signJson;
    }

    /**
    * 功能：生成微信端可认证的url
    * 参数：url（必选）——————业务页面
    *       type（可选）—————参数base：只能获取用户openId，特点是无感验证，要再用用户信息获取接口（500w/天）来获取用户信息，
    *                                  在点击量没有500w的时候用体验会好点, 但是未关注公众号的用户无法使用。
    *                        参数info：能通过网页access_token来获取用户信息（无次数限制），要点击确认授权，用户量特别大的时候用
    *       state（不可选）——生成的链接点击重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
    */
    public function makeUrl($url, $type = "base"){
        $url = urlencode($url);
        if ($type == "base") {
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appId."&redirect_uri=".$url."&response_type=code&scope=snsapi_base&state=base#wechat_redirect";
        }else{
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appId."&redirect_uri=".$url."&response_type=code&scope=snsapi_userinfo&state=info#wechat_redirect";
        }
        return $url;
    }

    /*获取jsapi_ticket(单值)*/
    private function _getJsTicket(){
        //从数据库中获取
        $db = $this->db;
        $db->query('SELECT * FROM jsapi_ticket WHERE id = 0');
        //判断是否有
        if ($db->results) {
            $data = $db->results;
            $jsapi_ticket = $data[0]['ticket'];
            $expires_in   = $data[0]['expires_in']; //有效时间
            $expires_time = $data[0]['expires_time']; //录入时间 
            if (time() >= ($expires_time + $expires_in) || $jsapi_ticket == "") {
                $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $this->access_token . "&type=jsapi";
                $res = file_get_contents($url);
                $result = json_decode($res, true);
                if ($result["errcode"] != 0) {
                    $this->getAccessToken();
                    $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $this->access_token . "&type=jsapi";
                    $res = file_get_contents($url);
                    $result = json_decode($res, true);
                    if ($result["errcode"] != 0) {
                        exit("获取jsapi_ticket时发生未知错误！");
                    }
                }
                $jsapi_ticket = $result["ticket"];
                $expires_in   = $result['expires_in']; //有效时间
                $expires_time = time(); //录入时间
                $date = date("Y-m-d H:i:s"); //可观时间
                $db->query("UPDATE jsapi_ticket SET ticket = '$jsapi_ticket', expires_in = '$expires_in', expires_time = '$expires_time', date = '$date' WHERE id = 0");  
            }
        }else{
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $this->access_token . "&type=jsapi";
            $res = file_get_contents($url);
            $result = json_decode($res, true);
            if ($result["errcode"] != 0) {
                $this->getAccessToken();
                $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $this->access_token . "&type=jsapi";
                $res = file_get_contents($url);
                $result = json_decode($res, true);
                if ($result["errcode"] != 0) {
                    exit("获取jsapi_ticket时发生未知错误！");
                }
            }
            $jsapi_ticket = $result["ticket"];
            $expires_in   = $result['expires_in']; //有效时间
            $expires_time = time(); //录入时间
            $date = date("Y-m-d H:i:s"); //可观时间
            $db->query("INSERT INTO jsapi_ticket (ticket,expires_in,expires_time,date) VALUES ('$jsapi_ticket','$expires_in','$expires_time','$date') ");
        }
        $this->jsapi_ticket = $jsapi_ticket;   //传入类全局变量
        $this->db = $db;
        return $jsapi_ticket;
    }

    /**
    * 以下为素材管理接口
    */

    /*存储文件*/
    function _saveFile($fileName, $fileContent)
    {
        $dir = dirname(dirname(__FILE__))."/tempFiles/";
        if(!is_dir($dir)){
            mkdir($dir, 0777);
        }
        $localFile = fopen($dir.$fileName, 'w');
        if (false !== $localFile){
            if (false !== fwrite($localFile, $fileContent)) {
                fclose($localFile);
                return true;
            }else{
                die("$fileName-文件存储失败。"."<br/>");
            }
        }else{
            die("$fileName-文件存储失败。"."<br/>");
        }
    }

    /**
    * 上传临时文件（保存3天）到微信服务器
    * 参数：type（必选）——————图片（image）、语音（voice）、视频（video）和缩略图（thumb）
    *       filePath——————————文件路径，绝对路径
    */
    public function tempUpload($type, $filePath){
        if(!file_exists($filePath)) die("$filePath——————文件不存在，请检查路径。"); 
        $url  = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$this->access_token."&type=".$type;
        $data = array('media' => '@' . $filePath);
        //curl设置
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //开启后从浏览器输出，curl_exec()方法没有返回值
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
    * 上传永久文件到微信服务器
    * 参数：type（必选）——————图片（image）、语音（voice）、视频（video）和缩略图（thumb）
    *       filePath（必选）——————————文件路径，绝对路径
    *       title（type为video时必选）——————视频标题
    *       intro（type为video时必选）——————视频描述
    */
    public function foreUpload($type, $filePath , $title='', $intro=''){
        if(!file_exists($filePath)) die("$filePath——文件不存在，请检查路径。"); 
        $url  = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=".$this->access_token."&type=".$type;
        if ($type == "video") {
            $video = array('title'        => $title,
                           'introduction' => $intro);
            $video_json = json_encode($video);
            $data = array('media'       => '@' . $filePath,
                          'description' => $video_json);
        }else{
            $data = array('media' => '@' . $filePath);
        }
        
        //curl设置
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //开启后从浏览器输出，curl_exec()方法没有返回值
        $result = curl_exec($ch);
        curl_close($ch);
        echo $result;
    }

    /**
    * 下载微信服务器上的临时文件
    * 参数：media_id————上传时获取的mediaid
    *       type————————文件的后缀
    */
    public function tempDownload($media_id){
        $url     = "http://api.weixin.qq.com/cgi-bin/media/get?access_token=".$this->access_token."&media_id=".$media_id;
        $file    = file_get_contents($url);
        $headers = get_headers($url, 1);
        preg_match('/filename="(.*?)"/', $headers["Content-disposition"], $matches);
        $this->_saveFile($matches[1], $file);
    }

    /*下载微信服务器上的永久文件*/
    public function foreDownload($media_id){
        $url  = "https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=".$this->access_token;
        $data = '{"media_id":"'.$media_id.'"}';
        //curl设置——获取headers
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, true);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //开启后从浏览器输出，curl_exec()方法没有返回值
        $header = curl_exec($ch);
        curl_close($ch);
        //curl设置——获取文件内容
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    //开启后从浏览器输出，curl_exec()方法没有返回值
        $file = curl_exec($ch);
        curl_close($ch);
        preg_match('/filename="\/(.*)\/(.*?).(.*?)"/', $header, $matches);
        $this->_saveFile($media_id.$matches[3], $file);
    }
}

?>