<html>
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo get_bloginfo('stylesheet_url'); ?>" media="all"/>

    <?php

    if (document_engine_pdf_use_theme_style()) {
        wp_head();
    }
    document_engine_pdf_css();

    ?>

</head>

<body>
<?php

$document_engine_post_id = get_query_var(DOCUMENT_ENGINE_QUERY_VAR_SLUG);

$post_type = get_post_type($document_engine_post_id);

$slug = $post_type === 'attachment' ? 'attachment' : 'post';

document_engine_get_template("post-types/pdf-{$slug}.php");

?>

</body>

</html>
