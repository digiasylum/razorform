<?php
/**
 * RazorForms — Settings Page
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Settings {

    public static function init() {
        add_action( 'admin_init',         array( __CLASS__, 'register' ) );
    }


    public static function register() {
        foreach ( array(
            'rf_razorpay_key', 'rf_webhook_secret',
            'rf_notify_email',
            'rf_email_brand_name', 'rf_email_brand_color',
            'rf_email_title_color', 'rf_email_body_color',
            'rf_email_footer_text',
            'rf_email_admin_title', 'rf_email_admin_subject',
            'rf_email_client_title', 'rf_email_client_subject',
        ) as $k ) {
            register_setting( 'rf_settings_group', $k,
                array( 'sanitize_callback' => 'sanitize_text_field' ) );
        }
    }

    public static function page() {
        if ( isset($_POST['rf_settings_nonce']) && wp_verify_nonce($_POST['rf_settings_nonce'],'rf_save_settings') ) {
            update_option( 'rf_razorpay_key',          sanitize_text_field( $_POST['rf_razorpay_key']          ?? '' ) );
            update_option( 'rf_webhook_secret',        sanitize_text_field( $_POST['rf_webhook_secret']        ?? '' ) );
            update_option( 'rf_notify_email',          sanitize_email(      $_POST['rf_notify_email']          ?? '' ) );
            update_option( 'rf_email_brand_name',      sanitize_text_field( $_POST['rf_email_brand_name']      ?? '' ) );
            update_option( 'rf_email_brand_color',     sanitize_hex_color(  $_POST['rf_email_brand_color']     ?? '#295cff' ) ?: '#295cff' );
            update_option( 'rf_email_title_color',     sanitize_hex_color(  $_POST['rf_email_title_color']     ?? '#ffffff' ) ?: '#ffffff' );
            update_option( 'rf_email_body_color',      sanitize_hex_color(  $_POST['rf_email_body_color']      ?? '#1a1a2e' ) ?: '#1a1a2e' );
            update_option( 'rf_email_footer_text',     wp_kses_post( wp_unslash( $_POST['rf_email_footer_text'] ?? '' ) ) );
            update_option( 'rf_email_admin_title',     sanitize_text_field( $_POST['rf_email_admin_title']     ?? '' ) );
            update_option( 'rf_email_admin_subject',   sanitize_text_field( $_POST['rf_email_admin_subject']   ?? '' ) );
            update_option( 'rf_email_client_title',    sanitize_text_field( $_POST['rf_email_client_title']    ?? '' ) );
            update_option( 'rf_email_client_subject',  sanitize_text_field( $_POST['rf_email_client_subject']  ?? '' ) );
            echo '<div class="notice notice-success is-dismissible"><p>✅ Settings saved.</p></div>';
        }

        $rzp_key        = get_option( 'rf_razorpay_key',         '' );
        $wh_secret      = get_option( 'rf_webhook_secret',       '' );
        $notify         = get_option( 'rf_notify_email',         get_option('admin_email') );
        $brand_name     = get_option( 'rf_email_brand_name',     '' );
        $brand_color    = get_option( 'rf_email_brand_color',    '#295cff' );
        $title_color    = get_option( 'rf_email_title_color',    '#ffffff' );
        $body_color     = get_option( 'rf_email_body_color',     '#1a1a2e' );
        $footer_text    = get_option( 'rf_email_footer_text',    get_bloginfo('name') . ' — All rights reserved.' );
        $admin_title    = get_option( 'rf_email_admin_title',    'New Payment Received' );
        $admin_subject  = get_option( 'rf_email_admin_subject',  'New Payment Received | ₹{amount} from {name}' );
        $client_title   = get_option( 'rf_email_client_title',   'Your Payment Has Been Confirmed!' );
        $client_subject = get_option( 'rf_email_client_subject', '{brand} | Payment Successful' );
        $wh_url         = rest_url( 'razorforms/v1/webhook' );
        $site_name      = get_bloginfo('name');
        ?>
        <div class="wrap rf-wrap">
            <div class="rf-admin-header"><h1>⚙️ RazorForms Settings</h1></div>
            <form method="POST" action="">
                <?php wp_nonce_field( 'rf_save_settings', 'rf_settings_nonce' ); ?>

                <!-- ── Razorpay ──────────────────────── -->
                <div class="rf-settings-card">
                    <h2>💳 Razorpay</h2>
                    <div class="rf-setting-row">
                        <label>Key ID <span class="rf-req">*</span></label>
                        <div>
                            <input type="text" name="rf_razorpay_key" value="<?php echo esc_attr($rzp_key); ?>"
                                   placeholder="rzp_live_XXXXXXXXXXXXXXXXXX" class="regular-text">
                            <p class="description">From <a href="https://dashboard.razorpay.com/app/keys" target="_blank">Razorpay Dashboard → Settings → API Keys</a>. Use <code>rzp_test_</code> prefix for testing.</p>
                        </div>
                    </div>
                    <div class="rf-setting-row">
                        <label>Webhook Secret</label>
                        <div>
                            <input type="text" name="rf_webhook_secret" value="<?php echo esc_attr($wh_secret); ?>"
                                   placeholder="From Razorpay webhook settings" class="regular-text">
                            <p class="description">Verifies incoming webhook authenticity. Highly recommended.</p>
                        </div>
                    </div>
                    <div class="rf-setting-row">
                        <label>Webhook URL</label>
                        <div>
                            <code class="rf-code-block"><?php echo esc_url($wh_url); ?></code>
                            <p class="description">Add in <a href="https://dashboard.razorpay.com/app/webhooks" target="_blank">Razorpay → Webhooks</a>. Enable: <strong>payment.captured</strong>, <strong>payment.failed</strong>, <strong>refund.created</strong>.</p>
                        </div>
                    </div>
                </div>

                <!-- ── Email ─────────────────────────── -->
                <div class="rf-settings-card">
                    <h2>📧 Email Notifications</h2>
                    <div class="rf-setting-row">
                        <label>Notification Email <span class="rf-req">*</span></label>
                        <div>
                            <input type="email" name="rf_notify_email" value="<?php echo esc_attr($notify); ?>"
                                   class="regular-text" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                            <p class="description">All payment alerts go here. Also used as the sender address for client confirmation emails.</p>
                        </div>
                    </div>
                </div>

                <!-- ── Email Template ────────────────── -->
                <div class="rf-settings-card">
                    <h2>🎨 Email Template Design</h2>
                    <p style="color:#666;font-size:.85rem;margin:0 0 1.5rem;">Customise how every payment email and PDF receipt looks.</p>

                    <!-- Brand Name -->
                    <div class="rf-setting-row">
                        <label>Brand Name</label>
                        <div>
                            <input type="text" name="rf_email_brand_name"
                                   value="<?php echo esc_attr($brand_name ?: $site_name); ?>"
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr($site_name); ?>">
                            <p class="description">Shown in the email header and PDF receipt. Enter exactly as you want it to appear — uppercase, lowercase, with symbols, etc. Leave blank to use your WordPress site name.</p>
                        </div>
                    </div>

                    <!-- Header Background Colour -->
                    <div class="rf-setting-row">
                        <label>Header Background</label>
                        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                            <input type="color" name="rf_email_brand_color" id="rfColorPicker"
                                   value="<?php echo esc_attr($brand_color); ?>"
                                   style="width:48px;height:36px;border:none;cursor:pointer;border-radius:6px;padding:2px;">
                            <input type="text" id="rfColorText" value="<?php echo esc_attr($brand_color); ?>"
                                   style="width:90px;" class="regular-text"
                                   placeholder="#295cff">
                            <p class="description" style="margin:0;">Header background, links, amounts, and buttons.</p>
                        </div>
                    </div>

                    <!-- Header Text / Title Colour -->
                    <div class="rf-setting-row">
                        <label>Header Text Colour</label>
                        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                            <input type="color" name="rf_email_title_color" id="rfTitleColorPicker"
                                   value="<?php echo esc_attr($title_color); ?>"
                                   style="width:48px;height:36px;border:none;cursor:pointer;border-radius:6px;padding:2px;">
                            <input type="text" id="rfTitleColorText" value="<?php echo esc_attr($title_color); ?>"
                                   style="width:90px;" class="regular-text"
                                   placeholder="#ffffff">
                            <p class="description" style="margin:0;">Colour of the brand name text in the email/receipt header. Set to white (#ffffff) for dark backgrounds, dark for light backgrounds.</p>
                        </div>
                    </div>

                    <!-- Body Text Colour -->
                    <div class="rf-setting-row">
                        <label>Body Text Colour</label>
                        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
                            <input type="color" name="rf_email_body_color" id="rfBodyColorPicker"
                                   value="<?php echo esc_attr($body_color); ?>"
                                   style="width:48px;height:36px;border:none;cursor:pointer;border-radius:6px;padding:2px;">
                            <input type="text" id="rfBodyColorText" value="<?php echo esc_attr($body_color); ?>"
                                   style="width:90px;" class="regular-text"
                                   placeholder="#1a1a2e">
                            <p class="description" style="margin:0;">Main text colour in the email body and PDF receipt. Defaults to near-black (#1a1a2e).</p>
                        </div>
                    </div>

                    <!-- Footer Text (HTML supported) -->
                    <div class="rf-setting-row" style="align-items:flex-start;">
                        <label style="padding-top:.4rem;">Footer Text</label>
                        <div>
                            <textarea name="rf_email_footer_text" rows="3"
                                      class="large-text"
                                      placeholder="<?php echo esc_attr($site_name); ?> &mdash; All rights reserved."
                                      style="font-size:.84rem;line-height:1.5;"><?php echo esc_textarea($footer_text); ?></textarea>
                            <p class="description">Appears at the bottom of every email and receipt. Supports HTML: <code>&lt;a href=""&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;br&gt;</code>, <code>&lt;em&gt;</code>.</p>
                        </div>
                    </div>

                    <!-- Colour swatches preview -->
                    <div class="rf-setting-row" style="align-items:start;">
                        <label style="padding-top:.3rem;">Colour Preview</label>
                        <div>
                            <div id="rfColorSwatchPreview" style="display:flex;gap:.85rem;align-items:stretch;border:1px solid #e2e4e7;border-radius:8px;overflow:hidden;max-width:380px;">
                                <div id="rfSwatchHeader" style="background:<?php echo esc_attr($brand_color); ?>;padding:14px 20px;min-width:120px;display:flex;align-items:center;justify-content:center;">
                                    <span id="rfSwatchBrandName" style="color:<?php echo esc_attr($title_color); ?>;font-weight:700;font-size:.88rem;text-align:center;"><?php echo esc_html($brand_name ?: $site_name); ?></span>
                                </div>
                                <div id="rfSwatchBody" style="background:#fff;padding:14px 16px;flex:1;display:flex;flex-direction:column;gap:4px;justify-content:center;">
                                    <div id="rfSwatchBodyText" style="color:<?php echo esc_attr($body_color); ?>;font-size:.78rem;font-weight:600;">Body text preview</div>
                                    <div style="font-size:.7rem;color:#999;">Amount: <strong id="rfSwatchAmount" style="color:<?php echo esc_attr($brand_color); ?>;">₹1,000.00</strong></div>
                                </div>
                            </div>
                            <p class="description" style="margin-top:.4rem;">Updates as you change colours above.</p>
                        </div>
                    </div>
                </div>

                <!-- ── Admin Email Customiser ─────────── -->
                <div class="rf-settings-card">
                    <h2>📬 Admin Notification Email</h2>
                    <p style="color:#666;font-size:.85rem;margin:0 0 1.25rem;">
                        Sent to your notification email on every successful payment.<br>
                        <strong>Available tokens:</strong>
                        <code>{id}</code> submission # &nbsp;·&nbsp;
                        <code>{form}</code> form name &nbsp;·&nbsp;
                        <code>{amount}</code> amount paid &nbsp;·&nbsp;
                        <code>{name}</code> client name &nbsp;·&nbsp;
                        <code>{email}</code> client email &nbsp;·&nbsp;
                        <code>{brand}</code> site name &nbsp;·&nbsp;
                        <code>{txn}</code> Razorpay payment ID
                    </p>
                    <div class="rf-setting-row">
                        <label>Email Subject</label>
                        <div>
                            <input type="text" name="rf_email_admin_subject"
                                   value="<?php echo esc_attr($admin_subject); ?>"
                                   class="large-text"
                                   placeholder="New Payment Received | ₹{amount} from {name}">
                            <p class="description">Subject line of the admin notification email.</p>
                        </div>
                    </div>
                    <div class="rf-setting-row">
                        <label>Email Headline</label>
                        <div>
                            <input type="text" name="rf_email_admin_title"
                                   value="<?php echo esc_attr($admin_title); ?>"
                                   class="large-text"
                                   placeholder="New Payment Received">
                            <p class="description">The bold headline shown at the top of the email body.</p>
                        </div>
                    </div>

                    <!-- Admin email preview -->
                    <div class="rf-setting-row" style="align-items:start;">
                        <label style="padding-top:.4rem;">Preview</label>
                        <div id="rfAdminEmailPreview"><?php echo self::render_email_preview( $brand_color, $footer_text, $admin_title, $admin_subject, 'admin', $brand_name ?: $site_name, $title_color, $body_color ); ?></div>
                    </div>
                </div>

                <!-- ── Client Email Customiser ─────────── -->
                <div class="rf-settings-card">
                    <h2>📨 Client Confirmation Email</h2>
                    <p style="color:#666;font-size:.85rem;margin:0 0 1.25rem;">
                        Sent to the customer after a successful payment (if enabled per form).<br>
                        <strong>Available tokens:</strong>
                        <code>{name}</code> client name &nbsp;·&nbsp;
                        <code>{amount}</code> amount paid &nbsp;·&nbsp;
                        <code>{brand}</code> site name &nbsp;·&nbsp;
                        <code>{txn}</code> Razorpay payment ID
                    </p>
                    <div class="rf-setting-row">
                        <label>Email Subject</label>
                        <div>
                            <input type="text" name="rf_email_client_subject"
                                   value="<?php echo esc_attr($client_subject); ?>"
                                   class="large-text"
                                   placeholder="{brand} | Payment Successful">
                            <p class="description">Subject line of the client confirmation email.</p>
                        </div>
                    </div>
                    <div class="rf-setting-row">
                        <label>Email Headline</label>
                        <div>
                            <input type="text" name="rf_email_client_title"
                                   value="<?php echo esc_attr($client_title); ?>"
                                   class="large-text"
                                   placeholder="Your Payment Has Been Confirmed!">
                            <p class="description">The bold headline shown at the top of the client email body.</p>
                        </div>
                    </div>

                    <!-- Client email preview -->
                    <div class="rf-setting-row" style="align-items:start;">
                        <label style="padding-top:.4rem;">Preview</label>
                        <div id="rfClientEmailPreview"><?php echo self::render_email_preview( $brand_color, $footer_text, $client_title, $client_subject, 'client', $brand_name ?: $site_name, $title_color, $body_color ); ?></div>
                    </div>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary button-large">💾 Save Settings</button>
                </p>
            </form>
        </div>

        <script>
        jQuery(function($){

            // ── Colour picker sync (all 3) ────────────
            function syncPicker(pickerId, textId, swatchFn) {
                $('#'+pickerId).on('input', function(){
                    $('#'+textId).val(this.value);
                    if(swatchFn) swatchFn(this.value);
                    updateSwatch();
                });
                $('#'+textId).on('input', function(){
                    if(/^#[0-9A-Fa-f]{6}$/.test(this.value)){
                        $('#'+pickerId).val(this.value);
                        updateSwatch();
                    }
                });
            }
            function updateSwatch() {
                var hdr   = $('#rfColorPicker').val()      || '#295cff';
                var title = $('#rfTitleColorPicker').val() || '#ffffff';
                var body  = $('#rfBodyColorPicker').val()  || '#1a1a2e';
                var name  = $('[name=rf_email_brand_name]').val() || '<?php echo esc_js($site_name); ?>';
                $('#rfSwatchHeader').css('background', hdr);
                $('#rfSwatchBrandName').css('color', title).text(name);
                $('#rfSwatchBodyText').css('color', body);
                $('#rfSwatchAmount').css('color', hdr);
            }
            syncPicker('rfColorPicker',      'rfColorText');
            syncPicker('rfTitleColorPicker', 'rfTitleColorText');
            syncPicker('rfBodyColorPicker',  'rfBodyColorText');
            $('[name=rf_email_brand_name]').on('input', function(){ updateSwatch(); });

            // Logo removed — text-only brand name used in emails

        });
        </script>
        <?php
    }

    /**
     * Render a mini HTML email preview card for the settings page.
     */
    public static function render_email_preview( $color, $footer, $title, $subject, $type = 'admin', $brand_name = '', $title_color = '#ffffff', $body_color = '#1a1a2e' ) {
        $display_brand = $brand_name ?: get_bloginfo('name');

        if ( $type === 'admin' ) {
            $body = '
            <p style="margin:0 0 12px;color:#666;font-size:.82rem;">A new payment was submitted via RazorForms.</p>
            <div style="background:#f4f6ff;border:1px solid #dde3ff;border-radius:7px;padding:12px 16px;font-size:.8rem;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="color:#888;padding:2px 0;width:90px;">Form</td><td style="font-weight:600;color:'.esc_attr($body_color).';">Sample Form</td></tr>
                    <tr><td style="color:#888;padding:2px 0;">Amount</td><td style="font-weight:700;color:'.esc_attr($color).';">₹1,000.00</td></tr>
                    <tr><td style="color:#888;padding:2px 0;">Payment ID</td><td><code style="font-size:.72rem;background:#ebebeb;padding:1px 5px;border-radius:3px;">pay_XXXXXXXX</code></td></tr>
                </table>
            </div>
            <div style="margin-top:12px;font-size:.78rem;color:'.esc_attr($body_color).';">
                <div style="color:#aaa;margin-bottom:4px;font-weight:600;text-transform:uppercase;font-size:.65rem;letter-spacing:.05em;">Client</div>
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="color:#888;padding:2px 0;width:60px;">Name</td><td style="color:'.esc_attr($body_color).';">John Doe</td></tr>
                    <tr><td style="color:#888;padding:2px 0;">Email</td><td style="color:'.esc_attr($color).';">john@example.com</td></tr>
                    <tr><td style="color:#888;padding:2px 0;">Phone</td><td style="color:'.esc_attr($body_color).';">+91 98765 43210</td></tr>
                </table>
            </div>
            <div style="text-align:center;margin-top:14px;">
                <a href="#" style="display:inline-block;background:'.esc_attr($color).';color:#fff;text-decoration:none;padding:7px 20px;border-radius:5px;font-size:.78rem;font-weight:600;">View Submission →</a>
            </div>';
        } else {
            $body = '
            <p style="margin:0 0 4px;font-size:.82rem;color:'.esc_attr($body_color).';">Hi John,</p>
            <p style="margin:0 0 12px;color:#666;font-size:.82rem;line-height:1.6;">Thank you for your payment. We have received it and will be in touch shortly.</p>
            <div style="background:#f4f6ff;border:1px solid #dde3ff;border-radius:7px;padding:12px 16px;font-size:.8rem;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr><td style="color:#888;padding:2px 0;width:100px;">Amount Paid</td><td style="font-weight:700;color:'.esc_attr($color).';">₹1,000.00</td></tr>
                    <tr><td style="color:#888;padding:2px 0;">Payment ID</td><td><code style="font-size:.72rem;background:#ebebeb;padding:1px 5px;border-radius:3px;">pay_XXXXXXXX</code></td></tr>
                </table>
            </div>';
        }

        ob_start(); ?>
        <div style="border:1px solid #e2e4e7;border-radius:9px;overflow:hidden;max-width:480px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:13px;color:<?php echo esc_attr($body_color); ?>;background:#f9fafb;">
            <div style="background:<?php echo esc_attr($color); ?>;padding:16px 24px;text-align:center;">
                <div style="color:<?php echo esc_attr($title_color); ?>;font-size:.9rem;font-weight:700;"><?php echo esc_html($display_brand); ?></div>
            </div>
            <div style="background:#fff;padding:20px 24px;">
                <div style="font-size:.95rem;font-weight:700;color:<?php echo esc_attr($body_color); ?>;margin-bottom:8px;"><?php echo esc_html($title ?: 'Email Headline'); ?></div>
                <?php echo $body; ?>
            </div>
            <div style="background:#f4f6ff;padding:10px 24px;text-align:center;font-size:.7rem;color:#999;border-top:1px solid #e4e8f0;">
                <?php echo wp_kses_post($footer); ?>
            </div>
        </div>
        <p class="description" style="margin-top:.5rem;">
            Subject preview: <em><?php echo esc_html($subject ?: 'Email Subject'); ?></em>
        </p>
        <?php
        return ob_get_clean();
    }
}
