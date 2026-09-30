<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Documents → Docs: the product documentation inside the admin, searchable, with links from each screen.
 */
class Docs
{
    const SLUG = 'dengine-docs';

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'menu'), 25);
        add_filter('document_engine_menu_ranks', function ($ranks) {
            $ranks[self::SLUG] = 900; // Always the last item, after Settings and Upgrade to Pro.
            return $ranks;
        });
    }

    public static function menu()
    {
        add_submenu_page('edit.php?post_type=' . PostType::POST_TYPE, __('Documentation', 'document-engine'), __('Docs', 'document-engine'), 'edit_dengine_documents', self::SLUG, array(__CLASS__, 'render'));
    }

    /**
     * Link to a section, or to an article (its section is looked up).
     */
    public static function url($article = '', $section = '')
    {
        if ($article !== '' && $section === '') {
            foreach (DocsContent::sections() as $id => $data) {
                if (isset($data['articles'][$article])) {
                    $section = $id;
                    break;
                }
            }
        }
        $url = admin_url('edit.php?post_type=' . PostType::POST_TYPE . '&page=' . self::SLUG . ($section !== '' ? '&section=' . $section : ''));
        return $article !== '' ? $url . '#doc-' . $article : $url;
    }

    /**
     * The article that explains the current screen (used by the "Help" link in the header).
     */
    public static function context_article()
    {
        global $plugin_page;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $map = array(
            'dengine-dashboard' => 'setup',
            'dengine-activity' => 'analytics',
            'dengine-searches' => 'analytics',
            'dengine-leads' => 'gate',
            'dengine-acks' => 'acks',
            'dengine-import' => 'import',
            'dengine-migrate' => 'migrate',
            'dengine-handbook' => 'portal',
            'dengine-pro-feature' => 'install-pro',
            'document-engine-pro' => 'install-pro',
        );
        $settings = array(
            'general' => 'settings-documents',
            'viewer' => 'settings-viewer',
            'pdf_downloads' => 'settings-pdf',
            'advanced' => 'settings-advanced',
            'pro' => 'settings-pro',
            'fields' => 'fields',
            'license' => 'install-pro',
            'pro_preview' => 'access',
        );
        $article = '';
        if ((string)$plugin_page === 'document-engine-settings') {
            $article = isset($settings[$tab]) ? $settings[$tab] : 'settings-documents';
        } elseif (isset($map[(string)$plugin_page])) {
            $article = $map[(string)$plugin_page];
        } elseif ($screen && $screen->post_type === 'dengine_request') {
            $article = 'format-requests';
        } elseif ($screen && $screen->base === 'edit' && $screen->post_type === PostType::POST_TYPE) {
            $article = 'documents';
        } elseif ($screen && in_array($screen->base, array('edit-tags', 'term'), true)) {
            $article = defined('DOCUMENT_ENGINE_PRO_FILE') ? 'access' : 'setup';
        }
        return (string)apply_filters('document_engine_docs_context_article', $article, $plugin_page, $screen);
    }

    /**
     * Allowed inline markup in article text.
     */
    private static function kses($text)
    {
        return wp_kses($text, array('code' => array(), 'strong' => array(), 'em' => array(), 'a' => array('href' => array())));
    }

    private static function block($block)
    {
        switch ($block[0]) {
            case 'p':
                return '<p>' . self::kses($block[1]) . '</p>';
            case 'note':
                return '<p class="dengine-docs__note">' . self::kses($block[1]) . '</p>';
            case 'code':
                return '<pre class="dengine-docs__code"><code>' . esc_html($block[1]) . '</code></pre>';
            case 'ul':
            case 'ol':
                $items = '';
                foreach ((array)$block[1] as $item) {
                    $items .= '<li>' . self::kses($item) . '</li>';
                }
                return '<' . $block[0] . '>' . $items . '</' . $block[0] . '>';
            case 'table':
                $html = '<div class="dengine-a-table-wrap"><table class="dengine-a-table dengine-docs__table">';
                if (!empty($block[2])) {
                    $html .= '<thead><tr>';
                    foreach ((array)$block[2] as $head) {
                        $html .= '<th scope="col">' . esc_html($head) . '</th>';
                    }
                    $html .= '</tr></thead>';
                }
                $html .= '<tbody>';
                foreach ((array)$block[1] as $row) {
                    $html .= '<tr>';
                    foreach ((array)$row as $cell) {
                        $html .= '<td>' . self::kses($cell) . '</td>';
                    }
                    $html .= '</tr>';
                }
                return $html . '</tbody></table></div>';
            case 'links':
                $html = '<p class="dengine-docs__links">';
                foreach ((array)$block[1] as $label => $url) {
                    $html .= '<a class="dengine-a-btn dengine-a-btn--secondary" href="' . esc_url($url) . '">' . esc_html($label) . '</a> ';
                }
                return $html . '</p>';
        }
        return '';
    }

    private static function plain($blocks)
    {
        $text = array();
        foreach ($blocks as $block) {
            $value = $block[1];
            if (is_array($value)) {
                $flat = array();
                array_walk_recursive($value, function ($v) use (&$flat) {
                    $flat[] = (string)$v;
                });
                $value = implode(' ', $flat);
            }
            $text[] = wp_strip_all_tags((string)$value);
        }
        return trim(preg_replace('/\s+/', ' ', implode(' ', $text)));
    }

    public static function render()
    {
        $sections = DocsContent::sections();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : 'start';
        if (!isset($sections[$current])) {
            $current = 'start';
        }
        $is_pro = UI::is_pro();

        // Search index for every article (title, section, text) so search covers all sections.
        $index = array();
        foreach ($sections as $sid => $section) {
            foreach ($section['articles'] as $aid => $article) {
                $index[] = array(
                    't' => $article[0],
                    's' => $section['title'],
                    'u' => self::url($aid, $sid),
                    'x' => mb_substr(self::plain($article[2]), 0, 1200),
                    'p' => !empty($article[1]),
                );
            }
        }

        $online = '<a href="' . esc_url('https://matrixaddons.com/plugins/document-engine/docs/') . '" target="_blank" rel="noopener">' . esc_html__('Full documentation online', 'document-engine') . '<span class="screen-reader-text"> ' . esc_html__('(opens in a new tab)', 'document-engine') . '</span></a>';
        UI::page_start(__('Documentation', 'document-engine'), esc_html__('How to set up and use Document Engine.', 'document-engine') . ' ' . $online, array(), 'dengine-docs-page');
        ?>
        <div class="dengine-docs">
            <nav class="dengine-docs__nav" aria-label="<?php esc_attr_e('Documentation sections', 'document-engine'); ?>">
                <div class="dengine-docs__search" role="search">
                    <label class="screen-reader-text" for="dengine-docs-q"><?php esc_html_e('Search the documentation', 'document-engine'); ?></label>
                    <?php echo UI::icon('search', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <input type="search" id="dengine-docs-q" class="dengine-a-input" placeholder="<?php esc_attr_e('Search docs…', 'document-engine'); ?>" autocomplete="off" aria-controls="dengine-docs-results">
                </div>
                <ul>
                    <?php foreach ($sections as $sid => $section) : $active = $sid === $current; ?>
                        <li class="<?php echo $active ? 'is-active' : ''; ?>">
                            <a href="<?php echo esc_url(self::url('', $sid)); ?>" <?php echo $active ? 'aria-current="page"' : ''; ?>><?php echo UI::icon($section['icon'], 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html($section['title']); ?></a>
                            <?php if ($active) : ?>
                                <ul class="dengine-a-nav__sub">
                                    <?php foreach ($section['articles'] as $aid => $article) : ?>
                                        <li><a href="#doc-<?php echo esc_attr($aid); ?>"><?php echo esc_html($article[0]); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="dengine-docs__main">
                <div id="dengine-docs-results" class="dengine-docs__results" aria-live="polite" hidden></div>
                <div class="dengine-docs__section" id="dengine-docs-section">
                    <header class="dengine-docs__head">
                        <h2><?php echo esc_html($sections[$current]['title']); ?></h2>
                        <p class="dengine-a-page__desc"><?php echo esc_html($sections[$current]['intro']); ?></p>
                    </header>
                    <?php if ($current === 'pro' && !$is_pro) : // One upgrade box for the whole Pro section; articles only carry a "Pro" badge. ?>
                        <div class="dengine-docs__upgrade"><span><?php esc_html_e('These features come with Document Engine Pro, an add-on to this plugin. Your documents and settings stay as they are when you add it.', 'document-engine'); ?></span> <a class="dengine-a-btn dengine-a-btn--primary" href="<?php echo esc_url(ProPage::url()); ?>"><?php esc_html_e('Upgrade to Pro', 'document-engine'); ?></a></div>
                    <?php endif; ?>
                    <?php foreach ($sections[$current]['articles'] as $aid => $article) : ?>
                        <article class="dengine-a-card dengine-docs__article" id="doc-<?php echo esc_attr($aid); ?>" tabindex="-1">
                            <div class="dengine-a-card__body">
                                <h3 class="dengine-docs__title"><?php echo esc_html($article[0]); ?><?php if (!empty($article[1])) : ?> <span class="dengine-a-pill dengine-a-pill--pro"><?php esc_html_e('Pro', 'document-engine'); ?></span><?php endif; ?></h3>
                                <?php
                                foreach ($article[2] as $block) {
                                    echo self::block($block); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped per block.
                                }
                                ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <script>
            (function () {
                var index = <?php echo wp_json_encode($index); ?>;
                var input = document.getElementById('dengine-docs-q'), out = document.getElementById('dengine-docs-results'), section = document.getElementById('dengine-docs-section');
                var i18n = <?php echo wp_json_encode(array(
                    'none' => __('No articles match your search.', 'document-engine'),
                    /* translators: %d: number of matching articles */
                    'count' => __('%d articles', 'document-engine'),
                    'pro' => __('Pro', 'document-engine'),
                )); ?>;
                function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
                function snippet(text, words) {
                    var lower = text.toLowerCase(), at = -1;
                    words.forEach(function (w) { var i = lower.indexOf(w); if (i > -1 && (at < 0 || i < at)) { at = i; } });
                    var start = Math.max(0, at - 60), s = (start ? '…' : '') + text.substr(start, 180) + (text.length > start + 180 ? '…' : '');
                    var html = esc(s);
                    words.forEach(function (w) { if (w.length > 1) { html = html.replace(new RegExp('(' + w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig'), '<mark>$1</mark>'); } });
                    return html;
                }
                function run() {
                    var q = input.value.trim().toLowerCase();
                    if (q.length < 2) { out.hidden = true; out.innerHTML = ''; section.hidden = false; return; }
                    var words = q.split(/\s+/).filter(Boolean);
                    var hits = index.map(function (a) {
                        var title = a.t.toLowerCase(), text = a.x.toLowerCase(), score = 0;
                        for (var i = 0; i < words.length; i++) {
                            var inT = title.indexOf(words[i]) > -1, inX = text.indexOf(words[i]) > -1;
                            if (!inT && !inX) { return null; }
                            score += inT ? 3 : 1;
                        }
                        return { a: a, score: score };
                    }).filter(Boolean).sort(function (x, y) { return y.score - x.score; });
                    section.hidden = true;
                    out.hidden = false;
                    if (!hits.length) { out.innerHTML = '<p class="dengine-docs__empty">' + esc(i18n.none) + '</p>'; return; }
                    out.innerHTML = '<p class="dengine-a-sub">' + esc(i18n.count.replace('%d', hits.length)) + '</p><ul class="dengine-docs__hits">' + hits.slice(0, 30).map(function (h) {
                        return '<li><a href="' + esc(h.a.u) + '"><strong>' + esc(h.a.t) + '</strong>' + (h.a.p ? ' <span class="dengine-a-pill dengine-a-pill--pro">' + esc(i18n.pro) + '</span>' : '') + '<span class="dengine-a-sub"> · ' + esc(h.a.s) + '</span><span class="dengine-docs__snippet">' + snippet(h.a.x, words) + '</span></a></li>';
                    }).join('') + '</ul>';
                }
                var t;
                input.addEventListener('input', function () { clearTimeout(t); t = setTimeout(run, 120); });
                input.addEventListener('keydown', function (e) { if (e.key === 'Escape') { input.value = ''; run(); } });
                // Focus the article a link points to, so keyboard and screen-reader users land on it.
                function focusHash() { var el = location.hash && document.getElementById(location.hash.slice(1)); if (el) { el.focus({ preventScroll: false }); el.classList.add('is-target'); } }
                window.addEventListener('hashchange', focusHash);
                focusHash();
            })();
        </script>
        <?php
        UI::page_end();
    }
}
