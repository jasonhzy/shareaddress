<?php

require_once ("tools.class.php");
require_once ("config.php");
require_once ("log.php");
require_once ("TenpayHttpClient.class.php");

$get_string = $_SERVER ["QUERY_STRING"];

if ($_GET ["code"] == "") {
	echo "error";
	exit ( 0 );
}

//https://api.weixin.qq.com/sns/oauth2/access_token?appid=APPID&secret=SECRET&code=CODE&grant_type=authorization_code
//获取access_token
$tokenurl = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $appsecret . "&code=" . $_GET ["code"] . "&grant_type=authorization_code";
$tokenclient = new TenpayHttpClient ();
$tokenclient->setReqContent ( $tokenurl );
$tokenclient->setMethod ( "GET" );
$tokenres = "";
if ($tokenclient->call ()) {
	$tokenres = $tokenclient->getResContent ();
}

if ($tokenres != "") {
	$tk = json_decode ( $tokenres );
	if ($tk->access_token != "") {
		log_result ( "addr|back|access_token:" . $tk->access_token . "|openid:" . $tk->openid );
		$accesstoken = $tk->access_token;
	} else {
		echo "get access token empty";
		exit ( 0 );
	}
} else {
	echo "get access token error";
	exit ( 0 );
}

//调起地址控件签名
$timestamp = time ();
$noncestr = rand ( 100000, 999999 );
$url = "http://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];

$myaddr = new SignTool ();
$myaddr->setParameter ( "appid", $appid );
$myaddr->setParameter ( "url", $url );
$myaddr->setParameter ( "noncestr", $noncestr );
$myaddr->setParameter ( "timestamp", $timestamp );
$myaddr->setParameter ( "accesstoken", $accesstoken );

$addrsign = $myaddr->genSha1Sign ();

$addrstring = $myaddr->getDebugInfo ();
log_result ( "addr|back|addsign:" . $addrstring );

?>


<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta id="viewport" name="viewport"
	content="width=device-width; initial-scale=1.0; maximum-scale=1; user-scalable=no;" />
<title>微信支付</title>
<style type="text/css">
/* 重置 [[*/
body,p,ul,li,h1,h2,form,input {
	margin: 0;
	padding: 0;
}

h1,h2 {
	font-size: 100%;
}

ul {
	list-style: none;
}

body {
	-webkit-user-select: none;
	-webkit-text-size-adjust: none;
	font-family: Helvetica;
	background: #ECECEC;
}

html,body {
	height: 100%;
}

a,button,input,img {
	-webkit-touch-callout: none;
	outline: none;
}

a {
	text-decoration: none;
}

/* 重置 ]]*/
/* 功能 [[*/
.hide {
	display: none !important;
}

.cf:after {
	content: ".";
	display: block;
	height: 0;
	clear: both;
	visibility: hidden;
}

/* 功能 ]]*/
/* 按钮 [[*/
a[class *="btn"] {
	display: block;
	height: 42px;
	line-height: 42px;
	color: #FFFFFF;
	text-align: center;
	border-radius: 5px;
}

.btn-blue {
	background: #3D87C3;
	border: 1px solid #1C5E93;
}

.btn-green {
	background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0, #43C750),
		color-stop(1, #31AB40) );
	border: 1px solid #2E993C;
	box-shadow: 0 1px 0 0 #69D273 inset;
}

/* 按钮 [[*/
/* 充值页 [[*/
.charge {
	font-family: Helvetica;
	padding-bottom: 10px;
	-webkit-user-select: none;
}

.charge h1 {
	height: 44px;
	line-height: 44px;
	color: #FFFFFF;
	background: #3D87C3;
	text-align: center;
	font-size: 20px;
	-webkit-box-sizing: border-box;
	box-sizing: border-box;
}

.charge h2 {
	font-size: 14px;
	color: #777777;
	margin: 5px 0;
	text-align: center;
}

.charge .content {
	padding: 10px 12px;
}

.charge .select li {
	position: relative;
	display: block;
	float: left;
	width: 100%;
	margin-right: 2%;
	height: 230px;
	line-height: 230px;
	text-align: center;
	border: 1px solid #BBBBBB;
	color: #666666;
	font-size: 16px;
	margin-bottom: 5px;
	border-radius: 3px;
	background-color: #FFFFFF;
	-webkit-box-sizing: border-box;
	box-sizing: border-box;
	overflow: hidden;
}

.charge .price {
	border-bottom: 1px dashed #C9C9C9;
	padding: 10px 10px 15px;
	margin-bottom: 20px;
	color: #666666;
	font-size: 12px;
}

.charge .price strong {
	font-weight: normal;
	color: #EE6209;
	font-size: 26px;
	font-family: Helvetica;
}

.charge .showaddr {
	border: 1px dashed #C9C9C9;
	padding: 10px 10px 15px;
	margin-bottom: 20px;
	color: #666666;
	font-size: 12px;
	text-align: center;
}

.charge .showaddr strong {
	font-weight: normal;
	color: #9900FF;
	font-size: 26px;
	font-family: Helvetica;
}

.charge .copy-right {
	margin: 5px 0;
	font-size: 12px;
	color: #848484;
	text-align: center;
}
/* 充值页 ]]*/
</style>
</head>
<script language="javascript">
document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
	WeixinJSBridge.call('hideToolbar');
});

function getaddr(){
	WeixinJSBridge.invoke('editAddress',{
		"appId" : "<?php
		echo $appid?>",
		"scope" : "jsapi_address",
		"signType" : "sha1",
		"addrSign" : "<?php
		echo $addrsign?>",
		"timeStamp" : "<?php
		echo $timestamp?>",
		"nonceStr" : "<?php
		echo $noncestr?>",
	},function(res){
		//若res 中所带的返回值不为空，则表示用户选择该返回值作为收货地址。否则若返回空，则表示用户取消了这一次编辑收货地址。
		if(res.err_msg == 'edit_address:ok'){
			//alert("收件人："+res.userName+"  联系电话："+res.telNumber+"  收货地址："+res.proviceFirstStageName+res.addressCitySecondStageName+res.addressCountiesThirdStageName+res.addressDetailInfo+"  邮编："+res.addressPostalCode);
			document.getElementById("showAddress").innerHTML="收件人："+res.userName+"  联系电话："+res.telNumber+"  收货地址："+res.proviceFirstStageName+res.addressCitySecondStageName+res.addressCountiesThirdStageName+res.addressDetailInfo+"  邮编："+res.addressPostalCode;
		}
		else{
			alert("获取地址失败，请重新点击");
		}
	});
}
</script>

<body>
<article class="charge">
<h1>微信支付-JSAPI-demo</h1>
<section class="content">
<h2>商品：测试商品。</h2>
<ul class="select cf">
	<li><img src="./weixin.jpg"></li>
</ul>
<p class="copy-right">亲，此商品不提供退款和发货服务哦</p>
<div class="price">微信价：<strong>￥0.01元</strong></div>
<div class="showaddr" id="showAddress"><a id="editAddress" 	href="javascript:getaddr();"><strong>设置收货地址</strong></a></div>
<p class="copy-right">微信支付demo 由腾讯财付通提供</p>
</section>
</article>
</body>
</html>