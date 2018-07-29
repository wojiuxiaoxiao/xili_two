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
        $(this).children().css({width:(place/W).toFixed(2)*100+"%"})
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

});