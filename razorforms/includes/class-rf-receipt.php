<?php
/**
 * RazorForms — PDF Receipt Generator
 *
 * Single-page branded receipt. Uses browser Print → Save as PDF.
 *
 * @package  RazorForms
 * @since    1.3.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Receipt {

    public static function init() {
        add_action( 'admin_post_rf_receipt',        array( __CLASS__, 'render' ) );
        add_action( 'admin_post_nopriv_rf_receipt', array( __CLASS__, 'render_token' ) );
    }

    public static function render() {
        if ( ! current_user_can('manage_options') ) wp_die('Access denied.');
        $id  = absint( $_GET['id'] ?? 0 );
        $sub = RF_DB::get_submission( $id );
        if ( ! $sub ) wp_die('Submission not found.');
        self::output( $sub );
    }

    public static function render_token() {
        $id    = absint( $_GET['id'] ?? 0 );
        $token = sanitize_text_field( $_GET['token'] ?? '' );
        $sub   = RF_DB::get_submission( $id );
        if ( ! $sub ) wp_die('Submission not found.');
        if ( ! hash_equals( self::make_token($id, $sub->razorpay_id), $token ) ) wp_die('Invalid receipt link.');
        self::output( $sub );
    }

    public static function make_token( $id, $razorpay_id ) {
        return hash_hmac( 'sha256', $id.'|'.$razorpay_id, get_option('rf_webhook_secret', wp_salt('auth')) );
    }

    public static function receipt_url( $sub ) {
        if ( is_admin() && current_user_can('manage_options') ) {
            return admin_url( 'admin-post.php?action=rf_receipt&id=' . $sub->id );
        }
        $token = self::make_token( $sub->id, $sub->razorpay_id );
        return admin_url( 'admin-post.php?action=rf_receipt&id=' . $sub->id . '&token=' . $token );
    }

    private static function output( $sub ) {
        $brand       = get_option( 'rf_email_brand_name', get_bloginfo('name') );
        $hdr_color   = get_option( 'rf_email_brand_color',  '#295cff' );
        $title_color = get_option( 'rf_email_title_color',  '#ffffff' );
        $body_color  = get_option( 'rf_email_body_color',   '#1a1a2e' );
        $footer_html = get_option( 'rf_email_footer_text',  $brand . ' — All rights reserved.' );

        $form      = get_post( $sub->form_id );
        $form_name = $form ? $form->post_title : 'Payment';
        $fields    = is_string($sub->field_data)
                     ? (json_decode($sub->field_data, true) ?: [])
                     : (array)$sub->field_data;

        $name    = $fields['_name']  ?? '—';
        $email   = $fields['_email'] ?? '—';
        $phone   = $fields['_phone'] ?? '—';
        $items   = $fields['_selected_items'] ?? [];
        $custom  = [];
        foreach ($fields as $k => $v) {
            if (substr($k,0,1)==='_') continue;
            $custom[$k] = is_array($v) ? implode(', ',$v) : $v;
        }

        $date       = date('d M Y, h:i A', strtotime($sub->created_at ?? 'now'));
        $receipt_no = 'RCT-' . str_pad($sub->id, 5, '0', STR_PAD_LEFT);
        $sym        = ($sub->currency ?? 'INR') === 'INR' ? '₹' : ($sub->currency . ' ');
        $amount     = number_format($sub->amount, 2);

        header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt <?php echo esc_html($receipt_no); ?> — <?php echo esc_html($brand); ?></title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{
    font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    font-size:11px; /* base size — everything scales from here */
    color:<?php echo esc_attr($body_color); ?>;
    background:#f0f2f8;
    -webkit-print-color-adjust:exact;
    print-color-adjust:exact;
}

/* ── Page wrapper ─────────────────────── */
.page{
    max-width:680px;
    margin:20px auto;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 6px 32px rgba(0,0,0,.10);
}

/* ── Header ──────────────────────────── */
.rh{
    background:<?php echo esc_attr($hdr_color); ?>;
    padding:18px 32px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:.75rem;
}
.rh-brand{
    color:<?php echo esc_attr($title_color); ?>;
    font-size:1.2rem;
    font-weight:800;
    letter-spacing:-.01em;
}
.rh-meta{text-align:right;color:rgba(255,255,255,.8);}
.rh-meta .rno{display:block;font-size:.9rem;font-weight:700;color:<?php echo esc_attr($title_color); ?>;}
.rh-meta .rdt{font-size:.72rem;margin-top:1px;}

