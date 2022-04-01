<?php

namespace MatrixAddons\DocumentEngine\Admin;

class Assets
{
    public static function init()
    {
        $self = new self();

        add_action('admin_enqueue_scripts', array($self, 'admin_assets'), 10, 1);
    }

    public function admin_assets()
    {

        if (isset($_GET['page']) && $_GET['page'] == 'dkpdf_settings') {
            wp_register_style('dkpdf-admin', plugins_url('dk-pdf/assets/css/admin.css'), array(), DKPDF_VERSION);
            wp_enqueue_style('dkpdf-admin');
        }
        if (isset($_GET['page']) && $_GET['page'] == 'dkpdf_settings') {
            wp_register_script('dkpdf-settings-admin', plugins_url('dk-pdf/assets/js/settings-admin.js'), array('jquery'), DKPDF_VERSION);
            wp_enqueue_script('dkpdf-settings-admin');

            wp_register_script('dkpdf-ace', plugins_url('dk-pdf/assets/js/src-min/ace.js'), array(), DKPDF_VERSION);
            wp_enqueue_script('dkpdf-ace');

            wp_register_script('dkpdf-admin', plugins_url('dk-pdf/assets/js/admin.js'), array('jquery'), DKPDF_VERSION);
            wp_enqueue_script('dkpdf-admin');
        }
    }
}
