<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="format-detection" content="telephone=no,email=no,date=no,address=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title>
        大师详情
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
    <link href="{{ asset('css/master.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/fonts/font_icon.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('css/sf-fonts/iconfont.css') }}" />
</head>

<body>
<div class="sf-xwrap">
    <div class="sf-master-header">
       <div class="sf-master-info">
            <div class="sf-master-summary">
                <div class="sf-master-logo">
                    <img src="{{ $body['profile']['avatar'] }}" class="sf-master-avt">
                    <img src="{{ asset('img/master_logo.png') }}" class="sf-master-sign">
                    @if (isset($body['profile']['is_focus']))
                        <div class="sf-master-follow">
                            <span class="sf">
                                {{ $body['profile']['is_focus'] === 1 ? '已关注' : '+关注' }}
                            </span>
                        </div>
                    @else
                    <div class="sf-master-follow" style="border: none;width: auto;">
                        <span>{{ $body['profile']['fans'] }}</span><span style="color: #323333">个粉丝</span>
                    </div>
                    @endif
                </div>
                <div class="sf-master-self">
                    <div class="sf-master-name">
                        <span>{{ $body['profile']['nickname'] }}</span>
                       <img src="{{ asset('img/master_standard.png') }}">
                    </div>
                    <div>
                       {{ $body['profile']['signature'] }}
                    </div>
                </div>
            </div>
            <div class="sf-master-detail">
                {{ $body['profile']['detail'] }}
            </div>
       </div>
    </div>
    <div class="sf-master-comment">
       <div class="sf-master-table-tableHeader">
            <div>
                <img src="{{ asset('img/master_comment.png') }}">
                <span>TA的回答</span>
            </div>
            <div>
                @if ($body['profile']['answer_count'] > 3)
                <span class="sf">更多</span>
                <img src="{{ asset('img/blue_more.png') }}">
                @endif
            </div>
       </div>
        @if (count($body['answer']) > 0)
        @foreach($body['answer'] as $answer)
            <div class="sf-master-table-cell">
                <div class="sf-master-cell-header zoom">
                <span class="sf-master-cell-title">{{ $answer['title'] }}</span>
                    <span class="sf-master-cell-bonus">
                        @if (isset($answer['price']))
                            <img src="{{ asset('img/bonus.png') }}">
                            <span>¥{{ sprintf('%.2f', ($answer['price'] / 100)) }}</span>
                        @endif
                </span>
                </div>
                <div class="sf-master-cell-content">
                    {{ $answer['content'] }}
                </div>
                <div class="sf-master-cell-agree">
                    {{ $answer['likes'] }}点赞
                </div>
            </div>
        @endforeach
        @else
            <div class="sf-nodata">
            <img class="sf-img" src="{{ asset('img/no-answer.png') }}"/>
                <span class="sf-span" style="color: #999999;margin-top: 0.4133333333rem;font-size: 0.35rem">Ta还没有回答过提问哦～</span>
            </div>
        @endif
    </div>
</div>

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