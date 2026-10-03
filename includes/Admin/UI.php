<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Shared admin shell: branded header, page header, cards, icons.
 * Used by the settings screen and every add-on page so the product looks like one app.
 */
class UI
{
    /**
     * Inline icons (24px grid, stroke).
     */
    public static function icon($name, $size = 18)
    {
        $paths = array(
            'documents' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
            'library' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'viewer' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
            'pdf' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 15h1.5a1.5 1.5 0 0 0 0-3H9v5"/>',
            'advanced' => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
            'shield' => '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
            'key' => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M17 6l3 3M15 8l2 2"/>',
            'activity' => '<path d="M3 12h4l3 8 4-16 3 8h4"/>',
            'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M21.5 20a6.5 6.5 0 0 0-4-6"/>',
            'upload' => '<path d="M12 16V4M7 9l5-5 5 5M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
            'book' => '<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19V5M9 7h6"/>',
            'download' => '<path d="M12 4v12M7 11l5 5 5-5M4 20h16"/>',
            'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v6H4V6h6"/>',
            'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17h0"/>',
            'star' => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
            'check' => '<path d="M5 12l5 5 9-10"/>',
            'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
            'home' => '<path d="M4 11l8-7 8 7v8a2 2 0 0 1-2 2h-3v-6H9v6H6a2 2 0 0 1-2-2z"/>',
            'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
            'tag' => '<path d="M3 12V4h8l9 9-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
            'expand' => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
            'shrink' => '<path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/>',
            'layout' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/>',
            'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l2 2H4zM10 20a2 2 0 0 0 4 0"/>',
            'migrate' => '<path d="M4 8h13M13 4l4 4-4 4M20 16H7M11 12l-4 4 4 4"/>',
            'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
            'sparkles' => '<path d="M12 3l1.8 4.7L18.5 9.5 13.8 11.3 12 16l-1.8-4.7L5.5 9.5l4.7-1.8zM19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
        );
        if (!isset($paths[$name])) {
            return '';
        }
        return '<svg class="dengine-a-icon" width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
    }

    public static function logo()
    {
        // Same mark as the WordPress.org icon (.wordpress-org/icon.svg).
        return '<svg class="dengine-a-logo" width="32" height="32" viewBox="0 0 256 256" aria-hidden="true" focusable="false"><defs><linearGradient id="dengine-logo-bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3B82F6"/><stop offset=".55" stop-color="#2563EB"/><stop offset="1" stop-color="#1E40AF"/></linearGradient></defs><rect width="256" height="256" rx="58" fill="url(#dengine-logo-bg)"/><rect x="58" y="58" width="112" height="146" rx="14" fill="#fff" opacity=".38" transform="rotate(-9 110 128)"/><path d="M92 50h66l40 40v106a14 14 0 0 1-14 14H92a14 14 0 0 1-14-14V64a14 14 0 0 1 14-14z" fill="#fff"/><path d="M158 50l40 40h-28a12 12 0 0 1-12-12z" fill="#BFDBFE"/><rect x="100" y="112" width="76" height="12" rx="6" fill="#2563EB"/><rect x="100" y="136" width="76" height="12" rx="6" fill="#93C5FD"/><rect x="100" y="160" width="48" height="12" rx="6" fill="#93C5FD"/></svg>';
    }

    public static function is_pro()
    {
        return defined('DOCUMENT_ENGINE_PRO_FILE');
    }

