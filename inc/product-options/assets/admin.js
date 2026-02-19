/* bcpo admin JS: render groups and keep hidden payload updated */
(function($){
    'use strict';

    var allowedTypes = ['radio','checkbox'];

    function buildOptionRow(optIndex, opt){
        opt = opt || {label:'',price:0};
        return $('<div class="bcpo-option">')
            .append($('<input type="text" class="bcpo-opt-label" placeholder="اسم الخيار">').val(opt.label))
            .append($('<input type="number" step="0.01" class="bcpo-opt-price" placeholder="السعر الإضافي">').val(opt.price))
            .append($('<button type="button" class="button bcpo-remove-opt">حذف الخيار</button>'));
    }

    function renderGroup(index, group){
        group = group || {title:'',type:'text',required:0,options:[]};
        var $g = $('<div class="bcpo-group" data-index="'+index+'">');
        $g.append($('<p class="bcpo-field">').append($('<label class="bcpo-label">عنوان القسم</label>')).append('<input class="bcpo-title" type="text">').find('input').val(group.title).end());
        $g.append($('<p class="bcpo-help small">').text('اكتب عنوان هذا القسم كما سيظهر للعميل. مثال: اختر الحجم – مستوى الحرارة – الإضافات.'));
        var $type = $('<select class="bcpo-type">');
        // Only two modes: single choice (radio) or multiple choice (checkbox)
        $type.append('<option value="radio">اختيار واحد</option>');
        $type.append('<option value="checkbox">اختيار متعدد</option>');
        $type.val( inArray(group.type, allowedTypes) ? group.type : 'radio' );
        $g.append($('<p class="bcpo-field">').append($('<label class="bcpo-label">طريقة اختيار العميل</label>')).append($type));
        $g.append($('<p class="bcpo-help small">').text('حدد طريقة اختيار العميل: اختيار واحد أو اختيار متعدد.'));
        $g.append($('<p class="bcpo-field">').append($('<label class="bcpo-label">هل هذا القسم إلزامي</label>')).append('<input type="checkbox" class="bcpo-required">').find('input').prop('checked', !!group.required).end());
        $g.append($('<p class="bcpo-help small">').text('عند التفعيل، لن يتمكن العميل من إتمام الطلب بدون اختيار من هذا القسم.'));
        $g.append($('<p class="form-field form-field-wide">').append($('<label class="bcpo-label">وصف توضيحي (اختياري)</label>')).append($('<textarea class="bcpo-desc-input" rows="2" placeholder="يمكنك كتابة ملاحظة قصيرة لمساعدة العميل. مثال: يمكنك اختيار 3 إضافات كحد أقصى."></textarea>').val(group.description || '')));
        // min/max selection removed per spec
        var $opts = $('<div class="bcpo-options">');
        $opts.append($('<p class="bcpo-options-title">').text('الخيارات المتاحة داخل هذا القسم'));
        $opts.append($('<p class="bcpo-help small">').text('أضف الخيارات التي يمكن للعميل اختيارها. إذا لم يكن هناك سعر إضافي اترك السعر 0.'));
        (group.options||[]).forEach(function(opt,i){ $opts.append(buildOptionRow(i,opt)); });
        $opts.append($('<p>').append($('<button type="button" class="button bcpo-add-opt">إضافة خيار جديد</button>')));
        $g.append($opts);
        $g.append($('<p>').append($('<button type="button" class="button bcpo-remove-group">حذف هذا القسم بالكامل</button>')));
        return $g;
    }

    function readPayload(){
        var raw = $('#bcpo_payload').val();
        try{ return JSON.parse(raw||'[]'); }catch(e){ return []; }
    }

    // tiny helper: safe check for array membership
    function inArray(needle, haystack){
        if (!needle) return false;
        for (var i=0;i<haystack.length;i++){ if (haystack[i] === needle) return true; }
        return false;
    }

    function writePayload(data){
        $('#bcpo_payload').val(JSON.stringify(data));
    }

    function collectFromDOM(){
        var data = [];
        $('#bcpo-groups .bcpo-group').each(function(){
            var $g = $(this);
            var group = {};
            group.title = $g.find('.bcpo-title').val();
            group.type = $g.find('.bcpo-type').val();
            group.required = $g.find('.bcpo-required').is(':checked') ? 1 : 0;
            // collect description
            group.description = $g.find('.bcpo-desc-input').val() || '';
            group.options = [];
            $g.find('.bcpo-option').each(function(){
                var $o = $(this);
                group.options.push({
                    label: $o.find('.bcpo-opt-label').val(),
                    price: parseFloat( $o.find('.bcpo-opt-price').val() || 0 )
                });
            });
            data.push(group);
        });
        return data;
    }

    function reindexAndSave(){
        writePayload( collectFromDOM() );
    }

    $(document).ready(function(){
        var saved = [];
        try{ saved = JSON.parse( $('#bcpo-groups').attr('data-saved') || '[]' ); }catch(e){ saved = []; }
        var $container = $('#bcpo-groups');
        $container.empty();
        saved.forEach(function(g,i){ $container.append( renderGroup(i,g) ); });

        $('#bcpo-add-group').on('click', function(){
            $container.append( renderGroup( Date.now(), null ) );
        });

        $container.on('click', '.bcpo-add-opt', function(){
            var $opts = $(this).closest('.bcpo-options');
            $opts.find('.bcpo-add-opt').before( buildOptionRow() );
        });

        $container.on('click', '.bcpo-remove-opt', function(){
            $(this).closest('.bcpo-option').remove();
        });

        $container.on('click', '.bcpo-remove-group', function(){
            $(this).closest('.bcpo-group').remove();
        });

        // update hidden payload before save
        $('#post').on('submit', function(){ reindexAndSave(); });
    });

})(jQuery);
