<?php

namespace MatrixAddons\DocumentEngine\Hooks;

use MatrixAddons\DocumentEngine\Generate_PDF;

class Template
{
    public function __construct()
    {
        add_filter('the_content', array($this, 'button'));
        add_filter('query_vars', array($this, 'set_query_vars'));
        add_action('wp', array($this, 'generate_pdf'));


    }

    public function button($content)
    {
        if (!is_singular()) {
            return;
        }
        if (is_archive() || is_front_page() || is_home()) {
            return $content;
        }


        // if is generated pdf don't show pdf button
        $pdf = get_query_var(DOCUMENT_ENGINE_QUERY_VAR_SLUG);

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

            document_engine_get_template('pdf-button.php');

            return $c . ob_get_clean();

        }


    }

    public function set_query_vars($query_vars)
    {
        $query_vars[] = DOCUMENT_ENGINE_QUERY_VAR_SLUG;

        return $query_vars;

    }

    public function generate_pdf($query)
    {
        $pdf_post_id = sanitize_text_field(get_query_var(DOCUMENT_ENGINE_QUERY_VAR_SLUG));

        if (absint($pdf_post_id) < 1) {

            return;
        }
        
        if (get_post_status($pdf_post_id) !== 'publish') {
            return;
        }

        Generate_PDF::generate();
    }
}