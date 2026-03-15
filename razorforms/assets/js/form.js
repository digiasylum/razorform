/* RazorForms — Frontend JS */
(function(){
'use strict';

// ── Item change (items price mode) ──────────────────────
// ── NEW: chip click handler (div-based, no <label> wrapper) ───
window.rfCloseSuccess = function(fid) {
    var ov = document.getElementById('rfSuccess_' + fid);
    if(ov) ov.classList.remove('rf-show');
};

window.rfChipClick = function(chip, fid) {
    var isChecked = chip.classList.contains('is-checked');
    chip.classList.toggle('is-checked', !isChecked);
    var priceRow = chip.querySelector('.rf-item-price-row');
    if(priceRow) priceRow.style.display = isChecked ? 'none' : 'flex';
    if(isChecked) {
        // unchecked — clear custom price
        var idx  = chip.dataset.idx;
        var pinp = document.getElementById('rfItem_'+fid+'_'+idx);
        if(pinp) pinp.value = '';
    }
    rfUpdateSummary(fid);
};

window.rfItemChange = function(cb, fid) {
    rfUpdateSummary(fid);
};

// ── Suggested amount buttons ─────────────────────────────
document.addEventListener('click', function(e){
    if(!e.target.classList.contains('rf-suggest-btn')) return;
    var fid = e.target.dataset.form;
    var amount = parseFloat(e.target.dataset.amount||0);
    var inp = document.getElementById('rfVarAmount_'+fid);
    if(inp){ inp.value = amount; }
    document.querySelectorAll('.rf-suggest-btn').forEach(function(b){ b.classList.remove('active'); });
    e.target.classList.add('active');
    rfUpdateSummary(fid);
});

// ── Variable amount input ─────────────────────────────────
document.addEventListener('input', function(e){
    var inp = e.target;
    if(!inp.id || inp.id.indexOf('rfVarAmount_')===-1) return;
    var fid = inp.id.replace('rfVarAmount_','');
    rfUpdateSummary(fid);
    document.querySelectorAll('.rf-suggest-btn').forEach(function(b){ b.classList.remove('active'); });
});

// ── Core field input → update steps ──────────────────────
document.addEventListener('input', function(e){
    var el = e.target;
    var wrap = el.closest('.rf-form-wrap');
    if(!wrap) return;
    var fid = wrap.dataset.formId;
    if(fid) rfUpdateSteps(fid);
});

// ── Update summary + CTA ─────────────────────────────────
function rfUpdateSummary(fid) {
    var wrap = document.getElementById('rfForm_'+fid);
    if(!wrap) return;

    var mode    = wrap.dataset.priceMode;
    var sym     = wrap.dataset.symbol || '₹';
    var minP    = parseFloat(wrap.dataset.minPrice||1);
    var fixedP  = parseFloat(wrap.dataset.fixedPrice||0);
    var total   = 0;
    var selItems = [];

    if(mode === 'fixed') {
        total = fixedP;
    } else if(mode === 'variable') {
        var ainp = document.getElementById('rfVarAmount_'+fid);
        total = ainp ? (parseFloat(ainp.value)||0) : 0;
    } else if(mode === 'items') {
        document.querySelectorAll('#rfItemsGrid_'+fid+' .rf-item-chip.is-checked').forEach(function(chip){
            var idx    = chip.dataset.idx;
            var custom = chip.dataset.custom === '1';
            var price;
            if(custom) {
                var pinp = document.getElementById('rfItem_'+fid+'_'+idx);
                price = pinp ? (parseFloat(pinp.value)||0) : 0;
            } else {
                price = parseFloat(chip.dataset.price||0);
            }
            total += price;
            selItems.push({ name: chip.dataset.name, price: price });
        });
    }

    // Total bar (items mode)
    var noHint = document.getElementById('rfNoHint_'+fid);
    var totalBar = document.getElementById('rfTotalBar_'+fid);
    if(noHint && totalBar) {
        if(selItems.length > 0) {
            noHint.style.display = 'none'; totalBar.style.display = 'flex';
            var names = document.getElementById('rfBarNames_'+fid);
            var tval  = document.getElementById('rfBarTotal_'+fid);
            if(names) names.textContent = selItems.map(function(s){return s.name;}).join(' · ');
            if(tval)  tval.textContent  = total > 0 ? sym+rfFmt(total) : sym+' —';
        } else {
            noHint.style.display = 'block'; totalBar.style.display = 'none';
        }
    }

    // Order summary box
    var oBox = document.getElementById('rfOrderSummary_'+fid);
    var oLines = document.getElementById('rfOsLines_'+fid);
    var oTotal = document.getElementById('rfOsTotal_'+fid);
    if(oBox) {
        if((mode==='items' && selItems.length>0) || mode!=='items') {
            oBox.style.display = 'block';
            if(oLines) {
                if(mode==='items') {
                    oLines.innerHTML = selItems.map(function(s){
                        return '<div class="rf-os-line"><span>'+s.name+'</span><span>'+(s.price>0?sym+rfFmt(s.price):'<em class="rf-os-pending">awaiting amount</em>')+'</span></div>';
                    }).join('');
                } else { oLines.innerHTML=''; }
            }
            if(oTotal) oTotal.textContent = total>0 ? sym+rfFmt(total) : '—';
        } else {
            oBox.style.display = 'none';
        }
    }

    // CTA button
    var btn = document.getElementById('rfPayBtn_'+fid);
    var txt = document.getElementById('rfPayBtnTxt_'+fid);
    if(!btn||!txt) return;

    var hasItems = mode!=='items' || selItems.length>0;

    if(!hasItems) {
        btn.disabled = true; txt.textContent = 'Select a service to continue';
    } else if(mode==='variable' && total < minP) {
        btn.disabled = (total>0); // enable once anything typed, validate min on submit
        txt.textContent = total>0 ? 'Enter amount & pay' : 'Enter an amount to continue';
    } else if(total <= 0 && mode!=='fixed') {
        btn.disabled = false; txt.textContent = 'Enter amount & pay';
    } else {
        btn.disabled = false;
        txt.textContent = 'Pay '+sym+rfFmt(total)+' Now';
    }

    rfUpdateSteps(fid);
    // store for pay()
    wrap._rfTotal = total;
    wrap._rfSelItems = selItems;
}

function rfFmt(n){ return Number(n).toLocaleString('en-IN'); }

// ── Step indicator ────────────────────────────────────────
function rfUpdateSteps(fid) {
    var wrap = document.getElementById('rfForm_'+fid);
    if(!wrap) return;

    var n  = (document.getElementById('rfName_'+fid)||{}).value||'';
    var e  = (document.getElementById('rfEmail_'+fid)||{}).value||'';
    var p  = ((document.getElementById('rfPhone_'+fid)||{}).value||'').replace(/\D/g,'');
    var eOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.trim());
    var detailsDone = n.trim() && eOk && p.length>=10;
    var total = wrap._rfTotal || 0;
    var mode  = wrap.dataset.priceMode;

    var s1=document.getElementById('rfStep1_'+fid);
    var s2=document.getElementById('rfStep2_'+fid);
    var s3=document.getElementById('rfStep3_'+fid);
    if(!s1) return;

    var servicesDone = mode==='items' ? (wrap._rfSelItems||[]).length>0 : true;

    s1.className = 'rf-step' + (servicesDone ? ' rf-step-done' : ' rf-step-active');
    s1.textContent = servicesDone ? '✓ Selected' : (mode==='items'?'1. Select Services':'1. Details');

    s2.className = 'rf-step' + (detailsDone && servicesDone ? ' rf-step-done' : (servicesDone ? ' rf-step-active' : ' rf-step'));
    s2.textContent = (detailsDone && servicesDone) ? '✓ Your Info' : '2. Your Info';

    s3.className = 'rf-step' + (detailsDone && servicesDone && total>0 ? ' rf-step-active' : ' rf-step');
    s3.textContent = (detailsDone && servicesDone && total>0) ? '3. Ready!' : '3. Pay';
}

// ── Validation ────────────────────────────────────────────
function rfValidate(fid) {
    var ok  = true;
    var wrap = document.getElementById('rfForm_'+fid);
    if(!wrap) return false;

    var n = document.getElementById('rfName_'+fid);
    var e = document.getElementById('rfEmail_'+fid);
    var p = document.getElementById('rfPhone_'+fid);

    [n,e,p].forEach(function(el){ if(el) el.classList.remove('rf-input-err'); });
    document.querySelectorAll('#rfForm_'+fid+' .rf-err').forEach(function(el){ el.style.display='none'; });

    if(!n||!n.value.trim()){ if(n)n.classList.add('rf-input-err'); var en=document.getElementById('rfErrName_'+fid); if(en)en.style.display='block'; ok=false; }
    if(!e||!e.value.trim()||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e.value)){ if(e)e.classList.add('rf-input-err'); var ee=document.getElementById('rfErrEmail_'+fid); if(ee)ee.style.display='block'; ok=false; }
    if(!p||p.value.replace(/\D/g,'').length<10){ if(p)p.classList.add('rf-input-err'); var ep=document.getElementById('rfErrPhone_'+fid); if(ep)ep.style.display='block'; ok=false; }

    // Custom required fields
    document.querySelectorAll('#rfForm_'+fid+' .rf-input[data-required="1"]').forEach(function(el){
        if(!el.value.trim()){ el.classList.add('rf-input-err'); ok=false; }
    });

    var mode  = wrap.dataset.priceMode;
    var minP  = parseFloat(wrap.dataset.minPrice||1);
    var total = wrap._rfTotal || 0;

    if(mode==='variable' && total < minP){ alert('Minimum amount is '+(wrap.dataset.symbol||'₹')+rfFmt(minP)); ok=false; }
    if(mode==='items' && (!(wrap._rfSelItems||[]).length)){ ok=false; }

    return ok;
}

