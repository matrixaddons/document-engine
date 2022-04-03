<html>
<head>
    <link type="text/css" rel="stylesheet" href="<?php echo get_bloginfo('stylesheet_url'); ?>" media="all"/>
    <?php

    if (document_engine_pdf_use_theme_style() == 'yes') {
        wp_head();
    }
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

        <?php

           echo  wp_kses(document_engine_pdf_custom_css(), array());

        ?>

    </style>

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
