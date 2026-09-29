<?php

namespace MatrixAddons\DocumentEngine\Admin;

use MatrixAddons\DocumentEngine\Documents\PostType;

defined('ABSPATH') || exit;

/**
 * Product screens live in the normal WordPress admin under the Documents menu:
 * a body class for the shared full-width styling, and one logical menu order for
 * free and Pro screens (library, then insights, then tools, then settings).
 */
class AppShell
{
    public static function init()
    {
        add_filter('admin_body_class', array(__CLASS__, 'body_class'));
        add_action('admin_menu', array(__CLASS__, 'order_menu'), 999);
        add_filter('submenu_file', array(__CLASS__, 'submenu_file'), 20);
        add_filter('parent_file', array(__CLASS__, 'parent_file'), 20);
        add_action('current_screen', array(__CLASS__, 'page_title'));
        add_action('all_admin_notices', array(__CLASS__, 'core_screen_tabs'), 1);
    }

    /** Resolved groups for this request: key => array(label, items => array(slug => label)). */
    private static $groups = array();
    /** Hidden submenu slug => its group's menu slug. */
    private static $hidden = array();
    /** Free previews of Pro screens (slug => feature). */
    private static $previews = array();
    /** Every Documents submenu slug (visible or grouped). */
    private static $all = array();

    /**
     * Related screens share one menu item and switch with tabs, so the Documents menu stays short.
     * Items are matched against submenu slugs (a full slug or a distinctive part of it). Screens keep
     * their own URLs; hidden ones are still registered, so links and bookmarks keep working.
     */
    public static function menu_groups()
    {
        return apply_filters('document_engine_menu_groups', array(
            'categories' => array(
                'label' => __('Categories', 'document-engine'),
                'items' => array('taxonomy=' . PostType::CATEGORY, 'taxonomy=' . PostType::TAG),
            ),
            'reports' => array(
                'label' => __('Reports', 'document-engine'),
                'items' => array('dengine-activity', 'dengine-searches', 'dengine-leads', 'dengine-acks', 'post_type=dengine_request'),
            ),
            'tools' => array(
                'label' => __('Tools', 'document-engine'),
                'items' => array('dengine-import', 'dengine-migrate', 'dengine-handbook'),
            ),
        ));
    }

    private static function matches($slug, $key)
    {
        $slug = html_entity_decode((string)$slug);
        return $slug === $key || strpos($slug, $key) !== false;
    }

