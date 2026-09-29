<?php

namespace MatrixAddons\DocumentEngine\Library;

defined('ABSPATH') || exit;

/**
 * A document search box that can sit anywhere (header, sidebar, home page) and shows its
 * results in a library on another page. Block `document-engine/search`, shortcode `[document_engine_search]`.
 */
class SearchBox
{
    public static function init()
    {
        add_action('init', array(__CLASS__, 'register'));
    }

    public static function register()
    {
        add_shortcode('document_engine_search', array(__CLASS__, 'shortcode'));
        register_block_type('document-engine/search', array(
            'api_version' => 3,
            'editor_script' => 'document-engine-pdf-block',
            'editor_style' => 'document-engine-blocks-editor',
            'attributes' => array(
                'pageId' => array('type' => 'number', 'default' => 0),
                'placeholder' => array('type' => 'string', 'default' => ''),
                'buttonText' => array('type' => 'string', 'default' => ''),
                'className' => array('type' => 'string', 'default' => ''),
            ),
            'supports' => array('html' => false),
            'render_callback' => array(__CLASS__, 'render'),
        ));
    }

    /**
     * [document_engine_search page="12" placeholder="Search policies…" button="Search"]
     */
    public static function shortcode($atts)
    {
        $atts = shortcode_atts(array('page' => 0, 'placeholder' => '', 'button' => ''), $atts, 'document_engine_search');
        return self::render(array('pageId' => absint($atts['page']), 'placeholder' => $atts['placeholder'], 'buttonText' => $atts['button']));
    }

    /**
     * Finds the library on the target page so the search lands in it (its URL prefix).
     */
    public static function library_id($page_id)
    {
        $content = (string)get_post_field('post_content', $page_id);
        if (preg_match('#<!-- wp:document-engine/library (\{.*?\}) /?-->#s', $content, $m)) {
            $attrs = json_decode($m[1], true);
            if (!empty($attrs['libraryId'])) {
                return preg_replace('/[^a-z0-9]/', '', strtolower($attrs['libraryId']));
            }
        }
        if (preg_match('#\[document_engine_library[^\]]*\bid=["\']?([a-z0-9]+)#i', $content, $m)) {
            return strtolower($m[1]);
        }
        return 'dl';
    }

    public static function render($attrs)
    {
        $attrs = wp_parse_args($attrs, array('pageId' => 0, 'placeholder' => '', 'buttonText' => '', 'className' => ''));
        $page_id = absint($attrs['pageId']);
        if (!$page_id || get_post_status($page_id) !== 'publish') {
            return current_user_can('edit_pages') ? '<p class="dengine-notice">' . esc_html__('Choose the page with your document library for this search box.', 'document-engine') . '</p>' : '';
        }
        document_engine_enqueue_frontend();
        $id = self::library_id($page_id);
        $uid = wp_unique_id('dengine-search-');
        $action = get_permalink($page_id);
        $current = isset($_GET[$id . '_s']) ? sanitize_text_field(wp_unslash($_GET[$id . '_s'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ob_start();
        ?>
        <form class="dengine-searchbox <?php echo esc_attr(implode(' ', array_map('sanitize_html_class', explode(' ', (string)$attrs['className'])))); ?>" role="search" method="get" action="<?php echo esc_url($action); ?>">
            <?php
            // Plain permalinks keep the page in the query string.
            parse_str((string)wp_parse_url($action, PHP_URL_QUERY), $query);
            foreach ($query as $key => $value) {
                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
            ?>
            <label class="screen-reader-text" for="<?php echo esc_attr($uid); ?>"><?php esc_html_e('Search documents', 'document-engine'); ?></label>
            <span class="dengine-searchbox__field">
                <?php echo document_engine_ui_icon('search'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <input type="search" id="<?php echo esc_attr($uid); ?>" name="<?php echo esc_attr($id); ?>_s" value="<?php echo esc_attr($current); ?>" placeholder="<?php echo esc_attr($attrs['placeholder'] !== '' ? $attrs['placeholder'] : __('Search documents…', 'document-engine')); ?>">
            </span>
            <button type="submit" class="dengine-button"><?php echo esc_html($attrs['buttonText'] !== '' ? $attrs['buttonText'] : __('Search', 'document-engine')); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }
}
