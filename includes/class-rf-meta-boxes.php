<?php
/**
 * RazorForms — Form Builder Meta Boxes
 *
 * 4-tab visual form builder: Design · Item List · Form Fields · Settings
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 * @link     https://www.digiasylum.com
 * @since    1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class RF_Meta_Boxes {

    public static function init() {
        add_action( 'add_meta_boxes',        array( __CLASS__, 'register' ) );
        add_action( 'save_post',             array( __CLASS__, 'save' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
    }

    public static function enqueue( $hook ) {
        global $post;
        if ( ! in_array( $hook, array('post.php','post-new.php') ) ) return;
        if ( ! $post || $post->post_type !== RF_Post_Type::CPT ) return;

        wp_enqueue_style(  'rf-builder', RF_URL . 'assets/css/builder.css', array(), RF_VERSION );
        wp_enqueue_script( 'rf-builder', RF_URL . 'assets/js/builder.js', array('jquery','wp-color-picker'), RF_VERSION, true );
        wp_enqueue_style(  'wp-color-picker' );
        wp_localize_script( 'rf-builder', 'RF_BUILDER', array(
            'nonce'       => wp_create_nonce('rf_builder'),
            'preview_url' => add_query_arg('rf_preview', $post->ID, home_url()),
        ));
    }

    public static function register() {
        add_meta_box( 'rf_form_builder', '⚡ Form Builder', array(__CLASS__,'box_builder'), RF_Post_Type::CPT, 'normal', 'high' );
        add_meta_box( 'rf_form_preview', '👁 Live Preview', array(__CLASS__,'box_preview'), RF_Post_Type::CPT, 'side',   'high' );
        add_meta_box( 'rf_form_code',    '📋 Shortcode',    array(__CLASS__,'box_shortcode'),RF_Post_Type::CPT, 'side',   'default' );
    }

    // ══════════════════════════════════════════════════════
    //  BUILDER
    // ══════════════════════════════════════════════════════
    public static function box_builder( $post ) {
        wp_nonce_field( 'rf_save_form', 'rf_nonce' );
        $meta = self::get_meta( $post->ID );
        ?>
        <div class="rf-builder-wrap" id="rfBuilderWrap">

            <!-- 4 TABS (Templates tab removed) -->
            <div class="rf-tabs">
                <button type="button" class="rf-tab active" data-tab="design">🎨 Design</button>
                <button type="button" class="rf-tab" data-tab="pricing">💳 Item List</button>
                <button type="button" class="rf-tab" data-tab="fields">📝 Form Fields</button>
                <button type="button" class="rf-tab" data-tab="settings">⚙️ Settings</button>
            </div>

            <!-- ── DESIGN ─────────────────────────────────── -->
            <div class="rf-tab-panel active" data-panel="design">
                <div class="rf-panel-grid">

                    <div class="rf-field-group">
                        <label>Page Title <span class="rf-req">*</span></label>
                        <input type="text" name="rf_title" value="<?php echo esc_attr($meta['title']); ?>" placeholder="e.g. Book a Consultation" class="rf-input-lg">
                        <span class="rf-hint">Main headline shown at the top of the payment page</span>
                    </div>

                    <div class="rf-field-group">
                        <label>Subtitle</label>
                        <input type="text" name="rf_subtitle" value="<?php echo esc_attr($meta['subtitle']); ?>" placeholder="e.g. Fill in your details to get started">
                    </div>

                    <div class="rf-field-group rf-full">
                        <label>Description / Body Content</label>
                        <textarea name="rf_description" rows="7"
                            placeholder="Describe your service, what the client gets, any terms…&#10;&#10;Use blank lines for paragraphs. Use • for bullet points."
                        ><?php echo esc_textarea($meta['description']); ?></textarea>
                        <span class="rf-hint">Tip: Use blank lines for new paragraphs. Use • characters for bullet lists. Plain text only.</span>
                    </div>

                    <div class="rf-field-group">
                        <label>Primary Color</label>
                        <input type="text" name="rf_color" value="<?php echo esc_attr($meta['color']); ?>" class="rf-color-picker" data-default-color="#295cff">
                    </div>

                    <div class="rf-field-group">
                        <label>Button Label</label>
                        <input type="text" name="rf_btn_label" value="<?php echo esc_attr($meta['btn_label']); ?>" placeholder="Pay Now">
                    </div>

                    <!-- Layout: always Split — hidden input, no UI shown -->
                    <input type="hidden" name="rf_layout" value="split">

                </div>
            </div>

            <!-- ── ITEM LIST ──────────────────────────────── -->
            <div class="rf-tab-panel" data-panel="pricing">
                <input type="hidden" name="rf_price_mode" value="items">
                <div class="rf-panel-grid">

                    <div class="rf-field-group rf-full">
                        <label>Service / Package Items</label>
                        <p class="rf-panel-desc" style="margin:0 0 .85rem;">
                            Add each service or package. Set a fixed price — or enable <strong>✏️ Custom Price</strong> so the client can enter their own amount for that item on the payment page.
                        </p>

                        <div class="rf-items-list" id="rfItemsList">
                            <?php
                            $items = $meta['items'] ?: array();
                            foreach ( $items as $i => $item ) :
                                self::render_item_row( $i, $item );
                            endforeach;
                            ?>
                        </div>

                        <button type="button" class="rf-btn-secondary" id="rfAddItem">+ Add Item</button>
                        <p class="rf-hint" style="margin-top:.6rem;">
                            <strong>✏️ Custom Price ON</strong>: price field is dimmed — the client enters their own amount directly on the payment form.<br>
                            <strong>✏️ Custom Price OFF</strong>: your fixed price is shown and charged.
                        </p>
                        <input type="hidden" name="rf_items_json" id="rfItemsJson" value="<?php echo esc_attr(json_encode($items)); ?>">
                    </div>

                    <div class="rf-field-group">
                        <label>Currency</label>
                        <select name="rf_currency">
                            <?php foreach( array('INR'=>'₹ INR – Indian Rupee','USD'=>'$ USD – US Dollar','EUR'=>'€ EUR – Euro','GBP'=>'£ GBP – British Pound','SGD'=>'S$ SGD – Singapore Dollar','AED'=>'AED – UAE Dirham') as $code=>$label ): ?>
                                <option value="<?php echo $code; ?>" <?php selected($meta['currency'],$code); ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>

            <!-- ── FORM FIELDS ────────────────────────────── -->
            <div class="rf-tab-panel" data-panel="fields">
                <p class="rf-panel-desc">Core fields (Name, Email, Phone) are always collected. Edit their labels and placeholders below, then add any extra fields you need.</p>

                <!-- ── Core fields — editable labels/placeholders ── -->
                <div class="rf-core-fields-section">
                    <div class="rf-core-fields-header">
                        <span>🔒 Core Fields — Always Included</span>
                        <span class="rf-hint" style="font-weight:normal;">You can edit labels and placeholder text</span>
                    </div>
                    <div class="rf-core-fields-grid">
                        <?php
                        $coreFields = $meta['core_fields'] ?? array();
                        $coreDefaults = array(
                            'name'  => array('label'=>'Full Name',      'placeholder'=>'Your name',       'icon'=>'👤'),
                            'email' => array('label'=>'Email Address',  'placeholder'=>'you@example.com', 'icon'=>'📧'),
                            'phone' => array('label'=>'Phone',          'placeholder'=>'+91 98765 43210', 'icon'=>'📱'),
                        );
                        foreach( $coreDefaults as $key => $def ):
                            $savedLabel = $coreFields[$key]['label']       ?? $def['label'];
                            $savedPh    = $coreFields[$key]['placeholder'] ?? $def['placeholder'];
                            $savedReq   = isset($coreFields[$key]['required']) ? (bool)$coreFields[$key]['required'] : true;
                        ?>
                        <div class="rf-core-field-edit">
                            <div class="rf-core-field-badge"><?php echo $def['icon']; ?></div>
                            <div class="rf-core-field-inputs">
                                <div class="rf-core-field-row">
                                    <label>Label</label>
                                    <input type="text" name="rf_core_<?php echo $key; ?>_label" value="<?php echo esc_attr($savedLabel); ?>" placeholder="<?php echo esc_attr($def['label']); ?>">
                                </div>
                                <div class="rf-core-field-row">
                                    <label>Placeholder</label>
                                    <input type="text" name="rf_core_<?php echo $key; ?>_ph" value="<?php echo esc_attr($savedPh); ?>" placeholder="<?php echo esc_attr($def['placeholder']); ?>">
                                </div>
                            </div>
                            <label class="rf-core-req-toggle" title="Toggle required">
                                <input type="checkbox" name="rf_core_<?php echo $key; ?>_required" value="1" <?php checked($savedReq); ?>>
                                <span class="rf-core-req-badge <?php echo $savedReq ? 'is-req' : 'is-opt'; ?>">
                                    <?php echo $savedReq ? 'Required' : 'Optional'; ?>
                                </span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ── Custom fields ── -->
                <div class="rf-custom-fields-header">
                    <span>➕ Additional Fields</span>
                    <span class="rf-hint" style="font-weight:normal;">Drag to reorder</span>
                </div>
                <div class="rf-fields-canvas" id="rfFieldsCanvas">
                    <?php
                    $fields = $meta['fields'] ?: array();
                    foreach ( $fields as $fi => $field ) {
                        self::render_field_row( $fi, $field );
                    }
                    ?>
                </div>

                <div class="rf-add-field-bar">
                    <span class="rf-add-label">Add Field:</span>
                    <button type="button" class="rf-add-field-btn" data-type="text">+ Text</button>
                    <button type="button" class="rf-add-field-btn" data-type="textarea">+ Textarea</button>
                    <button type="button" class="rf-add-field-btn" data-type="select">+ Dropdown</button>
                    <button type="button" class="rf-add-field-btn" data-type="checkbox">+ Checkbox</button>
                    <button type="button" class="rf-add-field-btn" data-type="radio">+ Radio</button>
                    <button type="button" class="rf-add-field-btn" data-type="date">+ Date</button>
                    <button type="button" class="rf-add-field-btn" data-type="number">+ Number</button>
                    <button type="button" class="rf-add-field-btn" data-type="url">+ URL</button>
                </div>
                <input type="hidden" name="rf_fields_json" id="rfFieldsJson" value="<?php echo esc_attr(json_encode($fields)); ?>">
            </div>

            <!-- ── SETTINGS ───────────────────────────────── -->
            <div class="rf-tab-panel" data-panel="settings">
                <div class="rf-panel-grid">

                    <div class="rf-field-group">
                        <label>Thank-You / Redirect URL</label>
                        <input type="url" name="rf_thankyou" value="<?php echo esc_attr($meta['thankyou']); ?>" placeholder="https://example.com/thank-you">
                        <span class="rf-hint">Leave blank to show the built-in success screen</span>
                    </div>

                    <div class="rf-field-group">
                        <label>Success Message</label>
                        <textarea name="rf_success_msg" rows="3" placeholder="Thank you! We'll be in touch shortly."><?php echo esc_textarea($meta['success_msg']); ?></textarea>
                    </div>

                    <div class="rf-field-group rf-full">
                        <label>Options</label>
                        <div class="rf-checkboxes">
                            <label><input type="checkbox" name="rf_send_client_email"  value="1" <?php checked($meta['send_client_email']??true); ?>> Send payment confirmation email to client</label>
                            <label><input type="checkbox" name="rf_show_order_summary" value="1" <?php checked($meta['show_order_summary']??true); ?>> Show order summary before payment</label>
                            <label><input type="checkbox" name="rf_show_trust_badges"  value="1" <?php checked($meta['show_trust_badges']??true); ?>> Show trust badges (SSL · Secure · No hidden charges)</label>
                        </div>
                    </div>

                    <input type="hidden" name="rf_use_global_key" value="1">
                </div>
            </div>

        </div><!-- /builder-wrap -->
        <?php
    }

    // ── Render one item row ────────────────────────────────
    private static function render_item_row( $i, $item ) {
        $custom = !empty($item['custom_price']);
        ?>
        <div class="rf-item-row" data-index="<?php echo $i; ?>">
            <span class="rf-item-drag" title="Drag to reorder">⠿</span>
            <input type="text"   class="rf-item-name"  name="rf_items[<?php echo $i; ?>][name]"  value="<?php echo esc_attr($item['name']??''); ?>"  placeholder="Service name">
            <input type="text"   class="rf-item-desc"  name="rf_items[<?php echo $i; ?>][desc]"  value="<?php echo esc_attr($item['desc']??''); ?>"  placeholder="Short description (optional)">
            <div class="rf-item-price-col">
                <input type="number" class="rf-item-price" name="rf_items[<?php echo $i; ?>][price]" value="<?php echo esc_attr($item['price']??''); ?>" placeholder="₹ Price" min="0" <?php echo $custom ? 'disabled class="rf-item-price rf-price-disabled"' : ''; ?>>
                <label class="rf-custom-price-toggle <?php echo $custom ? 'is-active' : ''; ?>" title="Let client enter their own amount on payment page">
                    <input type="checkbox" class="rf-custom-price-cb" name="rf_items[<?php echo $i; ?>][custom_price]" value="1" <?php checked($custom); ?>>
                    <span class="rf-cpt-inner"><span class="rf-cpt-icon">✏️</span><span class="rf-cpt-label">Custom Price</span></span>
                </label>
            </div>
            <button type="button" class="rf-remove-item" title="Remove">✕</button>
        </div>
        <?php
    }

    // ── Render a custom field row ──────────────────────────
    private static function render_field_row( $index, $field ) {
        $type  = $field['type']        ?? 'text';
        $label = $field['label']       ?? '';
        $req   = $field['required']    ?? false;
        $ph    = $field['placeholder'] ?? '';
        $opts  = $field['options']     ?? '';
        $width = $field['width']       ?? 'full';

        $type_icons = array(
            'text'=>'T', 'textarea'=>'¶', 'select'=>'▾', 'checkbox'=>'☑',
            'radio'=>'◉', 'date'=>'📅', 'number'=>'#', 'url'=>'🔗',
        );
        $icon = $type_icons[$type] ?? 'T';
        ?>
        <div class="rf-field-row" data-index="<?php echo $index; ?>" data-type="<?php echo esc_attr($type); ?>">
            <!-- Row 1: drag + icon + label input + controls -->
            <div class="rf-field-top-bar">
                <span class="rf-item-drag rf-field-handle" title="Drag to reorder">⠿</span>
                <span class="rf-field-type-badge"><?php echo esc_html(strtoupper($type)); ?></span>
                <input type="text" class="rf-field-label-input" value="<?php echo esc_attr($label); ?>" placeholder="Field Label">
                <div class="rf-field-controls">
                    <select class="rf-field-width" title="Width">
                        <option value="full" <?php selected($width,'full'); ?>>Full width</option>
                        <option value="half" <?php selected($width,'half'); ?>>Half width</option>
                    </select>
                    <label class="rf-core-req-toggle rf-field-req-wrap" title="Toggle required">
                        <input type="checkbox" class="rf-field-required" <?php checked($req); ?>>
                        <span class="rf-core-req-badge <?php echo $req ? 'is-req' : 'is-opt'; ?>">
                            <?php echo $req ? 'Required' : 'Optional'; ?>
                        </span>
                    </label>
                    <button type="button" class="rf-remove-item rf-field-remove" title="Remove field">✕</button>
                </div>
            </div>
            <!-- Row 2: placeholder + options -->
            <div class="rf-field-inputs">
                <div class="rf-core-field-row">
                    <label>Placeholder</label>
                    <input type="text" class="rf-field-placeholder" value="<?php echo esc_attr($ph); ?>" placeholder="Placeholder text (optional)">
                </div>
                <?php if ( in_array($type, array('select','checkbox','radio')) ) : ?>
                <div class="rf-core-field-row" style="align-items:flex-start;margin-top:.35rem;">
                    <label style="padding-top:.4rem;">Options</label>
                    <div style="flex:1;">
                        <textarea class="rf-field-options" placeholder="Option 1&#10;Option 2&#10;Option 3"><?php echo esc_textarea($opts); ?></textarea>
                        <span class="rf-hint">One option per line</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // ── Sidebar boxes ──────────────────────────────────────
    public static function box_preview( $post ) {
        echo '<div style="background:#f5f5f5;border-radius:8px;padding:1rem;text-align:center;color:#888;font-size:.85rem;min-height:120px;display:flex;align-items:center;justify-content:center;">';
        echo '<div><span style="font-size:2rem">👁</span><br>Fill in the Design tab<br>then save to see a live preview</div>';
        echo '</div>';
        if ( $post->post_status === 'publish' ) {
            $url = add_query_arg('rf_preview', $post->ID, home_url());
            echo '<a href="'.esc_url($url).'" target="_blank" class="button button-secondary" style="margin-top:.75rem;width:100%;text-align:center;display:block;">Open Live Preview ↗</a>';
        }
    }

    public static function box_shortcode( $post ) {
        if ( $post->post_status !== 'publish' ) {
            echo '<p style="color:#888;font-size:.85rem">Save &amp; publish the form to get its shortcode.</p>';
            return;
        }
        $sc = '[razorform id="'.$post->ID.'"]';
        echo '<div style="background:#f0f4ff;border:1px solid #c8d4ff;border-radius:6px;padding:.75rem 1rem;">';
        echo '<code style="font-size:.9rem;color:#295cff;font-weight:600;">'.esc_html($sc).'</code>';
        echo '</div>';
        echo '<p style="margin:.5rem 0 0;font-size:.78rem;color:#888;">Paste into any page or post. Works with Elementor, Gutenberg, and Classic Editor.</p>';
    }

    // ══════════════════════════════════════════════════════
    //  SAVE
    // ══════════════════════════════════════════════════════
    public static function save( $post_id ) {
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) ) return;
        if ( ! isset($_POST['rf_nonce']) || ! wp_verify_nonce($_POST['rf_nonce'],'rf_save_form') ) return;
        if ( ! current_user_can('edit_post', $post_id) ) return;
        if ( get_post_type($post_id) !== RF_Post_Type::CPT ) return;

        remove_action( 'save_post', array( __CLASS__, 'save' ) );

        // Items
        $items_raw   = json_decode( stripslashes( $_POST['rf_items_json'] ?? '[]' ), true ) ?: array();
        $items_clean = array();
        foreach ( $items_raw as $item ) {
            $items_clean[] = array(
                'name'         => sanitize_text_field( $item['name']  ?? '' ),
                'desc'         => sanitize_text_field( $item['desc']  ?? '' ),
                'price'        => floatval( $item['price'] ?? 0 ),
                'custom_price' => (bool)( $item['custom_price'] ?? false ),
            );
        }

        // Custom fields
        $fields_raw   = json_decode( stripslashes( $_POST['rf_fields_json'] ?? '[]' ), true ) ?: array();
        $fields_clean = array();
        foreach ( $fields_raw as $f ) {
            $fields_clean[] = array(
                'type'        => sanitize_key( $f['type'] ?? 'text' ),
                'label'       => sanitize_text_field( $f['label'] ?? '' ),
                'placeholder' => sanitize_text_field( $f['placeholder'] ?? '' ),
                'required'    => (bool)( $f['required'] ?? false ),
                'options'     => sanitize_textarea_field( $f['options'] ?? '' ),
                'width'       => in_array($f['width']??'full', array('full','half')) ? $f['width'] : 'full',
            );
        }

        // Core field customisations
        $core_fields = array(
            'name'  => array(
                'label'       => sanitize_text_field( $_POST['rf_core_name_label']  ?? 'Full Name' ),
                'placeholder' => sanitize_text_field( $_POST['rf_core_name_ph']     ?? 'Your name' ),
                'required'    => isset( $_POST['rf_core_name_required'] )  ? true : false,
            ),
            'email' => array(
                'label'       => sanitize_text_field( $_POST['rf_core_email_label'] ?? 'Email Address' ),
                'placeholder' => sanitize_text_field( $_POST['rf_core_email_ph']    ?? 'you@example.com' ),
                'required'    => isset( $_POST['rf_core_email_required'] ) ? true : false,
            ),
            'phone' => array(
                'label'       => sanitize_text_field( $_POST['rf_core_phone_label'] ?? 'Phone' ),
                'placeholder' => sanitize_text_field( $_POST['rf_core_phone_ph']    ?? '+91 98765 43210' ),
                'required'    => isset( $_POST['rf_core_phone_required'] ) ? true : false,
            ),
        );

        $meta = array(
            'title'             => sanitize_text_field(     $_POST['rf_title']          ?? '' ),
            'subtitle'          => sanitize_text_field(     $_POST['rf_subtitle']       ?? '' ),
            'description'       => sanitize_textarea_field( $_POST['rf_description']    ?? '' ),
            'color'             => sanitize_hex_color(      $_POST['rf_color']          ?? '#295cff' ) ?: '#295cff',
            'btn_label'         => sanitize_text_field(     $_POST['rf_btn_label']      ?? 'Pay Now' ),
            'layout'            => 'split',
            'price_mode'        => 'items',
            'currency'          => sanitize_text_field(     $_POST['rf_currency']       ?? 'INR' ),
            'items'             => $items_clean,
            'fields'            => $fields_clean,
            'core_fields'       => $core_fields,
            'thankyou'          => esc_url_raw(             $_POST['rf_thankyou']       ?? '' ),
            'success_msg'       => sanitize_textarea_field( $_POST['rf_success_msg']    ?? '' ),
            'use_global_key'    => 1,
            'rzp_key'           => '',
            'send_client_email' => isset($_POST['rf_send_client_email'])  ? 1 : 0,
            'show_order_summary'=> isset($_POST['rf_show_order_summary']) ? 1 : 0,
            'show_trust_badges' => isset($_POST['rf_show_trust_badges'])  ? 1 : 0,
            'brand'             => get_bloginfo('name'),
            'logo'              => '',
            'notify_email'      => get_option('rf_notify_email', get_option('admin_email')),
        );

        update_post_meta( $post_id, '_rf_meta', $meta );
        add_action( 'save_post', array( __CLASS__, 'save' ) );
    }

    public static function get_meta( $post_id ) {
        $defaults = array(
            'title'             => '',
            'subtitle'          => '',
            'description'       => '',
            'brand'             => get_bloginfo('name'),
            'logo'              => '',
            'color'             => '#295cff',
            'btn_label'         => 'Pay Now',
            'layout'            => 'split',
            'price_mode'        => 'items',
            'currency'          => 'INR',
            'items'             => array(),
            'fields'            => array(),
            'core_fields'       => array(),
            'thankyou'          => '',
            'notify_email'      => get_option('rf_notify_email', get_option('admin_email')),
            'success_msg'       => 'Thank you! Your payment was received.',
            'use_global_key'    => 1,
            'rzp_key'           => '',
            'send_client_email' => 1,
            'show_order_summary'=> 1,
            'show_trust_badges' => 1,
            'fixed_price'       => '',
            'price_label'       => '',
            'min_price'         => 100,
            'amount_label'      => 'Enter Amount',
            'suggested'         => '',
        );
        $saved = get_post_meta( $post_id, '_rf_meta', true ) ?: array();
        return wp_parse_args( $saved, $defaults );
    }
}
