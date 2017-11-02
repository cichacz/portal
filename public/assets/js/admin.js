$(function() {
    $('.page-remove-trigger').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var pid = $this.data('pid');
        if(!pid) {
            return;
        }

        $.ajax({
            url: $this.attr('href'),
            method: 'delete',
            data: {
                pid: pid
            },
            success: function() {
                $('#removeModal').modal('hide');
                $this.data('pid', null);
                location.reload();
            }
        });
    });

    $('#removeModal').on('show.bs.modal', function(e) {
        var pid = $(e.relatedTarget).data('pid');
        $(this).find('.page-remove-trigger').data('pid', pid);
    });
});