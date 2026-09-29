<?php

namespace MatrixAddons\DocumentEngine\Admin;

defined('ABSPATH') || exit;

/**
 * Renders settings field arrays (the 1.x format, also used by add-ons) as cards and rows.
 *
 * Input names and ids are unchanged, so Settings::save_fields() stores exactly what it did before.
 */
class Fields
{
    private static $card_open = false;
    private static $group_open = false;

    public static function render($options)
    {
        self::$card_open = false;
        self::$group_open = false;

        foreach ((array)$options as $value) {
            if (!isset($value['type'])) {
                continue;
            }
            $value = wp_parse_args($value, array(
                'id' => '',
                'title' => isset($value['name']) ? $value['name'] : '',
                'class' => '',
                'css' => '',
                'default' => '',
                'desc' => '',
                'desc_tip' => false,
                'placeholder' => '',
                'suffix' => '',
                'custom_attributes' => array(),
            ));

            switch ($value['type']) {
                case 'title':
                    self::close_group();
                    self::close_card();
                    UI::card_start((string)$value['title'], (string)$value['desc']);
                    self::$card_open = true;
                    do_action('document_engine_settings_' . sanitize_title($value['id']));
                    break;

                case 'sectionend':
                    self::close_group();
                    do_action('document_engine_settings_' . sanitize_title($value['id']) . '_end');
                    self::close_card();
                    do_action('document_engine_settings_' . sanitize_title($value['id']) . '_after');
                    break;

                case 'hidden':
                    echo '<input type="hidden" name="' . esc_attr($value['id']) . '" id="' . esc_attr($value['id']) . '" value="' . esc_attr(Settings::get_option($value['id'], $value['default'])) . '">';
                    break;

                case 'checkbox':
                    self::ensure_card();
                    self::checkbox($value);
                    break;

                default:
                    self::ensure_card();
                    self::close_group();
                    self::row($value);
            }
        }
        self::close_group();
        self::close_card();
    }

    private static function ensure_card()
    {
        if (!self::$card_open) {
            UI::card_start();
            self::$card_open = true;
        }
    }

    private static function close_card()
    {
        if (self::$card_open) {
            UI::card_end();
            self::$card_open = false;
        }
    }

    private static function close_group()
    {
        if (self::$group_open) {
            echo '</div></div>';
            self::$group_open = false;
        }
    }

