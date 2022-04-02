<?php

namespace MatrixAddons\DocumentEngine\Hooks;

use MatrixAddons\DocumentEngine\Loader;

class Template
{
    public function __construct()
    {
        add_filter('the_content', array($this, 'button'));

    }

    function button($content)
    {
        if (!is_singular()) {
            return;
        }
        if (is_archive() || is_front_page() || is_home()) {
            return $content;
        }


        // if is generated pdf don't show pdf button
        $pdf = get_query_var('generate_pdf');

        if ($pdf) {

            remove_shortcode('dkpdf-button');

            $content = str_replace("[dkpdf-button]", "", $content);

            return $content;

        }


        global $post;

        $option_post_types = array_keys(document_engine_pdf_post_type());


        if (!in_array(get_post_type($post), $option_post_types)) {

            return $content;

        }

        $c = $content;

        $button_position = document_engine_pdf_button_position();


        if ($button_position == '') {
            return $c;
        }

        if ($button_position == 'before') {

            ob_start();

            document_engine_get_template('pdf-button.php');


            return ob_get_clean() . $c;


        } else if ($button_position == 'after') {

            ob_start();

            $content = document_engine_get_template('pdf-button.php');

            return $c . ob_get_clean();

        }


    }

}