    public static function is_app_screen()
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->base === 'post') {
            return false;
        }
        return (bool)apply_filters('document_engine_is_app_screen', Assets::is_product_screen($screen->id), $screen);
    }

    public static function body_class($classes)
    {
        return self::is_app_screen() ? $classes . ' dengine-app' : $classes;
    }

    /**
     * Ranks for Documents submenu items (matched against the submenu slug). Add-ons can extend it.
     */
    public static function menu_ranks()
    {
        return apply_filters('document_engine_menu_ranks', array(
            'dengine-dashboard' => 10,
            'edit.php?post_type=' . PostType::POST_TYPE => 20,
            'post-new.php?post_type=' . PostType::POST_TYPE => 30,
            'taxonomy=' . PostType::CATEGORY => 40,
            'taxonomy=' . PostType::TAG => 50,
            'dengine-acks' => 60,
            'dengine-activity' => 70,
            'dengine-searches' => 80,
            'dengine-leads' => 90,
            'edit.php?post_type=dengine_request' => 100,
            'dengine-import' => 110,
            'dengine-migrate' => 120,
            'dengine-handbook' => 130,
            'document-engine-settings' => 200,
            ProPage::SLUG => 210,
        ));
    }

    public static function order_menu()
    {
        global $submenu;
        $parent = 'edit.php?post_type=' . PostType::POST_TYPE;
        if (empty($submenu[$parent]) || !is_array($submenu[$parent])) {
            return;
        }
        $ranks = self::menu_ranks();
        $rank = function ($item) use ($ranks) {
            $slug = isset($item[2]) ? (string)$item[2] : '';
            if (isset($ranks[$slug])) {
                return $ranks[$slug];
            }
            foreach ($ranks as $key => $value) {
                if (strpos($slug, $key) !== false && strpos($key, '?') === false) {
                    return $value;
                }
            }
            // Unknown items from other plugins sit after ours, before Settings.
            return 150;
        };
        $items = array_values($submenu[$parent]);
        $keyed = array();
        foreach ($items as $i => $item) {
            $keyed[] = array($rank($item), $i, $item);
        }
        usort($keyed, function ($a, $b) {
            return $a[0] === $b[0] ? $a[1] - $b[1] : $a[0] - $b[0];
        });
        $items = array_map(function ($row) {
            return $row[2];
        }, $keyed);
        foreach ($items as $item) {
            self::$all[] = html_entity_decode((string)$item[2]);
        }

        /** Free-edition preview screens of Pro features (slug => feature). */
        $previews = (array)apply_filters('document_engine_menu_previews', array());
        self::$previews = $previews;

        // Collapse each group into its first screen the user can open.
        foreach (self::menu_groups() as $key => $group) {
            $members = array();
            $real = 0;
            foreach ($group['items'] as $want) {
                foreach ($items as $i => $item) {
                    if (self::matches($item[2], $want) && current_user_can($item[1])) {
                        $members[$i] = $item;
                        $real += isset($previews[html_entity_decode((string)$item[2])]) ? 0 : 1;
                    }
                }
            }
            // Pro previews only appear as tabs beside at least one free screen.
            if ($real < 1 || count($members) < 2) {
                continue;
            }
            // The group opens on its first free screen.
            uksort($members, function ($a, $b) use ($items, $previews) {
                $pa = isset($previews[html_entity_decode((string)$items[$a][2])]) ? 1 : 0;
                $pb = isset($previews[html_entity_decode((string)$items[$b][2])]) ? 1 : 0;
                return $pa === $pb ? $a - $b : $pa - $pb;
            });
            reset($members);
            $first = key($members);
            $badges = '';
            $tabs = array();
            foreach ($members as $i => $item) {
                // Keep "awaiting" count bubbles on the group item.
                if (preg_match_all('#<span class="(?:awaiting-mod|update-plugins)[^"]*".*?</span>\s*</span>#s', (string)$item[0], $m)) {
                    $badges .= ' ' . implode(' ', $m[0]);
                }
                $tabs[html_entity_decode((string)$item[2])] = trim(wp_strip_all_tags(preg_replace('#<span class="(?:awaiting-mod|update-plugins).*$#s', '', (string)$item[0])));
                if ($i !== $first) {
                    self::$hidden[html_entity_decode((string)$item[2])] = $items[$first][2];
                    unset($items[$i]);
                }
            }
            $items[$first][0] = esc_html($group['label']) . $badges;
            self::$groups[$key] = array('label' => $group['label'], 'items' => $tabs, 'slug' => $items[$first][2]);
        }

        // Previews that did not join a group never get a menu item of their own.
        foreach ($items as $i => $item) {
            $slug = html_entity_decode((string)$item[2]);
            if (isset($previews[$slug]) && !isset(self::$hidden[$slug]) && !self::is_group_slug($slug)) {
                self::$hidden[$slug] = '';
                unset($items[$i]);
            }
        }

        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- reordering our own submenu.
        $submenu[$parent] = array_values($items);
    }

    private static function is_group_slug($slug)
    {
        foreach (self::$groups as $group) {
            if ($group['slug'] === $slug) {
                return true;
            }
        }
        return false;
    }

    /**
     * The group of the screen being viewed, if any.
     */
    public static function current_group()
    {
        global $plugin_page;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_here = function ($slug) use ($plugin_page, $screen) {
            if (!empty($plugin_page)) {
                return $slug === (string)$plugin_page;
            }
            if (!$screen) {
                return false;
            }
            if ($screen->base === 'edit-tags' || $screen->base === 'term') {
                return $slug === 'edit-tags.php?taxonomy=' . $screen->taxonomy || strpos($slug, 'edit-tags.php?taxonomy=' . $screen->taxonomy . '&') === 0;
            }
            if ($screen->base === 'edit') {
                return $slug === 'edit.php?post_type=' . $screen->post_type;
            }
            return false;
        };
        foreach (self::$groups as $key => $group) {
            foreach (array_keys($group['items']) as $slug) {
                if ($is_here($slug)) {
                    return array($key, $slug);
                }
            }
        }
        return null;
    }

    /**
     * Keeps the group's menu item highlighted on every screen in the group.
     */
    public static function submenu_file($file)
    {
        global $plugin_page;
        $current = self::current_group();
        if ($current) {
            return self::$groups[$current[0]]['slug'];
        }
        // Screens opened through admin.php (Settings keeps its 1.x URL) still highlight their item.
        return !empty($plugin_page) && in_array((string)$plugin_page, self::$all, true) ? $plugin_page : $file;
    }

    public static function parent_file($file)
    {
        global $plugin_page;
        if ((!empty($plugin_page) && in_array((string)$plugin_page, self::$all, true)) || self::current_group()) {
            return 'edit.php?post_type=' . PostType::POST_TYPE;
        }
        return $file;
    }

    /**
     * Screens hidden from the menu lose their <title>; give it back.
     */
    public static function page_title()
    {
        $current = self::current_group();
        if ($current && empty($GLOBALS['title'])) {
            $GLOBALS['title'] = self::$groups[$current[0]]['items'][$current[1]]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        }
    }

    /**
     * Tab bar for the current group (printed by UI::page_start and on core list screens).
     */
    public static function group_tabs()
    {
        $current = self::current_group();
        if (!$current) {
            return;
        }
        $group = self::$groups[$current[0]];
        echo '<nav class="dengine-a-tabs" aria-label="' . esc_attr($group['label']) . '">';
        foreach ($group['items'] as $slug => $label) {
            $url = strpos($slug, '.php') !== false ? admin_url($slug) : admin_url('edit.php?post_type=' . PostType::POST_TYPE . '&page=' . $slug);
            $is = $slug === $current[1];
            echo '<a class="dengine-a-tabs__tab' . ($is ? ' is-current' : '') . '" href="' . esc_url($url) . '"' . ($is ? ' aria-current="page"' : '') . '>' . esc_html($label);
            if (isset(self::$previews[$slug])) {
                echo ' <span class="dengine-a-pill dengine-a-pill--pro">' . esc_html__('Pro', 'document-engine') . '</span>';
            }
            echo '</a>';
        }
        echo '</nav>';
    }

    /**
     * WordPress's own screens in a group (categories, tags, format requests) get the tab bar too.
     */
    public static function core_screen_tabs()
    {
        global $plugin_page;
        if (empty($plugin_page) && self::current_group()) {
            echo '<div class="dengine-a-tabs-wrap">';
            self::group_tabs();
            echo '</div>';
        }
    }
}