// ── Collect custom field data ─────────────────────────────
function rfCollectFields(fid) {
    var data = {};
    document.querySelectorAll('#rfForm_'+fid+' .rf-input:not([id^=rfName]):not([id^=rfEmail]):not([id^=rfPhone])').forEach(function(el){
        var label = el.closest('.rf-fg') ? (el.closest('.rf-fg').querySelector('label')||{}).textContent : el.id;
        label = (label||el.id).replace(/\*/g,'').trim();
        if(el.tagName==='SELECT'||el.tagName==='TEXTAREA'||el.type==='text'||el.type==='number'||el.type==='url'||el.type==='date'||el.type==='email'||el.type==='tel') {
            if(el.value) data[label] = el.value;
        }
    });
    // Checkboxes
    var groups = {};
    document.querySelectorAll('#rfForm_'+fid+' input[type=checkbox][data-group]').forEach(function(cb){
        var g = cb.dataset.group;
        if(!groups[g]) groups[g]={};
        if(cb.checked){
            var lbl = cb.closest('.rf-fg')||(cb.closest('.rf-checkgroup')&&cb.closest('.rf-fg'));
            var key = lbl ? ((lbl.querySelector('label')||{}).textContent||g).replace(/\*/g,'').trim() : g;
            if(!data[key]) data[key]=[];
            if(!Array.isArray(data[key])) data[key]=[data[key]];
            data[key].push(cb.value);
        }
    });
    // Radio
    document.querySelectorAll('#rfForm_'+fid+' .rf-radiogroup').forEach(function(rg){
        var checked = rg.querySelector('input[type=radio]:checked');
        if(checked){
            var lbl = rg.closest('.rf-fg');
            var key = lbl ? ((lbl.querySelector('label')||{}).textContent||rg.id).replace(/\*/g,'').trim() : rg.id;
            data[key] = checked.value;
        }
    });
    return data;
}

