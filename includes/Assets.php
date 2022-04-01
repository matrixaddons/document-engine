<?php

namespace MatrixAddons\DocumentEngine;

class Assets
{
    public static function init()
    {
        $self = new self();
        add_action('init', [$self, 'register_assets']);
    }

    public function register_assets()
    {
        wp_register_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', array(), '4.3.0');

        wp_register_style( 'dkpdf-frontend', plugins_url( 'dk-pdf/assets/css/frontend.css' ), array(), DKPDF_VERSION );

        wp_register_script( 'dkpdf-frontend', plugins_url( 'dk-pdf/assets/js/frontend.js' ), array( 'jquery' ), DKPDF_VERSION, true );

        wp_enqueue_style( 'font-awesome' );

        wp_enqueue_style( 'dkpdf-frontend' );

        wp_enqueue_script( 'dkpdf-frontend' );

    }
}
