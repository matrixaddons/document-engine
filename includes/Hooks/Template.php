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
            return $content;
        }
        if (is_archive() || is_front_page() || is_home()) {
            return $content;
        }

        if (document_engine_pdf_is_valid_post_type()) {

            remove_shortcode('document_engine_pdf_button');

            return str_replace("[document_engine_pdf_button]", "", $content);

        }

        global $post;

        $option_post_types = array_keys(document_engine_pdf_post_type());

        if (!in_array(get_post_type($post), $option_post_types)) {

            return $content;

        }

        $c = $content;

        $button_position = document_engine_pdf_button_position();


        $button_args = array(
            'button_text' => document_engine_pdf_button_text(),
            'button_alignment' => document_engine_pdf_button_alignment(),
            'button_icon' => 'fa fa-file-pdf'
        );

        if ($button_position == '') {
            return $c;
        }

        if ($button_position == 'before') {

            ob_start();

            document_engine_get_template('pdf-button.php', $button_args);


            return ob_get_clean() . $c;


        } else if ($button_position == 'after') {

            ob_start();

            document_engine_get_template('pdf-button.php', $button_args);

            return $c . ob_get_clean();

        }

        return $content;

    }

    public function set_query_vars($query_vars)
    {
        $query_vars[] = DOCUMENT_ENGINE_QUERY_VAR_SLUG;

        return $query_vars;

    }

    public function generate_pdf($query)
    {

        if (!document_engine_pdf_is_valid_post_type()) {
            return;
        }

        Generate_PDF::generate();
    }
}