    private static function attrs($value)
    {
        $out = array();
        foreach ((array)$value['custom_attributes'] as $attribute => $attribute_value) {
            $out[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
        }
        return implode(' ', $out);
    }

    private static function label_col($value, $for = true)
    {
        $help = $value['type'] !== 'checkbox' && $value['desc_tip'] && $value['desc_tip'] !== true ? $value['desc_tip'] : '';
        echo '<div class="dengine-a-field__label">';
        if ($value['title'] !== '') {
            echo $for ? '<label for="' . esc_attr($value['id']) . '">' . esc_html($value['title']) . '</label>' : '<span class="dengine-a-field__title">' . esc_html($value['title']) . '</span>';
        }
        if ($help) {
            echo '<p class="dengine-a-help">' . wp_kses_post($help) . '</p>';
        }
        echo '</div>';
    }

    private static function help($value)
    {
        if ($value['desc'] !== '' && $value['desc_tip'] !== true) {
            echo '<p class="dengine-a-help">' . wp_kses_post($value['desc']) . '</p>';
        }
    }

    private static function row($value)
    {
        $type = $value['type'];
        $current = Settings::get_option($value['id'], $value['default']);
        $multi_label = in_array($type, array('radio', 'multicheckbox', 'multiselect'), true);

        echo '<div class="dengine-a-field dengine-a-field--' . esc_attr($type) . '" id="row-' . esc_attr($value['id']) . '">';
        self::label_col($value, !$multi_label);
        echo '<div class="dengine-a-field__control">';

        switch ($type) {
            case 'text':
            case 'password':
            case 'datetime':
            case 'datetime-local':
            case 'date':
            case 'month':
            case 'time':
            case 'week':
            case 'number':
            case 'email':
            case 'url':
            case 'tel':
            case 'color':
                $input_type = $type === 'color' ? 'text' : $type;
                $size = $type === 'number' ? ' dengine-a-input--short' : '';
                $input = '<input class="dengine-a-input' . $size . ' ' . esc_attr($value['class']) . '" type="' . esc_attr($input_type) . '" name="' . esc_attr($value['id']) . '" id="' . esc_attr($value['id']) . '" value="' . esc_attr(is_array($current) ? '' : $current) . '" placeholder="' . esc_attr($value['placeholder']) . '" style="' . esc_attr($value['css']) . '" ' . self::attrs($value) . '>';
                if ($value['suffix'] !== '') {
                    echo '<span class="dengine-a-affix">' . $input . '<span class="dengine-a-affix__text">' . esc_html($value['suffix']) . '</span></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                } else {
                    echo $input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                self::help($value);
                break;

            case 'textarea':
                $code = !empty($value['code']) || strpos((string)$value['class'], 'custom-css') !== false;
                echo '<textarea class="dengine-a-textarea ' . esc_attr($value['class']) . '" name="' . esc_attr($value['id']) . '" id="' . esc_attr($value['id']) . '" rows="' . ($code ? 10 : 3) . '" placeholder="' . esc_attr($value['placeholder']) . '" style="' . esc_attr($value['css']) . '" ' . ($code ? 'data-code="css" spellcheck="false"' : '') . ' ' . self::attrs($value) . '>' . esc_textarea((string)$current) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                self::help($value);
                break;

            case 'select':
                echo '<select class="dengine-a-select ' . esc_attr($value['class']) . '" name="' . esc_attr($value['id']) . '" id="' . esc_attr($value['id']) . '" style="' . esc_attr($value['css']) . '" ' . self::attrs($value) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                foreach ((array)$value['options'] as $key => $label) {
                    echo '<option value="' . esc_attr($key) . '"' . selected((string)$current, (string)$key, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
                self::help($value);
                break;

            case 'multiselect':
                $current = (array)$current;
                echo '<div class="dengine-a-chips" role="group" aria-label="' . esc_attr($value['title']) . '">';
                foreach ((array)$value['options'] as $key => $label) {
                    echo '<label class="dengine-a-chip"><input type="checkbox" name="' . esc_attr($value['id']) . '[]" value="' . esc_attr($key) . '"' . checked(in_array((string)$key, array_map('strval', $current), true), true, false) . '><span>' . esc_html($label) . '</span></label>';
                }
                echo '</div>';
                self::help($value);
                break;

            case 'multicheckbox':
                $current = is_array($current) ? $current : array();
                echo '<div class="dengine-a-chips" role="group" aria-label="' . esc_attr($value['title']) . '">';
                foreach ((array)$value['options'] as $option) {
                    $key = isset($option['id']) ? $option['id'] : '';
                    $label = isset($option['title']) ? $option['title'] : $key;
                    $on = isset($current[$key]) && $current[$key] === 'yes';
                    echo '<label class="dengine-a-chip"><input type="checkbox" name="' . esc_attr($value['id']) . '[' . esc_attr($key) . ']" value="yes"' . checked($on, true, false) . '><span>' . esc_html(ucfirst($label)) . '</span></label>';
                }
                echo '</div>';
                self::help($value);
                break;

            case 'radio':
                echo '<div class="dengine-a-radios" role="radiogroup" aria-label="' . esc_attr($value['title']) . '">';
                foreach ((array)$value['options'] as $key => $label) {
                    echo '<label class="dengine-a-radio"><input type="radio" name="' . esc_attr($value['id']) . '" value="' . esc_attr($key) . '"' . checked((string)$current, (string)$key, false) . '><span>' . esc_html($label) . '</span></label>';
                }
                echo '</div>';
                self::help($value);
                break;

            case 'image':
                $image_id = absint($current);
                $url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
                echo '<div class="dengine-a-image" data-dengine-image>';
                echo '<input type="hidden" name="' . esc_attr($value['id']) . '" id="' . esc_attr($value['id']) . '" value="' . esc_attr($image_id) . '">';
                echo '<span class="dengine-a-image__preview"' . ($url ? '' : ' hidden') . '>' . ($url ? '<img src="' . esc_url($url) . '" alt="">' : '') . '</span>';
                echo '<span class="dengine-a-image__buttons"><button type="button" class="dengine-a-btn dengine-a-btn--secondary" data-action="choose">' . ($url ? esc_html__('Replace image', 'document-engine') : esc_html__('Choose image', 'document-engine')) . '</button>';
                echo '<button type="button" class="dengine-a-btn dengine-a-btn--link dengine-a-btn--danger" data-action="remove"' . ($url ? '' : ' hidden') . '>' . esc_html__('Remove', 'document-engine') . '</button></span>';
                echo '</div>';
                self::help($value);
                break;

            case 'single_select_page':
                echo wp_dropdown_pages(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'name' => $value['id'],
                    'id' => $value['id'],
                    'selected' => absint($current),
                    'show_option_none' => ' ',
                    'class' => 'dengine-a-select',
                    'echo' => false,
                ));
                self::help($value);
                break;

            default:
                do_action('document_engine_admin_field_' . $type, $value);
        }

        echo '</div></div>';
    }

    /**
     * Checkboxes render as switches. checkboxgroup start/(empty)/end share one row.
     */
    private static function checkbox($value)
    {
        $group = isset($value['checkboxgroup']) ? $value['checkboxgroup'] : null;
        $current = Settings::get_option($value['id'], $value['default']);

        if ($group === null || $group === 'start') {
            self::close_group();
            echo '<div class="dengine-a-field dengine-a-field--checkbox" id="row-' . esc_attr($value['id']) . '">';
            self::label_col($value, false);
            echo '<div class="dengine-a-field__control dengine-a-switches">';
            self::$group_open = true;
        }

        $text = $value['desc'] !== '' ? $value['desc'] : $value['title'];
        echo '<label class="dengine-a-switch" for="' . esc_attr($value['id']) . '">'
            . '<input type="checkbox" role="switch" name="' . esc_attr($value['id']) . '" id="' . esc_attr($value['id']) . '" value="1"' . checked($current, 'yes', false) . ' ' . self::attrs($value) . '>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            . '<span class="dengine-a-switch__track" aria-hidden="true"></span>'
            . '<span class="dengine-a-switch__text">' . wp_kses_post($text) . '</span></label>';

        if (!empty($value['desc_tip']) && $value['desc_tip'] !== true) {
            echo '<p class="dengine-a-help">' . wp_kses_post($value['desc_tip']) . '</p>';
        }

        if ($group === null || $group === 'end') {
            self::close_group();
        }
    }
}