// ── PAY ───────────────────────────────────────────────────
window.rfPay = function(fid) {
    if(!rfValidate(fid)) return;

    var wrap    = document.getElementById('rfForm_'+fid);
    var cfg     = window.RF_CONFIG || {};
    var mode    = wrap.dataset.priceMode;
    var sym     = wrap.dataset.symbol || '₹';
    var total   = wrap._rfTotal || 0;
    var selItems= wrap._rfSelItems || [];
    var currency= wrap.dataset.currency || 'INR';
    var paise   = Math.round(total * 100);

    if(paise < 100){ alert('Minimum payment amount is '+sym+'1.'); return; }

    var name  = (document.getElementById('rfName_'+fid)||{}).value||'';
    var email = (document.getElementById('rfEmail_'+fid)||{}).value||'';
    var phone = ((document.getElementById('rfPhone_'+fid)||{}).value||'').replace(/\D/g,'').slice(-10);
    var fdata = rfCollectFields(fid);

    var desc = mode==='items' ? selItems.map(function(s){return s.name;}).join(', ') : ('Payment — Form #'+fid);

    var opts = {
        key:         cfg.razorpay_key || '',
        amount:      paise,
        currency:    currency,
        description: desc,
        prefill:     { name:name, email:email, contact:phone },
        notes:       { form_id: fid },
        handler: function(res) {
            rfHandleSuccess(fid, res, { name:name, email:email, phone:phone, total:total, selItems:selItems, fdata:fdata, currency:currency });
        },
        modal: { ondismiss: function(){} }
    };

    var rzp = new Razorpay(opts);
    rzp.on('payment.failed', function(r){
        alert('Payment failed: '+r.error.description+'. Please try again.');
    });
    rzp.open();
};

