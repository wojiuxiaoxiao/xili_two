<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title></title>
    <style>
        body{background: #fff;}
        *{padding:0;margin:0;outline:none;-webkit-tap-highlight-color:rgba(255,0,0,0);-webkit-user-select:text;}
        .title{height:51px;padding:97px 0 84px;background: url(../style/img/title.png) no-repeat center;background-color: #c19c75;background-size: 66.7% 51px;}
        .circle{height:58px;background: url(../style/img/circle.png) no-repeat center;background-size: cover;position: relative;margin-top:-1px;}
        .logo{position: absolute;top:-50px;left:calc(50% - 50px);width:100px;height:100px;background: url(../style/img/logo.png) no-repeat center;background-size: cover;}
        .main{background: #fff;}
        .headline{height:60px;line-height:60px;text-align: center;font-size: 20px;color:#323333;}
        .small{width:7px;height:7px;border-radius: 50%;background: #c19c75;opacity: 0.14;margin-left:calc(50% - 3px);margin-top:56px;}
        .middle{width:12px;height:12px;border-radius: 50%;background: #c19c75;opacity: 0.3;margin-left:calc(50% - 5px);margin-top:5px;}
        .lager{width:17px;height:17px;border-radius: 50%;background: #c19c75;opacity: 0.6;margin-left:calc(50% - 7px);margin-top:5px;}
        .btn{width:45%;height:44px;line-height:44px;border-radius: 22px;border: 1px solid #C19C75;font-size:16px;color:#c19d75;margin:0 auto;text-align: center;margin-top:43px;}
        .btn a{color: black;text-decoration: none;}
    </style>
</head>
<body>
<div class="title"></div>
<div class="circle">
    <div class="logo"></div>
</div>
<div class="main">
    <div class="headline">十方云水 APP</div>
    <div class="small"></div>
    <div class="middle"></div>
    <div class="lager"></div>
    <div class="btn"><a href="{{$down_url}}">点击下载</a></div>
</div>
</body>
</html>