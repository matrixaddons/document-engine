<?php

namespace MatrixAddons\DocumentEngine\Shortcodes;

use MatrixAddons\DocumentEngine\Shortcodes;

class ButtonShortcode
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

        if (!is_singular()) {
            return;
        }
        if (is_archive() || is_front_page() || is_home()) {
            return $content;
        }


        $shortcode_atts = shortcode_atts(array(
            'icon' => 'fa fa-file-pdf',
            'text' => document_engine_pdf_button_text(),
            'alignment' => document_engine_pdf_button_alignment(),
        ), $atts);

        $button_args = array(
            'button_text' => $shortcode_atts['text'],
            'button_alignment' => $shortcode_atts['alignment'],
            'button_icon' => $shortcode_atts['icon'],
        );
        document_engine_get_template('pdf-button.php', $button_args);
    }
}
