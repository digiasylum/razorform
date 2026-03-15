<?php
/**
 * Plugin Name:       RazorForms — Payment Page Builder
 * Plugin URI:        https://www.digiasylum.com/razorforms/
 * Description:       No-code Razorpay payment page builder for WordPress. Create beautiful, branded payment forms with a built-in item list, custom fields, and CRM — zero coding required.
 * Version:           1.5.0
 * Author:            Digiasylum
 * Author URI:        https://www.digiasylum.com/
 * Contributors:      umeshkumarsahai
 * Donate link:       https://about.me/umeshkumarsahai
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       razorforms
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Tested up to:      6.5
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com/
 *
 * Lead Developer : Umesh Kumar Sahai
 * LinkedIn       : https://linkedin.com/in/umeshkumarsahai
 * Personal       : https://about.me/umeshkumarsahai
 * Studio         : https://www.digiasylum.com/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RF_VERSION', '1.5.0' );
define( 'RF_DIR',     plugin_dir_path( __FILE__ ) );
define( 'RF_URL',     plugin_dir_url( __FILE__ ) );
define( 'RF_SLUG',    'razorforms' );

// ── Load classes (order matters) ───────────────────────────
require_once RF_DIR . 'includes/class-rf-db.php';
require_once RF_DIR . 'includes/class-rf-post-type.php';
require_once RF_DIR . 'includes/class-rf-templates.php';    // must load before meta-boxes
require_once RF_DIR . 'includes/class-rf-meta-boxes.php';
require_once RF_DIR . 'includes/class-rf-shortcode.php';
require_once RF_DIR . 'includes/class-rf-ajax.php';
require_once RF_DIR . 'includes/class-rf-receipt.php';
require_once RF_DIR . 'includes/class-rf-admin.php';
require_once RF_DIR . 'includes/class-rf-settings.php';
require_once RF_DIR . 'includes/class-rf-webhook.php';      // standalone — prevents fatal error

// ── Activation ──────────────────────────────────────────────
register_activation_hook( __FILE__, array( 'RF_DB', 'install' ) );

// ── Boot ────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
    RF_Post_Type::init();
    RF_Meta_Boxes::init();
    RF_Shortcode::init();
    RF_Ajax::init();
    RF_Receipt::init();
    RF_Admin::init();
    RF_Settings::init();
    RF_Webhook::init();
} );
