jQuery(document).ready(function ($) {
    var mediaUploader;

    $('.projects_logo_upload').click(function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Choose a Logo',
            button: { text: 'Choose Logo' },
            multiple: false
        });

        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#projects_logo').val(attachment.id);
            $('#projects_logo_preview').attr('src', attachment.url).show();
            $('.projects_logo_remove').show();
        });

        mediaUploader.open();
    });

    $('.projects_logo_remove').click(function (e) {
        e.preventDefault();
        $('#projects_logo').val('');
        $('#projects_logo_preview').hide();
        $(this).hide();
    });
});
