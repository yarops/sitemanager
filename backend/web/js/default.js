(function ($) {
    $('.admin_link-autocomplit--js').on('click', function(e) {
        e.preventDefault();
        $('#item-admin_link').val($(this).data('id'));
    })

})(jQuery);