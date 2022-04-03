<?php

global $post;

$image_url = document_engine_pdf_header_logo();

$pdf_header_show_title = document_engine_pdf_header_show_post_title();

$pdf_header_show_pagination = document_engine_pdf_header_show_pagination();
?>

<?php
// only enter here if any of the settings exists
if ($image_url !== '' || $pdf_header_show_title || $pdf_header_show_pagination) { ?>

    <div style="width:100%;float:left;">

        <?php
        // check if Header logo exists
        if ($image_url !== null) { ?>

            <div style="width:20%;float:left;">
                <img style="width:auto;height:55px;" src="<?php echo $image_url; ?>">
            </div>

        <?php }

        ?>

        <div style="width:75%;float:right;text-align:right;height:35px;padding-top:20px;">

            <?php
            // check if Header show title is checked
            if ($pdf_header_show_title) {

                echo apply_filters('document_engine_pdf_header_title', get_the_title($post->ID));

            }

            ?>

            <?php
            // check if Header show pagination is checked
            if ($pdf_header_show_pagination) {

                echo apply_filters('document_engine_pdf_header_pagination', '| {PAGENO}');

            }

            ?>

        </div>

    </div>

<?php }




