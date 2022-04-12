<?php
global $post;


$pdf_footer_text = document_engine_pdf_footer_text();

$pdf_footer_show_title = document_engine_pdf_footer_show_post_title();

$pdf_footer_show_pagination = document_engine_pdf_footer_show_pagination();
?>

<?php
// only enter here if any of the settings exists
if ($pdf_footer_text !== '' || $pdf_footer_show_pagination) { ?>

    <div class="document-engine-pdf-footer-wrap">
        <div class="document-engine-pdf-footer-content">

            <?php
            // check if Footer show title exists
            if ($pdf_footer_text) {

                echo wp_kses($pdf_footer_text, array(
                    'a' => array(
                        'href' => array(),
                        'target' => array()
                    ),
                    'br' => array(),
                    'em' => array(),
                    'strong' => array(),
                    'hr' => array(),
                    'p' => array(),
                    'h1' => array(),
                    'h2' => array(),
                    'h3' => array(),
                    'h4' => array(),
                    'h5' => array(),
                    'h6' => array(),
                ));

            }

            ?>

            <?php
            // check if Footer show title is checked
            if ($pdf_footer_show_title) {

                echo get_the_title($post->ID);

            }

            ?>

            <?php
            // check if Footer show pagination is checked
            if ($pdf_footer_show_pagination) {

                echo apply_filters('document_engine_pdf_footer_pagination', '| {PAGENO}');

            }

            ?>

        </div>
    </div>

<?php }

?>



