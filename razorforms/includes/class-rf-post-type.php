<?php
/**
 * RazorForms — Custom Post Type
 *
 * Registers the rf_form custom post type.
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Post_Type {

    const CPT = 'rf_form';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    public static function register() {
        register_post_type( self::CPT, array(
            'labels' => array(
                'name'               => 'Payment Forms',
                'singular_name'      => 'Payment Form',
                'add_new'            => 'New Form',
                'add_new_item'       => 'Create New Payment Form',
                'edit_item'          => 'Edit Payment Form',
                'view_item'          => 'View Form',
                'search_items'       => 'Search Forms',
                'not_found'          => 'No payment forms found.',
                'menu_name'          => 'Payment Forms',
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,  // Prevent auto-injection; submenu added manually
            'supports'           => array( 'title' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'rewrite'            => false,
        ));
    }
}
