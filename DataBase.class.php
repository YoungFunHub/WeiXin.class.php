<?php
/**
* 功能：数据库类（mysqli版）
* 作者：杨
* 时间：2017/1/29
* 注：看了一下mysqli和mysql，显然mysqli高级一点，mysqli有面向对象和面向过程两种写法
*     因为如果用面向对象，在类内部的函数里面要么一直用$this->mysqli,要么在前面赋值一
*     次，有点麻烦，所以就用函数比较好。
*/
include_once 'dbConfig.php';

class DataBase {

     public  $debug;                        //调试开启
     public  $results;                      //数据库查询结果集（数组）
     private $db_host;                      //数据库主机
     private $db_user;                      //数据库登陆名
     private $db_pwd;                       //数据库登陆密码
     private $db_charset;                   //数据库字符编码
     private $db_name;                      //数据库名
     private $mysqli;                       //数据库连接标识
     private $msg = "";                     //数据库操纵信息

     /*new对象时可以什么都不填,一般填一个数据库名字*/
     public function __construct($db_name = '', $debug = false, $db_host = dbHost, $db_user = dbUser, $db_pwd = dbPassword, $db_charset = dbCharset) {
         $this->db_host = $db_host;
         $this->db_user = $db_user;
         $this->db_pwd  = $db_pwd;
         $this->db_name = $db_name;
         $this->db_charset = $db_charset;
         $this->debug   = $debug;
         $this->results = array();
         $this->initConnect();
     }

     /*连接数据库*/
     public function initConnect() {
         $mysqli = new mysqli($this->db_host, $this->db_user, $this->db_pwd, $this->db_name);
         //连接失败时的返回
         if ($mysqli->connect_errno) {
            echo "错误原因：: Unable to connect to MySQL." . "<br/>";
            echo "错误代码: " . $mysqli->connect_errno . "<br/>";   //这里用的是面向过程的函数
            echo "错误解释: " . $mysqli->connect_error . "<br/>";   //这里用的是面向过程的函数
            exit;   
         }else if($this->debug){
            echo "连接数据库".$this->db_name."成功（初始连接）。<br/>";
         }
         //设置编码为utf8
         if (!$mysqli->set_charset("utf8")) {
            echo "设置编码格式时发送错误: " . $mysqli->error . "<br/>";
            echo "错误代码: " . $mysqli->errno . "<br/>";
            exit();
         }
         $this->mysqli = $mysqli;
     }

     /*选择数据库*/
     public function selectDb($dbname) {
         $this->db_name = $dbname;
         if (mysqli_select_db($this->mysqli, $dbname)) {
             echo "连接数据库时发生错误: " . mysqli_error($this->mysqli) . "<br/>";
             echo "错误代码: " . mysqli_errno($this->mysqli) . "<br/>";
             exit();
         }else if($this->debug){
            echo "连接数据库".$dbname."成功（后继连接）。<br/>";
         }
     }

     /*执行SQL语句*/
     public function query($sql) {
         if (!mysqli_real_query($this->mysqli, $sql)) {
             echo "SQL语句执行时出错: " . mysqli_error($this->mysqli) . "<br/>";
             echo "错误代码: " . mysqli_errno($this->mysqli) . "<br/>";
             echo "SQL语句: " . $sql . "<br/>";
             exit();
         }else if($this->debug){
            echo "SQL语句执行成功：".$sql."<br/>";
         }
         if ($result = mysqli_store_result($this->mysqli)) {
            while($row = mysqli_fetch_array($result)){
                $this->results[] =  $row;
            } 
            // $this->results = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
         }
         if ($this->debug) {
             if($this->results){
                 echo "受影响行数：" . mysqli_affected_rows($this->mysqli) . "<br/>";
                 if ($this->debug === "print") {
                    print_r($this->results);
                 }
                 echo "<br/>";
             }
         }
         return $this->results;
     }
 }