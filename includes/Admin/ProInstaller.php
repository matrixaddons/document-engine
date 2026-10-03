<?php

namespace MatrixAddons\DocumentEngine\Admin;

use WP_Error;

defined('ABSPATH') || exit;

/**
 * "Already bought Pro? Enter your licence key" — activates the key with the store, downloads the
 * licensed package and installs/updates + activates Document Engine Pro. User-initiated only.
 *
 * Store (wp-config.php overrides): DOCUMENT_ENGINE_PRO_STORE_URL, DOCUMENT_ENGINE_PRO_ITEM_ID.
 */
class ProInstaller
{
    const ITEM_NAME = 'Document Engine Pro';
    /** EDD download ID of Document Engine Pro on store.mantrabrain.com. */
    const ITEM_ID = 39061;
    const PRO_SLUG = 'document-engine-pro';

    public static function item_id()
    {
        return defined('DOCUMENT_ENGINE_PRO_ITEM_ID') ? (int)DOCUMENT_ENGINE_PRO_ITEM_ID : self::ITEM_ID;
    }

    public static function init()
    {
        add_action('admin_post_dengine_install_pro', array(__CLASS__, 'handle'));
    }

    public static function store_url()
    {
        return defined('DOCUMENT_ENGINE_PRO_STORE_URL') ? DOCUMENT_ENGINE_PRO_STORE_URL : 'https://store.mantrabrain.com';
    }

    private static function request($action, $key)
    {
        $body = array(
            'edd_action' => $action,
            'license' => $key,
            'item_name' => self::ITEM_NAME,
            'url' => home_url(),
        );
        if (self::item_id()) {
            $body['item_id'] = self::item_id();
        }
        $response = wp_remote_post(self::store_url(), array('timeout' => 20, 'body' => $body));
        if (is_wp_error($response)) {
            return $response;
        }
        if ((int)wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error('store', __('The licence server could not be reached. Please try again in a few minutes.', 'document-engine'));
        }
        $data = json_decode(wp_remote_retrieve_body($response));
        return is_object($data) ? $data : new WP_Error('store', __('Unexpected answer from the licence server.', 'document-engine'));
    }

    private static function error_text($code)
    {
        $map = array(
            'expired' => __('This licence has expired. Renew it to install Pro.', 'document-engine'),
            'disabled' => __('This licence has been disabled.', 'document-engine'),
            'revoked' => __('This licence has been disabled.', 'document-engine'),
            'missing' => __('We couldn\'t find that licence key. Please check it and try again.', 'document-engine'),
            'invalid' => __('That licence key isn\'t valid for this site.', 'document-engine'),
            'item_name_mismatch' => __('That key is for a different product.', 'document-engine'),
            'no_activations_left' => __('This licence is already used on its maximum number of sites. Deactivate it elsewhere or upgrade your plan.', 'document-engine'),
        );
        return isset($map[$code]) ? $map[$code] : __('The licence could not be activated.', 'document-engine');
    }

