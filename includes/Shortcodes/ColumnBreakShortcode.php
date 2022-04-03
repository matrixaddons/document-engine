<?php

namespace MatrixAddons\DocumentEngine\Shortcodes;

use MatrixAddons\DocumentEngine\Shortcodes;

class ColumnBreakShortcode
{

    /**
     * Get the shortcode content.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function get($atts, $content = null)
    {
        return Shortcodes::shortcode_wrapper(array(__CLASS__, 'output'), $atts, $content);
    }

    /**
     * Output the shortcode.
     *
     * @param array $atts Shortcode attributes.
     */
    public static function output($atts, $content = null)
    {

        if (document_engine_pdf_is_valid_post_type()) {

            echo '<columnbreak />';

        }

    }
}
