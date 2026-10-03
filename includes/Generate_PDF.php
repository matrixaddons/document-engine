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

        try {
            $mpdf = self::build_mpdf();

            do_action('document_engine_before_generate_pdf', $mpdf, $post);

            $content = $mpdf->Output('', 'S');
        } finally {
            // Temporary copies of fetched images and styles are removed even if mPDF fails.
            self::cleanup_assets();
        }

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

        $mpdf_config = apply_filters('document_engine_pdf_config', array_merge([
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
            // Images and styles are copied to local files first (localize_assets), so mPDF itself never
            // opens URLs: its local-file path would otherwise fopen() http:// links found inside SVGs.
            'whitelistStreamWrappers' => ['file'],
        ], self::font_config($document_engine_pdf_font_dir, $document_engine_pdf_font_data)));

        // mPDF's own fetches (e.g. images inside SVGs) go through WordPress' SSRF-safe HTTP API.
        $mpdf = apply_filters('document_engine_pdf_mpdf_instance', new Mpdf($mpdf_config, \MatrixAddons\DocumentEngine\Pdf\SafeHttpClient::container()));
        // Relative URLs resolve against this site, never the visitor's Host header.
        $mpdf->SetBasePath(home_url('/'));

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
     * The chosen font, and the site's own font file (from the Media Library) as main or fallback font.
     */
    public static function font_config($font_dir, $font_data)
    {
        $choice = (string)get_option('document_engine_pdf_font', 'dejavusans');
        $config = array();
        if (in_array($choice, array('dejavusans', 'dejavuserif', 'dejavusansmono'), true)) {
            $config['default_font'] = $choice;
        }
        $path = self::custom_font_path();
        if ($path !== '') {
            $font_dir = array_merge((array)$font_dir, array(dirname($path)));
            $font_data['dengineuser'] = array('R' => basename($path), 'useOTL' => 0xFF);
            $config['fontDir'] = $font_dir;
            $config['fontdata'] = $font_data;
            if ($choice === 'custom' || get_option('document_engine_pdf_font_use', 'fallback') === 'all') {
                $config['default_font'] = 'dengineuser';
            } else {
                // Characters the main font lacks are drawn with the site's font.
                $config['useSubstitutions'] = true;
                $config['backupSubsFont'] = array('dengineuser');
                $config['backupSIPFont'] = 'dengineuser';
            }
        } elseif ($choice === 'custom') {
            $config['default_font'] = 'dejavusans';
        }
        return $config;
    }

    /**
     * Local path of the font file set in Settings → Post to PDF, if it's a .ttf/.otf inside uploads.
     */
    public static function custom_font_path()
    {
        $url = (string)get_option('document_engine_pdf_font_file', '');
        if ($url === '') {
            return '';
        }
        $uploads = wp_upload_dir();
        $base = set_url_scheme($uploads['baseurl'], 'https');
        $candidate = set_url_scheme($url, 'https');
        if (strpos($candidate, $base . '/') !== 0) {
            return '';
        }
        $path = realpath($uploads['basedir'] . '/' . ltrim(strtok(substr($candidate, strlen($base)), '?#'), '/'));
        if (!$path || strpos($path, realpath($uploads['basedir'])) !== 0 || !is_file($path) || !preg_match('/\.(ttf|otf)$/i', $path)) {
            return '';
        }
        return $path;
    }

    /** Remote assets fetched for the PDF being built: url => local temp path ('' when refused). */
    private static $fetched = array();

    /**
     * Makes every asset mPDF will read a local file, so mPDF itself never makes a network request.
     *
     * Files on this site are mapped to their path on disk. Other http(s) assets are downloaded by
     * WordPress (wp_safe_remote_get refuses private and internal addresses on every redirect) into
     * a temporary file. Attribute values and CSS are entity-decoded first, so encoded or unquoted
     * URLs can't slip past; anything still remote afterwards is removed.
     */
    public static function localize_assets($html)
    {
        $html = (string)$html;
        // <img src>, <link href>, and src/background on any tag (mPDF also reads <watermarkimage src>, td background…).
        $html = preg_replace_callback('#<([a-z][a-z0-9-]*)\b([^>]*)>#i', function ($tag) {
            $name = strtolower($tag[1]);
            $attrs = preg_replace_callback('#(\s)(src|href|background|data)(\s*=\s*)(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'>]+))#i', function ($a) use ($name) {
                $attr = strtolower($a[2]);
                if (($attr === 'href' && $name !== 'link') || ($attr === 'data' && $name !== 'object')) {
                    return $a[0]; // Links and data-* style values are not fetched.
                }
                $raw = isset($a[6]) && $a[6] !== '' ? $a[6] : (isset($a[5]) && $a[5] !== '' ? $a[5] : (isset($a[4]) ? $a[4] : ''));
                $url = trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $local = self::local_asset($url, $name === 'link' ? 'css' : 'binary');
                return $a[1] . $a[2] . '="' . esc_attr($local) . '"';
            }, $tag[2]);
            // Inline style attributes: decode entities, then make every url(...) local.
            $attrs = preg_replace_callback('#(\s)style\s*=\s*(?:"([^"]*)"|\'([^\']*)\')#i', function ($a) {
                $css = html_entity_decode(isset($a[3]) && $a[3] !== '' ? $a[3] : $a[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $a[1] . 'style="' . esc_attr(self::localize_css($css)) . '"';
            }, $attrs);
            return '<' . $tag[1] . $attrs . '>';
        }, $html);
        // <style> blocks.
        $html = preg_replace_callback('#(<style\b[^>]*>)(.*?)(</style>)#is', function ($m) {
            return $m[1] . self::localize_css(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')) . $m[3];
        }, $html);
        return $html;
    }

    /**
     * Rewrites url(...) and @import in CSS to local files (or removes them).
     */
    private static function localize_css($css)
    {
        $css = preg_replace_callback('#url\(\s*(["\']?)(.*?)\1\s*\)#is', function ($m) {
            $url = trim($m[2]);
            if ($url === '' || stripos($url, 'data:') === 0) {
                return $m[0];
            }
            $local = self::local_asset($url, 'binary');
            return $local === '' ? 'none' : 'url("' . str_replace('"', '', $local) . '")';
        }, (string)$css);
        return preg_replace_callback('#@import\s+(["\'])(.*?)\1#i', function ($m) {
            $local = self::local_asset(trim($m[2]), 'css');
            return $local === '' ? '' : '@import "' . str_replace('"', '', $local) . '"';
        }, $css);
    }

    /**
     * A local path (or a data: URI / relative value kept as is) for an asset URL; '' when it may not be used.
     */
    private static function local_asset($url, $kind)
    {
        if ($url === '' || stripos($url, 'data:') === 0) {
            return $url;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url) && !preg_match('#^https?:#i', $url)) {
            return ''; // file:, php:, ftp:, gopher:… never.
        }
        if (!preg_match('#^(https?:)?//#i', $url)) {
            if (strpos($url, '/') === 0) {
                $url = home_url($url); // Site-relative: resolve against this site, never the request's Host header.
            } elseif (is_readable($url) && self::inside_allowed_dir($url)) {
                return $url; // Already a local path we produced (header logo etc.).
            } else {
                return '';
            }
        }
        $url = strpos($url, '//') === 0 ? 'https:' . $url : $url;

        // Files on this site: read from disk.
        if (apply_filters('document_engine_pdf_localize_assets', true)) {
            foreach (array(untrailingslashit(content_url()) => untrailingslashit(WP_CONTENT_DIR), untrailingslashit(includes_url()) => untrailingslashit(ABSPATH . WPINC)) as $prefix => $dir) {
                foreach (array(set_url_scheme($prefix, 'http'), set_url_scheme($prefix, 'https')) as $candidate) {
                    if (strpos($url, $candidate . '/') === 0) {
                        $path = $dir . strtok(substr($url, strlen($candidate)), '?#');
                        $real = realpath($path);
                        if ($real && strpos($real, realpath($dir)) === 0 && is_file($real) && !preg_match('/\.(php\d?|phtml|phar)$/i', $real)) {
                            return $real;
                        }
                        return '';
                    }
                }
            }
        }
        return self::fetch_remote($url, $kind);
    }

    private static function inside_allowed_dir($path)
    {
        $real = realpath($path);
        if (!$real) {
            return false;
        }
        foreach (array(WP_CONTENT_DIR, ABSPATH . WPINC, get_temp_dir()) as $dir) {
            $d = realpath($dir);
            if ($d && strpos($real, $d) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Downloads a remote asset with WordPress' SSRF-safe HTTP API into a temporary file.
     */
    private static function fetch_remote($url, $kind)
    {
        if (isset(self::$fetched[$url])) {
            return self::$fetched[$url];
        }
        self::$fetched[$url] = '';
        if (!wp_http_validate_url($url) || !apply_filters('document_engine_pdf_allow_remote_asset', true, $url) || count(self::$fetched) > 50) {
            return '';
        }
        $response = wp_safe_remote_get($url, array('timeout' => 8, 'redirection' => 3, 'limit_response_size' => 5 * MB_IN_BYTES, 'reject_unsafe_urls' => true));
        if (is_wp_error($response) || (int)wp_remote_retrieve_response_code($response) !== 200) {
            return '';
        }
        $body = (string)wp_remote_retrieve_body($response);
        $type = strtolower((string)wp_remote_retrieve_header($response, 'content-type'));
        if ($body === '') {
            return '';
        }
        if ($kind === 'css') {
            if (strpos($type, 'css') === false && strpos($type, 'text/plain') === false) {
                return '';
            }
            $body = self::localize_css($body); // A remote stylesheet's own url()s go through the same rules.
            $ext = 'css';
        } else {
            $info = function_exists('getimagesizefromstring') ? @getimagesizefromstring($body) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            $svg = strpos($type, 'svg') !== false || preg_match('#^\s*(<\?xml[^>]*>\s*)?<svg\b#i', substr($body, 0, 400));
            if (!$info && !$svg) {
                return '';
            }
            $ext = $svg ? 'svg' : image_type_to_extension($info[2], false);
        }
        // The plugin's own (non-public) temp folder and an unguessable, per-request name: no shared predictable paths.
        $dir = trailingslashit(document_engine()->get_tmp_pdf_dir(true, false)) . 'assets';
        wp_mkdir_p($dir);
        $path = $dir . '/' . wp_generate_password(24, false) . '.' . preg_replace('/[^a-z0-9]/', '', (string)$ext);
        if (file_put_contents($path, $body) === false) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            return '';
        }
        self::$fetched[$url] = $path;
        return $path;
    }

    /**
     * Removes the temporary files of fetched remote assets.
     */
    public static function cleanup_assets()
    {
        foreach (self::$fetched as $path) {
            if ($path !== '' && is_file($path)) {
                @unlink($path); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
            }
        }
        self::$fetched = array();
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
