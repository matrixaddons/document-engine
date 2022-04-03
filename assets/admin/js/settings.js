(function ($) {
    var DocumentEnginePDFSettings = {
        init: function () {

            this.initLib();
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

        }
    };

    $(document).ready(function () {
        DocumentEnginePDFSettings.init();

    });
}(jQuery));
