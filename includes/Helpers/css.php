<?php
$header_font_size = document_engine_pdf_header_font_size();
$header_font_size = $header_font_size > 0 ? $header_font_size . 'pt' : 'inherit';

$footer_font_size = document_engine_pdf_footer_font_size();
$footer_font_size = $footer_font_size > 0 ? $footer_font_size . 'pt' : 'inherit';
?>
<style type="text/css">
    body {
        background: #FFF;
        font-size: 100%;
    }

    /* fontawesome compatibility */
    .fa {
        font-family: fontawesome;
        display: inline-block;
        font: normal normal normal 14px/1 FontAwesome;
        font-size: inherit;
        text-rendering: auto;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        transform: translate(0, 0);
    }

    .document-engine-pdf-header-wrap {
        width: 100%;
        float: left;
        font-size: <?php echo esc_attr($header_font_size) ?>;
    }

    .document-engine-pdf-header-image {
        width: 20%;
        float: left;
    }

    .document-engine-pdf-header-image img {
        width: auto;
        height: 55px;
    }

    .document-engine-pdf-header-content {
        width: 75%;
        float: right;
        text-align: right;
        height: 35px;
        padding-top: 20px;
    }

    .document-engine-pdf-footer-wrap {
        width: 100%;
        float: left;
        padding-top: 10px;
        font-size: <?php echo esc_attr($footer_font_size) ?>;
    }

    .document-engine-pdf-footer-content {
        float: right;
        text-align: right;
    }

    <?php echo  wp_kses(document_engine_pdf_custom_css(), array()); ?>
</style>