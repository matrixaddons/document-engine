<?php

$pdfbutton_text = document_engine_pdf_button_text();

$pdfbutton_align = document_engine_pdf_button_alignment();

global $post;
?>
<div class="dkpdf-button-container"
     style="<?php echo apply_filters('dkpdf_button_container_css', ''); ?> text-align:<?php echo $pdfbutton_align; ?> ">

    <a class="dkpdf-button" href="<?php echo esc_url(add_query_arg('generate_pdf', $post->ID)); ?>" target="_blank"><span
                class="dkpdf-button-icon"><i class="fa fa-file-pdf-o"></i></span> <?php echo $pdfbutton_text; ?></a>

</div>


