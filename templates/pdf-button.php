<?php

$button_text = document_engine_pdf_button_text();

$button_alignment = document_engine_pdf_button_alignment();

global $post;
?>
<div class="document-engine-pdf-button-container"
     style="<?php echo apply_filters('document_engine_pdf_button_container_css', ''); ?> text-align:<?php echo $button_alignment; ?> ">

    <a class="document-engine-pdf-button" href="<?php echo esc_url(add_query_arg(DOCUMENT_ENGINE_QUERY_VAR_SLUG, $post->ID)); ?>"
       target="_blank"><span
                class="document-engine-pdf-button-icon"><i
                    class="fa fa-file-pdf-o"></i></span> <?php echo $button_text; ?></a>

</div>