    private static function pro_basename()
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        foreach (array_keys(get_plugins()) as $basename) {
            if (strpos($basename, self::PRO_SLUG . '/') === 0) {
                return $basename;
            }
        }
        return '';
    }

    /**
     * The licence card (used on the Pro page and every preview).
     */
    public static function form()
    {
        $notice = get_transient('dengine_install_pro_' . get_current_user_id());
        delete_transient('dengine_install_pro_' . get_current_user_id());
        $installed = self::pro_basename();
        ?>
        <div class="dengine-a-card dengine-a-install" id="install-pro">
            <div class="dengine-a-card__head"><div>
                    <h3 class="dengine-a-card__title"><?php echo $installed ? esc_html__('Activate Pro', 'document-engine') : esc_html__('Already bought Pro?', 'document-engine'); ?></h3>
                    <p class="dengine-a-card__desc"><?php esc_html_e('Enter your licence key and we\'ll install and activate Pro for you. Your documents and settings stay as they are.', 'document-engine'); ?></p>
                </div></div>
            <div class="dengine-a-card__body">
                <?php if (is_array($notice)) : ?>
                    <div class="dengine-a-notice dengine-a-notice--<?php echo $notice['ok'] ? 'success' : 'error'; ?>" role="status"><?php echo esc_html($notice['message']); ?></div>
                <?php endif; ?>
                <?php if (!current_user_can('install_plugins') || !current_user_can('activate_plugins')) : ?>
                    <p class="dengine-a-help"><?php esc_html_e('Ask a site administrator to install Pro — your account can\'t install plugins.', 'document-engine'); ?></p>
                <?php else : ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="dengine-a-install__form" data-dengine-install>
                        <input type="hidden" name="action" value="dengine_install_pro">
                        <?php wp_nonce_field('dengine_install_pro'); ?>
                        <label class="screen-reader-text" for="dengine-pro-key"><?php esc_html_e('Licence key', 'document-engine'); ?></label>
                        <input type="text" id="dengine-pro-key" name="license_key" class="dengine-a-input code" placeholder="<?php esc_attr_e('Licence key', 'document-engine'); ?>" autocomplete="off" spellcheck="false" required>
                        <button type="submit" class="dengine-a-btn dengine-a-btn--primary"><?php echo $installed ? esc_html__('Activate Pro', 'document-engine') : esc_html__('Install Pro', 'document-engine'); ?></button>
                    </form>
                    <p class="dengine-a-help"><?php esc_html_e('You\'ll find the key in your purchase email and your account on our store.', 'document-engine'); ?></p>
                    <script>
                        (function () {
                            var f = document.querySelector('[data-dengine-install]');
                            if (f) { f.addEventListener('submit', function () { var b = f.querySelector('button'); b.disabled = true; b.textContent = <?php echo wp_json_encode(__('Installing…', 'document-engine')); ?>; }); }
                        })();
                    </script>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private static function back($ok, $message, $url = '')
    {
        set_transient('dengine_install_pro_' . get_current_user_id(), array('ok' => $ok, 'message' => $message), 120);
        wp_safe_redirect($url ?: (wp_get_referer() ?: ProPage::url()));
        exit;
    }

    public static function handle()
    {
        check_admin_referer('dengine_install_pro');
        if (!current_user_can('install_plugins') || !current_user_can('activate_plugins')) {
            wp_die(esc_html__('You cannot install plugins.', 'document-engine'), 403);
        }
        $key = isset($_POST['license_key']) ? trim(sanitize_text_field(wp_unslash($_POST['license_key']))) : '';
        if ($key === '') {
            self::back(false, __('Please enter your licence key.', 'document-engine'));
        }

        $activation = self::request('activate_license', $key);
        if (is_wp_error($activation)) {
            self::back(false, $activation->get_error_message());
        }
        if (empty($activation->success) || !isset($activation->license) || $activation->license !== 'valid') {
            self::back(false, self::error_text(isset($activation->error) ? sanitize_key($activation->error) : 'invalid'));
        }

        // The Pro plugin reads this option, so updates work straight away.
        update_option('document_engine_pro_license', array(
            'key' => $key,
            'status' => 'valid',
            'expires' => isset($activation->expires) ? sanitize_text_field($activation->expires) : '',
        ), false);

        $basename = self::pro_basename();
        $needs_package = !$basename || (defined('DOCUMENT_ENGINE_PRO_VERSION') && version_compare(DOCUMENT_ENGINE_PRO_VERSION, '2.0.0', '<'));

        if ($needs_package) {
            $info = self::request('get_version', $key);
            if (is_wp_error($info) || empty($info->package)) {
                self::back(false, __('Your licence is active, but the download link could not be fetched. Download Pro from your store account and upload it in Plugins → Add New.', 'document-engine'));
            }
            $package = esc_url_raw($info->package);
            // Only install packages served by our store (or hosts the store is configured to use, e.g. a CDN).
            $allowed_hosts = (array)apply_filters('document_engine_pro_package_hosts', array(wp_parse_url(self::store_url(), PHP_URL_HOST)));
            if (!in_array(wp_parse_url($package, PHP_URL_HOST), $allowed_hosts, true) || wp_parse_url($package, PHP_URL_SCHEME) !== 'https') {
                self::back(false, __('The download link did not come from our store, so it was not installed.', 'document-engine'));
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/misc.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            if (!WP_Filesystem()) {
                self::back(false, __('WordPress can\'t write plugin files on this server. Download Pro from your store account and upload it in Plugins → Add New.', 'document-engine'));
            }
            $skin = new \WP_Ajax_Upgrader_Skin();
            $upgrader = new \Plugin_Upgrader($skin);
            if ($basename) {
                // Replace the old version in place.
                add_filter('upgrader_package_options', function ($options) {
                    $options['clear_destination'] = true;
                    $options['abort_if_destination_exists'] = false;
                    return $options;
                });
            }
            $result = $upgrader->install($package, array('overwrite_package' => true));
            if (is_wp_error($result) || !$result) {
                $error = is_wp_error($result) ? $result->get_error_message() : implode(' ', (array)$skin->get_error_messages());
                self::back(false, trim(__('Pro could not be installed.', 'document-engine') . ' ' . $error));
            }
            wp_clean_plugins_cache();
            $basename = self::pro_basename();
        }

        if ($basename && !is_plugin_active($basename)) {
            $activated = activate_plugin($basename);
            if (is_wp_error($activated)) {
                self::back(false, $activated->get_error_message());
            }
        }

        do_action('document_engine_pro_installed', $basename);
        self::back(true, __('Document Engine Pro is installed and active. Welcome aboard!', 'document-engine'), Dashboard::url() . '&dengine_pro=welcome');
    }
}
