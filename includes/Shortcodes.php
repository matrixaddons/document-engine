<?php

namespace MatrixAddons\DocumentEngine;

class Shortcodes
{

    public static function init()
    {
        $shortcodes = array(

            'document_engine_pdf_button' => __CLASS__ . '::pdf_button',
            'document_engine_pdf_remove' => __CLASS__ . '::remove_content',
            'document_engine_pdf_page_break' => __CLASS__ . '::page_break',
            'document_engine_pdf_columns' => __CLASS__ . '::columns',
            'document_engine_pdf_column_break' => __CLASS__ . '::column_break',

        );

        foreach ($shortcodes as $shortcode => $function) {
            add_shortcode(apply_filters("{$shortcode}_shortcode_tag", $shortcode), $function);
        }

    }


    public static function shortcode_wrapper(
        $function,
        $atts = array(),
        $content = null,
        $wrapper = array(
            'class' => 'document-engine-shortcode-wrapper',
            'before' => null,
            'after' => null,
        )
    )
    {
        ob_start();

        // @codingStandardsIgnoreStart
        echo empty($wrapper['before']) ? '<div class="' . esc_attr($wrapper['class']) . '">' : esc_html($wrapper['before']);
        call_user_func($function, $atts, $content);
        echo empty($wrapper['after']) ? '</div>' : esc_html($wrapper['after']);
        // @codingStandardsIgnoreEnd

        return ob_get_clean();
    }


    public static function pdf_button($atts, $content = null)
    {
        wp_enqueue_style('document-engine-frontend');
        return self::shortcode_wrapper(array('\MatrixAddons\DocumentEngine\Shortcodes\ButtonShortcode', 'output'), $atts, $content);
    }

    public static function remove_content($atts, $content = null)
    {

        return self::shortcode_wrapper(array('\MatrixAddons\DocumentEngine\Shortcodes\RemoveContentShortcode', 'output'), $atts, $content);
    }

    public static function page_break($atts, $content = null)
    {

        return self::shortcode_wrapper(array('\MatrixAddons\DocumentEngine\Shortcodes\PageBreakShortcode', 'output'), $atts, $content);
    }

    public static function columns($atts, $content = null)
    {

        return self::shortcode_wrapper(array('\MatrixAddons\DocumentEngine\Shortcodes\ColumnsShortcode', 'output'), $atts, $content);
    }

    public static function column_break($atts, $content = null)
    {
        return self::shortcode_wrapper(array('\MatrixAddons\DocumentEngine\Shortcodes\ColumnBreakShortcode', 'output'), $atts, $content);
    }


}
