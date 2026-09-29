<?php

namespace MatrixAddons\DocumentEngine\Documents;

defined('ABSPATH') || exit;

/**
 * A document: a post of type dengine_document pointing at one file (Media Library or external URL).
 */
class Document
{
    const META_FILE_ID = '_dengine_file_id';
    const META_FILE_URL = '_dengine_file_url';
    const META_BEHAVIOR = '_dengine_link_behavior';
    const META_FILE_TYPE = '_dengine_file_type';
    const META_FILE_SIZE = '_dengine_file_size';
    const META_DOWNLOADS = '_dengine_downloads';

    /** @var \WP_Post */
    private $post;

    private function __construct(\WP_Post $post)
    {
        $this->post = $post;
    }

    /**
     * @param int|\WP_Post $post
     * @return Document|null
     */
    public static function get($post)
    {
        $post = get_post($post);
        if (!$post || $post->post_type !== PostType::POST_TYPE) {
            return null;
        }
        return new self($post);
    }

    public function get_id()
    {
        return (int)$this->post->ID;
    }

    public function get_post()
    {
        return $this->post;
    }

    /**
     * Title without WordPress' "Protected:"/"Private:" prefixes (access is shown separately).
     */
    public function get_title()
    {
        return (string)apply_filters('the_title', $this->post->post_title, $this->post->ID);
    }

    public function get_file_id()
    {
        return absint(get_post_meta($this->post->ID, self::META_FILE_ID, true));
    }

    public function get_external_url()
    {
        return (string)get_post_meta($this->post->ID, self::META_FILE_URL, true);
    }

    public function is_external()
    {
        return $this->get_file_id() < 1 && $this->get_external_url() !== '';
    }

    public function has_file()
    {
        return $this->get_file_id() > 0 || $this->get_external_url() !== '';
    }

    /**
     * Public URL of the underlying file (attachment URL or external URL). Protected files return ''.
     */
    public function get_file_url()
    {
        $url = '';
        if ($this->get_file_id() > 0) {
            $url = (string)wp_get_attachment_url($this->get_file_id());
        } elseif ($this->get_external_url() !== '') {
            $url = $this->get_external_url();
        }
        return (string)apply_filters('document_engine_document_file_url', $url, $this);
    }

    /**
     * Absolute path of a local file, or '' for external documents.
     */
    public function get_file_path()
    {
        $path = $this->get_file_id() > 0 ? (string)get_attached_file($this->get_file_id()) : '';
        return (string)apply_filters('document_engine_document_file_path', $path, $this);
    }

    public function get_extension()
    {
        $ext = (string)get_post_meta($this->post->ID, self::META_FILE_TYPE, true);
        if ($ext === '') {
            $source = $this->get_file_id() > 0 ? (string)get_attached_file($this->get_file_id()) : $this->get_external_url();
            $ext = self::extension_from($source);
        }
        return $ext;
    }

    public static function extension_from($path_or_url)
    {
        $path = (string)wp_parse_url($path_or_url, PHP_URL_PATH);
        $ext = strtolower(pathinfo($path !== '' ? $path : $path_or_url, PATHINFO_EXTENSION));
        return preg_replace('/[^a-z0-9]/', '', $ext);
    }

    public function is_pdf()
    {
        return $this->get_extension() === 'pdf';
    }

    public function get_type_label()
    {
        $ext = $this->get_extension();
        return $ext !== '' ? strtoupper($ext) : ($this->is_external() ? __('Link', 'document-engine') : '');
    }

    public function get_size()
    {
        return absint(get_post_meta($this->post->ID, self::META_FILE_SIZE, true));
    }

    public function get_size_label()
    {
        $size = $this->get_size();
        return $size > 0 ? size_format($size, $size >= MB_IN_BYTES ? 1 : 0) : '';
    }

    public function get_download_count()
    {
        return absint(get_post_meta($this->post->ID, self::META_DOWNLOADS, true));
    }

    /**
     * 'download' forces a download, 'inline' opens in the browser.
     */
    public function get_behavior()
    {
        $behavior = (string)get_post_meta($this->post->ID, self::META_BEHAVIOR, true);
        if (!in_array($behavior, array('download', 'inline'), true)) {
            $behavior = get_option('document_engine_link_behavior', 'download') === 'inline' ? 'inline' : 'download';
        }
        return $behavior;
    }

    /**
     * Link that goes through the file server (counts, checks access, sets headers).
     *
     * @param array $args inline => bool.
     */
    public function get_download_url($args = array())
    {
        $query = array(FileServer::QUERY_VAR => $this->get_id());
        if (!empty($args['inline'])) {
            $query['inline'] = 1;
        }
        if (!empty($args['view'])) {
            $query['view'] = 1;
        }
        return apply_filters('document_engine_download_url', add_query_arg($query, home_url('/')), $this, $args);
    }

    public function get_thumbnail_html($size = 'medium')
    {
        if (has_post_thumbnail($this->post)) {
            return get_the_post_thumbnail($this->post, $size, array('loading' => 'lazy', 'alt' => ''));
        }
        return document_engine_file_icon($this->get_extension());
    }

    /**
     * Refreshes cached file facts after the file changes.
     */
    public function sync_file_meta()
    {
        $file_id = $this->get_file_id();
        $size = 0;
        $ext = '';

        if ($file_id > 0) {
            $path = get_attached_file($file_id);
            $ext = self::extension_from((string)$path);
            $meta = wp_get_attachment_metadata($file_id);
            if (is_array($meta) && !empty($meta['filesize'])) {
                $size = (int)$meta['filesize'];
            } elseif ($path && file_exists($path)) {
                $size = (int)filesize($path);
            }
        } elseif ($this->get_external_url() !== '') {
            $ext = self::extension_from($this->get_external_url());
        }

        update_post_meta($this->post->ID, self::META_FILE_TYPE, $ext);
        update_post_meta($this->post->ID, self::META_FILE_SIZE, $size);
    }

    public function increment_downloads()
    {
        global $wpdb;

        // Atomic increment so busy libraries don't lose counts.
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + 1 WHERE post_id = %d AND meta_key = %s",
            $this->get_id(),
            self::META_DOWNLOADS
        ));
        if (!$updated) {
            add_post_meta($this->get_id(), self::META_DOWNLOADS, 1, true) || update_post_meta($this->get_id(), self::META_DOWNLOADS, $this->get_download_count() + 1);
        }
        wp_cache_delete($this->get_id(), 'post_meta');
    }
}
