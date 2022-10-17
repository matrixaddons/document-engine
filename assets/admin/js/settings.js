(function ($) {
    var DocumentEnginePDFSettings = {
        init: function () {

            this.image_upload_frame = null;
            this.initLib();
            this.initMediaUploader();
        },
        initLib: function () {
            if ($('.document-engine-pdf-custom-css').length) {

                // ref: http://jsfiddle.net/deepumohanp/tGF6y/

                var textarea = $('.document-engine-pdf-custom-css');
                //$('.document-engine-pdf-custom-css').hide();

                var editor = ace.edit("document_engine_pdf_custom_css_textarea_wrap");
                editor.setTheme("ace/theme/twilight");
                editor.getSession().setMode("ace/mode/css");

                editor.getSession().on('change', function (event, content) {
                    $(editor.container).closest('td').find('textarea.document-engine-pdf-custom-css').val(editor.getSession().getValue()).trigger('change');

                    //textarea.text(editor.getSession().getValue()).trigger('change');

                });

                editor.setValue($(editor.container).closest('td').find('textarea.document-engine-pdf-custom-css').val());

                //$(editor.container).closest('td').find('textarea.document-engine-pdf-custom-css').val(editor.getSession().getValue()).trigger('change');
                //textarea.text(editor.getSession().getValue()).trigger('change');

            }

        },
        initMediaUploader: function () {
            var _this = this;
            $('body').on('click', '.document-engine-image-field .matrixaddons-image-field-add', function (event) {
                event.preventDefault();
                _this.uploadWindow($(this), $(this).closest('.document-engine-image-field'));
            });
            $('body').on('click', '.document-engine-remove-image', function (event) {

                event.preventDefault();
                var imageField = $(this).closest('.document-engine-image-field');
                imageField.find('.image-wrapper').attr('data-url', '');
                imageField.find('.image-container, .field-container').addClass('document-engine-hide');
                imageField.find('.matrixaddons-image-field-add').removeClass('document-engine-hide');
                imageField.find('.document-engine-image-field-input').val(0).trigger('change');

            });
        },
        getImageElement: function (src) {
            return '<div data-url="' + src + '" class="image-wrapper"><div class="image-content"><img src="' + src + '" alt=""><div class="image-overlay"><a class="matrixaddons-image-delete document-engine-remove-image dashicons dashicons-trash"></a></div></div></div>';
        },
        uploadWindow: function (uploadBtn, wrapper) {

            var _this = this;
            if (this.image_upload_frame) this.image_upload_frame.close();

            this.image_upload_frame = wp.media.frames.file_frame = wp.media({
                title: uploadBtn.data('uploader-title'),
                button: {
                    text: uploadBtn.data('uploader-button-text'),
                },
                multiple: false
            });

            this.image_upload_frame.on('select', function () {

                var selection = _this.image_upload_frame.state().get('selection');
                var selected_list_node = wrapper.find('.image-container');
                var imageHtml = '';
                var attachment_id = 0;
                selection.map(function (attachment_object, i) {
                    var attachment = attachment_object.toJSON();
                    attachment_id = attachment.id;

                    var attachment_url = attachment.sizes.full.url;
                    imageHtml = _this.getImageElement(attachment_url);

                });

                if (attachment_id > 0) {
                    wrapper.find('.image-container, .field-container').removeClass('document-engine-hide');
                    wrapper.find('.matrixaddons-image-field-add').addClass('document-engine-hide');
                    selected_list_node.find('.image-wrapper').remove();
                    selected_list_node.append(imageHtml);
                    wrapper.find('.document-engine-image-field-input').val(attachment_id).trigger('change');
                }
            });


            this.image_upload_frame.open();
        },
    };

    $(document).ready(function () {
        DocumentEnginePDFSettings.init();

    });
}(jQuery));
