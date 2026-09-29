<?php

namespace MatrixAddons\DocumentEngine\Documents;

defined('ABSPATH') || exit;

/**
 * Dedicated capabilities for documents, so organisations decide who manages the library
 * independently of who writes blog posts.
 */
class Capabilities
{
    const MANAGER_OPTION = 'document_engine_manager_roles';
    const AUTHOR_OPTION = 'document_engine_author_roles';
    const VERSION_OPTION = 'document_engine_caps_version';
    // Bump when the capability set changes so every site re-applies it once.
    const VERSION = 1;

    public static function init()
    {
        add_action('document_engine_upgraded', array(__CLASS__, 'sync'));
        add_action('document_engine_settings_saved', array(__CLASS__, 'sync'));
        add_action('init', array(__CLASS__, 'maybe_sync'), 20);
        add_filter('user_has_cap', array(__CLASS__, 'admin_safety_net'), 10, 4);
    }

    /**
     * Applies the roles once per capability version, independent of the database version,
     * so sites that were already on 2.x (or had their roles reset) get the capabilities too.
     */
    public static function maybe_sync()
    {
        if ((int)get_option(self::VERSION_OPTION, 0) !== self::VERSION || !is_array(get_option('document_engine_granted_caps', null))) {
            self::sync();
        }
    }

    /**
     * Site administrators always keep full access to the library, even if a role editor
     * or another plugin stripped the document capabilities from their role.
     */
    public static function admin_safety_net($allcaps, $caps, $args, $user)
    {
        if (empty($allcaps['manage_options'])) {
            return $allcaps;
        }
        foreach ($caps as $cap) {
            if (strpos((string)$cap, 'dengine') !== false && !isset($allcaps[$cap]) && in_array($cap, self::manager_caps(), true)) {
                $allcaps[$cap] = true;
            }
        }
        return $allcaps;
    }

    /**
     * Everything needed to run the library.
     */
    public static function manager_caps()
    {
        return apply_filters('document_engine_manager_caps', array(
            'edit_dengine_documents',
            'edit_others_dengine_documents',
            'edit_published_dengine_documents',
            'edit_private_dengine_documents',
            'publish_dengine_documents',
            'read_private_dengine_documents',
            'delete_dengine_documents',
            'delete_others_dengine_documents',
            'delete_published_dengine_documents',
            'delete_private_dengine_documents',
            'manage_dengine_categories',
            'view_dengine_reports',
        ));
    }

    /**
     * Add and publish their own documents only.
     */
    public static function author_caps()
    {
        return apply_filters('document_engine_author_caps', array(
            'edit_dengine_documents',
            'edit_published_dengine_documents',
            'publish_dengine_documents',
            'delete_dengine_documents',
            'delete_published_dengine_documents',
        ));
    }

    /**
     * Draft their own documents (no publishing) — like WordPress contributors.
     */
    public static function contributor_caps()
    {
        return apply_filters('document_engine_contributor_caps', array('edit_dengine_documents', 'delete_dengine_documents'));
    }

    /**
     * Roles whose post permissions match a level. Used as defaults, so upgrading keeps
     * everyone's access the same as when documents used post permissions.
     */
    private static function roles_matching($cap, $not_cap = '')
    {
        $roles = array();
        foreach (wp_roles()->role_objects as $key => $role) {
            if ($role->has_cap($cap) && ($not_cap === '' || !$role->has_cap($not_cap))) {
                $roles[] = $key;
            }
        }
        return $roles;
    }

    public static function manager_roles()
    {
        $roles = get_option(self::MANAGER_OPTION, null);
        $roles = is_array($roles) ? $roles : self::roles_matching('edit_others_posts');
        $roles[] = 'administrator';
        return array_values(array_unique($roles));
    }

    public static function author_roles()
    {
        $roles = get_option(self::AUTHOR_OPTION, null);
        return is_array($roles) ? $roles : array_diff(self::roles_matching('publish_posts'), self::manager_roles());
    }

    public static function contributor_roles()
    {
        $roles = get_option('document_engine_contributor_roles', null);
        return is_array($roles) ? $roles : array_diff(self::roles_matching('edit_posts', 'publish_posts'), self::manager_roles());
    }

    /**
     * Applies the role settings. Only capabilities this plugin granted are ever removed,
     * so grants made with a role editor are left alone.
     */
    public static function sync()
    {
        $managers = self::manager_roles();
        $authors = array_diff(self::author_roles(), $managers);
        $contributors = array_diff(self::contributor_roles(), $managers, $authors);
        $granted = get_option('document_engine_granted_caps', array());
        $granted = is_array($granted) ? $granted : array();
        $next = array();

        foreach (wp_roles()->role_objects as $key => $role) {
            if (in_array($key, $managers, true)) {
                $want = self::manager_caps();
            } elseif (in_array($key, $authors, true)) {
                $want = self::author_caps();
            } elseif (in_array($key, $contributors, true)) {
                $want = self::contributor_caps();
            } else {
                $want = array();
            }
            foreach ($want as $cap) {
                if (!$role->has_cap($cap)) {
                    $role->add_cap($cap);
                }
            }
            $previous = isset($granted[$key]) ? (array)$granted[$key] : array();
            foreach (array_diff($previous, $want) as $cap) {
                $role->remove_cap($cap);
            }
            if ($want) {
                $next[$key] = array_values($want);
            }
        }
        update_option('document_engine_granted_caps', $next, false);
        update_option(self::VERSION_OPTION, self::VERSION, false);
    }

    public static function remove_all()
    {
        $granted = get_option('document_engine_granted_caps', array());
        foreach ((array)$granted as $key => $caps) {
            $role = get_role($key);
            if ($role) {
                foreach ((array)$caps as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
        delete_option('document_engine_granted_caps');
    }
}
