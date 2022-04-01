<?php

if (!defined('ABSPATH')) exit;

/**
 * displays pdf button
 */
function document_engine_display_pdf_button($content)
{

    // if is generated pdf don't show pdf button
    $pdf = get_query_var('pdf');

    if (apply_filters('document_engine_hide_button_isset', isset($_POST['dkpdfg_action_create']))) {

        if ($pdf || apply_filters('document_engine_hide_button_equal', $_POST['dkpdfg_action_create'] == 'dkpdfg_action_create')) {

            remove_shortcode('dkpdf-button');
            $content = str_replace("[dkpdf-button]", "", $content);

            return $content;

        }

    } else {

        if ($pdf) {

            remove_shortcode('dkpdf-button');
            $content = str_replace("[dkpdf-button]", "", $content);

            return $content;

        }

    }

    global $post;
    $post_type = get_post_type($post->ID);

    $option_post_types = sanitize_option('document_engine_pdfbutton_post_types', get_option('document_engine_pdfbutton_post_types', array()));

    // TODO button checkboxes?
    if (is_archive() || is_front_page() || is_home()) {
        return $content;
    }

    // return content if not checked
    if ($option_post_types) {

        if (!in_array(get_post_type($post), $option_post_types)) {

            return $content;

        }

    }

    if ($option_post_types) {

        if (in_array(get_post_type($post), $option_post_types)) {

            $c = $content;

            $pdfbutton_position = sanitize_option('document_engine_pdfbutton_position', get_option('document_engine_pdfbutton_position', 'before'));

            $template = new DKPDF_Template_Loader;

            if ($pdfbutton_position) {

                if ($pdfbutton_position == 'shortcode') {
                    return $c;
                }

                if ($pdfbutton_position == 'before') {

                    ob_start();

                    $content = $template->get_template_part('dkpdf-button');

                    return ob_get_clean() . $c;


                } else if ($pdfbutton_position == 'after') {

                    ob_start();

                    $content = $template->get_template_part('dkpdf-button');

                    return $c . ob_get_clean();

                }

            }

        }

    } else {

        return $content;

    }

}

add_filter('the_content', 'document_engine_display_pdf_button');

/**
 * output the pdf
 */
function document_engine_output_pdf($query)
{

    $pdf = sanitize_text_field(get_query_var('pdf'));

    if ($pdf) {

        require_once realpath(__DIR__ . '/..') . '/vendor/autoload.php';

        // page orientation
        $document_engine_page_orientation = get_option('document_engine_page_orientation', '');

        if ($document_engine_page_orientation == 'horizontal') {

            $format = apply_filters('document_engine_pdf_format', 'A4') . '-L';

        } else {

            $format = apply_filters('document_engine_pdf_format', 'A4');

        }

        // font size
        $document_engine_font_size = get_option('document_engine_font_size', '12');
        $document_engine_font_family = '';

        // margins
        $document_engine_margin_left = get_option('document_engine_margin_left', '15');
        $document_engine_margin_right = get_option('document_engine_margin_right', '15');
        $document_engine_margin_top = get_option('document_engine_margin_top', '50');
        $document_engine_margin_bottom = get_option('document_engine_margin_bottom', '30');
        $document_engine_margin_header = get_option('document_engine_margin_header', '15');

        // fonts
        $mpdf_default_config = (new Mpdf\Config\ConfigVariables())->getDefaults();
        $document_engine_mpdf_font_dir = apply_filters('document_engine_mpdf_font_dir', $mpdf_default_config['fontDir']);

        $mpdf_default_font_config = (new Mpdf\Config\FontVariables())->getDefaults();
        $document_engine_mpdf_font_data = apply_filters('document_engine_mpdf_font_data', $mpdf_default_font_config['fontdata']);

        // temp directory
        $document_engine_mpdf_temp_dir = apply_filters('document_engine_mpdf_temp_dir', realpath(__DIR__ . '/..') . '/tmp');

        $mpdf_config = apply_filters('document_engine_mpdf_config', [
            'tempDir' => $document_engine_mpdf_temp_dir,
            'default_font_size' => $document_engine_font_size,
            'format' => $format,
            'margin_left' => $document_engine_margin_left,
            'margin_right' => $document_engine_margin_right,
            'margin_top' => $document_engine_margin_top,
            'margin_bottom' => $document_engine_margin_bottom,
            'margin_header' => $document_engine_margin_header,
            'fontDir' => $document_engine_mpdf_font_dir,
            'fontdata' => $document_engine_mpdf_font_data,
        ]);

        // creating and setting the pdf
        $mpdf = new \Mpdf\Mpdf($mpdf_config);

        // encrypts and sets the PDF document permissions
        // https://mpdf.github.io/reference/mpdf-functions/setprotection.html
        $enable_protection = get_option('document_engine_enable_protection');

        if ($enable_protection == 'on') {
            $grant_permissions = get_option('document_engine_grant_permissions');
            $mpdf->SetProtection($grant_permissions);
        }

        // keep columns
        $keep_columns = get_option('document_engine_keep_columns');

        if ($keep_columns == 'on') {
            $mpdf->keepColumns = true;
        }

        /*
        // make chinese characters work in the pdf
        $mpdf->useAdobeCJK = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        */

        // header
        $pdf_header_html = document_engine_get_template('dkpdf-header');
        $mpdf->SetHTMLHeader($pdf_header_html);

        // footer
        $pdf_footer_html = document_engine_get_template('dkpdf-footer');
        $mpdf->SetHTMLFooter($pdf_footer_html);

        $mpdf->WriteHTML(apply_filters('document_engine_before_content', ''));
        $mpdf->WriteHTML(document_engine_get_template('dkpdf-index'));
        $mpdf->WriteHTML(apply_filters('document_engine_after_content', ''));

        // action to do (open or download)
        $pdfbutton_action = sanitize_option('document_engine_pdfbutton_action', get_option('document_engine_pdfbutton_action', 'open'));

        global $post;
        $title = apply_filters('document_engine_pdf_filename', get_the_title($post->ID));

        $mpdf->SetTitle($title);
        $mpdf->SetAuthor(apply_filters('document_engine_pdf_author', get_bloginfo('name')));

        if ($pdfbutton_action == 'open') {

            $mpdf->Output($title . '.pdf', 'I');

        } else {

            $mpdf->Output($title . '.pdf', 'D');

        }

        exit;

    }

}

