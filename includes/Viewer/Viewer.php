<?php

namespace MatrixAddons\DocumentEngine\Viewer;

use MatrixAddons\DocumentEngine\Documents\Document;
use MatrixAddons\DocumentEngine\Documents\FileServer;

defined('ABSPATH') || exit;

/**
 * Self-hosted PDF viewer (PDF.js). Block `document-engine/viewer`, shortcode `[document_engine_viewer]`.
 * No request leaves the site: the library, worker, fonts and character maps ship with the plugin.
 */
class Viewer
{
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register_shortcode'));
    }

    public static function register_shortcode()
    {
        add_shortcode('document_engine_viewer', array(__CLASS__, 'shortcode'));
    }

    /**
     * [document_engine_viewer id="document id" | file="attachment id" | url="https://…" height="800px" toolbar="yes" download="yes" print="yes"]
     */
    public static function shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0,
            'file' => 0,
            'url' => '',
            'height' => '',
            'width' => '',
            'toolbar' => '',
            'download' => '',
            'print' => '',
            'fullscreen' => '',
            'page' => 1,
            'zoom' => '',
        ), $atts);

        $to_bool = function ($value) {
            return $value === '' ? null : in_array(strtolower($value), array('1', 'yes', 'true', 'on'), true);
        };

        return self::render(array(
            'documentId' => absint($atts['id']),
            'fileId' => absint($atts['file']),
            'url' => $atts['url'],
            'height' => $atts['height'],
            'width' => $atts['width'],
            'toolbar' => $to_bool($atts['toolbar']),
            'download' => $to_bool($atts['download']),
            'print' => $to_bool($atts['print']),
            'fullscreen' => $to_bool($atts['fullscreen']),
            'page' => absint($atts['page']),
            'zoom' => $atts['zoom'],
        ));
    }

    /**
     * Viewer markup. Accepts documentId (document post), fileId (attachment) or url.
     *
     * @param array $attrs
     * @return string
     */
    public static function render($attrs)
    {
        $attrs = wp_parse_args($attrs, array(
            'documentId' => 0,
            'fileId' => 0,
            'url' => '',
            'height' => '',
            'width' => '',
            'toolbar' => null,
            'download' => null,
            'print' => null,
            'fullscreen' => null,
            'search' => null,
            'sidebar' => null,
            'page' => 1,
            'zoom' => '',
            'className' => '',
            'align' => '',
        ));

        $setting = function ($key, $value) {
            return $value === null ? get_option('document_engine_viewer_' . $key, 'yes') === 'yes' : (bool)$value;
        };

        $document = null;
        $src = '';
        $download_url = '';
        $title = '';

        if (absint($attrs['documentId']) > 0) {
            $document = Document::get(absint($attrs['documentId']));
            if (!$document || !$document->has_file()) {
                return self::notice(__('Choose a PDF document for this viewer.', 'document-engine'));
            }
            if ($document->get_post()->post_status !== 'publish' && !current_user_can('read_post', $document->get_id())) {
                return '';
            }
            if (!FileServer::can_access($document, null, 'view')) {
                return '<div class="dengine-notice dengine-notice--locked">' . document_engine_ui_icon('lock') . ' ' . esc_html__('You do not have access to this document.', 'document-engine') . '</div>';
            }
            $src = $document->get_download_url(array('view' => 1));
            $download_url = $document->get_download_url();
            $title = $document->get_title();
        } elseif (absint($attrs['fileId']) > 0) {
            if (!FileServer::can_embed_attachment($attrs['fileId'])) {
                return self::notice(__('Add a PDF to this viewer.', 'document-engine'));
            }
            $src = (string)wp_get_attachment_url(absint($attrs['fileId']));
            $download_url = $src;
            $title = get_the_title(absint($attrs['fileId']));
        } elseif ($attrs['url'] !== '') {
            $src = esc_url_raw($attrs['url'], array('http', 'https'));
            $download_url = $src;
            $title = wp_basename((string)wp_parse_url($src, PHP_URL_PATH));
        }

        if ($src === '') {
            return self::notice(__('Add a PDF to this viewer.', 'document-engine'));
        }

        // A file served through the file server (e.g. a protected attachment) is checked before rendering.
        if (!$document) {
            parse_str((string)wp_parse_url($src, PHP_URL_QUERY), $query);
            if (!empty($query[FileServer::QUERY_VAR])) {
                $linked = Document::get(absint($query[FileServer::QUERY_VAR]));
                if ($linked && !FileServer::can_access($linked, null, 'view')) {
                    return '<div class="dengine-notice dengine-notice--locked">' . document_engine_ui_icon('lock') . ' ' . esc_html__('You do not have access to this document.', 'document-engine') . '</div>';
                }
            }
        }

        $height = self::css_length($attrs['height'], get_option('document_engine_viewer_height', '800px'));
        $width = self::css_length($attrs['width'], '100%');

        $config = array(
            'src' => $src,
            'title' => $title,
            'download' => $setting('download', $attrs['download']) ? $download_url : '',
            'print' => $setting('print', $attrs['print']),
            'fullscreen' => $setting('fullscreen', $attrs['fullscreen']),
            'toolbar' => $setting('toolbar', $attrs['toolbar']),
            'search' => $setting('search', $attrs['search']),
            'sidebar' => $setting('sidebar', $attrs['sidebar']),
            'page' => max(1, absint($attrs['page'])),
            'zoom' => in_array($attrs['zoom'], array('auto', 'page-width', 'page-fit'), true) || is_numeric($attrs['zoom']) ? $attrs['zoom'] : get_option('document_engine_viewer_zoom', 'page-width'),
            'textLayer' => true,
            'secure' => false,
            'watermark' => '',
            'sameOrigin' => self::is_same_origin($src),
        );

        /**
         * Adjust a viewer before it renders (Pro: secure mode, watermarks, analytics).
         */
        $config = apply_filters('document_engine_viewer_config', $config, $document, $attrs);

        document_engine_enqueue_frontend(array('document-engine-viewer'));

        $classes = array('dengine-viewer');
        if (!$config['toolbar']) {
            $classes[] = 'dengine-viewer--no-toolbar';
        }
        if (!empty($config['secure'])) {
            $classes[] = 'dengine-viewer--secure';
        }
        if ($attrs['align']) {
            $classes[] = 'align' . sanitize_html_class($attrs['align']);
        }
        if ($attrs['className']) {
            $classes[] = implode(' ', array_map('sanitize_html_class', explode(' ', $attrs['className'])));
        }

        $open_url = $config['download'] ?: (empty($config['secure']) ? $src : '');

        ob_start();
        document_engine_get_template('viewer/viewer.php', array(
            'config' => $config,
            'classes' => $classes,
            'height' => $height,
            'width' => $width,
            'open_url' => $open_url,
        ));
        return ob_get_clean();
    }

    private static function notice($message)
    {
        return current_user_can('edit_dengine_documents') ? '<p class="dengine-notice">' . esc_html($message) . '</p>' : '';
    }

    public static function is_same_origin($url)
    {
        $host = wp_parse_url($url, PHP_URL_HOST);
        return !$host || strtolower($host) === strtolower((string)wp_parse_url(home_url(), PHP_URL_HOST));
    }

    /**
     * Accepts "800", "800px", "80vh", "100%" and returns a safe CSS length.
     */
    public static function css_length($value, $default)
    {
        $value = trim((string)$value);
        if ($value === '') {
            $value = (string)$default;
        }
        if (is_numeric($value)) {
            return absint($value) . 'px';
        }
        if (preg_match('/^(\d{1,5}(?:\.\d+)?)(px|%|vh|em|rem)$/', $value, $m)) {
            return $m[1] . $m[2];
        }
        return is_numeric($default) ? absint($default) . 'px' : (string)$default;
    }
}