    /**
     * Top bar shared by every screen of the product.
     */
    public static function header()
    {
        // "Help" opens the in-plugin docs at the article for this screen.
        $article = Docs::context_article();
        $links = apply_filters('document_engine_admin_header_links', array(
            'docs' => array('label' => $article !== '' ? __('Help for this screen', 'document-engine') : __('Docs', 'document-engine'), 'url' => Docs::url($article), 'icon' => 'help'),
        ));
        ?>
        <div class="dengine-a-top">
            <div class="dengine-a-top__brand">
                <?php echo self::logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <span class="dengine-a-top__name"><?php echo esc_html(DOCUMENT_ENGINE_BRAND); ?></span>
                <?php if (self::is_pro()) : ?><span class="dengine-a-pill dengine-a-pill--pro"><?php esc_html_e('Pro', 'document-engine'); ?></span><?php endif; ?>
            </div>
            <nav class="dengine-a-top__links" aria-label="<?php esc_attr_e('Product links', 'document-engine'); ?>">
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=' . PostType::POST_TYPE)); ?>"><?php echo self::icon('documents', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e('Documents', 'document-engine'); ?></a>
                <?php if (!self::is_pro()) : ?>
                    <a href="<?php echo esc_url(ProPage::url()); ?>"><?php echo self::icon('layout', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e('Free vs Pro', 'document-engine'); ?></a>
                <?php endif; ?>
                <?php foreach ($links as $link) : ?>
                    <a href="<?php echo esc_url($link['url']); ?>" <?php echo strpos($link['url'], admin_url()) === 0 ? '' : 'target="_blank" rel="noopener"'; ?>><?php echo self::icon(isset($link['icon']) ? $link['icon'] : 'external', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html($link['label']); ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
    }

    /**
     * Opens a full-width product page (header + title row). Close with page_end().
     *
     * @param string $title
     * @param string $description
     * @param array $actions list of array(label, url, primary?, icon?)
     */
    public static function page_start($title, $description = '', $actions = array(), $class = '')
    {
        echo '<div class="wrap dengine-a ' . esc_attr($class) . '">';
        // WordPress injects notices after the first heading; keep an empty one here so they stay above our layout.
        echo '<h1 class="screen-reader-text">' . esc_html($title) . '</h1>';
        self::header();
        echo '<div class="dengine-a-page">';
        AppShell::group_tabs();
        echo '<div class="dengine-a-page__head"><div><h2 class="dengine-a-page__title">' . esc_html($title) . '</h2>';
        if ($description !== '') {
            echo '<p class="dengine-a-page__desc">' . wp_kses_post($description) . '</p>';
        }
        echo '</div>';
        if ($actions) {
            echo '<div class="dengine-a-page__actions">';
            foreach ($actions as $action) {
                echo self::button($action[0], $action[1], !empty($action[2]) ? 'primary' : 'secondary', isset($action[3]) ? $action[3] : ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            echo '</div>';
        }
        echo '</div>';
    }

    public static function page_end()
    {
        echo '</div></div>';
    }

    public static function button($label, $url, $variant = 'secondary', $icon = '')
    {
        return '<a class="dengine-a-btn dengine-a-btn--' . esc_attr($variant) . '" href="' . esc_url($url) . '">' . ($icon ? self::icon($icon, 16) : '') . '<span>' . esc_html($label) . '</span></a>';
    }

    public static function card_start($title = '', $description = '', $aside = '')
    {
        echo '<section class="dengine-a-card">';
        if ($title !== '' || $aside !== '') {
            echo '<header class="dengine-a-card__head"><div>';
            if ($title !== '') {
                echo '<h3 class="dengine-a-card__title">' . esc_html($title) . '</h3>';
            }
            if ($description !== '') {
                echo '<p class="dengine-a-card__desc">' . wp_kses_post($description) . '</p>';
            }
            echo '</div>' . $aside . '</header>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '<div class="dengine-a-card__body">';
    }

    public static function card_end()
    {
        echo '</div></section>';
    }

    public static function empty_state($icon, $title, $text, $action = null)
    {
        echo '<div class="dengine-a-empty">' . self::icon($icon, 28) . '<h4>' . esc_html($title) . '</h4><p>' . esc_html($text) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        if ($action) {
            echo self::button($action[0], $action[1], 'primary'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</div>';
    }

    public static function enqueue()
    {
        wp_enqueue_style('document-engine-admin', DOCUMENT_ENGINE_ASSETS_URI . 'admin/css/admin.css', array(), DOCUMENT_ENGINE_VERSION);
        // Right-to-left sites: admin-rtl.css is generated from admin.css by "npm run rtl".
        wp_style_add_data('document-engine-admin', 'rtl', 'replace');
    }
}
