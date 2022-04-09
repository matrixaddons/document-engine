<?php

global $post;

$image_url = document_engine_pdf_header_logo();

$pdf_header_show_title = document_engine_pdf_header_show_post_title();

$pdf_header_show_pagination = document_engine_pdf_header_show_pagination();


// only enter here if any of the settings exists
if ($image_url !== '' || $pdf_header_show_title || $pdf_header_show_pagination) { ?>

    <div class="document-engine-pdf-header-wrap">

        <?php
        // check if Header logo exists
        if ($image_url !== null) { ?>

            <div class="document-engine-pdf-header-image">
                <img src="<?php echo esc_url_raw($image_url); ?>"/>
            </div>

        <?php }

        ?>

        <div class="document-engine-pdf-header-content">

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




