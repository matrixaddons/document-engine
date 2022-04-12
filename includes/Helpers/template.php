<?php
if (!function_exists('document_engine_get_template')) {

    function document_engine_get_template($template_name, $args = array(), $template_path = '', $default_path = '')
    {
        $cache_key = sanitize_key(implode('-', array('template', $template_name, $template_path, $default_path)));
        $template = (string)wp_cache_get($cache_key, 'document-engine');

        if (!$template) {
            $template = document_engine_locate_template($template_name, $template_path, $default_path);
            wp_cache_set($cache_key, $template, 'document-engine');
        }
// Allow 3rd party plugin filter template file from their plugin.
        $filter_template = apply_filters('document_engine_get_template', $template, $template_name, $args, $template_path, $default_path);

        if ($filter_template !== $template) {
            if (!file_exists($filter_template)) {
                /* translators: %s template */
                _doing_it_wrong(__FUNCTION__, sprintf(__('%s does not exist.', 'document-engine'), '<code>' . $template . '</code>'), '1.0.1');
                return;
            }
            $template = $filter_template;
        }

        $action_args = array(
            'template_name' => $template_name,
            'template_path' => $template_path,
            'located' => $template,
            'args' => $args,
        );

        if (!empty($args) && is_array($args)) {
            if (isset($args['action_args'])) {
                _doing_it_wrong(
                    __FUNCTION__,
                    __('action_args should not be overwritten when calling document_engine_get_template.', 'document-engine'),
                    '1.0.0'
                );
                unset($args['action_args']);
            }
            extract($args); // @codingStandardsIgnoreLine
        }

        do_action('document_engine_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args']);

        include $action_args['located'];

        do_action('document_engine_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args']);
    }
}

if (!function_exists('document_engine_locate_template')) {
    function document_engine_locate_template($template_name, $template_path = '', $default_path = '')
    {
        if (!$template_path) {
            $template_path = document_engine()->template_path();
        }

        if (!$default_path) {
            $default_path = document_engine()->plugin_template_path();
        }

// Look within passed path within the theme - this is priority.
        $template = locate_template(
            array(
                trailingslashit($template_path) . $template_name,
                $template_name,
            )
        );

// Get default template/.
        if (!$template) {
            $template = $default_path . $template_name;
        }
// Return what we found.
        return apply_filters('document_engine_locate_template', $template, $template_name, $template_path);
    }
}

function document_engine_pdf_view_callback($settings = array())
{


    ob_start();

    $align = 'display: block; margin-left: auto; margin-right: auto;';


    $width_unit = $settings['width_unit'] ?? '%';

    $height_unit = $settings['height_unit'] ?? 'px';

    $width_size = $settings['width_size'] ?? '100';

    $height_size = $settings['height_size'] ?? '1000';

    $width_size = $width_unit === "%" && absint($width_size) > 100 ? 100 : $width_size;

    $height_size = $height_unit === "%" && absint($height_size) > 100 ? 100 : $height_size;

    $width = ' width: ' . esc_attr($width_size) . esc_attr($width_unit) . ';';

    $height = ' height: ' . esc_attr($height_size) . esc_attr($height_unit) . ';';

    $pdf_url = $settings['pdf_type'] === 'url' ? $settings['pdf_url'] : '';

    if ($settings['pdf_type'] === "file") {
        $file_id = isset($settings['pdf_id']) ? absint($settings['pdf_id']) : 0;

        $pdf_url = $file_id > 0 ? wp_get_attachment_url($file_id) : '';

    }

    if ($pdf_url === '') {

        echo '<h2>Invalid PDF Link</h2>';
    } else {

        echo '<iframe src="https://docs.google.com/viewer?url=' . esc_url_raw($pdf_url) . '&amp;embedded=true" style="' . $align . $width . $height . '" frameborder="1" marginheight="0px" marginwidth="0px" allowfullscreen></iframe>';
    }

    return ob_get_clean();
}