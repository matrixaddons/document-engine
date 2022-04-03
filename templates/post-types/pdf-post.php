<?php
$document_engine_post_id = get_query_var(DOCUMENT_ENGINE_QUERY_VAR_SLUG);
$post_type = get_post_type($document_engine_post_id);
$args = array(
    'p' => $document_engine_post_id,
    'post_status' => 'publish',
    'post_type' => $post_type

);

$the_query = new WP_Query(apply_filters('document_engine_pdf_query_args', $args));

if ($the_query->have_posts()) {

    while ($the_query->have_posts()) {
        $the_query->the_post();
        global $post;
        ?>

        <div class="document-engine-pdf-content">

            <?php the_content(); ?>

        </div>

    <?php }

} else {

    echo '<h2>no results found</h2>';
}

wp_reset_postdata();
