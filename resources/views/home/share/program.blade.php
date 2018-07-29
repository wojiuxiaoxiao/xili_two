<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="format-detection" content="telephone=no,email=no,date=no,address=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>@if (!empty($body))
            {{ $body['name'] }}
           @endif
    </title>
    <script>
        ;(function(win, lib) {
            var doc = win.document;
            var docEl = doc.documentElement;
            var metaEl = doc.querySelector('meta[name="viewport"]');
            var flexibleEl = doc.querySelector('meta[name="flexible"]');
            var dpr = 0;
            var scale = 0;
            var tid;
            var flexible = lib.flexible || (lib.flexible = {});

            if (metaEl) {
                console.warn('将根据已有的meta标签来设置缩放比例');
                var match = metaEl.getAttribute('content').match(/initial\-scale=([\d\.]+)/);
                if (match) {
                    scale = parseFloat(match[1]);
                    dpr = parseInt(1 / scale);
                }
            } else if (flexibleEl) {
                var content = flexibleEl.getAttribute('content');
                if (content) {
                    var initialDpr = content.match(/initial\-dpr=([\d\.]+)/);
                    var maximumDpr = content.match(/maximum\-dpr=([\d\.]+)/);
                    if (initialDpr) {
                        dpr = parseFloat(initialDpr[1]);
                        scale = parseFloat((1 / dpr).toFixed(2));
                    }
                    if (maximumDpr) {
                        dpr = parseFloat(maximumDpr[1]);
                        scale = parseFloat((1 / dpr).toFixed(2));
                    }
                }
            }

            if (!dpr && !scale) {
                var isAndroid = win.navigator.appVersion.match(/android/gi);
                var isIPhone = win.navigator.appVersion.match(/iphone/gi);
                var devicePixelRatio = win.devicePixelRatio;
                if (isIPhone) {
                    // iOS下，对于2和3的屏，用2倍的方案，其余的用1倍方案
                    if (devicePixelRatio >= 3 && (!dpr || dpr >= 3)) {
                        dpr = 3;
                    } else if (devicePixelRatio >= 2 && (!dpr || dpr >= 2)){
                        dpr = 2;
                    } else {
                        dpr = 1;
                    }
                } else {
                    // 其他设备下，仍旧使用1倍的方案
                    dpr = 1;
                }
                scale = 1 / dpr;
            }

            docEl.setAttribute('data-dpr', dpr);
            if (!metaEl) {
                metaEl = doc.createElement('meta');
                metaEl.setAttribute('name', 'viewport');
                metaEl.setAttribute('content', 'initial-scale=' + scale + ', maximum-scale=' + scale + ', minimum-scale=' + scale + ', user-scalable=no');
                if (docEl.firstElementChild) {
                    docEl.firstElementChild.appendChild(metaEl);
                } else {
                    var wrap = doc.createElement('div');
                    wrap.appendChild(metaEl);
                    doc.write(wrap.innerHTML);
                }
            }

            function refreshRem(){
                var width = docEl.getBoundingClientRect().width;
                if (width / dpr > 540) {
                    width = 540 * dpr;
                }
                var rem = width / 10;
                docEl.style.fontSize = rem + 'px';
                flexible.rem = win.rem = rem;
            }

            win.addEventListener('resize', function() {
                clearTimeout(tid);
                tid = setTimeout(refreshRem, 300);
            }, false);
            win.addEventListener('pageshow', function(e) {
                if (e.persisted) {
                    clearTimeout(tid);
                    tid = setTimeout(refreshRem, 300);
                }
            }, false);

            if (doc.readyState === 'complete') {
                doc.body.style.fontSize = 12 * dpr + 'px';
            } else {
                doc.addEventListener('DOMContentLoaded', function(e) {
                    doc.body.style.fontSize = 12 * dpr + 'px';
                }, false);
            }


            refreshRem();

            flexible.dpr = win.dpr = dpr;
            flexible.refreshRem = refreshRem;
            flexible.rem2px = function(d) {
                var val = parseFloat(d) * this.rem;
                if (typeof d === 'string' && d.match(/rem$/)) {
                    val += 'px';
                }
                return val;
            }
            flexible.px2rem = function(d) {
                var val = parseFloat(d) / this.rem;
                if (typeof d === 'string' && d.match(/px$/)) {
                    val += 'rem';
                }
                return val;
            }

        })(window, window['lib'] || (window['lib'] = {}));
    </script>
    <link href="{{ asset('css/mbase.css') }}" rel="stylesheet">
    <link href="{{ asset('css/index.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/fonts/font_icon.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('css/sf-fonts/iconfont.css') }}" />
