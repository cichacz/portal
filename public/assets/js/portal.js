'use strict';
var portal = {
    doingAjax: false
};

function isTouchDevice() {
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

    return (elemTop <= docViewBottom) && (isTouchDevice() || $elem.is(':visible'));
    //&& (elemTop >= docViewTop)
}

function initUploaders() {
    $('.upload').on('change', function() {
        var files = this.files;
        var file = files[0];

        if (!file.type.match('image.*')) {
            alert('Wybrany plik nie jest obrazkiem');
            return;
        }

        if (file.size > $(this).data('max-size')) {
            alert('Wybrany plik jest za duży');
            return;
        }

        var formData = new FormData();
        formData.append('file', file);

        var $this = $(this);
        var fileUrlInput = $($this.data('target'));

        $this.val('');
        var $progress = $this.closest('.form-group').find('#image-progress');

        // Set up the AJAX request.
        var xhr = new XMLHttpRequest();
        xhr.open('POST', $this.data('url').toString(), true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                $progress.removeClass('hidden')
                    .find('.progress-bar').css('width', percentComplete + '%').html(percentComplete.toFixed(0) + '%');
            }
        };

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(this.response);
                    fileUrlInput.val(data.location);
                } catch(e) {

                }
            } else {
                alert("Wystąpił błąd podczas przesyłania");
            }
            $progress.addClass('hidden');
        };

        xhr.send(formData);
    });
}

$(document).ready(function() {
    initUploaders();
});