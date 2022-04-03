<?php

namespace MatrixAddons\DocumentEngine\Shortcodes;

use MatrixAddons\DocumentEngine\Shortcodes;

class PageBreakShortcode
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
     * [document_engine_pdf_page_break]
     * Allows adding page breaks for sending content after this shortcode to the next page.
     * Uses <pagebreak /> http://mpdf1.com/manual/index.php?tid=108
     * @return string
     */
    public static function output($atts, $content = null)
    {

        if (document_engine_pdf_is_valid_post_type()) {

            echo '<pagebreak />';
        }
    }
}
