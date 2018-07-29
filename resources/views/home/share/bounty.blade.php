<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="format-detection" content="telephone=no,email=no,date=no,address=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>
        @if (isset($body['title']))
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
    <link href="{{ asset('css/masterAsk.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/fonts/font_icon.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('css/sf-fonts/iconfont.css') }}" />
</head>
<body>
@if (!isset($body['bounty']['status']) && !isset($body['bounty']['msg']))
<div class="sf-xwrap">
    <div class="sf-article-con">
        <div class="sf-ask-userInfo">
            <div>
                <img class="sf" src="{{ $body['bounty']['avatar'] }}">
                <span>{{ $body['bounty']['nickname'] }}</span>
                <span class="sf-ask-userInfo-level">{{ $body['bounty']['rankName'] }}</span>
            </div>
            <div class="sf-ask-userInfo-bonus">
                <img src="{{ asset('img/bonus.png') }}">
                <span>¥{{ $body['bounty']['price'] }}</span>
            </div>
        </div>
        <div class="sf-article-title sf-xfont40">
            {{ $body['bounty']['title'] }}
        </div>

        <div class="sf-article-content">{!! $body['bounty']['content'] !!}</div>
        @if (count($body['bounty']['pic']) > 0)
            <div class="sf-ask-img zoom">
                @foreach($body['bounty']['pic'] as $pic)
                    <div>
                        <img src="{{ $pic }}">
                    </div>
                @endforeach
            </div>
        @endif
        <div class="sf-ask-attach"><span>{{ $body['bounty']['create_time'] }}</span><span>{{ $body['bounty']['views'] }}阅读</span></div>
    </div>
    <div class="sf-article-line"></div>
    <div class="sf-article-comment">
            <div class="sf-article-label">全部回答（{{ $body['bounty']['answer_nums'] }}）</div>
            @if ($body['bounty']['answer_nums'] <= 0)
                <div class="sf-wrap">
                    <div class="mc tc"><img class="sf-index-like" src="{{ asset('img/comment.png') }}"/></div>
                    <div class="tc sf-index-empty">还没有大师前来解答哦</div>
                </div>
            @else
            <ul>
                @if (count($body['answer']) > 0)
                    @foreach( $body['answer'] as $answer)
                    <li>
                        <div class="sf-xcol-box">
                            <div  class="sf-article-img" >
                                    <img class="comment-logo" src="{{ $answer['avatar'] }}">
                                    <img  class="master-logo" src="{{ asset('img/master_logo.png') }}">
                            </div>
                            <div class="sf-xflex">
                                <div class="comment-con">
                                    <div class="user-name">
                                        <span class="user-name-standard"><span>{{ $answer['nickname'] }}</span><img src="{{ asset('img/master_standard.png') }}"></span>
                                        <div class="sf assist-num">
                                            <span>{{ $answer['likes'] }}<i class="iconfont sf-dianzan1"></i></span>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        {{ $answer['content'] }}
                                    </div>
                                        <span class="master-adopt">
                                            <span class="date">{{ $answer['create_time'] }}</span>
                                            @if ($answer['id'] == $body['bounty']['comment_id'])
                                            <span class="sf-ask-adopt"><img src="{{ asset('img/haveAdopt.png') }}">已采纳</span>
                                            @endif
                                        </span>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                @endif
            </ul>
            @endif
    </div>

</div>
@else
    <div class="sf-wrap">
        <img class="sf-img" src="{{ asset('img/empty-img.png') }}" class="sf-xwrap-remove-img"/>
        <span class="sf-span" class="sf-xwrap-remove-title">{{ $body['bounty']['msg'] }}</span>

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