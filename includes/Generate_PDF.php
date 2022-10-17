<?php

namespace MatrixAddons\DocumentEngine;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class Generate_PDF
{
    public static function generate()
    {

        if (!document_engine_pdf_is_valid_post_type()) {
            return;
        }
        // page orientation
        $page_orientation = document_engine_pdf_page_orientation();

        if ($page_orientation == 'horizontal') {

            $format = apply_filters('document_engine_pdf_format', 'A4') . '-L';

        } else {

            $format = apply_filters('document_engine_pdf_format', 'A4');

        }

        add_filter('document_engine_get_attachment_image_url', array(__CLASS__, 'modify_attachment_src'), 10, 2);

        // font size
        $document_engine_font_size = document_engine_pdf_page_font_size();

        $document_engine_font_family = '';

        // margins
        $document_engine_margin_left = document_engine_pdf_page_margin_left();
        $document_engine_margin_right = document_engine_pdf_page_margin_right();
        $document_engine_margin_top = document_engine_pdf_page_margin_top();
        $document_engine_margin_bottom = document_engine_pdf_page_margin_bottom();
        $document_engine_margin_header = document_engine_pdf_page_margin_header();

        // fonts
        $mpdf_default_config = (new ConfigVariables())->getDefaults();
        $document_engine_pdf_font_dir = apply_filters('document_engine_pdf_font_dir', $mpdf_default_config['fontDir']);

        $mpdf_default_font_config = (new FontVariables())->getDefaults();
        $document_engine_pdf_font_data = apply_filters('document_engine_pdf_font_data', $mpdf_default_font_config['fontdata']);

        $document_engine_pdf_temp_dir = document_engine()->get_tmp_pdf_dir(true, true);

        $mpdf_config = apply_filters('document_engine_pdf_config', [
            'tempDir' => $document_engine_pdf_temp_dir,
            'default_font_size' => $document_engine_font_size,
            'format' => $format,
            'margin_left' => $document_engine_margin_left,
            'margin_right' => $document_engine_margin_right,
            'margin_top' => $document_engine_margin_top,
            'margin_bottom' => $document_engine_margin_bottom,
            'margin_header' => $document_engine_margin_header,
            'fontDir' => $document_engine_pdf_font_dir,
            'fontdata' => $document_engine_pdf_font_data,
        ]);


        $mpdf = apply_filters('document_engine_pdf_mpdf_instance', new Mpdf($mpdf_config));

        $enable_protection = document_engine_pdf_page_enable_protection();

        if ($enable_protection == 'yes') {
            $grant_permissions = array_keys(document_engine_pdf_page_protected_permissions());

            if (count($grant_permissions) > 0) {
                $mpdf->SetProtection($grant_permissions);
            }
        }

        // keep columns
        $keep_columns = document_engine_pdf_page_keep_columns();

        if ($keep_columns == 'yes') {
            $mpdf->keepColumns = true;
        }

        /*
        // make chinese characters work in the pdf
        $mpdf->useAdobeCJK = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        */
        // header
        ob_start();
        document_engine_get_template('pdf-header.php');
        $pdf_header_html = ob_get_clean();

        $mpdf->SetHTMLHeader($pdf_header_html);

        // footer
        ob_start();
        document_engine_get_template('pdf-footer.php');
        $pdf_footer_html = ob_get_clean();
        $mpdf->SetHTMLFooter($pdf_footer_html);

        $mpdf->WriteHTML(apply_filters('document_engine_before_content', ''));
        ob_start();
        document_engine_get_template('pdf-index.php');

        $main_html = ob_get_clean();

        $mpdf->WriteHTML($main_html);
        $mpdf->WriteHTML(apply_filters('document_engine_after_content', ''));

        // action to do (open or download)
        $pdfbutton_action = document_engine_pdf_button_action();

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

    public static function modify_attachment_src($src, $image_id)
    {
        if (absint($image_id) < 1) {
            return $src;
        }
        return wp_get_original_image_path($image_id);
    }


}