/* ── Status bar ──────────────────────── */
.rs{
    background:<?php echo esc_attr($hdr_color); ?>18;
    border-bottom:1.5px solid <?php echo esc_attr($hdr_color); ?>30;
    padding:8px 32px;
    display:flex;align-items:center;gap:.6rem;
}
.rs-badge{
    display:inline-flex;align-items:center;gap:.3rem;
    background:#16a34a;color:#fff;
    font-size:.65rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
    padding:.2rem .7rem;border-radius:20px;
}
.rs-badge::before{content:'✓';}
.rs-desc{font-size:.72rem;color:#666;}

/* ── Body ────────────────────────────── */
.rb{padding:20px 32px;}

/* Amount hero */
.amount-hero{
    text-align:center;
    padding:16px 0 18px;
    border-bottom:1px solid #eef0f6;
    margin-bottom:18px;
}
.amount-hero .lbl{
    font-size:.6rem;text-transform:uppercase;letter-spacing:.08em;
    color:#999;font-weight:600;margin-bottom:4px;
}
.amount-hero .fig{
    font-size:2.2rem;font-weight:800;
    color:<?php echo esc_attr($hdr_color); ?>;
    letter-spacing:-.02em;line-height:1;
}
.amount-hero .fn{margin-top:5px;font-size:.72rem;color:#aaa;}

/* Sections */
.sec{margin-bottom:16px;}
.sec-title{
    font-size:.6rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.1em;color:#bbb;
    margin-bottom:8px;padding-bottom:5px;
    border-bottom:1px solid #f0f0f0;
}

/* Detail grid */
.dg{display:grid;grid-template-columns:140px 1fr;gap:0;}
.dg dt{font-size:.75rem;color:#888;padding:3px 0;font-weight:500;}
.dg dd{font-size:.75rem;color:<?php echo esc_attr($body_color); ?>;padding:3px 0;font-weight:500;}
.dg dd.txn{
    font-family:'Courier New',monospace;font-size:.7rem;
    background:#f4f6ff;padding:2px 8px;border-radius:4px;
    display:inline-block;margin-top:1px;
    color:<?php echo esc_attr($hdr_color); ?>;font-weight:600;letter-spacing:.02em;
}

/* Two-column layout for details */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}

/* Items table */
.it{width:100%;border-collapse:collapse;font-size:.75rem;}
.it thead th{
    text-align:left;font-size:.6rem;font-weight:700;
    text-transform:uppercase;letter-spacing:.07em;color:#bbb;
    padding:6px 10px;background:#f8f9ff;
}
.it thead th:last-child{text-align:right;}
.it tbody tr{border-bottom:1px solid #f0f2f8;}
.it tbody td{padding:7px 10px;color:<?php echo esc_attr($body_color); ?>;}
.it tbody td:last-child{text-align:right;font-weight:600;color:<?php echo esc_attr($hdr_color); ?>;}
.it tfoot td{padding:8px 10px;font-size:.8rem;font-weight:800;color:<?php echo esc_attr($body_color); ?>;}
.it tfoot td:last-child{text-align:right;color:<?php echo esc_attr($hdr_color); ?>;font-size:.85rem;}

/* ── Footer ──────────────────────────── */
.rf{
    background:#f8f9ff;
    border-top:1px solid #eef0f6;
    padding:12px 32px;
    display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;
}
.rf-txt{font-size:.68rem;color:#bbb;}
.prt-btn{
    background:<?php echo esc_attr($hdr_color); ?>;color:#fff;
    border:none;padding:.35rem 1rem;border-radius:5px;
    font-size:.7rem;font-weight:600;cursor:pointer;
    font-family:inherit;
}
.prt-btn:hover{opacity:.85;}

/* ── Print overrides ─────────────────── */
@media print{
    html,body{background:#fff;font-size:10px;}
    .page{box-shadow:none;margin:0;border-radius:0;max-width:100%;}
    .prt-btn,.no-print{display:none!important;}
    @page{margin:8mm;size:A4;}
}
</style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="rh">
        <div class="rh-brand"><?php echo esc_html($brand); ?></div>
        <div class="rh-meta">
            <span class="rno"><?php echo esc_html($receipt_no); ?></span>
            <span class="rdt"><?php echo esc_html($date); ?></span>
        </div>
    </div>

    <!-- Status -->
    <div class="rs">
        <span class="rs-badge">Payment Successful</span>
        <span class="rs-desc">Official payment receipt — keep for your records.</span>
    </div>

    <!-- Body -->
    <div class="rb">

        <!-- Amount hero -->
        <div class="amount-hero">
            <div class="lbl">Amount Paid</div>
            <div class="fig"><?php echo esc_html($sym.$amount); ?></div>
            <div class="fn"><?php echo esc_html($form_name); ?></div>
        </div>

        <!-- Two-column: txn details + client details -->
        <div class="two-col">
            <div class="sec">
                <div class="sec-title">Transaction Details</div>
                <dl class="dg">
                    <dt>Receipt No.</dt><dd><?php echo esc_html($receipt_no); ?></dd>
                    <dt>Payment ID</dt><dd><span class="txn"><?php echo esc_html($sub->razorpay_id); ?></span></dd>
                    <dt>Status</dt><dd style="color:#16a34a;font-weight:700;">Paid</dd>
                    <dt>Date</dt><dd><?php echo esc_html($date); ?></dd>
                    <dt>Currency</dt><dd><?php echo esc_html($sub->currency ?? 'INR'); ?></dd>
                </dl>
            </div>
            <div class="sec">
                <div class="sec-title">Client Details</div>
                <dl class="dg">
                    <dt>Name</dt><dd><?php echo esc_html($name); ?></dd>
                    <dt>Email</dt><dd><?php echo esc_html($email); ?></dd>
                    <dt>Phone</dt><dd><?php echo esc_html($phone); ?></dd>
                    <?php foreach($custom as $ck=>$cv): ?>
                    <dt><?php echo esc_html($ck); ?></dt>
                    <dd><?php echo esc_html($cv); ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>

        <!-- Items table -->
        <?php if(!empty($items)): ?>
        <div class="sec">
            <div class="sec-title">Order Summary</div>
            <table class="it">
                <thead><tr><th>Item</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td><?php echo esc_html($item['name']??''); ?></td>
                        <td><?php echo esc_html($sym.number_format($item['price']??0,2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td>Total Paid</td><td><?php echo esc_html($sym.$amount); ?></td></tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>

    </div><!-- /rb -->

    <!-- Footer -->
    <div class="rf">
        <div class="rf-txt"><?php echo wp_kses_post($footer_html); ?></div>
        <button class="prt-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

</div><!-- /page -->
</body>
</html>
<?php
        exit;
    }
}
