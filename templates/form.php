<?php
/**
 * RazorForms — Frontend Form Template
 *
 * Variables available: $id (form post ID), $meta (form meta array)
 *
 * @package  RazorForms
 * @author   Digiasylum <hello@digiasylum.com>
 */
if(!defined('ABSPATH')) exit;

$color      = esc_attr($meta['color'] ?? '#295cff');
$layout     = $meta['layout']     ?? 'split';
$sym        = ['INR'=>'₹','USD'=>'$','EUR'=>'€','GBP'=>'£','SGD'=>'S$','AED'=>'AED '][$meta['currency']??'INR'] ?? '₹';
$btn_label  = esc_html($meta['btn_label'] ?: 'Pay Now');
$items      = $meta['items']  ?? [];
$fields     = $meta['fields'] ?? [];
// price_mode is always 'items' now
?>

<div class="rf-form-wrap rf-layout-<?php echo esc_attr($layout); ?>" id="rfForm_<?php echo $id; ?>"
     data-form-id="<?php echo $id; ?>"
     data-price-mode="items"
     data-currency="<?php echo esc_attr($meta['currency']??'INR'); ?>"
     data-symbol="<?php echo esc_attr($sym); ?>"
     data-min-price="1"
     data-fixed-price="0"
     style="--rf-color:<?php echo $color; ?>; --rf-color-light:<?php echo $color; ?>1a;">

  <!-- SUCCESS OVERLAY -->
  <div class="rf-success-overlay" id="rfSuccess_<?php echo $id; ?>">
    <div class="rf-success-card">
      <div class="rf-success-icon" style="background:var(--rf-color-light)">✓</div>
      <div class="rf-success-title">Payment Successful!</div>
      <p class="rf-success-msg" id="rfSuccessMsg_<?php echo $id; ?>"><?php echo esc_html($meta['success_msg']??'Thank you! Your payment was received.'); ?></p>
      <div class="rf-success-txn" id="rfSuccessTxn_<?php echo $id; ?>">TXN: —</div>
      <button class="rf-success-close" onclick="rfCloseSuccess('<?php echo $id; ?>')" title="Close">✕ Close</button>
    </div>
  </div>

  <!-- HEADER -->
  <header class="rf-header">
    <div class="rf-header-steps">
      <span class="rf-step rf-step-active" id="rfStep1_<?php echo $id; ?>">1. Select Items</span>
      <span class="rf-step-sep">›</span>
      <span class="rf-step" id="rfStep2_<?php echo $id; ?>">2. Your Info</span>
      <span class="rf-step-sep">›</span>
      <span class="rf-step" id="rfStep3_<?php echo $id; ?>">3. Pay</span>
    </div>
    <div class="rf-header-secure">
      <span class="rf-secure-dot"></span> Secured by Razorpay
    </div>
  </header>

  <!-- MAIN BODY -->
  <div class="rf-body">

    <!-- LEFT COLUMN -->
    <div class="rf-col-left">
      <div class="rf-intro">
        <?php if(!empty($meta['title'])): ?>
          <h1 class="rf-page-title"><?php echo esc_html($meta['title']); ?></h1>
        <?php endif; ?>
        <?php if(!empty($meta['subtitle'])): ?>
          <p class="rf-page-subtitle"><?php echo esc_html($meta['subtitle']); ?></p>
        <?php endif; ?>
        <?php if(!empty($meta['description'])): ?>
          <div class="rf-page-desc"><?php echo nl2br(esc_html($meta['description'])); ?></div>
        <?php endif; ?>
      </div>

      <!-- ITEM LIST -->
      <?php if(!empty($items)): ?>
      <div class="rf-items-section">
        <div class="rf-items-label">Select what you need</div>
        <div class="rf-items-grid" id="rfItemsGrid_<?php echo $id; ?>">
          <?php foreach($items as $idx => $item):
            $has_custom = !empty($item['custom_price']);
          ?>
          <?php /* Use div+button pattern — NO <label> wrapping inputs to avoid CSS conflicts */ ?>
          <div class="rf-item-chip" role="button" tabindex="0"
               data-idx="<?php echo $idx; ?>"
               data-name="<?php echo esc_attr($item['name']); ?>"
               data-price="<?php echo floatval($item['price']??0); ?>"
               data-custom="<?php echo $has_custom ? '1' : '0'; ?>"
               data-fid="<?php echo $id; ?>"
               onclick="rfChipClick(this,'<?php echo $id; ?>')"
               onkeydown="if(event.key==='Enter'||event.key===' ')rfChipClick(this,'<?php echo $id; ?>')">
            <div class="rf-item-body">
              <div class="rf-item-top">
                <div class="rf-item-text">
                  <div class="rf-item-name-text"><?php echo esc_html($item['name']); ?></div>
                  <?php if(!empty($item['desc'])): ?>
                    <div class="rf-item-desc-text"><?php echo esc_html($item['desc']); ?></div>
                  <?php endif; ?>
                </div>
                <div class="rf-item-chk">✓</div>
              </div>
              <?php if($has_custom): ?>
              <div class="rf-item-price-row" style="display:none;">
                <span class="rf-item-price-lbl">Enter Amount</span>
                <div class="rf-item-price-wrap">
                  <span class="rf-item-sym"><?php echo $sym; ?></span>
                  <input type="number" class="rf-item-price-inp" min="1" placeholder="0"
                         id="rfItem_<?php echo $id.'_'.$idx; ?>"
                         oninput="rfItemChange(null,'<?php echo $id; ?>')"
                         onclick="event.stopPropagation()"
                         onmousedown="event.stopPropagation()">
                </div>
              </div>
              <?php elseif(!empty($item['price'])): ?>
              <div class="rf-item-price-row" style="display:none;">
                <span class="rf-item-fixed-price"><?php echo $sym.number_format($item['price'],0); ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="rf-items-no-hint" id="rfNoHint_<?php echo $id; ?>">← Select a service to continue</div>
        <div class="rf-total-bar" id="rfTotalBar_<?php echo $id; ?>" style="display:none">
          <div>
            <div class="rf-total-bar-label">Selected</div>
            <div class="rf-total-bar-names" id="rfBarNames_<?php echo $id; ?>">—</div>
          </div>
          <div style="text-align:right">
            <div class="rf-total-bar-amount" id="rfBarTotal_<?php echo $id; ?>">—</div>
            <div class="rf-total-bar-sub">/ total</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if(!empty($meta['show_trust_badges'])): ?>
      <div class="rf-trust-row">
        <span class="rf-trust-item"><span class="rf-trust-dot">✓</span> 100% Secure</span>
        <span class="rf-trust-item"><span class="rf-trust-dot">✓</span> No Hidden Charges</span>
        <span class="rf-trust-item"><span class="rf-trust-dot">✓</span> Instant Confirmation</span>
      </div>
      <?php endif; ?>
    </div><!-- /col-left -->

    <!-- RIGHT COLUMN -->
    <div class="rf-col-right">
      <div class="rf-form-eyebrow">Your Details</div>

      <div class="rf-fields" id="rfFields_<?php echo $id; ?>">

        <?php
        $cf        = $meta['core_fields'] ?? [];
        $nameLbl   = esc_html( $cf['name']['label']       ?? 'Full Name' );
        $namePh    = esc_attr( $cf['name']['placeholder'] ?? 'Your name' );
        $nameReq   = isset($cf['name']['required'])  ? (bool)$cf['name']['required']  : true;
        $emailLbl  = esc_html( $cf['email']['label']       ?? 'Email Address' );
        $emailPh   = esc_attr( $cf['email']['placeholder'] ?? 'you@example.com' );
        $emailReq  = isset($cf['email']['required']) ? (bool)$cf['email']['required'] : true;
        $phoneLbl  = esc_html( $cf['phone']['label']       ?? 'Phone' );
        $phonePh   = esc_attr( $cf['phone']['placeholder'] ?? '+91 98765 43210' );
        $phoneReq  = isset($cf['phone']['required']) ? (bool)$cf['phone']['required'] : true;
        ?>
        <!-- Core fields — always visible, labels/placeholders/required from builder -->
        <div class="rf-fg-row">
          <div class="rf-fg">
            <label><?php echo $nameLbl; ?><?php if($nameReq) echo ' <span class="rf-req">*</span>'; ?></label>
            <input type="text" id="rfName_<?php echo $id; ?>" class="rf-input"
                   placeholder="<?php echo $namePh; ?>"
                   <?php if($nameReq) echo 'data-required="1"'; ?>>
            <div class="rf-err" id="rfErrName_<?php echo $id; ?>">Please enter your name</div>
          </div>
          <div class="rf-fg">
            <label><?php echo $phoneLbl; ?><?php if($phoneReq) echo ' <span class="rf-req">*</span>'; ?></label>
            <input type="tel" id="rfPhone_<?php echo $id; ?>" class="rf-input"
                   placeholder="<?php echo $phonePh; ?>"
                   <?php if($phoneReq) echo 'data-required="1"'; ?>>
            <div class="rf-err" id="rfErrPhone_<?php echo $id; ?>">Enter a valid number</div>
          </div>
        </div>

        <div class="rf-fg rf-fg-full">
          <label><?php echo $emailLbl; ?><?php if($emailReq) echo ' <span class="rf-req">*</span>'; ?></label>
          <input type="email" id="rfEmail_<?php echo $id; ?>" class="rf-input"
                 placeholder="<?php echo $emailPh; ?>"
                 <?php if($emailReq) echo 'data-required="1"'; ?>>
          <div class="rf-err" id="rfErrEmail_<?php echo $id; ?>">Enter a valid email</div>
        </div>

        <!-- Dynamic custom fields -->
        <?php
        $row_open = false;
        foreach($fields as $fi => $field):
          $fid  = 'rfCf_'.$id.'_'.$fi;
          $half = ($field['width']??'full') === 'half';
          $req  = !empty($field['required']);
          $ph   = esc_attr($field['placeholder']??'');
          $lbl  = esc_html($field['label']??'Field');
          $type = $field['type']??'text';
          $opts = array_filter(array_map('trim', explode("\n", $field['options']??'')));

          if($half && !$row_open) { echo '<div class="rf-fg-row">'; $row_open=true; }
          elseif(!$half && $row_open) { echo '</div>'; $row_open=false; }
        ?>
        <div class="rf-fg <?php echo $half?'':'rf-fg-full'; ?>">
          <label><?php echo $lbl; ?><?php if($req) echo ' <span class="rf-req">*</span>'; ?></label>
          <?php if($type==='textarea'): ?>
            <textarea id="<?php echo $fid; ?>" class="rf-input rf-textarea" placeholder="<?php echo $ph; ?>" <?php if($req) echo 'data-required="1"'; ?>></textarea>
          <?php elseif($type==='select'): ?>
            <select id="<?php echo $fid; ?>" class="rf-input rf-select" <?php if($req) echo 'data-required="1"'; ?>>
              <option value="">— Select —</option>
              <?php foreach($opts as $opt): ?>
                <option value="<?php echo esc_attr($opt); ?>"><?php echo esc_html($opt); ?></option>
              <?php endforeach; ?>
            </select>
          <?php elseif($type==='checkbox'): ?>
            <div class="rf-checkgroup" id="<?php echo $fid; ?>">
              <?php foreach($opts as $opt): ?>
                <label class="rf-check-item">
                  <input type="checkbox" value="<?php echo esc_attr($opt); ?>" data-group="<?php echo $fid; ?>">
                  <?php echo esc_html($opt); ?>
                </label>
              <?php endforeach; ?>
            </div>
          <?php elseif($type==='radio'): ?>
            <div class="rf-radiogroup" id="<?php echo $fid; ?>">
              <?php foreach($opts as $opt): ?>
                <label class="rf-radio-item">
                  <input type="radio" name="<?php echo $fid; ?>" value="<?php echo esc_attr($opt); ?>">
                  <?php echo esc_html($opt); ?>
                </label>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <input type="<?php echo esc_attr($type); ?>" id="<?php echo $fid; ?>" class="rf-input"
                   placeholder="<?php echo $ph; ?>"
                   <?php if($req) echo 'data-required="1"'; ?>>
          <?php endif; ?>
        </div>
        <?php endforeach;
        if($row_open) echo '</div>'; ?>

        <!-- Order Summary -->
        <?php if(!empty($meta['show_order_summary'])): ?>
        <div class="rf-order-summary" id="rfOrderSummary_<?php echo $id; ?>" style="display:none">
          <div class="rf-os-title">Order Summary</div>
          <div id="rfOsLines_<?php echo $id; ?>"></div>
          <div class="rf-os-total">
            <span>Total</span>
            <span class="rf-os-total-val" id="rfOsTotal_<?php echo $id; ?>">—</span>
          </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <button type="button" class="rf-pay-btn" id="rfPayBtn_<?php echo $id; ?>" disabled
                onclick="rfPay('<?php echo $id; ?>')"
                style="background:var(--rf-color)">
          <span id="rfPayBtnTxt_<?php echo $id; ?>">Select a service to continue</span>
          <span class="rf-btn-arrow">→</span>
        </button>
        <div class="rf-pay-note">🔒 SSL secured · UPI · Cards · Net Banking</div>

      </div><!-- /rf-fields -->
    </div><!-- /col-right -->
  </div><!-- /rf-body -->
</div><!-- /rf-form-wrap -->
