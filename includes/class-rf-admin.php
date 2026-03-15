<?php
/**
 * RazorForms — Admin Pages
 *
 * Dashboard, submissions list, submission detail view, CSV export,
 * and About page with Digiasylum + contributor links.
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Admin {

    public static function init() {
        add_action( 'admin_menu',            array( __CLASS__, 'menus' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_rf_export',  array( __CLASS__, 'export' ) );
        // Keep the CPT edit screen highlighted under Payment Forms submenu
        add_filter( 'parent_file',     array( __CLASS__, 'fix_parent_file' ) );
        add_filter( 'submenu_file',    array( __CLASS__, 'fix_submenu_file' ) );
    }

    // Make the sidebar highlight "RazorForms" when editing a form post
    public static function fix_parent_file( $parent ) {
        global $post_type;
        if ( $post_type === RF_Post_Type::CPT ) return 'rf-dashboard';
        return $parent;
    }

    // Highlight "Payment Forms" submenu when on CPT edit/new screens
    public static function fix_submenu_file( $submenu ) {
        global $post_type, $pagenow;
        if ( $post_type === RF_Post_Type::CPT
             && in_array( $pagenow, array('edit.php','post.php','post-new.php') ) ) {
            return 'rf-forms';
        }
        return $submenu;
    }

    public static function menus() {
        // ── Main entry point → Dashboard ─────────────────────
        add_menu_page(
            'RazorForms', 'RazorForms', 'manage_options',
            'rf-dashboard',
            array( __CLASS__, 'page_about' ),
            'dashicons-money-alt', 25
        );

        // Override the auto-duplicate first submenu WordPress creates
        add_submenu_page( 'rf-dashboard', 'Dashboard', 'Dashboard', 'manage_options',
            'rf-dashboard', array( __CLASS__, 'page_about' ) );

        // Payment Forms — uses a redirect wrapper page (rf-forms slug)
        // so WordPress never confuses it with the CPT auto-menu
        add_submenu_page( 'rf-dashboard', 'Payment Forms', 'Payment Forms', 'manage_options',
            'rf-forms', array( __CLASS__, 'page_forms_redirect' ) );

        // All Submissions (CRM)
        add_submenu_page( 'rf-dashboard', 'All Submissions', 'All Submissions', 'manage_options',
            'rf-submissions', array( __CLASS__, 'page_submissions' ) );

        // Settings
        add_submenu_page( 'rf-dashboard', 'Settings', 'Settings', 'manage_options',
            'rf-settings', array( 'RF_Settings', 'page' ) );
    }

    // Redirect rf-forms to the CPT list page instantly
    public static function page_forms_redirect() {
        $url = admin_url( 'edit.php?post_type=' . RF_Post_Type::CPT );
        echo '<script>window.location.href=' . json_encode( $url ) . ';</script>';
        echo '<meta http-equiv="refresh" content="0;url=' . esc_url( $url ) . '">';
        echo '<p>Redirecting to Payment Forms… <a href="' . esc_url( $url ) . '">Click here</a> if not redirected.</p>';
    }

    public static function assets( $hook ) {
        if ( strpos($hook,'razorforms') === false && strpos($hook,'rf-') === false
             && strpos($hook, RF_Post_Type::CPT) === false ) return;
        wp_enqueue_style( 'rf-admin', RF_URL . 'assets/css/builder.css', array(), RF_VERSION );
    }

    // ── Dashboard ──────────────────────────────────────────
    public static function page_dashboard() {
        $stats  = RF_DB::get_stats();
        $forms  = get_posts( array('post_type'=>RF_Post_Type::CPT,'posts_per_page'=>5,'post_status'=>'publish','orderby'=>'date','order'=>'DESC') );
        $recent = RF_DB::get_submissions( array('per_page'=>8,'page'=>1) );
        ?>
        <div class="wrap rf-wrap">
            <div class="rf-admin-header">
                <div class="rf-admin-logo">⚡ RazorForms</div>
                <a href="<?php echo admin_url('post-new.php?post_type='.RF_Post_Type::CPT); ?>" class="button button-primary button-large">+ Create New Form</a>
            </div>

            <div class="rf-stat-row">
                <div class="rf-stat"><div class="rf-stat-n">₹<?php echo number_format($stats['revenue'],0); ?></div><div class="rf-stat-l">Total Revenue</div></div>
                <div class="rf-stat rf-stat-accent"><div class="rf-stat-n">₹<?php echo number_format($stats['month'],0); ?></div><div class="rf-stat-l">This Month</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo $stats['total']; ?></div><div class="rf-stat-l">Successful Payments</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo $stats['failed']; ?></div><div class="rf-stat-l">Failed</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo count($forms); ?></div><div class="rf-stat-l">Active Forms</div></div>
            </div>

            <div class="rf-dash-grid">
                <div class="rf-dash-card">
                    <h3>Your Forms <a href="<?php echo admin_url('edit.php?post_type='.RF_Post_Type::CPT); ?>" class="rf-card-link">View all →</a></h3>
                    <?php if ( empty($forms) ): ?>
                        <div class="rf-empty-state">
                            <span style="font-size:2.5rem">📋</span>
                            <p>No forms yet. <a href="<?php echo admin_url('post-new.php?post_type='.RF_Post_Type::CPT); ?>">Create your first form</a>.</p>
                        </div>
                    <?php else: foreach($forms as $f):
                        $fmeta  = RF_Meta_Boxes::get_meta($f->ID);
                        $fstats = RF_DB::get_stats($f->ID);
                        ?>
                        <div class="rf-form-item">
                            <div class="rf-form-item-dot" style="background:<?php echo esc_attr($fmeta['color']); ?>"></div>
                            <div class="rf-form-item-info">
                                <strong><?php echo esc_html($f->post_title); ?></strong>
                                <span><?php echo $fstats['total']; ?> payments · ₹<?php echo number_format($fstats['revenue'],0); ?></span>
                            </div>
                            <div class="rf-form-item-actions">
                                <a href="<?php echo admin_url('post.php?post='.$f->ID.'&action=edit'); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo add_query_arg('rf_preview',$f->ID,home_url()); ?>" target="_blank" class="button button-small">Preview</a>
                                <a href="<?php echo admin_url('admin.php?page=rf-submissions&form_id='.$f->ID); ?>" class="button button-small">Submissions</a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="rf-dash-card">
                    <h3>Recent Submissions <a href="<?php echo admin_url('admin.php?page=rf-submissions'); ?>" class="rf-card-link">View all →</a></h3>
                    <?php if ( empty($recent) ): ?>
                        <div class="rf-empty-state"><span style="font-size:2.5rem">📭</span><p>No submissions yet.</p></div>
                    <?php else: ?>
                    <table class="rf-mini-table">
                        <thead><tr><th>Name</th><th>Form</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach($recent as $r):
                            $fd   = json_decode($r->field_data,true) ?: array();
                            $name = $fd['_name'] ?? '—';
                            ?>
                            <tr>
                                <td><?php echo esc_html($name); ?></td>
                                <td><?php echo esc_html($r->form_name ?? '—'); ?></td>
                                <td class="rf-amount">₹<?php echo number_format($r->amount,0); ?></td>
                                <td><span class="rf-status rf-status-<?php echo esc_attr($r->status); ?>"><?php echo ucfirst($r->status); ?></span></td>
                                <td><?php echo date('d M', strtotime($r->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rf-dash-card rf-getting-started">
                <h3>🚀 Getting Started</h3>
                <div class="rf-steps-guide">
                    <div class="rf-gs-step"><span class="rf-gs-num">1</span><div><strong>Configure Razorpay</strong><br>Go to <a href="<?php echo admin_url('admin.php?page=rf-settings'); ?>">Settings</a> and enter your Razorpay Key ID.</div></div>
                    <div class="rf-gs-step"><span class="rf-gs-num">2</span><div><strong>Create a Form</strong><br>Click <a href="<?php echo admin_url('post-new.php?post_type='.RF_Post_Type::CPT); ?>">+ Create New Form</a> — items are pre-filled to get you started fast.</div></div>
                    <div class="rf-gs-step"><span class="rf-gs-num">3</span><div><strong>Embed the Shortcode</strong><br>Copy the shortcode (e.g. <code>[razorform id="1"]</code>) into any page.</div></div>
                    <div class="rf-gs-step"><span class="rf-gs-num">4</span><div><strong>Monitor Payments</strong><br>All submissions appear under <a href="<?php echo admin_url('admin.php?page=rf-submissions'); ?>">All Submissions</a>.</div></div>
                </div>
            </div>
        </div>
        <?php
    }

    // ── Submissions List ───────────────────────────────────
    public static function page_submissions() {
        if ( isset($_GET['id']) && is_numeric($_GET['id']) ) {
            self::page_submission_detail( absint($_GET['id']) );
            return;
        }
        $search  = sanitize_text_field( $_GET['s']       ?? '' );
        $status  = sanitize_text_field( $_GET['status']  ?? '' );
        $form_id = absint(              $_GET['form_id'] ?? 0  );
        $page    = max(1, absint(       $_GET['paged']   ?? 1  ));
        $pp      = 25;
        $args    = compact('search','status','form_id','page') + array('per_page'=>$pp);
        $rows    = RF_DB::get_submissions( $args );
        $total   = RF_DB::count_submissions( $args );
        $stats   = RF_DB::get_stats( $form_id );
        $pages   = ceil($total/$pp);
        $all_forms  = get_posts(array('post_type'=>RF_Post_Type::CPT,'posts_per_page'=>-1,'post_status'=>array('publish','draft')));
        $export_url = wp_nonce_url( admin_url('admin-post.php?action=rf_export&status='.urlencode($status).'&s='.urlencode($search).'&form_id='.$form_id), 'rf_export' );
        ?>
        <div class="wrap rf-wrap">
            <div class="rf-admin-header">
                <h1>📊 All Submissions <?php if($form_id): $fp=get_post($form_id); echo '<span class="rf-form-badge">'.esc_html($fp?$fp->post_title:'').'</span>'; endif; ?></h1>
                <a href="<?php echo esc_url($export_url); ?>" class="button">⬇ Export CSV</a>
            </div>
            <div class="rf-stat-row rf-stat-sm">
                <div class="rf-stat"><div class="rf-stat-n">₹<?php echo number_format($stats['revenue'],0); ?></div><div class="rf-stat-l">Revenue</div></div>
                <div class="rf-stat rf-stat-accent"><div class="rf-stat-n">₹<?php echo number_format($stats['month'],0); ?></div><div class="rf-stat-l">This Month</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo $stats['total']; ?></div><div class="rf-stat-l">Successful</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo $stats['failed']; ?></div><div class="rf-stat-l">Failed</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo $stats['pending']; ?></div><div class="rf-stat-l">Pending</div></div>
            </div>
            <form method="GET" action="" class="rf-filters">
                <input type="hidden" name="page" value="rf-submissions">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, email, TXN ID…" class="rf-search">
                <select name="form_id" class="rf-select">
                    <option value="">All Forms</option>
                    <?php foreach($all_forms as $f): ?><option value="<?php echo $f->ID; ?>" <?php selected($form_id,$f->ID); ?>><?php echo esc_html($f->post_title); ?></option><?php endforeach; ?>
                </select>
                <select name="status" class="rf-select">
                    <option value="">All Statuses</option>
                    <option value="paid"     <?php selected($status,'paid'); ?>>✅ Paid</option>
                    <option value="failed"   <?php selected($status,'failed'); ?>>❌ Failed</option>
                    <option value="pending"  <?php selected($status,'pending'); ?>>⏳ Pending</option>
                    <option value="refunded" <?php selected($status,'refunded'); ?>>↩ Refunded</option>
                </select>
                <button type="submit" class="button button-primary">Filter</button>
                <?php if($search||$status||$form_id): ?><a href="<?php echo admin_url('admin.php?page=rf-submissions'); ?>" class="button">Clear</a><?php endif; ?>
                <span class="rf-count"><?php echo $total; ?> submissions</span>
            </form>
            <table class="rf-table widefat">
                <thead><tr><th>#</th><th>Date</th><th>Name</th><th>Email</th><th>Phone</th><th>Form</th><th>Amount</th><th>TXN ID</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if(empty($rows)): ?>
                    <tr><td colspan="10" class="rf-empty">No submissions found.</td></tr>
                <?php else: foreach($rows as $r):
                    $fd    = json_decode($r->field_data,true) ?: array();
                    $name  = $fd['_name']  ?? '—';
                    $email = $fd['_email'] ?? '—';
                    $phone = $fd['_phone'] ?? '—';
                    ?>
                    <tr>
                        <td><strong>#<?php echo $r->id; ?></strong></td>
                        <td><span class="rf-date"><?php echo date('d M Y', strtotime($r->created_at)); ?></span><span class="rf-time"><?php echo date('h:i A', strtotime($r->created_at)); ?></span></td>
                        <td><strong><?php echo esc_html($name); ?></strong></td>
                        <td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td>
                        <td><?php echo esc_html($phone); ?></td>
                        <td><span class="rf-form-badge-sm"><?php echo esc_html($r->form_name ?? '—'); ?></span></td>
                        <td class="rf-amount">₹<?php echo number_format($r->amount,2); ?></td>
                        <td><code class="rf-txn"><?php echo esc_html($r->razorpay_id ?: '—'); ?></code></td>
                        <td><span class="rf-status rf-status-<?php echo esc_attr($r->status); ?>"><?php echo ucfirst($r->status); ?></span></td>
                        <td style="white-space:nowrap;">
                            <a href="<?php echo admin_url('admin.php?page=rf-submissions&id='.$r->id); ?>" class="button button-small">View</a>
                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=rf_receipt&id='.$r->id)); ?>" class="button button-small" target="_blank" title="View Receipt">🖨️</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php if($pages>1): ?>
            <div class="rf-pagination">
                <?php $base = admin_url('admin.php?page=rf-submissions');
                if($search)  $base.='&s='.urlencode($search);
                if($status)  $base.='&status='.urlencode($status);
                if($form_id) $base.='&form_id='.$form_id;
                for($i=1;$i<=$pages;$i++): ?><a href="<?php echo $base.'&paged='.$i; ?>" class="rf-pg <?php if($i===$page) echo 'rf-pg-active'; ?>"><?php echo $i; ?></a><?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // ── Submission Detail ──────────────────────────────────
    private static function page_submission_detail( $id ) {
        $sub = RF_DB::get_submission($id);
        if (!$sub) { echo '<div class="wrap"><div class="notice notice-error"><p>Submission not found.</p></div></div>'; return; }
        $fd = $sub->field_data;
        ?>
        <div class="wrap rf-wrap">
            <div class="rf-admin-header">
                <div><a href="<?php echo admin_url('admin.php?page=rf-submissions'); ?>" class="rf-back">← All Submissions</a><h1>Submission #<?php echo $sub->id; ?></h1></div>
                <div style="display:flex;align-items:center;gap:.65rem;">
                    <span class="rf-status rf-status-<?php echo esc_attr($sub->status); ?>" style="font-size:.9rem;padding:.3rem .9rem"><?php echo ucfirst($sub->status); ?></span>
                    <a href="<?php echo esc_url( admin_url('admin-post.php?action=rf_receipt&id='.$sub->id) ); ?>"
                       target="_blank" class="button" style="display:flex;align-items:center;gap:.35rem;">
                       🖨️ View Receipt
                    </a>
                </div>
            </div>
            <div class="rf-detail-grid">
                <div class="rf-detail-card">
                    <h3>👤 Contact Information</h3>
                    <table class="rf-dt">
                        <tr><td>Name</td><td><strong><?php echo esc_html($fd['_name']??'—'); ?></strong></td></tr>
                        <tr><td>Email</td><td><a href="mailto:<?php echo esc_attr($fd['_email']??''); ?>"><?php echo esc_html($fd['_email']??'—'); ?></a></td></tr>
                        <tr><td>Phone</td><td><?php echo esc_html($fd['_phone']??'—'); ?></td></tr>
                        <tr><td>IP</td><td><?php echo esc_html($sub->ip_address??'—'); ?></td></tr>
                    </table>
                </div>
                <div class="rf-detail-card">
                    <h3>💳 Payment Details</h3>
                    <table class="rf-dt">
                        <tr><td>Razorpay ID</td><td><code><?php echo esc_html($sub->razorpay_id ?: '—'); ?></code></td></tr>
                        <tr><td>Order Ref</td><td><code><?php echo esc_html($sub->order_ref); ?></code></td></tr>
                        <tr><td>Amount</td><td><strong style="color:#295cff;font-size:1.1rem">₹<?php echo number_format($sub->amount,2); ?></strong></td></tr>
                        <tr><td>Currency</td><td><?php echo esc_html($sub->currency); ?></td></tr>
                        <tr><td>Form</td><td><?php echo esc_html($sub->form_name??'—'); ?></td></tr>
                        <tr><td>Date</td><td><?php echo date('d M Y, h:i A', strtotime($sub->created_at)); ?></td></tr>
                    </table>
                </div>
                <?php if(!empty($fd['_selected_items'])): ?>
                <div class="rf-detail-card rf-span2">
                    <h3>🛒 Selected Items</h3>
                    <table class="rf-dt">
                        <thead><tr><th style="text-align:left">Item</th><th style="text-align:right">Price</th></tr></thead>
                        <tbody><?php foreach($fd['_selected_items'] as $item): ?><tr><td style="text-align:left"><?php echo esc_html($item['name']??''); ?></td><td style="text-align:right">₹<?php echo number_format($item['price']??0,2); ?></td></tr><?php endforeach; ?></tbody>
                        <tfoot><tr><td style="text-align:left"><strong>Total</strong></td><td style="text-align:right"><strong>₹<?php echo number_format($sub->amount,2); ?></strong></td></tr></tfoot>
                    </table>
                </div>
                <?php endif;
                $custom = array_filter($fd, function($k){ return substr($k,0,1) !== '_'; }, ARRAY_FILTER_USE_KEY);
                if (!empty($custom)): ?>
                <div class="rf-detail-card rf-span2">
                    <h3>📝 Form Responses</h3>
                    <table class="rf-dt">
                        <?php foreach($custom as $label=>$val): ?><tr><td><?php echo esc_html($label); ?></td><td><?php echo esc_html(is_array($val) ? implode(', ',$val) : $val); ?></td></tr><?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // ── About / Home Page ─────────────────────────────────────
    public static function page_about() {
        $stats  = RF_DB::get_stats();
        $forms  = get_posts( array('post_type'=>RF_Post_Type::CPT,'posts_per_page'=>-1,'post_status'=>'publish') );
        ?>
        <div class="wrap rf-wrap">

            <!-- Header -->
            <div class="rf-admin-header">
                <div class="rf-admin-logo">⚡ RazorForms</div>
                <a href="<?php echo admin_url('post-new.php?post_type='.RF_Post_Type::CPT); ?>" class="button button-primary button-large">+ Create New Form</a>
            </div>

            <!-- Stats row -->
            <div class="rf-stat-row">
                <div class="rf-stat"><div class="rf-stat-n">₹<?php echo number_format($stats['revenue'],0); ?></div><div class="rf-stat-l">Total Revenue</div></div>
                <div class="rf-stat rf-stat-accent"><div class="rf-stat-n">₹<?php echo number_format($stats['month'],0); ?></div><div class="rf-stat-l">This Month</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo $stats['total']; ?></div><div class="rf-stat-l">Successful Payments</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo $stats['failed']; ?></div><div class="rf-stat-l">Failed</div></div>
                <div class="rf-stat"><div class="rf-stat-n"><?php echo count($forms); ?></div><div class="rf-stat-l">Active Forms</div></div>
            </div>

            <!-- Quick links -->
            <div class="rf-dash-grid" style="margin-bottom:1.25rem;">
                <div class="rf-dash-card" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                    <div>
                        <strong style="font-size:.95rem;">Payment Forms</strong>
                        <p style="margin:.2rem 0 0;color:#888;font-size:.8rem;"><?php echo count($forms); ?> form(s) published</p>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                        <a href="<?php echo admin_url('post-new.php?post_type='.RF_Post_Type::CPT); ?>" class="button button-primary">+ New Form</a>
                        <a href="<?php echo admin_url('edit.php?post_type='.RF_Post_Type::CPT); ?>" class="button">All Forms</a>
                    </div>
                </div>
                <div class="rf-dash-card" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                    <div>
                        <strong style="font-size:.95rem;">Submissions</strong>
                        <p style="margin:.2rem 0 0;color:#888;font-size:.8rem;"><?php echo $stats['total']; ?> successful payment(s)</p>
                    </div>
                    <a href="<?php echo admin_url('admin.php?page=rf-submissions'); ?>" class="button">View All →</a>
                </div>
            </div>

            <!-- About / Credits -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

                <div class="rf-dash-card" style="grid-column:1/-1;display:flex;align-items:center;gap:2rem;padding:2rem;background:linear-gradient(135deg,#f0f4ff 0%,#fff 100%);border-color:#c8d4ff;">
                    <div style="font-size:3.5rem;line-height:1;flex-shrink:0;">⚡</div>
                    <div>
                        <h2 style="margin:0 0 .4rem;font-size:1.4rem;color:#295cff;">RazorForms <span style="font-size:.8rem;font-weight:400;color:#999;">v<?php echo RF_VERSION; ?></span></h2>
                        <p style="margin:0 0 .8rem;color:#555;font-size:.88rem;line-height:1.6;">No-code Razorpay payment page builder for WordPress. Accept payments directly on your website — zero coding required.</p>
                        <a href="https://www.digiasylum.com/" target="_blank" class="button button-primary">Visit Digiasylum.com ↗</a>
                    </div>
                </div>

                <div class="rf-dash-card">
                    <h3 style="color:#295cff;font-size:.9rem;text-transform:uppercase;letter-spacing:.06em;margin:0 0 1rem;">👤 Author &amp; Contributor</h3>
                    <div style="display:flex;flex-direction:column;gap:.85rem;">

                        <!-- Digiasylum — entire row is clickable -->
                        <a href="https://www.digiasylum.com/" target="_blank"
                           style="display:flex;align-items:center;gap:.85rem;text-decoration:none;padding:.65rem .75rem;border-radius:8px;border:1.5px solid #e4e7ef;transition:border-color .15s,background .15s;"
                           onmouseover="this.style.borderColor='#295cff';this.style.background='#f0f4ff'"
                           onmouseout="this.style.borderColor='#e4e7ef';this.style.background='#fff'">
                            <div style="width:42px;height:42px;background:#295cff;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.95rem;font-weight:700;flex-shrink:0;">DA</div>
                            <div>
                                <strong style="display:block;font-size:.9rem;color:#1a1a1a;">Digiasylum</strong>
                                <span style="font-size:.76rem;color:#295cff;">www.digiasylum.com ↗</span>
                            </div>
                        </a>

                        <!-- Umesh Kumar Sahai — entire row is clickable -->
                        <a href="https://linkedin.com/in/umeshkumarsahai" target="_blank"
                           style="display:flex;align-items:center;gap:.85rem;text-decoration:none;padding:.65rem .75rem;border-radius:8px;border:1.5px solid #e4e7ef;transition:border-color .15s,background .15s;"
                           onmouseover="this.style.borderColor='#0077b5';this.style.background='#f0f8ff'"
                           onmouseout="this.style.borderColor='#e4e7ef';this.style.background='#fff'">
                            <div style="width:42px;height:42px;background:#0077b5;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;font-weight:700;flex-shrink:0;">in</div>
                            <div>
                                <strong style="display:block;font-size:.9rem;color:#1a1a1a;">Umesh Kumar Sahai</strong>
                                <span style="font-size:.76rem;color:#0077b5;">linkedin.com/in/umeshkumarsahai ↗</span>
                            </div>
                        </a>

                    </div>
                </div>

                <div class="rf-dash-card">
                    <h3 style="color:#295cff;font-size:.9rem;text-transform:uppercase;letter-spacing:.06em;margin:0 0 1rem;">💛 Support &amp; Donate</h3>
                    <p style="color:#666;font-size:.83rem;margin:0 0 1rem;line-height:1.6;">RazorForms is free to use. If it saves you time and helps your business grow, consider supporting the developer.</p>
                    <a href="https://about.me/umeshkumarsahai" target="_blank" class="button button-primary" style="display:block;text-align:center;margin-bottom:.6rem;">💛 Support the Developer ↗</a>
                    <a href="https://www.digiasylum.com/" target="_blank" class="button" style="display:block;text-align:center;">🌐 Digiasylum Website ↗</a>
                </div>

                <div class="rf-dash-card" style="grid-column:1/-1;">
                    <h3 style="font-size:.9rem;text-transform:uppercase;letter-spacing:.06em;margin:0 0 1rem;">🚀 Getting Started</h3>
                    <div class="rf-steps-guide">
                        <div class="rf-gs-step"><span class="rf-gs-num">1</span><div><strong>Configure Razorpay</strong><br>Go to <a href="<?php echo admin_url('admin.php?page=rf-settings'); ?>">Settings</a> and enter your Razorpay Key ID &amp; Secret.</div></div>
                        <div class="rf-gs-step"><span class="rf-gs-num">2</span><div><strong>Create a Form</strong><br>Click <a href="<?php echo admin_url('post-new.php?post_type='.RF_Post_Type::CPT); ?>">+ Create New Form</a> — pre-filled with a ready-to-edit service template.</div></div>
                        <div class="rf-gs-step"><span class="rf-gs-num">3</span><div><strong>Embed the Shortcode</strong><br>Copy the shortcode (e.g. <code>[razorform id="1"]</code>) and paste into any page.</div></div>
                        <div class="rf-gs-step"><span class="rf-gs-num">4</span><div><strong>Monitor Payments</strong><br>All submissions and revenue appear under <a href="<?php echo admin_url('admin.php?page=rf-submissions'); ?>">All Submissions</a>.</div></div>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }

    // ── CSV Export ─────────────────────────────────────────
    public static function export() {
        if (!current_user_can('manage_options')) wp_die('Unauthorised');
        check_admin_referer('rf_export');
        $rows = RF_DB::get_submissions(array(
            'status'  => sanitize_text_field($_GET['status']??''),
            'search'  => sanitize_text_field($_GET['s']??''),
            'form_id' => absint($_GET['form_id']??0),
            'per_page'=> 9999, 'page'=>1,
        ));
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="razorforms-submissions-'.date('Y-m-d').'.csv"');
        header('Pragma: no-cache');
        $out = fopen('php://output','w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, array('ID','Date','Form','Name','Email','Phone','Amount','Currency','Razorpay TXN ID','Status','Custom Fields','Order Ref'));
        foreach($rows as $r) {
            $fd = json_decode($r->field_data,true) ?: array();
            $custom = array_filter($fd, function($k){ return substr($k,0,1)!=='_'; }, ARRAY_FILTER_USE_KEY);
            $custom_str = implode(' | ', array_map(function($k,$v){ return "$k: ".(is_array($v)?implode(',',$v):$v); }, array_keys($custom), $custom));
            fputcsv($out, array($r->id,$r->created_at,$r->form_name??'',$fd['_name']??'',$fd['_email']??'',$fd['_phone']??'',$r->amount,$r->currency,$r->razorpay_id,$r->status,$custom_str,$r->order_ref));
        }
        fclose($out); exit;
    }
}