add_action('wp', 'document_engine_output_pdf');

/**
 * returs a template
 * @param string template name
 */
function document_engine_get_template($template_name)
{

    $template = new DKPDF_Template_Loader;

    ob_start();
    $template->get_template_part($template_name);
    return ob_get_clean();

}

/**
 * returns an array of active post, page, attachment and custom post types
 * @return array
 */
function document_engine_get_post_types()
{

    $args = array(
        'public' => true,
        '_builtin' => false
    );

    $post_types = get_post_types($args);
    $post_arr = array('post' => 'post', 'page' => 'page', 'attachment' => 'attachment');

    foreach ($post_types as $post_type) {

        $arr = array($post_type => $post_type);
        $post_arr += $arr;

    }

    $post_arr = apply_filters('dkpdf' . '_posts_arr', $post_arr);

    return $post_arr;

}

/**
 * set query_vars
 */
function document_engine_set_query_vars($query_vars)
{

    $query_vars[] = 'pdf';

    return $query_vars;

}

add_filter('query_vars', 'document_engine_set_query_vars');

/**
 * sanitizes dkpdf options
 */
function document_engine_sanitize_options()
{

    add_filter('pre_update_option_document_engine_pdfbutton_text', 'document_engine_update_field_document_engine_pdfbutton_text', 10, 2);
    add_filter('pre_update_option_document_engine_pdfbutton_post_types', 'document_engine_update_field_document_engine_pdfbutton_post_types', 10, 2);
    add_filter('pre_update_option_document_engine_pdfbutton_action', 'document_engine_update_field_document_engine_pdfbutton_action', 10, 2);
    add_filter('pre_update_option_document_engine_pdfbutton_position', 'document_engine_update_field_document_engine_pdfbutton_position', 10, 2);
    add_filter('pre_update_option_document_engine_pdfbutton_align', 'document_engine_update_field_document_engine_pdfbutton_align', 10, 2);
    add_filter('pre_update_option_document_engine_page_orientation', 'document_engine_update_field_document_engine_page_orientation', 10, 2);
    add_filter('pre_update_option_document_engine_font_size', 'document_engine_update_field_document_engine_font_size', 10, 2);
    add_filter('pre_update_option_document_engine_margin_left', 'document_engine_update_field_document_engine_margin_left', 10, 2);
    add_filter('pre_update_option_document_engine_margin_right', 'document_engine_update_field_document_engine_margin_right', 10, 2);
    add_filter('pre_update_option_document_engine_margin_top', 'document_engine_update_field_document_engine_margin_top', 10, 2);
    add_filter('pre_update_option_document_engine_margin_bottom', 'document_engine_update_field_document_engine_margin_bottom', 10, 2);
    add_filter('pre_update_option_document_engine_margin_header', 'document_engine_update_field_document_engine_margin_header', 10, 2);
    add_filter('pre_update_option_document_engine_pdf_header_image', 'document_engine_update_field_document_engine_pdf_header_image', 10, 2);
    add_filter('pre_update_option_document_engine_pdf_header_show_title', 'document_engine_update_field_document_engine_pdf_header_show_title', 10, 2);
    add_filter('pre_update_option_document_engine_pdf_header_show_pagination', 'document_engine_update_field_document_engine_pdf_header_show_pagination', 10, 2);
    add_filter('pre_update_option_document_engine_pdf_footer_text', 'document_engine_update_field_document_engine_pdf_footer_text', 10, 2);
    add_filter('pre_update_option_document_engine_pdf_footer_show_title', 'document_engine_update_field_document_engine_pdf_footer_show_title', 10, 2);
    add_filter('pre_update_option_document_engine_pdf_footer_show_pagination', 'document_engine_update_field_document_engine_pdf_footer_show_pagination', 10, 2);
    add_filter('pre_update_option_document_engine_pdf_custom_css', 'document_engine_update_field_document_engine_pdf_custom_css', 10, 2);
    add_filter('pre_update_option_document_engine_print_wp_head', 'document_engine_update_field_document_engine_print_wp_head', 10, 2);


}

