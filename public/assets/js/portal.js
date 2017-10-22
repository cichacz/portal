'use strict';
var portal = {
    doingAjax: false
};

function is_touch_device() {
    return (('ontouchstart' in window) || (navigator.MaxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0));
}

function isScrolledIntoView( elem, margin, fullyVisibleCheck ){
    if(typeof margin === 'undefined') {
        margin = 0;
    }

    var $elem = $(elem);
    var $window = $(window);

    var docViewTop = $window.scrollTop();
    var docViewBottom = docViewTop + $window.height();

    var elemHeight = $elem.outerHeight();

    var elemTop = $elem.offset().top + margin;
    var elemBottom = elemTop + $elem.height() + margin;

    if(fullyVisibleCheck === true) {
        return elemTop < docViewBottom && elemBottom > docViewTop;
    } else if(fullyVisibleCheck === '%') {
        return elemTop < docViewBottom && elemBottom > docViewTop ? (docViewBottom - elemTop)/elemHeight : 0;
    }

    return (elemTop <= docViewBottom) && (is_touch_device() || $elem.is(':visible'));
    //&& (elemTop >= docViewTop)
}