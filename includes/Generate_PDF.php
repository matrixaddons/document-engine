<?php

namespace MatrixAddons\DocumentEngine;

use MatrixAddons\DocumentEngine\Pdf\Cache;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Config\ConfigVariables;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Config\FontVariables;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Mpdf;

class Generate_PDF
{
    public static function generate()
    {

        if (!document_engine_pdf_is_valid_post_type()) {
            return;
        }

        $post_id = absint(get_query_var(DOCUMENT_ENGINE_QUERY_VAR_SLUG));

        $post = get_post($post_id);

        if (!$post) {
            return;
        }

        // Templates (header/footer/filters) read the global post, as in 1.x.
        $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        setup_postdata($post);

        $title = apply_filters('document_engine_pdf_filename', get_the_title($post->ID));

        $pdfbutton_action = document_engine_pdf_button_action();

        $cached = Cache::get($post->ID);

        if ($cached !== '') {
            self::send_file($cached, $title, $pdfbutton_action);
        }

        if (!Cache::allow_generation()) {
            status_header(429);
            header('Retry-After: 60');
            wp_die(esc_html__('Too many PDF requests. Please try again in a minute.', 'document-engine'), esc_html(DOCUMENT_ENGINE_BRAND), array('response' => 429));
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(120); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }

        $mpdf = self::build_mpdf();

        do_action('document_engine_before_generate_pdf', $mpdf, $post);

        $content = $mpdf->Output('', 'S');

        Cache::put($post->ID, $content);

        self::send_content($content, $title, $pdfbutton_action);
    }

    /**
     * Loads prefixed mPDF (and its helper functions) on demand.
     */
    private static function load_library()
    {
        $autoload = DOCUMENT_ENGINE_ABSPATH . 'vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
    }

