<?php
/**
 * Removes Document Engine data only when the owner opted in (Settings → Advanced), on every site of a network.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

$dengine_cleanup = function () {
    if (get_option('document_engine_delete_data', 'no') !== 'yes') {
        return;
    }

    global $wpdb;

    $document_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('dengine_document', 'dengine_request')"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    foreach ($document_ids as $document_id) {
        wp_delete_post((int)$document_id, true);
    }

    foreach (array('dengine_category', 'dengine_tag') as $taxonomy) {
        $terms = $wpdb->get_results($wpdb->prepare("SELECT term_taxonomy_id, term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", $taxonomy)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        foreach ($terms as $term) {
            $wpdb->delete($wpdb->term_relationships, array('term_taxonomy_id' => (int)$term->term_taxonomy_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->delete($wpdb->term_taxonomy, array('term_taxonomy_id' => (int)$term->term_taxonomy_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            // A term row can be shared with another taxonomy on old sites; only remove it when nothing else uses it.
            if (!$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE term_id = %d", (int)$term->term_id))) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->delete($wpdb->terms, array('term_id' => (int)$term->term_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->delete($wpdb->termmeta, array('term_id' => (int)$term->term_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            }
        }
    }

    // Before the options go: the list of capabilities this plugin granted lives in an option.
    foreach ((array)get_option('document_engine_granted_caps', array()) as $dengine_role_key => $dengine_caps) {
        $dengine_role = get_role($dengine_role_key);
        if ($dengine_role) {
            foreach ((array)$dengine_caps as $dengine_cap) {
                $dengine_role->remove_cap($dengine_cap);
            }
        }
    }

    $wpdb->query("DELETE FROM {$wpdb->options} WHERE (option_name LIKE 'document\\_engine\\_%' AND option_name NOT LIKE 'document\\_engine\\_pro\\_%') OR option_name LIKE '\\_transient\\_dengine\\_%' OR option_name LIKE '\\_transient\\_timeout\\_dengine\\_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'document\\_engine\\_%'"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

    wp_clear_scheduled_hook('document_engine_daily');


    $uploads = wp_upload_dir();
    $dir = trailingslashit($uploads['basedir']) . 'document-engine';
    if (is_dir($dir)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname()); // phpcs:ignore
        }
        @rmdir($dir); // phpcs:ignore
    }
};

if (is_multisite()) {
    foreach (get_sites(array('fields' => 'ids', 'number' => 0)) as $dengine_site_id) {
        switch_to_blog($dengine_site_id);
        $dengine_cleanup();
        restore_current_blog();
    }
} else {
    $dengine_cleanup();
}
