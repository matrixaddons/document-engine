<?php

namespace MatrixAddons\DocumentEngine\Shortcodes;

use MatrixAddons\DocumentEngine\Shortcodes;

class ColumnsShortcode
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
     * [document_engine_pdf_columns]text[/document_engine_pdf_columns]
     * https://mpdf.github.io/what-else-can-i-do/columns.html
     *
     * <columns column-count=”n” vAlign=”justify” column-gap=”n” />
     * column-count = Number of columns. Anything less than 2 sets columns off. (Required)
     * vAlign = Automatically adjusts height of columns to be equal if set to J or justify. Default Off. (Optional)
     * gap = gap in mm between columns. Default 5. (Optional)
     *
     * <columnbreak /> <column_break /> or <newcolumn /> (synonymous) can be included to force a new column.
     * (This will automatically disable any justification or readjustment of column heights.)
     */
    public static function output($atts, $content = null)
    {

        $shortcode_attributes = shortcode_atts(array(
            'columns' => 2,
            'equal_columns' => false,
            'gap' => 10
        ), $atts);


        if (document_engine_pdf_is_valid_post_type()) {
            $columns = sanitize_text_field($shortcode_attributes['columns']);
            $equal_columns = (boolean)($shortcode_attributes['equal_columns']);
            $vAlign = $equal_columns == true ? 'vAlign="justify"' : '';
            $gap = absint($shortcode_attributes['gap']);
            echo '<columns column-count="' . esc_attr($columns) . '" ' . esc_attr($vAlign) . ' column-gap="' . esc_attr($gap) . '" />' . do_shortcode($content) . '<columns column-count="1">';
        } else {
            remove_shortcode('document_engine_pdf_column_break');
            add_shortcode('document_engine_pdf_column_break', '__return_false');
            echo do_shortcode($content);
        }
    }
}
