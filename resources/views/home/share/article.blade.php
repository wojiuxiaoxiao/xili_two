<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="format-detection" content="telephone=no,email=no,date=no,address=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>
        @if (!empty($body))
        {{ $body['title'] }}
        @endif
    </title>
    <script>
        ; (function (win, lib) {
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
                    } else if (devicePixelRatio >= 2 && (!dpr || dpr >= 2)) {
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

            function refreshRem() {
                var width = docEl.getBoundingClientRect().width;
                if (width / dpr > 540) {
                    width = 540 * dpr;
                }
                var rem = width / 10;
                docEl.style.fontSize = rem + 'px';
                flexible.rem = win.rem = rem;
            }

            win.addEventListener('resize', function () {
                clearTimeout(tid);
                tid = setTimeout(refreshRem, 300);
            }, false);
            win.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    clearTimeout(tid);
                    tid = setTimeout(refreshRem, 300);
                }
            }, false);

            if (doc.readyState === 'complete') {
                doc.body.style.fontSize = 12 * dpr + 'px';
            } else {
                doc.addEventListener('DOMContentLoaded', function (e) {
                    doc.body.style.fontSize = 12 * dpr + 'px';
                }, false);
            }


            refreshRem();

            flexible.dpr = win.dpr = dpr;
            flexible.refreshRem = refreshRem;
            flexible.rem2px = function (d) {
                var val = parseFloat(d) * this.rem;
                if (typeof d === 'string' && d.match(/rem$/)) {
                    val += 'px';
                }
                return val;
            }
            flexible.px2rem = function (d) {
                var val = parseFloat(d) / this.rem;
                if (typeof d === 'string' && d.match(/px$/)) {
                    val += 'rem';
                }
                return val;
            }

        })(window, window['lib'] || (window['lib'] = {}));
    </script>
    <link href="{{ asset('css/mbase.css') }}" rel="stylesheet">
    <link href="{{ asset('css/articleSharex.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/fonts/font_icon.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('css/sf-fonts/iconfont.css') }}" />
</head>

<body>
@if (!empty($body))
<div class="sf-xwrap">
    <div class="sf-article-con">
        <div class="sf-article-title sf-xfont40">
            {{ $body['title'] }}
        </div>
        <div class="sf-article-author">
            <span>{{ $body['author'] }}</span>
            <span class="time">{{ date('Y-m-d', $body['update_time']) }}</span>
        </div>
        {{--<img src="{{ $body['imgsJson'] }}" alt="" class="sf-article-logo">--}}
        <div class="sf-article-content">{!! $body['contentY'] !!}</div>
        <div class="sf-article-commentary">
            <img src="{{ asset('img/look.png') }}" alt="" class="sf look">
            <span>{{ $body['views'] }}</span>
            <img src="{{ asset('img/no-assist.png') }}" alt="" class="sf assist" style="margin-top: -0.13333rem">
            <span>{{ $body['likes'] }}</span>
        </div>
    </div>
    <div class="sf-article-line"></div>
    <div class="sf-article-comment">
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
                                        {!! $comment[0]['content'] !!}
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
                                            {{--<span>{{ $comment[0]['likes'] }}<i class="iconfont sf-dianzan1"></i></span>--}}
                                            <img src="{{ asset('img/assist.png') }}" style="margin-top:-0.13333rem">
                                            <span>
                                                    {{ $comment[1]['likes'] }}
                                        </span>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        {!! $comment[1]['content'] !!}
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
                                            {{--<span>{{ $comment[0]['likes'] }}<i class="iconfont sf-dianzan1"></i></span>--}}
                                        <img src="{{ asset('img/assist.png') }}" style="margin-top:-0.13333rem">
                                        <span>
                                                {{ $comment[2]['likes'] }}
                                        </span>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        {!! $comment[2]['content'] !!}
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
</div>
@else
    <div class="sf-wrap">
        <img class="sf-img" src="{{ asset('img/empty-img.png') }}" class="sf-xwrap-remove-img"/>
        <span class="sf-span" class="sf-xwrap-remove-title">该文章已经被删除了哦～</span>

    </div>
@endif
<!--底部logo-->
<div class="sf-index-top sf-xcol-box">
    <div class="sf-index-logo ">
        <img src="{{ asset('img/logo.1.png') }}">
    </div>
    <div class="sf-index-logo-font">
        <img src="{{ asset('img/logo-font.png') }}">
    </div>
    <div class="tr sf-xflex sf-index-btn ">
        <span class="sf tc">打开APP</span>
    </div>
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
<script src="{{ asset('js/sfArticleShare.js') }}"></script>
</body>

</html>