jQuery(document).ready(function($) {

    $(document.body).on('click', 'div[data-dhlpwc-dismissable-notice] button.notice-dismiss', function(e) {
        e.preventDefault();

        var notice_tag = $(this).parent().data('dhlpwc-dismissable-notice');

        var data = {
            'action': 'dhlpwc_dismiss_admin_notice',
            'notice_tag': notice_tag
        };

        $.post(ajaxurl, data);
    }).on('click', 'a#dhlpwc-dismiss-notice-forever', function(e) {
        e.preventDefault();

        var data = {
            'action': 'dhlpwc_dismiss_admin_notice_forever',
            'notice_tag_forever': $(this).data('notice_tag_forever')
        };

        $.post(ajaxurl, data);

        $(this).siblings('.notice-dismiss').trigger('click');

    });

});
