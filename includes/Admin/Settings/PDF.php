<?php

namespace MatrixAddons\DocumentEngine\Admin\Settings;


use MatrixAddons\DocumentEngine\Admin\Setting_Base;
use MatrixAddons\DocumentEngine\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class PDF extends Setting_Base
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = 'pdf_downloads';
        $this->label = __('Post to PDF', 'document-engine');

        parent::__construct();

        add_action('document_engine_settings_header_actions', array(__CLASS__, 'preview_action'));

        // CSS must keep characters like ">" that HTML sanitising would encode.
        add_filter('document_engine_admin_settings_sanitize_option_document_engine_pdf_custom_css', function ($value, $option, $raw) {
            return trim(wp_strip_all_tags((string)$raw));
        }, 10, 3);
    }

    /**
     * Get sections.
     *
     * @return array
     */
    public function get_sections()
    {
        $sections = array(
            '' => __('Button', 'document-engine'),
            'header_footer' => __('Header & footer', 'document-engine'),
            'page' => __('Page & protection', 'document-engine'),
            'style' => __('Style', 'document-engine'),
        );

        return apply_filters('document_engine_get_sections_' . $this->id, $sections);
    }

    /**
     * Output the settings.
     */
    public function output()
    {
        global $current_section;

        $settings = $this->get_settings($current_section);

        Settings::output_fields($settings);
    }

    /**
     * Save settings.
     */
    public function save()
    {
        global $current_section;

        $settings = $this->get_settings($current_section);
        Settings::save_fields($settings);

        if ($current_section) {
            do_action('document_engine_update_options_' . $this->id . '_' . $current_section);
        }
    }

    /**
     * Get settings array.
     *
     * @param string $current_section Current section name.
     * @return array
     */
    public function get_settings($current_section = '')
    {
        if ('page' === $current_section) {
            $settings = array(
                array('title' => __('Page', 'document-engine'), 'type' => 'title', 'desc' => __('Size and spacing of every generated page.', 'document-engine'), 'id' => 'document_engine_pdf_page_options'),
                array(
                    'title' => __('Paper size', 'document-engine'),
                    'id' => 'document_engine_pdf_page_size',
                    'type' => 'select',
                    'default' => 'A4',
                    'options' => array(
                        'A4' => __('A4 (210 × 297 mm)', 'document-engine'),
                        'Letter' => __('US Letter (8.5 × 11 in)', 'document-engine'),
                        'Legal' => __('US Legal (8.5 × 14 in)', 'document-engine'),
                        'A5' => __('A5 (148 × 210 mm)', 'document-engine'),
                        'A3' => __('A3 (297 × 420 mm)', 'document-engine'),
                    ),
                ),
                array(
                    'title' => __('Orientation', 'document-engine'),
                    'id' => 'document_engine_pdf_page_orientation',
                    'type' => 'select',
                    'default' => 'vertical',
                    'options' => array(
                        'vertical' => __('Portrait', 'document-engine'),
                        'horizontal' => __('Landscape', 'document-engine'),
                    ),
                ),
                array('title' => __('Text size', 'document-engine'), 'id' => 'document_engine_pdf_page_font_size', 'type' => 'number', 'suffix' => 'pt', 'default' => 12, 'custom_attributes' => array('min' => 6, 'max' => 36)),
                array(
                    'title' => __('Font', 'document-engine'),
                    'id' => 'document_engine_pdf_font',
                    'type' => 'select',
                    'default' => 'dejavusans',
                    'options' => array(
                        'dejavusans' => __('Sans (DejaVu Sans)', 'document-engine'),
                        'dejavuserif' => __('Serif (DejaVu Serif)', 'document-engine'),
                        'dejavusansmono' => __('Monospaced (DejaVu Sans Mono)', 'document-engine'),
                        'custom' => __('Your own font (below)', 'document-engine'),
                    ),
                    'desc' => __('The built-in fonts cover Latin, Greek, Cyrillic, Arabic and Hebrew. For Chinese, Japanese, Korean or other scripts, upload a font that has them (for example Noto Sans SC) and use it below.', 'document-engine'),
                ),
                array(
                    'title' => __('Your own font', 'document-engine'),
                    'id' => 'document_engine_pdf_font_file',
                    'type' => 'url',
                    'default' => '',
                    'placeholder' => 'https://…/wp-content/uploads/…/NotoSansSC-Regular.ttf',
                    'desc' => __('Upload a .ttf or .otf file to the Media Library (administrators can) and paste its address here.', 'document-engine'),
                ),
                array(
                    'title' => __('Use your font for', 'document-engine'),
                    'id' => 'document_engine_pdf_font_use',
                    'type' => 'select',
                    'default' => 'fallback',
                    'options' => array(
                        'fallback' => __('Only characters the main font doesn\'t have (for example Chinese, Japanese, Korean)', 'document-engine'),
                        'all' => __('All text', 'document-engine'),
                    ),
                ),
                array('title' => __('Left margin', 'document-engine'), 'id' => 'document_engine_pdf_page_margin_left', 'type' => 'number', 'suffix' => 'mm', 'default' => 15, 'custom_attributes' => array('min' => 0)),
                array('title' => __('Right margin', 'document-engine'), 'id' => 'document_engine_pdf_page_margin_right', 'type' => 'number', 'suffix' => 'mm', 'default' => 15, 'custom_attributes' => array('min' => 0)),
                array('title' => __('Top margin', 'document-engine'), 'desc' => __('Leave room for the header.', 'document-engine'), 'id' => 'document_engine_pdf_page_margin_top', 'type' => 'number', 'suffix' => 'mm', 'default' => 50, 'custom_attributes' => array('min' => 0)),
                array('title' => __('Bottom margin', 'document-engine'), 'desc' => __('Leave room for the footer.', 'document-engine'), 'id' => 'document_engine_pdf_page_margin_bottom', 'type' => 'number', 'suffix' => 'mm', 'default' => 50, 'custom_attributes' => array('min' => 0)),
                array('title' => __('Header distance', 'document-engine'), 'desc' => __('Space between the top of the page and the header.', 'document-engine'), 'id' => 'document_engine_pdf_page_margin_header', 'type' => 'number', 'suffix' => 'mm', 'default' => 15, 'custom_attributes' => array('min' => 0)),
                array(
                    'title' => __('Columns', 'document-engine'),
                    'desc' => __('Fill columns one after another instead of balancing their length ([document_engine_pdf_columns])', 'document-engine'),
                    'id' => 'document_engine_pdf_page_keep_columns',
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                array('type' => 'sectionend', 'id' => 'document_engine_pdf_page_options'),

                array('title' => __('Protection', 'document-engine'), 'type' => 'title', 'desc' => __('Encrypt PDFs and choose what readers may do with them.', 'document-engine'), 'id' => 'document_engine_pdf_protection_options'),
                array(
                    'title' => __('Protect PDFs', 'document-engine'),
                    'desc' => __('Encrypt generated PDFs', 'document-engine'),
                    'id' => 'document_engine_pdf_page_enable_protection',
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                array(
                    'title' => __('Readers may', 'document-engine'),
                    'id' => 'document_engine_pdf_page_protected_permissions',
                    'desc' => __('Protection is applied when at least one permission is selected.', 'document-engine'),
                    'type' => 'multicheckbox',
                    'options' => document_engine_get_available_pdf_permissions(),
                ),
                array('type' => 'sectionend', 'id' => 'document_engine_pdf_protection_options'),
            );

        } else if ('style' === $current_section) {
            $settings = array(
                array('title' => __('Style', 'document-engine'), 'type' => 'title', 'desc' => __('Make PDFs match your brand.', 'document-engine'), 'id' => 'document_engine_pdf_style'),
                array(
                    'title' => __('Theme styles', 'document-engine'),
                    'desc' => __('Include your theme\'s stylesheet (custom CSS below still wins)', 'document-engine'),
                    'id' => 'document_engine_pdf_use_theme_style',
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                array(
                    'title' => __('Custom CSS', 'document-engine'),
                    'desc' => __('Applied to every PDF. Example: <code>h2 { color: #1d4ed8; }</code>', 'document-engine'),
                    'id' => 'document_engine_pdf_custom_css',
                    'type' => 'textarea',
                    'code' => 'css',
                    'class' => 'document-engine-pdf-custom-css',
                    'default' => '',
                ),
                array('type' => 'sectionend', 'id' => 'document_engine_pdf_style'),
            );
            // T8 (static): watermarks are a Pro feature; shown as information, not as a control that looks like it works.
            if (\MatrixAddons\DocumentEngine\Admin\Nudges::can_show('t8-watermark', false)) {
                $settings[] = array(
                    'title' => __('Watermark (Document Engine Pro)', 'document-engine'),
                    'type' => 'title',
                    'desc' => esc_html__('Add a text or image watermark to every PDF, with placeholders such as {name} and {date}.', 'document-engine') . ' <a href="' . esc_url(\MatrixAddons\DocumentEngine\Admin\ProPage::url()) . '">' . esc_html__('Compare Free and Pro', 'document-engine') . '</a>',
                    'id' => 'document_engine_pdf_watermark_pro',
                );
                $settings[] = array('type' => 'sectionend', 'id' => 'document_engine_pdf_watermark_pro');
            }

        } else if ('header_footer' === $current_section) {
            $allowed = array(
                'a' => array('href' => array(), 'target' => array()),
                'br' => array(), 'em' => array(), 'strong' => array(), 'hr' => array(), 'p' => array(),
                'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
            );
            $settings = array(
                array('title' => __('Header', 'document-engine'), 'type' => 'title', 'desc' => __('Shown at the top of every page.', 'document-engine'), 'id' => 'document_engine_pdf_header_options'),
                array('title' => __('Logo', 'document-engine'), 'desc' => __('PNG or JPG, shown on the left.', 'document-engine'), 'id' => 'document_engine_pdf_header_logo', 'type' => 'image', 'default' => '0'),
                array('title' => __('Show', 'document-engine'), 'desc' => __('Post title', 'document-engine'), 'id' => 'document_engine_pdf_header_show_post_title', 'type' => 'checkbox', 'default' => 'no', 'checkboxgroup' => 'start'),
                array('desc' => __('Page numbers', 'document-engine'), 'id' => 'document_engine_pdf_header_show_pagination', 'type' => 'checkbox', 'default' => 'no', 'checkboxgroup' => 'end'),
                array('title' => __('Text size', 'document-engine'), 'desc' => __('Empty uses the page text size.', 'document-engine'), 'id' => 'document_engine_pdf_header_font_size', 'type' => 'number', 'suffix' => 'pt', 'default' => ''),
                array('type' => 'sectionend', 'id' => 'document_engine_pdf_header_options'),

                array('title' => __('Footer', 'document-engine'), 'type' => 'title', 'desc' => __('Shown at the bottom of every page.', 'document-engine'), 'id' => 'document_engine_pdf_footer_options'),
                array(
                    'title' => __('Footer text', 'document-engine'),
                    'id' => 'document_engine_pdf_footer_text',
                    'desc' => __('For example your organization name or a disclaimer. Allowed HTML: a, br, em, strong, hr, p, h1–h6.', 'document-engine'),
                    'type' => 'textarea',
                    'allowed_html' => $allowed,
                ),
                array('title' => __('Show', 'document-engine'), 'desc' => __('Post title', 'document-engine'), 'id' => 'document_engine_pdf_footer_show_post_title', 'type' => 'checkbox', 'default' => 'no', 'checkboxgroup' => 'start'),
                array('desc' => __('Page numbers', 'document-engine'), 'id' => 'document_engine_pdf_footer_show_pagination', 'type' => 'checkbox', 'default' => 'no', 'checkboxgroup' => 'end'),
                array('title' => __('Text size', 'document-engine'), 'desc' => __('Empty uses the page text size.', 'document-engine'), 'id' => 'document_engine_pdf_footer_font_size', 'type' => 'number', 'suffix' => 'pt', 'default' => ''),
                array('type' => 'sectionend', 'id' => 'document_engine_pdf_footer_options'),
            );

        } else {
            $post_types_arr = document_engine_get_available_post_types();
            foreach ($post_types_arr as &$type) {
                $object = get_post_type_object($type['id']);
                if ($object) {
                    $type['title'] = $object->labels->name;
                }
            }
            unset($type);

            $settings = array(
                array('title' => __('Download PDF button', 'document-engine'), 'type' => 'title', 'desc' => __('Adds a button to the chosen content types. You can also place it anywhere with the Save as PDF block or [document_engine_pdf_button].', 'document-engine'), 'id' => 'document_engine_pdf_button_options'),
                array(
                    'title' => __('Show on', 'document-engine'),
                    'id' => 'document_engine_pdf_post_type',
                    'type' => 'multicheckbox',
                    'options' => $post_types_arr,
                ),
                array(
                    'title' => __('Button text', 'document-engine'),
                    'id' => 'document_engine_pdf_button_text',
                    'type' => 'text',
                    'default' => __('Download PDF', 'document-engine'),
                ),
                array(
                    'title' => __('Placement', 'document-engine'),
                    'id' => 'document_engine_pdf_button_position',
                    'type' => 'select',
                    'options' => array(
                        'before' => __('Above the content', 'document-engine'),
                        'after' => __('Below the content', 'document-engine'),
                    ),
                    'default' => 'before',
                ),
                array(
                    'title' => __('Alignment', 'document-engine'),
                    'id' => 'document_engine_pdf_button_alignment',
                    'type' => 'select',
                    'options' => array(
                        'left' => __('Left', 'document-engine'),
                        'center' => __('Center', 'document-engine'),
                        'right' => __('Right', 'document-engine'),
                    ),
                    'default' => 'right',
                ),
                array(
                    'title' => __('When clicked', 'document-engine'),
                    'id' => 'document_engine_pdf_button_action',
                    'type' => 'select',
                    'options' => array(
                        'download' => __('Download the PDF', 'document-engine'),
                        'open' => __('Open the PDF in a new tab', 'document-engine'),
                    ),
                    'default' => 'download',
                ),
                array('type' => 'sectionend', 'id' => 'document_engine_pdf_button_options'),
            );
        }

        return apply_filters('document_engine_get_settings_' . $this->id, $settings, $current_section);
    }

    /**
     * "Preview PDF" in the page header: the latest post of an enabled type.
     */
    public static function preview_action($tab)
    {
        if ($tab !== 'pdf_downloads') {
            return;
        }
        $types = array_keys(document_engine_pdf_post_type());
        $types = $types ?: array('post');
        $latest = get_posts(array('post_type' => $types, 'post_status' => 'publish', 'numberposts' => 1, 'has_password' => false));
        if ($latest) {
            echo \MatrixAddons\DocumentEngine\Admin\UI::button(__('Preview PDF', 'document-engine'), add_query_arg(DOCUMENT_ENGINE_QUERY_VAR_SLUG, $latest[0]->ID, get_permalink($latest[0])), 'secondary', 'external'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}
