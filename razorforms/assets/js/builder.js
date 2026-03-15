/* RazorForms — Builder JS | Digiasylum © */
jQuery(function($){

    // TABS
    $('.rf-tab').on('click', function(){
        var tab = $(this).data('tab');
        $('.rf-tab').removeClass('active');
        $(this).addClass('active');
        $('.rf-tab-panel').removeClass('active');
        $('[data-panel="'+tab+'"]').addClass('active');
    });

    // COLOR PICKER
    if($.fn.wpColorPicker) {
        $('.rf-color-picker').wpColorPicker();
    }

    // ITEM LIST — original working logic
    var itemIndex = $('#rfItemsList .rf-item-row').length;

    $('#rfAddItem').on('click', function(){
        var idx = itemIndex++;
        var row = '<div class="rf-item-row" data-index="'+idx+'">' +
            '<span class="rf-item-drag" title="Drag to reorder">\u2803</span>' +
            '<input type="text" class="rf-item-name" name="rf_items['+idx+'][name]" value="" placeholder="Service name">' +
            '<input type="text" class="rf-item-desc" name="rf_items['+idx+'][desc]" value="" placeholder="Short description (optional)">' +
            '<div class="rf-item-price-col">' +
            '<input type="number" class="rf-item-price" name="rf_items['+idx+'][price]" value="" placeholder="\u20b9 Price" min="0">' +
            '<label class="rf-custom-price-toggle" title="Let client enter their own amount">' +
            '<input type="checkbox" class="rf-custom-price-cb" name="rf_items['+idx+'][custom_price]" value="1">' +
            '<span class="rf-cpt-inner"><span class="rf-cpt-icon">\u270f\ufe0f</span><span class="rf-cpt-label">Custom Price</span></span>' +
            '</label>' +
            '</div>' +
            '<button type="button" class="rf-remove-item" title="Remove">\u2715</button>' +
            '</div>';
        $('#rfItemsList').append(row);
        syncItemsJson();
    });

    $(document).on('click', '.rf-remove-item', function(){
        $(this).closest('.rf-item-row').remove();
        syncItemsJson();
    });

    $(document).on('change', '.rf-custom-price-cb', function(){
        var $label    = $(this).closest('.rf-custom-price-toggle');
        var $priceInp = $(this).closest('.rf-item-price-col').find('.rf-item-price');
        var isOn      = this.checked;
        $label.toggleClass('is-active', isOn);
        $priceInp.prop('disabled', isOn)
                 .toggleClass('rf-price-disabled', isOn)
                 .attr('placeholder', isOn ? 'Client will enter' : '\u20b9 Price');
        if(isOn) $priceInp.val('');
        syncItemsJson();
    });

    $(document).on('input change', '#rfItemsList .rf-item-row input', syncItemsJson);

    function syncItemsJson(){
        var items = [];
        $('#rfItemsList .rf-item-row').each(function(){
            items.push({
                name:         $(this).find('.rf-item-name').val()  || '',
                desc:         $(this).find('.rf-item-desc').val()  || '',
                price:        parseFloat($(this).find('.rf-item-price').val()) || 0,
                custom_price: $(this).find('.rf-custom-price-cb').is(':checked'),
            });
        });
        $('#rfItemsJson').val(JSON.stringify(items));
    }

    // FIELD BUILDER — original working logic
    var fieldCount = $('#rfFieldsCanvas .rf-field-row').length;

    var fieldMeta = {
        text:     { label:'Text Field',     hasOpts:false },
        textarea: { label:'Text Area',      hasOpts:false },
        select:   { label:'Dropdown',       hasOpts:true  },
        checkbox: { label:'Checkbox Group', hasOpts:true  },
        radio:    { label:'Radio Buttons',  hasOpts:true  },
        date:     { label:'Date Picker',    hasOpts:false },
        number:   { label:'Number Input',   hasOpts:false },
        url:      { label:'URL / Website',  hasOpts:false },
    };

    $('.rf-add-field-btn').on('click', function(){
        var type = $(this).data('type');
        var fm   = fieldMeta[type] || {label:'Field', hasOpts:false};
        var idx  = fieldCount++;
        var opts = fm.hasOpts
            ? '<div class="rf-core-field-row" style="align-items:flex-start;margin-top:.35rem;"><label style="padding-top:.4rem;">Options</label><div style="flex:1;"><textarea class="rf-field-options" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea><span class="rf-hint">One option per line</span></div></div>'
            : '';
        var row =
            '<div class="rf-field-row" data-index="'+idx+'" data-type="'+type+'">' +
            '<div class="rf-field-top-bar">' +
            '<span class="rf-item-drag rf-field-handle" title="Drag to reorder">\u2803</span>' +
            '<span class="rf-field-type-badge">'+type.toUpperCase()+'</span>' +
            '<input type="text" class="rf-field-label-input" value="'+fm.label+'" placeholder="Field Label">' +
            '<div class="rf-field-controls">' +
            '<select class="rf-field-width"><option value="full">Full width</option><option value="half">Half width</option></select>' +
            '<label class="rf-core-req-toggle rf-field-req-wrap">' +
            '<input type="checkbox" class="rf-field-required">' +
            '<span class="rf-core-req-badge is-opt">Optional</span>' +
            '</label>' +
            '<button type="button" class="rf-remove-item rf-field-remove" title="Remove field">\u2715</button>' +
            '</div></div>' +
            '<div class="rf-field-inputs">' +
            '<div class="rf-core-field-row"><label>Placeholder</label>' +
            '<input type="text" class="rf-field-placeholder" value="" placeholder="Placeholder text (optional)">' +
            '</div>' +
            opts +
            '</div></div>';
        $('#rfFieldsCanvas').append(row);
        syncFieldsJson();
    });

    $(document).on('click', '.rf-field-remove', function(){
        $(this).closest('.rf-field-row').remove();
        syncFieldsJson();
    });

    if($.fn.sortable) {
        $('#rfFieldsCanvas').sortable({ handle:'.rf-field-handle', update: syncFieldsJson });
    }

    $(document).on('input change',
        '#rfFieldsCanvas .rf-field-row input, #rfFieldsCanvas .rf-field-row select, #rfFieldsCanvas .rf-field-row textarea',
        syncFieldsJson
    );

    $(document).on('change', '.rf-field-required, [name*="_required"]', function(){
        var badge = $(this).closest('label').find('.rf-core-req-badge');
        if(this.checked){
            badge.removeClass('is-opt').addClass('is-req').text('Required');
        } else {
            badge.removeClass('is-req').addClass('is-opt').text('Optional');
        }
    });

    function syncFieldsJson(){
        var fields = [];
        $('#rfFieldsCanvas .rf-field-row').each(function(){
            fields.push({
                type:        $(this).data('type'),
                label:       $(this).find('.rf-field-label-input').val()  || '',
                placeholder: $(this).find('.rf-field-placeholder').val()  || '',
                required:    $(this).find('.rf-field-required').is(':checked'),
                options:     $(this).find('.rf-field-options').val()       || '',
                width:       $(this).find('.rf-field-width').val()         || 'full',
            });
        });
        $('#rfFieldsJson').val(JSON.stringify(fields));
    }

    function escHtml(s){
        return $('<div>').text(String(s||'')).html();
    }

    // Initial sync — reads PHP-rendered DOM rows into hidden JSON fields
    syncItemsJson();
    syncFieldsJson();
});
