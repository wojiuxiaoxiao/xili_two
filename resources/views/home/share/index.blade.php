<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<title>{{$program['name']}}</title>
		<style>
			/*通用样式*/
			body { font: 15px "yahei microsoft", Arial, Helvetica,sans-serif;color: #333; }
			ul,li,p,h1,h2,h3,h4,h5,h6,dl,dd,dt{ border: none;list-style: none; }
			* { padding: 0;margin: 0;outline: none;-webkit-tap-highlight-color: rgba(255,0,0,0);-webkit-user-select: text; }
			.lf { float: left; }
			.rt { float: right; }
			a{text-decoration:none;color:#c19275;}
			a:hover{text-decoration:underline;}
			.zoom:after { clear: both;content: "/";height: 0;display: block;visibility: hidden;overflow: hidden; }
			img { width: 100%;height: 100%; }

			/*顶部样式*/
			.top { padding: 0 15px;padding-left: 9px;border-bottom: 1px solid #d8d8d8;}
			.logo { width: 165px;margin-top: 2px;}
			.open { height: 35px;line-height: 35px;width: 96px;text-align: center;border: 1px solid #c19c75;border-radius: 4px;margin-top: 12px;color: #c19c75; }

			.video { width: 300px;margin: 0 auto;position: relative;margin-top: 30px;box-shadow:5px 5px 3px #f0f0f0;-moz-box-shadow:5px 5px 3px #f0f0f0;-webkit-box-shadow:5px 5px 3px #f0f0f0; }
			.poster { width: 100%;height: 240px;background-repeat:  no-repeat;background-position:  center;background-size:100% 100%;font-size: 50px;border-top-right-radius: 5px;border-top-left-radius: 5px; }
			.poster img {display: block;margin: 0 auto;width: 55px;height: 55px;padding-top: 76px;}
			.title { width: 300px;margin: 15px auto; height: 35px;line-height: 35px;color: #323333;text-align: center;overflow: hidden;text-overflow:ellipsis;white-space: nowrap;-webkit-line-clamp: 2;-webkit-box-orient: vertical; }

			.play-box {width: 100%;position: absolute;left: 0;bottom: 0;height: 4px;z-index: 10;}
			.play-box .left {width: 100%;float: left; background-color: #f8f8f8;height:4px;}
			.play-box .left div.timeline { width: calc(100% - 0.8rem);height: 4px; position: absolute;left: 0;bottom: 0; z-index: 10;}
			.play-box .left div.timeline span {position: absolute;top: 0;left:0;width: 0px;height: 4px;background-color: #c19c75;display: block;-webkit-transition: width ease-out 0.3s;-o-transition: width ease-out 0.3s;transition: width ease-out 0.3s;z-index: 196;}
			.play-box .left div.timeline span:after{content: ""; position: absolute; top: -4px; right:-0.8rem;width: 0.8rem; height:0.8rem; border-radius: 50%;background-color: #fff;z-index: 99}
			.play-box .left div.info { height: 26px; line-height: 26px; font-size: 12px; position: absolute; bottom: 8px; z-index: 1;color: #FFF;width: calc(100% - 24px);margin-left: 12px; }
			.play-box .left div.info .size { float: left; display: block;}
			.play-box .left div.info .timeshow { float: right; display: block;}
			.buff { position: absolute;bottom: 0;left: 0;height:4px;z-index: 11;background: #d8d8d8; }

			.foot { position: fixed;left: 0;bottom: 30px;width: 100%; }
			.app { border: 1px solid #c19c75; color: #c19c75;width: 50%;text-align: center;padding: 10px 0;border-radius: 5px;margin: 0 auto; }
		</style>
		<script src="{{ URL::asset('js/jquery-1.11.0.min.js') }}"></script><!--需要引入jquery.min.js-->
	</head>
	<body>
		<div class="top zoom">
			<div class="logo lf">
				<img src="{{ URL::asset('img/logo.png') }}" alt="" />
			</div>
			<div class="open rt"><a href="{{$program['down_url']}}">打开APP</a></div>
		</div>
		<div class="main">
			<audio class="audio" id="audio" src="{{$program['radio_url']}}"></audio>
			<div class="video">
				<div class="poster" style="background-image: url({{$program['radio_pic']}});">
					<img class="play-state" src="{{ URL::asset('img/play.png') }}">
					<div style="position: absolute;top:53.5px;left:0;width:100%;height:100px;z-index: 44;">
						<div class="stroke" style="width:100px;margin:0 auto;height: 100px;z-index: 100;"></div>
					</div>
				</div>
				<div class="play-box">
			      	<div class="left">
			        	<div class="timeline"><span style=""></span><div class="buff"></div></div>
			        	<div class="info">
			          		<span class="size">00:00</span>
			          		<span class="timeshow">{{$program['burning_time']}}</span>
			        	</div>
			      	</div>
			    </div>
			</div>
			<div class="title">{{$program['name']}}</div>
		</div>
		{{--<div class="foot">--}}
			{{--<div class="app"><a href="{{$program['down_url']}}">打开APP参与讨论</a></div>--}}
		{{--</div>--}}
		<script type="text/javascript">
			$(function() {
				var audio = document.getElementById('audio');
				var drag = document.getElementById('drag');
				var currentTime = audio.currentTime;
				var startX, moveEndX, X;
				//$('audio').on('loadedmetadata', function() {
					$(document).on('touchend','.timeline',function(e){
						e.preventDefault();
				       	var x = e.originalEvent.changedTouches[0].clientX-this.offsetLeft;
				       	var X = x < 0 ? 0 : x ;
				       	var W = $(this).width();
				       	var place = X > W ? W : X;
				       	audio.currentTime = (place/W).toFixed(2)*audio.duration
				       	$(this).children().css({width:(place/W).toFixed(2)*100+"%"})
				    });
					$('.stroke').on('click',function(){
						if(audio.paused){
							audio.play();
							$('.play-state').attr('src','{{ URL::asset('img/paused.png') }}');
						}else{
							audio.pause();
							$('.play-state').attr('src','{{ URL::asset('img/play.png') }}');
						}
					});
					setInterval(function(){
						if(audio.paused){
							$('.play-state').attr('src','img/play.png');
						}else{
							$('.play-state').attr('src','img/paused.png');
						}
					},1000);
					setInterval(function() {
						var current = audio.currentTime;
						var timeRages = audio.buffered;
						var buffered = timeRages.end(timeRages.length - 1);
	　　　　　　　　　	setTimeShow(current);
						buff(buffered);
	　　　　　　　　}, 1000);
					function setTimeShow(t) {
	　　　　　　　　　　t = Math.floor(t);
						var playTime = secondToMin(audio.currentTime);
					    $(".size").html(playTime);
//						$('.timeshow').text(secondToMin(audio.duration));
	　　　　　　　　　　$('.timeline').children().css({width:(t/audio.duration).toFixed(4)*100+"%"});
	　　　　　　　　}
					function buff(t) {
	　　　　　　　　　　t = Math.floor(t);
	　　　　　　　　　　$('.buff').css({
	　　　　　　　　　　　　width: (t / audio.duration).toFixed(4) * 100 + "%"
	　　　　　　　　　　})
	　　　　　　　　}
					function secondToMin(s) {
				     	var MM = Math.floor(s / 60);
				     	var SS = s % 60;
				     	if (MM < 10)
				       		MM = "0" + MM;
				     	if (SS < 10)
				       		SS = "0" + SS;
				     		var min = MM + ":" + SS;
				     		return min.split('.')[0];
				   	}
				//});
			});
		</script>
	</body>
</html>
