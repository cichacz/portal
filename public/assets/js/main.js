$(function() {
    $(window).resize(function(){
        var height = $(window).height() - $('.header').outerHeight() - $('.header-nav').outerHeight();
        $('.page-content').css('min-height', height);
    }).trigger('resize');
});