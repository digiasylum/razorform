<?php
/**
 * RazorForms — AJAX Handler
 *
 * Handles form submission, saves records, and sends notification emails.
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Ajax {

    public static function init() {
        add_action( 'wp_ajax_rf_submit',        array( __CLASS__, 'submit' ) );
        add_action( 'wp_ajax_nopriv_rf_submit', array( __CLASS__, 'submit' ) );

        // Template apply via builder
        add_action( 'wp_ajax_rf_apply_template', array( __CLASS__, 'apply_template' ) );
    }

    public static function submit() {
        if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'rf_payment_nonce') ) {
            wp_send_json_error( array('message'=>'Security check failed.'), 403 );
        }

        $form_id    = absint( $_POST['form_id'] ?? 0 );
        $razorpay_id= sanitize_text_field( $_POST['razorpay_id'] ?? '' );

        if ( ! $form_id || empty($razorpay_id) ) {
            wp_send_json_error( array('message'=>'Missing required data.'), 400 );
        }

        $meta      = RF_Meta_Boxes::get_meta( $form_id );
        $form_post = get_post($form_id);

        // Build field_data — all custom field responses keyed by label
        $raw_fields = json_decode( stripslashes($_POST['field_data'] ?? '{}'), true ) ?: array();
        $clean_fields = array();

        // Core fields always saved
        $clean_fields['_name']  = sanitize_text_field( $_POST['core_name']  ?? '' );
        $clean_fields['_email'] = sanitize_email(      $_POST['core_email'] ?? '' );
        $clean_fields['_phone'] = sanitize_text_field( $_POST['core_phone'] ?? '' );

        foreach ( $raw_fields as $key => $value ) {
            $clean_fields[ sanitize_text_field($key) ] = is_array($value)
                ? array_map('sanitize_text_field', $value)
                : sanitize_textarea_field($value);
        }

        // Selected items (for items price mode)
        $items_raw = json_decode( stripslashes($_POST['selected_items'] ?? '[]'), true ) ?: array();
        if ( !empty($items_raw) ) {
            $clean_fields['_selected_items'] = $items_raw;
        }

        $amount    = floatval( $_POST['amount'] ?? 0 );
        $currency  = sanitize_text_field( $_POST['currency'] ?? 'INR' );

        $sub_id = RF_DB::insert_submission( array(
            'form_id'    => $form_id,
            'razorpay_id'=> $razorpay_id,
            'status'     => 'paid',
            'amount'     => $amount,
            'currency'   => $currency,
            'field_data' => $clean_fields,
        ));

        if ( ! $sub_id ) {
            wp_send_json_error( array('message'=>'Failed to save submission.'), 500 );
        }

        // Admin email notification
        self::send_admin_email( $meta, $form_post, $clean_fields, $amount, $razorpay_id, $sub_id );

        // Client confirmation email
        if ( ! empty($meta['send_client_email']) && ! empty($clean_fields['_email']) ) {
            self::send_client_email( $meta, $form_post, $clean_fields, $amount, $razorpay_id, $sub_id );
        }

        $redirect = ! empty($meta['thankyou']) ? esc_url($meta['thankyou']) : '';

        wp_send_json_success( array(
            'submission_id' => $sub_id,
            'thankyou_url'  => $redirect,
            'success_msg'   => wp_kses_post( $meta['success_msg'] ?: 'Thank you! Your payment was received.' ),
        ));
    }

    public static function apply_template() {
        if ( ! current_user_can('edit_posts') ) wp_send_json_error('Unauthorised', 403);
        check_ajax_referer('rf_builder');

        $tpl_id  = sanitize_key( $_POST['template'] ?? '' );
        $template = RF_Templates::get( $tpl_id );
        if ( ! $template ) wp_send_json_error('Template not found', 404);

        wp_send_json_success( $template['meta'] );
    }

    // ── Shared: replace tokens in subject/title strings ─────────
    private static function replace_tokens( $str, $data ) {
        $map = array(
            '{id}'     => $data['id']     ?? '',
            '{form}'   => $data['form']   ?? '',
            '{amount}' => $data['amount'] ?? '',
            '{name}'   => $data['name']   ?? '',
            '{email}'  => $data['email']  ?? '',
            '{brand}'  => $data['brand']  ?? '',
            '{txn}'    => $data['txn']    ?? '',
        );
        return str_replace( array_keys($map), array_values($map), $str );
    }

    // ── Shared: build branded HTML email wrapper ──────────────
    private static function email_html( $brand, $color, $logo, $footer, $body_html, $title_color = '#ffffff' ) {
        // Logo removed — text-only brand name in header
        return '<!DOCTYPE html><html><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>'.$brand.'</title></head>
<body style="margin:0;padding:0;background:#f1f3f9;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;font-size:14px;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
<tr><td align="center">
<table width="540" cellpadding="0" cellspacing="0" style="max-width:540px;width:100%;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
  <!-- HEADER -->
  <tr><td style="background:'.esc_attr($color).';padding:24px 32px;text-align:center;">
    <div style="color:'.esc_attr($title_color).';font-size:1.25rem;font-weight:700;letter-spacing:.02em;">'.esc_html($brand).'</div>
  </td></tr>
  <!-- BODY -->
  <tr><td style="background:#ffffff;padding:28px 32px;">'.$body_html.'</td></tr>
  <!-- FOOTER -->
  <tr><td style="background:#f4f6ff;padding:14px 32px;text-align:center;font-size:.75rem;color:#999;border-top:1px solid #e4e8f0;">
    '.wp_kses_post($footer).'
  </td></tr>
</table>
</td></tr></table>
</body></html>';
    }

    private static function send_admin_email( $meta, $form_post, $fields, $amount, $rzp_id, $sub_id ) {
        // Always resolve recipient fresh — never rely on stale saved meta value
        $notify_opt = get_option('rf_notify_email', '');
        $to = ! empty($notify_opt) ? $notify_opt : get_option('admin_email', '');
        if ( empty($to) ) return;

        $brand       = get_option('rf_email_brand_name', get_bloginfo('name')) ?: get_bloginfo('name');
        $color       = get_option('rf_email_brand_color',  '#295cff');
        $title_color = get_option('rf_email_title_color',  '#ffffff');
        $logo        = '';
        $footer      = get_option('rf_email_footer_text', $brand . ' — All rights reserved.');
        $from      = get_option('rf_notify_email', get_option('admin_email'));
        $form_name = $form_post ? $form_post->post_title : 'Unknown Form';
        $view_url  = admin_url('admin.php?page=rf-submissions&id='.$sub_id);

        // Token data for subject/title replacement
        $tokens = array(
            'id'     => $sub_id,
            'form'   => $form_name,
            'amount' => number_format($amount,2),
            'name'   => $fields['_name']  ?? '',
            'email'  => $fields['_email'] ?? '',
            'brand'  => $brand,
            'txn'    => $rzp_id,
        );

        $subj_tpl  = get_option('rf_email_admin_subject', 'New Payment Received | ₹{amount} from {name}');
        $title_tpl = get_option('rf_email_admin_title',   'New Payment Received');
        $subject   = self::replace_tokens($subj_tpl,  $tokens);
        $email_title = self::replace_tokens($title_tpl, $tokens);

        // Custom fields rows
        $custom_rows = '';
        foreach ( $fields as $k => $v ) {
            if ( substr($k,0,1) === '_' ) continue;
            $val = is_array($v) ? implode(', ',$v) : $v;
            $custom_rows .= '<tr><td style="padding:3px 0;color:#888;width:130px;">'.esc_html($k).'</td><td>'.esc_html($val).'</td></tr>';
        }

        // Items rows
        $items_html = '';
        if ( !empty($fields['_selected_items']) ) {
            $items_html = '<div style="margin:16px 0 0;"><div style="font-weight:700;color:#555;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase;margin-bottom:6px;">Items</div>
            <table style="width:100%;border-collapse:collapse;font-size:.84rem;">';
            foreach ($fields['_selected_items'] as $item) {
                $items_html .= '<tr><td style="padding:3px 0;color:#333;">'.esc_html($item['name']??'').'</td><td style="text-align:right;font-weight:600;color:'.esc_attr($color).';">₹'.number_format($item['price']??0,2).'</td></tr>';
            }
            $items_html .= '<tr style="border-top:1.5px solid #e4e7ef;"><td style="padding:6px 0 0;font-weight:700;">Total</td><td style="padding:6px 0 0;text-align:right;font-weight:700;color:'.esc_attr($color).';">₹'.number_format($amount,2).'</td></tr>';
            $items_html .= '</table></div>';
        }

        $body_html = '
        <h2 style="margin:0 0 4px;font-size:1.1rem;color:#111;">'.esc_html($email_title).'</h2>
        <p style="margin:0 0 20px;color:#777;font-size:.85rem;">A new payment was submitted via RazorForms.</p>

        <div style="background:#f4f6ff;border:1px solid #dde3ff;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                <tr><td style="padding:4px 0;color:#888;width:120px;">Form</td><td style="font-weight:600;">'.esc_html($form_name).'</td></tr>
                <tr><td style="padding:4px 0;color:#888;">Submission</td><td>#'.intval($sub_id).'</td></tr>
                <tr><td style="padding:4px 0;color:#888;">Amount</td><td style="font-weight:700;font-size:1.05rem;color:'.esc_attr($color).';">₹'.number_format($amount,2).'</td></tr>
                <tr><td style="padding:4px 0;color:#888;">Payment ID</td><td><code style="font-size:.78rem;background:#ebebeb;padding:2px 6px;border-radius:4px;">'.esc_html($rzp_id).'</code></td></tr>
            </table>
        </div>

        <div style="margin-bottom:'.($custom_rows||$items_html?'20':'4').'px;">
            <div style="font-weight:700;color:#555;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase;margin-bottom:6px;">Client Details</div>
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                <tr><td style="padding:3px 0;color:#888;width:80px;">Name</td><td>'.esc_html($fields['_name']??'—').'</td></tr>
                <tr><td style="padding:3px 0;color:#888;">Email</td><td style="color:'.esc_attr($color).';">'.esc_html($fields['_email']??'—').'</td></tr>
                <tr><td style="padding:3px 0;color:#888;">Phone</td><td>'.esc_html($fields['_phone']??'—').'</td></tr>
                '.$custom_rows.'
            </table>
        </div>
        '.$items_html.'
        <div style="text-align:center;margin-top:24px;">
            <a href="'.esc_url($view_url).'" style="display:inline-block;background:'.esc_attr($color).';color:#fff;text-decoration:none;padding:10px 28px;border-radius:6px;font-weight:600;font-size:.88rem;">View Submission →</a>
        </div>';

        $html    = self::email_html($brand, $color, $logo, $footer, $body_html, $title_color);
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: '.$brand.' <'.$from.'>');
        wp_mail($to, $subject, $html, $headers);
    }

    private static function send_client_email( $meta, $form_post, $fields, $amount, $rzp_id, $sub_id = 0 ) {
        $to = $fields['_email'] ?? '';
        if ( empty($to) ) return;

        $_brand_raw  = ! empty($meta['brand']) ? $meta['brand'] : get_bloginfo('name');
        $brand       = get_option('rf_email_brand_name', $_brand_raw) ?: $_brand_raw;
        $color       = get_option('rf_email_brand_color',  '#295cff');
        $title_color = get_option('rf_email_title_color',  '#ffffff');
        $logo        = '';
        $footer  = get_option('rf_email_footer_text', $brand . ' — All rights reserved.');
        $from    = get_option('rf_notify_email', get_option('admin_email'));
        $msg     = ! empty($meta['success_msg']) ? $meta['success_msg'] : 'Your payment was received successfully.';

        $tokens_c = array(
            'name'   => $fields['_name']  ?? '',
            'email'  => $fields['_email'] ?? '',
            'amount' => number_format($amount,2),
            'brand'  => $brand,
            'txn'    => $rzp_id,
        );
        $subj_c  = get_option('rf_email_client_subject', '{brand} | Payment Successful');
        $title_c = get_option('rf_email_client_title',   'Your Payment Has Been Confirmed!');
        $subject      = self::replace_tokens($subj_c,  $tokens_c);
        $client_title = self::replace_tokens($title_c, $tokens_c);

        // Items rows
        $items_html = '';
        if ( !empty($fields['_selected_items']) ) {
            $items_html = '<div style="margin:16px 0;background:#f4f6ff;border:1px solid #dde3ff;border-radius:8px;padding:14px 18px;">
            <div style="font-weight:700;color:#555;font-size:.72rem;letter-spacing:.05em;text-transform:uppercase;margin-bottom:8px;">Order Summary</div>
            <table style="width:100%;border-collapse:collapse;font-size:.84rem;">';
            foreach ($fields['_selected_items'] as $item) {
                $items_html .= '<tr><td style="padding:3px 0;color:#333;">'.esc_html($item['name']??'').'</td><td style="text-align:right;font-weight:600;color:'.esc_attr($color).';">₹'.number_format($item['price']??0,2).'</td></tr>';
            }
            $items_html .= '<tr style="border-top:1.5px solid #dde3ff;"><td style="padding:6px 0 0;font-weight:700;">Total Paid</td><td style="padding:6px 0 0;text-align:right;font-weight:700;color:'.esc_attr($color).';">₹'.number_format($amount,2).'</td></tr>';
            $items_html .= '</table></div>';
        }

        $body_html = '
        <h2 style="margin:0 0 6px;font-size:1.1rem;color:#111;">'.esc_html($client_title).'</h2>
        <p style="margin:0 0 12px;font-size:.9rem;color:#555;">Hi '.esc_html($fields['_name']??'there').',</p>
        <p style="margin:0 0 20px;color:#555;font-size:.9rem;line-height:1.6;">'.nl2br(esc_html($msg)).'</p>

        <div style="background:#f4f6ff;border:1px solid #dde3ff;border-radius:8px;padding:16px 20px;margin-bottom:16px;">
            <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                <tr><td style="padding:4px 0;color:#888;width:120px;">Amount Paid</td><td style="font-weight:700;font-size:1.05rem;color:'.esc_attr($color).';">₹'.number_format($amount,2).'</td></tr>
                <tr><td style="padding:4px 0;color:#888;">Payment ID</td><td><code style="font-size:.78rem;background:#ebebeb;padding:2px 6px;border-radius:4px;">'.esc_html($rzp_id).'</code></td></tr>
            </table>
        </div>
        '.$items_html.'
        <p style="margin:20px 0 0;color:#888;font-size:.82rem;">Thank you for choosing '.esc_html($brand).'. We will be in touch shortly.</p>';

        $html    = self::email_html($brand, $color, $logo, $footer, $body_html, $title_color);
        $headers = array('Content-Type: text/html; charset=UTF-8', 'From: '.$brand.' <'.$from.'>');
        wp_mail($to, $subject, $html, $headers);
    }
}