</head>
<body>
    <!--顶部logo-->
    <div class="sf-index-top sf-xcol-box">
         <div class="sf-index-logo ">
             <img src="{{ asset('img/logo.1.png')}}">
         </div>
        <div class="sf-index-logo-font">
            <img src="{{ asset('img/logo-font.png') }}">
        </div>
        <div class="tr sf-xflex sf-index-btn "><span class="sf tc">打开APP</span></div>
    </div>
    <div class="sf-xwrap" style="margin-top: 1.64rem;">
        @if (!empty($body))
            @if (2 == $body['type'])
                <div class="sf-wrap">
                    <img class="sf-img" src="{{ asset('img/remove-img.png') }}" class="sf-xwrap-remove-img"/>
                    <span class="sf-span" class="sf-xwrap-remove-title">该节目已经下架～</span>

                </div>
            @else
            <div class="sf-index-audio" style="background-image: url({{ $body['radio_pic'] }});">
                <!--音频播放器-->
                <audio  id="audio" src="{{ $body['radio_url'] }}"></audio>
                <!--播放控制器-->
                <div class="sf-index-video" style="position: relative;z-index: 3">
                    <img class="sf-index-video-control" src="{{ asset('img/pause.png') }}"/>
                    <!--进度条信息-->
                    <div class="timeline"><span style=""></span><div class="buff"></div></div>
                    <div class="info">
                        <span class="size">00:00</span>
                        <span class="timeshow">{{ $body['burning_time'] }}</span>
                    </div>
                </div>
                <div class="sf-index-bg"></div>
            </div>
            <div class="sf-index-title mc textoverflow tc">{{ $body['name'] }}</div>
            <!--打开APP按钮-->
            <div class="sf-index-open sf-index-btn tc"><span id="sf" class="sf tc">打开APP，收听更流畅哦～</span></div>
            <div class="sf-article-line"></div>
            <!--全部评论-->
            <div class="sf-article-comment" style="padding-bottom:0">
                <div class="sf-article-label">全部评论（{{ $body['comment_count'] }}）</div>
                @if (!empty($comment))
                <ul>
                    @if (isset($comment[0]))
                    <li>
                        <div class="sf-xcol-box">
                            <div  class="sf-article-img" >
                                <img class="comment-logo" src="{{ $comment[0]['avatar'] }}">
                            </div>
                            <div class="sf-xflex">
                                <div class="comment-con">
                                    <div class="user-name">
                                        <span>{{ $comment[0]['nickname'] }}</span>
                                        <div class="sf assist-num">
                                            {{--<span>{{ $comment[0]['likes'] }}<i class="iconfont sf-dianzan1"></i></span>--}}
                                            <img src="{{ asset('img/assist.png') }}" style="margin-top:-0.13333rem">
                                            <span>
                                                {{ $comment[0]['likes'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        {{ $comment[0]['content'] }}
                                    </div>
                                    <span class="date">{{ $comment[0]['time'] }}</span>
                                    <span class="sf has-reply">
                                        @if ($comment[0]['reply'])
                                        {{ $comment[0]['reply'] }}
                                        @endif
                                     回复</span>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endif
                    @if (isset($comment[1]))
                    <li>
                        <div class="sf-xcol-box">
                            <div  class="sf-article-img" >
                                <img class="comment-logo" src="{{ $comment[1]['avatar'] }}">
                            </div>
                            <div class="sf-xflex">
                                <div class="comment-con">
                                    <div class="user-name">
                                        <span>{{ $comment[1]['nickname'] }}</span>
                                        <div class="sf assist-num">
                                            {{--<span>{{ $comment[1]['likes'] }}<i class="iconfont sf-dianzan1"></i></span>--}}
                                            <img src="{{ asset('img/assist.png') }}" style="margin-top:-0.13333rem">
                                            <span>
                                                    {{ $comment[1]['likes'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        {{ $comment[1]['content'] }}
                                    </div>
                                    <span class="date">{{ $comment[1]['time'] }}</span>
                                    <span class="sf has-reply">
                                            @if ($comment[1]['reply'])
                                            {{ $comment[1]['reply'] }}
                                            @endif
                                        回复</span>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endif
                    @if (isset($comment[2]))
                    <li>
                        <div class="sf-xcol-box">
                            <div  class="sf-article-img" >
                                <img class="comment-logo" src="{{ $comment[2]['avatar'] }}">
                            </div>
                            <div class="sf-xflex">
                                <div style="border: 0;" class="comment-con">
                                    <div class="user-name">
                                        <span>{{ $comment[2]['nickname'] }}</span>
                                        <div class="sf assist-num">
                                            {{--<span>{{ $comment[2]['likes'] }}<i class="iconfont sf-dianzan1"></i></span>--}}
                                            <img src="{{ asset('img/assist.png') }}" style="margin-top:-0.13333rem">
                                            <span>
                                                    {{ $comment[2]['likes'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        {{ $comment[2]['content'] }}
                                    </div>
                                    <span class="date">{{ $comment[2]['time'] }}</span>
                                    <span class="sf has-reply">
                                            @if ($comment[2]['reply'])
                                            {{ $comment[2]['reply'] }}
                                            @endif
                                        回复</span>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endif
                </ul>
                @else
                    <div class="mc tc"><img class="sf-index-like" src="{{ asset('img/comment.png') }}"/></div>
                    <div class="tc sf-index-empty">还没有评论哦，快来抢沙发吧~</div>
                @endif
            </div>
            <div class="sf-index-open sf-index-discuss-btn sf-index-btn tc"><span class="sf tc" style=" width:4.24rem;">打开APP参与讨论</span></div>
            <div class="sf-article-line"></div>
            <!--热门节目-->
            <div class="sf-article-comment"style="padding-bottom:0.32rem;">
                <div class="sf-article-label">热门节目</div>
                @if (isset($hot[0]))
                <div class="sf sf-index-program sf-xcol-box" @if (1 == $hot['count']) style="border-bottom:0" @endif>
                    <div class="sf-mr30"><img class="sf-index-img" src="{{ $hot[0]['radio_pic'] }}"></div>
                    <div class="sf-xflex">
                        <div class="sf-index-program-title">{{ $hot[0]['name'] }}</div>
                        <div class="sf-index-program-time"><span>时长{{ $hot[0]['burning_time'] }}</span><span style="margin-left: 0.30667rem;">{{ $hot[0]['column_name'] }}</span></div>
                    </div>
                    <div class="sf-index-icon"><img src="{{ asset('img/back.png') }}"/></div>
                </div>
                @endif
                @if (isset($hot[1]))
                <div class="sf sf-index-program sf-index-mt34 sf-xcol-box" @if (2 == $hot['count']) style="border-bottom:0" @endif>
                    <div class="sf-mr30"><img class="sf-index-img" src="{{ $hot[1]['radio_pic'] }}"></div>
                    <div class="sf-xflex">
                        <div class="sf-index-program-title">{{ $hot[1]['name'] }}</div>
                        <div class="sf-index-program-time"><span>时长{{ $hot[1]['burning_time'] }}</span><span style="margin-left: 0.30667rem;">{{ $hot[1]['column_name'] }}</span></div>
                    </div>
                    <div class="sf-index-icon"><img src="{{ asset('img/back.png') }}"/></div>
                </div>
                @endif
                @if (isset($hot[2]))
                    {{--style="border-bottom:0--}}
                <div class="sf sf-index-program sf-index-mt34 sf-xcol-box" @if (3 == $hot['count']) style="border-bottom:0" @endif>
                    <div class="sf-mr30"><img class="sf-index-img" src="{{ $hot[2]['radio_pic'] }}"></div>
                    <div class="sf-xflex">
                        <div class="sf-index-program-title">{{ $hot[2]['name'] }}</div>
                        <div class="sf-index-program-time"><span>时长{{ $hot[2]['burning_time'] }}</span><span style="margin-left: 0.30667rem;">{{ $hot[2]['column_name'] }}</span></div>
                    </div>
                    <div class="sf-index-icon"><img src="{{ asset('img/back.png') }}"/></div>
                </div>
                @endif
            </div>
            @endif
        @else
            <div class="sf-wrap">
                <img class="sf-img" src="{{ asset('img/remove-img.png') }}" class="sf-xwrap-remove-img"/>
                <span class="sf-span" class="sf-xwrap-remove-title">该节目已经被删除了哦～</span>

            </div>
        @endif
    </div>
    <!--模态层-->
    <div class="common-window guide-img">
        <span class="eraytfont eraytfont-jiantou jiantou"></span>
        <div class="guide-text">
            点击右上角选择系统浏览器打开
        </div>
    </div>
    <script src="{{ asset('js/jquery1.12.4/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery.mobile.custom.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/sfArticleShare.js') }}" ></script>
    <script type="text/javascript">
        $(function() {
            var audio = document.getElementById('audio');
            var drag = document.getElementById('drag');
            var currentTime = audio.currentTime;
            var startX, moveEndX, X;
            $(document).on('touchend','.timeline',function(e){
                e.preventDefault();
                var x = e.originalEvent.changedTouches[0].clientX-this.offsetLeft;
                var X = x < 0 ? 0 : x ;
                var W = $(this).width();
                var place = X > W ? W : X;
                audio.currentTime = (place/W).toFixed(2)*audio.duration;
                // $(this).children().css({width:(place/W).toFixed(2)*100+"%"})
                if((place/W).toFixed(2)*100 >=100){
                    $('.timeline').children().css({width:calc(100% - 0.1+'rem')});
                    // $(this).children().css({width:100+"%"});
                }else{
                    $(this).children().css({width:(place/W)*100+"%"});
                    // $(this).children().css({width:(place/W)*100+"%"});
                }

            });
            $('.sf-index-video-control').on('click',function(){
                if(audio.paused){
                    audio.play();
                    $('.sf-index-video-control').prop('src','{{ URL::asset('img/paused.png') }}');

                }else{
                    audio.pause();
                    $('.sf-index-video-control').prop('src','{{ URL::asset('img/play.png') }}');
                }
            });
            setInterval(function(){
                if(audio.paused){
                    $('.sf-index-video-control').prop('src','{{ URL::asset('img/play.png') }}');
                }else{
                    $('.sf-index-video-control').prop('src','{{ URL::asset('img/paused.png') }}');
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
                if(t >= Math.floor(audio.duration)){
                    $('.timeline').children().css({width:calc(100% - 0.1+'rem')});
                    // $('.timeline').children().css({width:100 + "%"});

                }else{
                    $('.timeline').children().css({width:(t/audio.duration)*100+"%"});
                    // $('.timeline').children().css({width:(t/audio.duration)*100+"%"});
                }
//                $('.timeline').children().css({width:(t/audio.duration).toFixed(4)*100+"%"});
            }
            function buff(t) {
                t = Math.floor(t);
//                $('.buff').css({
//                    width: (t / audio.duration).toFixed(4) * 100 + "%"
//                })
                if(t >= Math.floor(audio.duration)){
                    $('.buff').css({width:calc(100% - 0.1+'rem')});
                    // $('.buff').css({width:100 + "%"});

                }else{
                    $('.buff').css({width: (t / audio.duration)* 100 + "%"});
                    // $('.buff').css({width: (t / audio.duration) * 100 + "%"});
                }
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

        });
    </script>
</body>
</html>