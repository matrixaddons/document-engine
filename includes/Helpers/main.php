<?php

if (!defined('ABSPATH')) exit;

/**
 * displays pdf button
 */


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

    $post_types_updated = array(
        array('id' => 'post', 'title' => __('post', 'document-engine')),
        array('id' => 'page', 'title' => __('page', 'document-engine')),
        array('id' => 'attachment', 'title' => __('attachment', 'document-engine')),
    );

    foreach ($post_types as $post_type) {

        $post_types_updated[] = array('id' => $post_type, 'title' => $post_type);


    }

    return $post_types_updated;

}

function document_engine_get_pdf_permissions()
{
    return array(
        array('id' => 'copy', 'title' => 'Copy'),
        array('id' => 'print', 'title' => 'Print'),
        array('id' => 'print-highres', 'title' => 'Print Highres'),
        array('id' => 'modify', 'title' => 'Modify'),
        array('id' => 'annot-forms', 'title' => 'Annot Forms'),
        array('id' => 'fill-forms', 'title' => 'Fill Forms'),
        array('id' => 'extract', 'title' => 'Extract'),
        array('id' => 'assemble', 'title' => 'Assemble')
    );

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
