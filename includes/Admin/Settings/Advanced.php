<?php

namespace MatrixAddons\DocumentEngine\Admin\Settings;

use MatrixAddons\DocumentEngine\Admin\Setting_Base;
use MatrixAddons\DocumentEngine\Admin\Settings;

defined('ABSPATH') || exit;

class Advanced extends Setting_Base
{
    public function __construct()
    {
        $this->id = 'advanced';
        $this->label = __('Advanced', 'document-engine');

        parent::__construct();
    }

    public function output()
    {
        Settings::output_fields($this->get_settings());
    }

    public function save()
    {
        Settings::save_fields($this->get_settings());
    }

    public function get_settings()
    {
        $roles = array();
        foreach (wp_roles()->get_names() as $key => $name) {
            $roles[$key] = translate_user_role($name);
        }
        $settings = array(
            array('title' => __('Permissions', 'document-engine'), 'type' => 'title', 'desc' => __('Who can work with documents in the dashboard. Administrators always can.', 'document-engine'), 'id' => 'document_engine_permission_options'),
            array('title' => __('Manage all documents', 'document-engine'), 'desc' => __('Add, edit and delete any document, manage categories and see reports.', 'document-engine'), 'id' => 'document_engine_manager_roles', 'type' => 'multiselect', 'default' => \MatrixAddons\DocumentEngine\Documents\Capabilities::manager_roles(), 'options' => $roles),
            array('title' => __('Publish their own documents', 'document-engine'), 'desc' => __('Add and publish documents, and edit only their own.', 'document-engine'), 'id' => 'document_engine_author_roles', 'type' => 'multiselect', 'default' => array_values(\MatrixAddons\DocumentEngine\Documents\Capabilities::author_roles()), 'options' => $roles),
            array('title' => __('Draft their own documents', 'document-engine'), 'desc' => __('Add documents for review; someone else publishes them.', 'document-engine'), 'id' => 'document_engine_contributor_roles', 'type' => 'multiselect', 'default' => array_values(\MatrixAddons\DocumentEngine\Documents\Capabilities::contributor_roles()), 'options' => $roles),
            array('type' => 'sectionend', 'id' => 'document_engine_permission_options'),
            array('title' => __('Performance', 'document-engine'), 'type' => 'title', 'desc' => '', 'id' => 'document_engine_performance_options'),
            array(
                'title' => __('PDF cache', 'document-engine'),
                'desc' => __('Reuse generated PDFs', 'document-engine'),
                'desc_tip' => __('For visitors who are not logged in. A PDF is rebuilt when its post or these settings change.', 'document-engine'),
                'id' => 'document_engine_pdf_cache',
                'type' => 'checkbox',
                'default' => 'yes',
            ),
            array(
                'title' => __('Generation limit', 'document-engine'),
                'desc' => __('New PDFs one visitor can create per minute. 0 turns the limit off. Cached PDFs and logged-in users are never limited.', 'document-engine'),
                'id' => 'document_engine_pdf_rate_limit',
                'type' => 'number',
                'suffix' => __('per minute', 'document-engine'),
                'default' => 20,
                'custom_attributes' => array('min' => 0),
            ),
            array('type' => 'sectionend', 'id' => 'document_engine_performance_options'),
            array('title' => __('Data', 'document-engine'), 'type' => 'title', 'desc' => '', 'id' => 'document_engine_data_options'),
            array(
                'title' => __('When deleting the plugin', 'document-engine'),
                'desc' => __('Also delete all documents, categories and settings', 'document-engine'),
                'desc_tip' => __('Uploaded files stay in the Media Library. Leave this off unless you are removing the plugin for good.', 'document-engine'),
                'id' => 'document_engine_delete_data',
                'type' => 'checkbox',
                'default' => 'no',
            ),
            array('type' => 'sectionend', 'id' => 'document_engine_data_options'),
        );

        return apply_filters('document_engine_get_settings_' . $this->id, $settings);
    }
}
