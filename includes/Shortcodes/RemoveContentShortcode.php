<?php

namespace MatrixAddons\DocumentEngine\Shortcodes;

use MatrixAddons\DocumentEngine\Shortcodes;

class RemoveContentShortcode
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
     * [document_engine_pdf_remove tag="gallery"]content to remove[/document_engine_pdf_remove]
     * This shortcode is used remove pieces of content in the generated PDF
     * @return string
     */
    public static function output($atts, $content = null)
    {
        $shortcode_atts = shortcode_atts(array(
            'tag' => ''
        ), $atts);

        $tag = sanitize_text_field($shortcode_atts['tag']);

        $in_pdf = document_engine_pdf_is_valid_post_type();
        if ($tag === '') {
            // No tag: the content shows on the page and is left out of the PDF.
            echo $in_pdf ? '' : do_shortcode($content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } elseif ($in_pdf) {
            // With a tag: in the PDF, that shortcode is left out of this content only; it is restored afterwards.
            global $shortcode_tags;
            $saved = isset($shortcode_tags[$tag]) ? $shortcode_tags[$tag] : null;
            $shortcode_tags[$tag] = '__return_empty_string';
            echo do_shortcode($content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            if ($saved !== null) {
                $shortcode_tags[$tag] = $saved;
            } else {
                unset($shortcode_tags[$tag]);
            }
        } else {
            echo do_shortcode($content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

    }
}
