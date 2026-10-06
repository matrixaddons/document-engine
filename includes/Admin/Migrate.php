<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;
use MatrixAddons\DocumentEngine\Migrate\Migrator;
use MatrixAddons\DocumentEngine\Migrate\Sources;

defined('ABSPATH') || exit;

/**
 * Tools → Migrate: move documents over from Barn2 Document Library, Download Monitor and WPDM.
 */
class Migrate
{
    const SLUG = 'dengine-migrate';

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'menu'), 30);
        add_action('wp_ajax_dengine_migrate', array(__CLASS__, 'ajax'));
        add_action('document_engine_dashboard_sidebar', array(__CLASS__, 'dashboard_card'), 5);
    }

    public static function url($source = '')
    {
        return add_query_arg(array_filter(array('post_type' => PostType::POST_TYPE, 'page' => self::SLUG, 'source' => $source)), admin_url('edit.php'));
    }

    public static function menu()
    {
        $parent = 'edit.php?post_type=' . PostType::POST_TYPE;
        add_submenu_page($parent, __('Migrate from another plugin', 'document-engine'), __('Migrate', 'document-engine'), 'manage_options', self::SLUG, array(__CLASS__, 'render'));
    }

    /**
     * Sources that hold documents on this site.
     */
    public static function detected()
    {
        $found = array();
        foreach (Sources::all() as $key => $source) {
            $count = Sources::count($key);
            if ($count > 0) {
                $found[$key] = $count;
            }
        }
        return $found;
    }

    /**
     * "Moving from X?" card on the dashboard, until that source has been migrated.
     */
    public static function dashboard_card()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $done = (array)get_option(Migrator::OPTION_DONE, array());
        $pending = array_diff_key(self::detected(), $done);
        if (!$pending) {
            return;
        }
        $key = key($pending);
        $source = Sources::get($key);
        // What is still to move (a migration may have been run part way).
        $preview = Migrator::preview($key);
        $pending[$key] = isset($preview['remaining']) ? (int)$preview['remaining'] : $pending[$key];
        if ($pending[$key] < 1) {
            return;
        }
        UI::card_start(
            /* translators: %s: plugin name */
            sprintf(__('Moving from %s?', 'document-engine'), $source['label']),
            sprintf(
                /* translators: %s: number of documents */
                _n('We found %s document you can bring over, with its file, categories and download count.', 'We found %s documents you can bring over, with their files, categories and download counts.', $pending[$key], 'document-engine'),
                number_format_i18n($pending[$key])
            )
        );
        echo UI::button(__('Review and migrate', 'document-engine'), self::url($key), 'primary', 'migrate'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        UI::card_end();
    }

    public static function render()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to do that.', 'document-engine'));
        }
        $detected = self::detected();
        $current = isset($_GET['source']) ? sanitize_key(wp_unslash($_GET['source'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($detected[$current])) {
            // Open the first source that still has work to do.
            $pending = array_diff_key($detected, (array)get_option(Migrator::OPTION_DONE, array()));
            $current = $pending ? key($pending) : ($detected ? key($detected) : '');
        }

        UI::page_start(
            __('Migrate from another plugin', 'document-engine'),
            esc_html__('Bring documents over with their files, categories, tags, dates and download counts. The old plugin\'s data is only read, never changed, and running this again skips anything already moved.', 'document-engine')
        );

        echo '<div class="dengine-a-layout dengine-a-migrate">';
        echo '<nav class="dengine-a-nav" aria-label="' . esc_attr__('Sources', 'document-engine') . '"><ul>';
        foreach (Sources::all() as $key => $source) {
            $count = isset($detected[$key]) ? $detected[$key] : 0;
            $active = $key === $current;
            echo '<li' . ($active ? ' class="is-active"' : '') . '><a href="' . esc_url(self::url($key)) . '"' . ($active ? ' aria-current="page"' : '') . '>' . UI::icon('migrate', 16) . '<span>' . esc_html($source['label']) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                . '<span class="dengine-a-nav__count">' . esc_html(number_format_i18n($count)) . '</span></a></li>';
        }
        echo '</ul></nav><div class="dengine-a-main">';

        if ($current === '') {
            UI::card_start();
            UI::empty_state(
                'migrate',
                __('Nothing to migrate', 'document-engine'),
                __('No documents from Document Library (Barn2), Download Monitor or WordPress Download Manager were found on this site.', 'document-engine')
            );
            UI::card_end();
        } else {
            self::render_source($current);
        }

        echo '</div></div>';
        UI::page_end();
    }

    private static function render_source($key)
    {
        $source = Sources::get($key);
        $p = Migrator::preview($key);
        $done = $p['remaining'] === 0;

        UI::card_start($source['label'], $source['description']);

        echo '<div class="dengine-a-kpis dengine-a-kpis--compact">';
        echo self::kpi(__('Found', 'document-engine'), $p['total']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::kpi(__('Already moved', 'document-engine'), $p['done']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::kpi(__('To move', 'document-engine'), $p['remaining']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';

        $notes = array();
        if ($p['restricted']) {
            $notes[] = defined('DOCUMENT_ENGINE_PRO_FILE')
                /* translators: %s: number */
                ? sprintf(_n('%s item is for members only. It keeps that restriction and its file is moved to protected storage.', '%s items are for members only. They keep that restriction and their files are moved to protected storage.', $p['restricted'], 'document-engine'), number_format_i18n($p['restricted']))
                /* translators: %s: number */
                : sprintf(_n('%s item is for members only. It is imported as a draft so it is not made public. Document Engine Pro can keep it restricted.', '%s items are for members only. They are imported as drafts so they are not made public. Document Engine Pro can keep them restricted.', $p['restricted'], 'document-engine'), number_format_i18n($p['restricted']));
        }
        if ($p['locked']) {
            /* translators: %s: number */
            $notes[] = sprintf(_n('%s item had a lock this plugin does not copy (paid, email or captcha). It is imported as a draft without its file, for you to review.', '%s items had a lock this plugin does not copy (paid, email or captcha). They are imported as drafts without their files, for you to review.', $p['locked'], 'document-engine'), number_format_i18n($p['locked']));
        }
        if ($p['passwords']) {
            /* translators: %s: number */
            $notes[] = sprintf(_n('%s item has a password. It keeps it as a WordPress page password.', '%s items have a password. They keep it as a WordPress page password.', $p['passwords'], 'document-engine'), number_format_i18n($p['passwords']));
        }
        if ($p['multi']) {
            /* translators: %s: number */
            $notes[] = sprintf(_n('%s item holds more than one file. Only its main file is moved; the list after the move shows which.', '%s items hold more than one file. Only their main file is moved; the list after the move shows which.', $p['multi'], 'document-engine'), number_format_i18n($p['multi']));
        }
        $notes[] = __('Files already in the Media Library are reused. Files kept elsewhere on this server are copied into the Media Library, and links to other websites stay links.', 'document-engine');
        $notes[] = __('Pages that use the old plugin\'s shortcodes keep working after you deactivate it; they show the moved documents.', 'document-engine');
        if ($p['active']) {
            $notes[] = __('The old plugin is still active. You can leave it on while you check the result.', 'document-engine');
        }

        echo '<ul class="dengine-a-checklist dengine-a-checklist--plain">';
        foreach ($notes as $note) {
            echo '<li>' . UI::icon('check', 16) . '<span>' . esc_html($note) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</ul>';

        ?>
        <form class="dengine-a-migrate__form" data-dengine-migrate="<?php echo esc_attr(wp_json_encode(array(
            'source' => $key,
            'nonce' => wp_create_nonce('dengine_migrate'),
            'ajax' => admin_url('admin-ajax.php'),
            'total' => $p['total'],
            'documents' => admin_url('edit.php?post_type=' . PostType::POST_TYPE),
            'i18n' => array(
                /* translators: 1: done, 2: total */
                'progress' => __('Checked %1$s of %2$s', 'document-engine'),
                /* translators: 1: moved, 2: skipped, 3: failed */
                'done' => __('Finished: %1$s moved, %2$s skipped (already moved or members only), %3$s failed.', 'document-engine'),
                'failed' => __('The migration stopped because of an error. Run it again to continue where it stopped.', 'document-engine'),
                'view' => __('View documents', 'document-engine'),
                'edit' => __('Edit', 'document-engine'),
                'leave' => __('The migration is still running. Leave anyway?', 'document-engine'),
            ),
        ))); ?>">
            <?php if ($p['restricted'] && !$done) : ?>
                <label class="dengine-a-switch">
                    <input type="checkbox" name="skip_restricted" value="1">
                    <span class="dengine-a-switch__track" aria-hidden="true"></span>
                    <span class="dengine-a-switch__text"><?php esc_html_e('Skip members-only items', 'document-engine'); ?></span>
                </label>
            <?php endif; ?>
            <div class="dengine-a-migrate__actions">
                <?php if ($done) : ?>
                    <span class="dengine-a-pill dengine-a-pill--success"><?php echo UI::icon('check', 14); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e('Everything has been moved', 'document-engine'); ?></span>
                <?php else : ?>
                    <button type="submit" class="dengine-a-btn dengine-a-btn--primary">
                        <?php echo UI::icon('migrate', 16); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <span><?php
                            /* translators: %s: number */
                            echo esc_html(sprintf(_n('Move %s document', 'Move %s documents', $p['remaining'], 'document-engine'), number_format_i18n($p['remaining'])));
                        ?></span>
                    </button>
                <?php endif; ?>
                <?php if ($p['done']) : ?>
                    <?php echo UI::button(__('View moved documents', 'document-engine'), admin_url('edit.php?post_type=' . PostType::POST_TYPE), 'secondary'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
            </div>
            <div class="dengine-a-progress" hidden>
                <div class="dengine-a-progress__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span></span></div>
                <p class="dengine-a-progress__text" aria-live="polite"></p>
            </div>
            <ul class="dengine-a-migrate__log" hidden></ul>
        </form>
        <?php
        // T5: after a finished migration (never before or during it), what Pro would add for these documents.
        if ($done && $p['done']) {
            $text = $p['restricted']
                /* translators: %s: number of members-only items */
                ? sprintf(_n('%s item was members-only in the old plugin and is now a draft. Document Engine Pro keeps documents restricted by role, person or category, with their files in private storage.', '%s items were members-only in the old plugin and are now drafts. Document Engine Pro keeps documents restricted by role, person or category, with their files in private storage.', $p['restricted'], 'document-engine'), number_format_i18n($p['restricted']))
                : __('Your documents are in. Document Engine Pro adds versions, review-by dates and an activity log of who opened what.', 'document-engine');
            echo Nudges::card('t5-migrate', $text, Upsell::url($p['restricted'] ? 'access' : 'activity')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
        <?php
        UI::card_end();
        self::script();
    }

    private static function kpi($label, $value)
    {
        return '<div class="dengine-a-kpi"><span class="dengine-a-kpi__label">' . esc_html($label) . '</span><strong class="dengine-a-kpi__value">' . esc_html(number_format_i18n($value)) . '</strong></div>';
    }

    public static function ajax()
    {
        check_ajax_referer('dengine_migrate', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Sorry, you are not allowed to do that.', 'document-engine')), 403);
        }
        $key = isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : '';
        if (!Sources::get($key)) {
            wp_send_json_error(array('message' => __('Unknown source.', 'document-engine')), 400);
        }
        $after = isset($_POST['after']) ? absint($_POST['after']) : 0;
        $options = array('skip_restricted' => !empty($_POST['skip_restricted']));
        wp_send_json_success(Migrator::run_batch($key, $after, $options));
    }

    private static function script()
    {
        ?>
        <script>
        (function () {
            var form = document.querySelector('[data-dengine-migrate]');
            if (!form) { return; }
            var cfg = JSON.parse(form.getAttribute('data-dengine-migrate'));
            var btn = form.querySelector('button[type="submit"]');
            var box = form.querySelector('.dengine-a-progress');
            var bar = box.querySelector('[role="progressbar"]');
            var text = box.querySelector('.dengine-a-progress__text');
            var log = form.querySelector('.dengine-a-migrate__log');
            var running = false, moved = 0, skipped = 0, failed = 0;

            function num(n) { try { return Number(n).toLocaleString(document.documentElement.lang || undefined); } catch (e) { return String(n); } }
            function fmt(s, a, b) { return s.replace('%1$s', num(a)).replace('%2$s', num(b)); }
            function update() {
                var handled = moved + skipped + failed;
                var pct = cfg.total ? Math.min(100, Math.round(handled / cfg.total * 100)) : 100;
                bar.setAttribute('aria-valuenow', pct);
                bar.firstElementChild.style.width = pct + '%';
                text.textContent = fmt(cfg.i18n.progress, handled, cfg.total);
            }
            function addLog(entry) {
                var li = document.createElement('li');
                li.className = 'is-' + entry.status;
                var strong = document.createElement('strong');
                strong.textContent = entry.title;
                li.appendChild(strong);
                li.appendChild(document.createTextNode(' ' + entry.message + ' '));
                if (entry.edit) {
                    var a = document.createElement('a');
                    a.href = entry.edit;
                    a.textContent = cfg.i18n.edit;
                    li.appendChild(a);
                }
                log.appendChild(li);
                log.hidden = false;
            }
            function finish(message, ok) {
                running = false;
                text.textContent = message;
                var toggle = form.querySelector('.dengine-a-switch');
                if (toggle) { toggle.hidden = true; }
                box.classList.toggle('is-error', !ok);
                var a = document.createElement('a');
                a.className = 'dengine-a-btn dengine-a-btn--secondary';
                a.href = cfg.documents;
                a.textContent = cfg.i18n.view;
                btn.replaceWith(a);
            }
            function step(after) {
                var body = new FormData();
                body.append('action', 'dengine_migrate');
                body.append('nonce', cfg.nonce);
                body.append('source', cfg.source);
                body.append('after', after);
                var skip = form.querySelector('[name="skip_restricted"]');
                if (skip && skip.checked) { body.append('skip_restricted', '1'); }
                fetch(cfg.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res || !res.success) { throw new Error(res && res.data && res.data.message || 'error'); }
                        var d = res.data;
                        if (d.busy) { setTimeout(function () { step(after); }, 3000); return; }
                        moved += d.imported; skipped += d.skipped; failed += d.failed;
                        (d.log || []).forEach(addLog);
                        update();
                        if (d.done) { bar.firstElementChild.style.width = '100%'; finish(cfg.i18n.done.replace('%1$s', num(moved)).replace('%2$s', num(skipped)).replace('%3$s', num(failed)), true); }
                        else { step(d.next); }
                    })
                    .catch(function () { finish(cfg.i18n.failed, false); });
            }
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (running) { return; }
                running = true;
                btn.disabled = true;
                box.hidden = false;
                update();
                step(0);
            });
            window.addEventListener('beforeunload', function (e) {
                if (running) { e.preventDefault(); e.returnValue = cfg.i18n.leave; }
            });
        })();
        </script>
        <?php
    }
}