add_action('init', 'document_engine_sanitize_options');

/**
 * sanitizes document_engine_pdfbutton_text option
 */
function document_engine_update_field_document_engine_pdfbutton_text($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdfbutton_post_types option
 */
function document_engine_update_field_document_engine_pdfbutton_post_types($new_value, $old_value)
{
    // TODO sanitize_text_field doesn't work
    //$new_value = sanitize_text_field( $new_value );
    return $new_value;
}

/**
 * sanitizes document_engine_pdfbutton_action option
 */
function document_engine_update_field_document_engine_pdfbutton_action($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdfbutton_position option
 */
function document_engine_update_field_document_engine_pdfbutton_position($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdfbutton_align option
 */
function document_engine_update_field_document_engine_pdfbutton_align($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_page_orientation option
 */
function document_engine_update_field_document_engine_page_orientation($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_font_size option
 */
function document_engine_update_field_document_engine_font_size($new_value, $old_value)
{
    $new_value = intval($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_margin_left option
 */
function document_engine_update_field_document_engine_margin_left($new_value, $old_value)
{
    $new_value = intval($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_margin_right option
 */
function document_engine_update_field_document_engine_margin_right($new_value, $old_value)
{
    $new_value = intval($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_margin_top option
 */
function document_engine_update_field_document_engine_margin_top($new_value, $old_value)
{
    $new_value = intval($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_margin_bottom option
 */
function document_engine_update_field_document_engine_margin_bottom($new_value, $old_value)
{
    $new_value = intval($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_margin_header option
 */
function document_engine_update_field_document_engine_margin_header($new_value, $old_value)
{
    $new_value = intval($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdf_header_image option
 */
function document_engine_update_field_document_engine_pdf_header_image($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdf_header_show_title option
 */
function document_engine_update_field_document_engine_pdf_header_show_title($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdf_header_show_pagination option
 */
function document_engine_update_field_document_engine_pdf_header_show_pagination($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdf_footer_text option
 */
function document_engine_update_field_document_engine_pdf_footer_text($new_value, $old_value)
{

    $arr = array(
        'a' => array(
            'href' => array(),
            'title' => array(),
            'class' => array(),
            'style' => array()
        ),
        'br' => array(),
        'em' => array(),
        'strong' => array(),
        'hr' => array(),
        'p' => array(
            'title' => array(),
            'class' => array(),
            'style' => array()
        ),
        'h1' => array(
            'title' => array(),
            'class' => array(),
            'style' => array()
        ),
        'h2' => array(
            'title' => array(),
            'class' => array(),
            'style' => array()
        ),
        'h3' => array(
            'title' => array(),
            'class' => array(),
            'style' => array()
        ),
        'h4' => array(
            'title' => array(),
            'class' => array(),
            'style' => array()
        ),
        'div' => array(
            'title' => array(),
            'class' => array(),
            'style' => array()
        )
    );

    $new_value = wp_kses($new_value, $arr);
    return $new_value;

}

/**
 * sanitizes document_engine_pdf_header_show_pagination option
 */
function document_engine_update_field_document_engine_pdf_footer_show_title($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdf_header_show_pagination option
 */
function document_engine_update_field_document_engine_pdf_footer_show_pagination($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_pdf_custom_css option
 */
function document_engine_update_field_document_engine_pdf_custom_css($new_value, $old_value)
{
    $new_value = wp_filter_nohtml_kses($new_value);
    $new_value = str_replace('\"', '"', $new_value);
    $new_value = str_replace("\'", "'", $new_value);
    return $new_value;
}

/**
 * sanitizes document_engine_print_wp_head option
 */
function document_engine_update_field_document_engine_print_wp_head($new_value, $old_value)
{
    $new_value = sanitize_text_field($new_value);
    return $new_value;
}
