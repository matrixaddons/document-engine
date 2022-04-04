<?php

global $post;
?>
<div class="document-engine-pdf-button-container"
     style="<?php echo apply_filters('document_engine_pdf_button_container_css', ''); ?> text-align:<?php echo $button_alignment; ?> ">

    <a class="document-engine-pdf-button button"
       href="<?php echo esc_url(add_query_arg(DOCUMENT_ENGINE_QUERY_VAR_SLUG, $post->ID)); ?>"
       target="_blank"><span
                class="document-engine-pdf-button-icon"><i
                    class="<?php echo esc_attr($button_icon); ?>"></i></span> <?php echo esc_html($button_text); ?></a>
</div>


