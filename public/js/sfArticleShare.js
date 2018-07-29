(function($) {
    var inviteGroup = {
        init: function() {
            var params = inviteGroup.GetQueryString("id");//获取url参数
            this.params = params;
            inviteGroup.bindEvent();
        },
        bindEvent: function() {
            //监听事件
            $(".sf").bind('tap', function() {
             
                inviteGroup.openApp();
            });
        },
        openApp: function() {
            var nativeLink, newLink, versions;
            if(browser.versions.android) {
                nativeLink = "sfysapp://m.sfys365.com";//原生提供的打开app的安卓协议
                versions = "android";
            } else if(browser.versions.ios) {
                if(this.isIOS9()) {
                    nativeLink = 'sfysapp://m.sfys365.com';//ios原生提供的打开app的协议
                    versions = "ios9";
                } else {
                    nativeLink = 'sfysapp://m.sfys365.com';//ios原生提供的打开app的协议
                    versions = "ios";
                }
            } else {
                alert('目前只支持ios或安卓');
                return;
            }
            newLink = nativeLink + '?id=' + encodeURIComponent(this.params);//打开app地址并传参给app
            this.otherOpenApp(newLink, function(res) {
                if(res === 0) {
                    if(browser.versions.ios) {
                        // window.location = "https://itunes.apple.com/cn/app/id1332982959";//苹果下载页
                        window.location = "https://itunes.apple.com/cn/app/id1332982959?mt=8";//苹果下载页
                    } else {
                        window.location = "http://admin.sfys365.com:8100/download/android.apk";//安卓下载页
                    }
                }
            }, versions);
        },
        otherOpenApp: function(openUrl, callback, versions) {
            //检查app是否打开
            if(browser.isOtherApp()) {
                $('.guide-img').show();
                return;
            }
            if((versions == "android") || (versions == "ios9")) {
                window.location = openUrl;
            } else if(versions == "ios") {
                window.location = openUrl;
            } else {
                return;
            }
            if(callback) {
                this.checkOpen(function(opened) {
                    callback && callback(opened);
                });
            }
        },
        //判断app在规定时间内是否打开
        checkOpen: function(cb) {
            var _clickTime = +(new Date());

            function check(elsTime) {
                //超时或dom隐藏则认为app已切入后台（跳转至其他app成功）
                if(elsTime > 3000 || document.hidden || document.webkitHidden) {
                    cb(1);//打开app
                } else {
                    cb(0);//没打开app
                }
            }
            //启动间隔20ms运行的定时器，并检测累计消耗时间是否超过3000ms，超过则结束
            var _count = 0,
                intHandle;
            intHandle = setInterval(function() {
                _count++;
                var elsTime = +(new Date()) - _clickTime;
                if(_count >= 100 || elsTime > 3000) {
                    clearInterval(intHandle);
                    check(elsTime);
                }
            }, 20);
        },
        GetQueryString: function(param) {
            var reg = new RegExp("(^|&)" + param + "=([^&]*)(&|$)");
            var r = window.location.search.substr(1).match(reg);
            if(r != null) return decodeURIComponent(r[2]);
            return null;
        },
        /**
         * ios9以上返回true,以下返回false
         */
        isIOS9: function() {
            //获取ios固件版本信息
            var getOsv = function() {
                var reg = /OS ((\d+_?){2,3})\s/;
                if(navigator.userAgent.match(/iPad/i) || navigator.platform.match(/iPad/i) || navigator.userAgent.match(/iP(hone|od)/i) || navigator.platform.match(/iP(hone|od)/i)) {
                    var osv = reg.exec(navigator.userAgent);
                    if(osv.length > 0) {
                        return osv[0].replace('OS', '').replace('os', '').replace(/\s+/g, '').replace(/_/g, '.');
                    }
                }
                return '';
            };
            var osv = getOsv();
            var osvArr = osv.split('.');
            //初始化显示ios9引导
            if(osvArr && osvArr.length > 0) {
                if(parseInt(osvArr[0]) >= 9) {
                    return true
                }
            }
            return false
        }
    }
    //获取浏览器代理信息
    var browser = {
        versions: function() {
            var u = navigator.userAgent,
                app = navigator.appVersion;
            var uLower = u.toLowerCase();
            return { //移动终端浏览器版本信息
                trident: u.indexOf('Trident') > -1, //IE内核
                presto: u.indexOf('Presto') > -1, //opera内核
                webKit: u.indexOf('AppleWebKit') > -1, //苹果、谷歌内核
                gecko: u.indexOf('Gecko') > -1 && u.indexOf('KHTML') == -1, //火狐内核
                mobile: !!u.match(/AppleWebKit.*Mobile.*/) || !!u.match(/AppleWebKit/), //是否为移动终端
                ios: !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/), //ios终端
                android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1, //android终端或者uc浏览器
                iPhone: u.indexOf('iPhone') > -1 || u.indexOf('Mac') > -1, //是否为iPhone或者QQHD浏览器
                iPad: u.indexOf('iPad') > -1, //是否iPad
                webApp: u.indexOf('Safari') == -1, //是否web应该程序，没有头部与底部
                isWechat: uLower.match(/MicroMessenger/i) == "micromessenger", //是否为微信
                isAlipay: uLower.match(/Alipay/i) == "alipay", //是否为支付宝
                isWeibo: uLower.match(/WeiBo/i) == "weibo", //是否为微博
                //isQQ: uLower.match(/mobile Mqqbrowser/i) == "mobile mqqbrowser", //是否为QQ
                //isQQ: uLower.match(/MQQBrowserQQ/i) == "MQQBrowserQQ", //是否为QQ
                //isQQX: uLower.match(/MQQBrowser QQ/i) == "MQQBrowser QQ", //是否为QQ
                // isQQ:uLower.indexOf('MQQBrowserQQ') > -1,
                // isQQX:uLower.indexOf('MQQBrowser QQ') > -1,
                // isQQ: uLower.indexOf("mobile") > -1 && uLower.indexOf("qq") > -1, //是否为QQ
                isQQ: uLower.indexOf("mobile") > -1 && uLower.indexOf("qq/") > -1,//是否为QQ
            };
        }(),
        language: (navigator.browserLanguage || navigator.language).toLowerCase(),
        //是否第三方app
        isOtherApp: function() {
            var ver = this.versions;
            return ver.isWechat || ver.isAlipay || ver.isWeibo || ver.isQQ;
        }
    }
    inviteGroup.init();
})(jQuery);