    /**
     * Builds the mPDF document for the current post.
     *
     * @return Mpdf
     */
    public static function build_mpdf()
    {
        self::load_library();

        // page orientation
        $page_orientation = document_engine_pdf_page_orientation();

        $size = apply_filters('document_engine_pdf_format', document_engine_pdf_page_size());
        $format = $page_orientation == 'horizontal' ? $size . '-L' : $size;

        add_filter('document_engine_get_attachment_image_url', array(__CLASS__, 'modify_attachment_src'), 10, 2);

        // font size
        $document_engine_font_size = document_engine_pdf_page_font_size();

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

        $document_engine_pdf_temp_dir = document_engine()->get_tmp_pdf_dir(true, false);

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
            // Right-to-left sites (Arabic, Hebrew, Persian) get right-to-left PDFs; the bundled DejaVu fonts cover those scripts.
            'directionality' => is_rtl() ? 'rtl' : 'ltr',
        ]);

        $mpdf = apply_filters('document_engine_pdf_mpdf_instance', new Mpdf($mpdf_config));

        if (document_engine_pdf_page_enable_protection()) {
            $grant_permissions = array_keys(array_filter(document_engine_pdf_page_protected_permissions(), function ($value) {
                return $value === 'yes';
            }));

            // As in 1.x, protection applies once at least one permission is granted.
            if (count($grant_permissions) > 0) {
                $mpdf->SetProtection($grant_permissions);
            }
        }

        if (document_engine_pdf_page_keep_columns()) {
            $mpdf->keepColumns = true;
        }

        // header
        ob_start();
        document_engine_get_template('pdf-header.php');
        $pdf_header_html = ob_get_clean();

        $mpdf->SetHTMLHeader(self::localize_assets($pdf_header_html));

        // footer
        ob_start();
        document_engine_get_template('pdf-footer.php');
        $pdf_footer_html = ob_get_clean();
        $mpdf->SetHTMLFooter(self::localize_assets($pdf_footer_html));

        $mpdf->WriteHTML(apply_filters('document_engine_before_content', ''));
        ob_start();
        document_engine_get_template('pdf-index.php');

        $main_html = ob_get_clean();

        $mpdf->WriteHTML(self::localize_assets($main_html));
        $mpdf->WriteHTML(apply_filters('document_engine_after_content', ''));

        global $post;

        $mpdf->SetTitle(apply_filters('document_engine_pdf_filename', get_the_title($post->ID)));
        $mpdf->SetAuthor(apply_filters('document_engine_pdf_author', get_bloginfo('name')));
        $mpdf->SetCreator(DOCUMENT_ENGINE_BRAND);

        return $mpdf;
    }

    /**
     * Points <img src> and <link href> at files on disk instead of this site's URLs, so mPDF
     * reads them directly rather than downloading them over HTTP on every PDF.
     */
    public static function localize_assets($html)
    {
        // Filtering this off keeps URLs as they are; the remote-host check below always runs.
        $map = apply_filters('document_engine_pdf_localize_assets', true) ? array(
            untrailingslashit(content_url()) => untrailingslashit(WP_CONTENT_DIR),
            untrailingslashit(includes_url()) => untrailingslashit(ABSPATH . WPINC),
        ) : array();
        $html = preg_replace_callback('#<(img|link)\b[^>]*>#i', function ($tag) use ($map) {
            return preg_replace_callback('#\b(src|href)=(["\'])([^"\']+)\2#i', function ($attr) use ($map) {
                $url = html_entity_decode($attr[3], ENT_QUOTES);
                foreach ($map as $prefix => $dir) {
                    foreach (array($prefix, set_url_scheme($prefix, 'http'), set_url_scheme($prefix, 'https')) as $candidate) {
                        if (strpos($url, $candidate . '/') === 0) {
                            $path = $dir . strtok(substr($url, strlen($candidate)), '?#');
                            if (file_exists($path) && strpos(realpath($path), realpath($dir)) === 0) {
                                return $attr[1] . '=' . $attr[2] . $path . $attr[2];
                            }
                        }
                    }
                }
                // Remote files: only public hosts (no internal or private addresses; mPDF fetches them from the server).
                return self::safe_remote($url) ? $attr[0] : $attr[1] . '=' . $attr[2] . $attr[2];
            }, $tag[0]);
        }, $html);
        // CSS url(...) in style attributes and <style> blocks.
        return preg_replace_callback('#url\(\s*(["\']?)(https?:)?//([^)"\']+)\1\s*\)#i', function ($m) {
            $url = ($m[2] !== '' ? $m[2] : 'https:') . '//' . html_entity_decode($m[3], ENT_QUOTES);
            return self::safe_remote($url) ? $m[0] : 'url()';
        }, $html);
    }

    /**
     * Whether mPDF may fetch this URL: local files were already mapped; anything else must be a public http(s) host.
     */
    private static function safe_remote($url)
    {
        if (!preg_match('#^(https?:)?//#i', $url)) {
            return true; // Relative paths, data: URIs and local file paths are not network requests.
        }
        $url = strpos($url, '//') === 0 ? 'https:' . $url : $url;
        return (bool)apply_filters('document_engine_pdf_allow_remote_asset', (bool)wp_http_validate_url($url), $url);
    }

    private static function headers($title, $action, $length)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $name = sanitize_file_name(wp_strip_all_tags(html_entity_decode((string)$title, ENT_QUOTES, 'UTF-8')));
        $name = ($name !== '' ? $name : 'document') . '.pdf';
        $disposition = $action === 'open' ? 'inline' : 'attachment';

        status_header(200);
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', $name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('Content-Length: ' . (int)$length);
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('X-Robots-Tag: noindex');
        header('X-Content-Type-Options: nosniff');
    }

    private static function send_file($path, $title, $action)
    {
        self::headers($title, $action, filesize($path));
        readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        exit;
    }

    private static function send_content($content, $title, $action)
    {
        self::headers($title, $action, strlen($content));
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    public static function modify_attachment_src($src, $image_id)
    {
        if (absint($image_id) < 1) {
            return $src;
        }
        $path = wp_get_original_image_path($image_id);
        return $path ? $path : $src;
    }


}
