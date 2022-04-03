<div style="width:100%;float:left;">
    <?php
    $document_engine_post_id = get_query_var(DOCUMENT_ENGINE_QUERY_VAR_SLUG);
    // image
    $image = wp_get_attachment_image($document_engine_post_id, 'full');

    echo $image ? $image : '';


    $thumb_img = get_post(get_post_thumbnail_id());

    if ($thumb_img) {

        echo '<p style="margin-top:30px;">Caption: ' . $thumb_img->post_excerpt . '</p>';
        echo '<p>Description: ' . $thumb_img->post_content . '</p>';

    }
 
    $metadata = wp_get_attachment_metadata($document_engine_post_id);

    if ($metadata) {

        $metadata_width = $metadata['width'];
        $metadata_height = $metadata['height'];
        $image_meta = $metadata['image_meta'];

        echo '<p style="margin-top:30px;">Dimensions: ' . $metadata_width . ' x ' . $metadata_height . '</p>';

        // image metadata
        foreach ($image_meta as $key => $value) {

            echo $key . ': ' . $value . '<br>';

        }

    }

    ?>

</div>