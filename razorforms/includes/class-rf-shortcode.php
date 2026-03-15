<?php
/**
 * RazorForms — Shortcode & Front-end Renderer
 *
 * Renders the payment form on the front-end via [razorform id="X"].
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Shortcode {

    public static function init() {
        add_shortcode( 'razorform', array( __CLASS__, 'render' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );

        // Preview mode
        add_action( 'template_redirect', array( __CLASS__, 'preview_page' ) );
    }

    public static function maybe_enqueue() {
        global $post;
        if ( is_a($post,'WP_Post') && (
            has_shortcode($post->post_content,'razorform') ||
            isset($_GET['rf_preview'])
        )) {
            self::enqueue_assets();
        }
    }

    public static function enqueue_assets( $form_id = 0, $meta = array() ) {
        wp_enqueue_style( 'rf-form', RF_URL . 'assets/css/form.css', array(), RF_VERSION );
        wp_enqueue_script( 'razorpay', 'https://checkout.razorpay.com/v1/checkout.js', array(), null, true );
        wp_enqueue_script( 'rf-form',  RF_URL . 'assets/js/form.js', array('razorpay'), RF_VERSION, true );

        $rzp_key = '';
        if ( !empty($meta) ) {
            $rzp_key = (!empty($meta['use_global_key']) || empty($meta['rzp_key']))
                ? get_option('rf_razorpay_key','')
                : $meta['rzp_key'];
        } else {
            $rzp_key = get_option('rf_razorpay_key','');
        }

        wp_localize_script( 'rf-form', 'RF_CONFIG', array(
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('rf_payment_nonce'),
            'razorpay_key' => $rzp_key,
        ));
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array('id'=>0), $atts, 'razorform' );
        $id   = absint($atts['id']);
        if ( ! $id ) return '<p style="color:red;">RazorForms: missing <code>id</code> attribute. Use <code>[razorform id="123"]</code>.</p>';

        $post = get_post($id);
        if ( ! $post || $post->post_type !== RF_Post_Type::CPT || $post->post_status !== 'publish' ) {
            if ( current_user_can('manage_options') ) return '<p style="color:orange;">RazorForms: Form #'.$id.' not found or not published.</p>';
            return '';
        }

        $meta = RF_Meta_Boxes::get_meta($id);
        self::enqueue_assets($id, $meta);

        ob_start();
        include RF_DIR . 'templates/form.php';
        return ob_get_clean();
    }

    public static function preview_page() {
        if ( ! isset($_GET['rf_preview']) ) return;
        if ( ! current_user_can('edit_posts') ) wp_die('Unauthorised');

        $id   = absint($_GET['rf_preview']);
        $post = get_post($id);
        if ( ! $post || $post->post_type !== RF_Post_Type::CPT ) wp_die('Form not found');

        $meta = RF_Meta_Boxes::get_meta($id);
        self::enqueue_assets($id, $meta);

        // Render standalone preview page
        ?><!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Preview: <?php echo esc_html($post->post_title); ?></title>
        <?php wp_head(); ?>
        </head>
        <body>
        <div style="background:#f0f0f0;padding:.5rem 1rem;font-size:.78rem;color:#555;font-family:sans-serif;border-bottom:2px solid #295cff;">
            👁 <strong>Preview Mode</strong> — This is how the form looks to visitors. Payments won't be processed in preview.
            <a href="<?php echo admin_url('post.php?post='.$id.'&action=edit'); ?>" style="margin-left:1rem;color:#295cff;">← Back to Editor</a>
        </div>
        <?php
        echo do_shortcode('[razorform id="'.$id.'"]');
        wp_footer();
        ?>
        </body></html>
        <?php
        exit;
    }
}