// ── Handle success ────────────────────────────────────────
function rfHandleSuccess(fid, rzpRes, d) {
    var cfg = window.RF_CONFIG || {};

    var body = new URLSearchParams({
        action:         'rf_submit',
        nonce:          cfg.nonce||'',
        form_id:        fid,
        razorpay_id:    rzpRes.razorpay_payment_id,
        core_name:      d.name,
        core_email:     d.email,
        core_phone:     d.phone,
        amount:         d.total,
        currency:       d.currency,
        field_data:     JSON.stringify(d.fdata),
        selected_items: JSON.stringify(d.selItems),
    });

    fetch(cfg.ajax_url||'/wp-admin/admin-ajax.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: body.toString()
    })
    .then(function(r){ return r.json(); })
    .then(function(data){
        var redirect = (data.data&&data.data.thankyou_url) ? data.data.thankyou_url : '';
        if(redirect){
            setTimeout(function(){ window.location.href = redirect; }, 600);
        } else {
            var msg = (data.data&&data.data.success_msg) ? data.data.success_msg : 'Thank you! Your payment was received.';
            var msgEl = document.getElementById('rfSuccessMsg_'+fid);
            var txnEl = document.getElementById('rfSuccessTxn_'+fid);
            if(msgEl) msgEl.textContent = msg;
            if(txnEl) txnEl.textContent = 'TXN ID: '+rzpRes.razorpay_payment_id;
            var ov = document.getElementById('rfSuccess_'+fid);
            if(ov) ov.classList.add('rf-show');
        }
    })
    .catch(function(){
        // Payment done — show success even if AJAX call fails
        var txnEl = document.getElementById('rfSuccessTxn_'+fid);
        if(txnEl) txnEl.textContent = 'TXN ID: '+rzpRes.razorpay_payment_id;
        var ov = document.getElementById('rfSuccess_'+fid);
        if(ov) ov.classList.add('rf-show');
    });
}

// ── Init all forms on page ────────────────────────────────
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.rf-form-wrap').forEach(function(wrap){
        var fid = wrap.dataset.formId;
        if(!fid) return;

        // Stop price input clicks from toggling the chip
        wrap.querySelectorAll('.rf-item-price-inp').forEach(function(inp){
            inp.addEventListener('click',     function(e){e.stopPropagation();});
            inp.addEventListener('mousedown', function(e){e.stopPropagation();});
        });

        // Init state
        rfUpdateSummary(fid);
    });
});

})();
