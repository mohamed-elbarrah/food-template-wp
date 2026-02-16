/* bcpo admin JS: render groups and keep hidden payload updated */
(function($){
    'use strict';

    var allowedTypes = ['radio','checkbox','select','text'];

    function buildOptionRow(optIndex, opt){
        opt = opt || {label:'',price:0};
        return $('<div class="bcpo-option">')
            .append($('<input type="text" class="bcpo-opt-label" placeholder="Label">').val(opt.label))
            .append($('<input type="number" step="0.01" class="bcpo-opt-price" placeholder="Price">').val(opt.price))
            .append($('<button type="button" class="button bcpo-remove-opt">Remove</button>'));
    }

    function renderGroup(index, group){
        group = group || {title:'',type:'text',required:0,options:[]};
        var $g = $('<div class="bcpo-group" data-index="'+index+'">');
        $g.append($('<p>').append($('<label>Title: </label>')).append('<input class="bcpo-title" type="text">').find('input').val(group.title).end());
        var $type = $('<select class="bcpo-type">');
        allowedTypes.forEach(function(t){ $type.append('<option value="'+t+'">'+t+'</option>'); });
        $type.val(group.type);
        $g.append($('<p>').append($('<label>Type: </label>')).append($type));
        $g.append($('<p>').append($('<label>Required: </label>')).append('<input type="checkbox" class="bcpo-required">').find('input').prop('checked', !!group.required).end());
        // min/max selection removed per spec
        var $opts = $('<div class="bcpo-options">');
        (group.options||[]).forEach(function(opt,i){ $opts.append(buildOptionRow(i,opt)); });
        $opts.append($('<p>').append($('<button type="button" class="button bcpo-add-opt">Add Option</button>')));
        $g.append($opts);
        $g.append($('<p>').append($('<button type="button" class="button bcpo-remove-group">Remove Group</button>')));
        return $g;
    }

    function readPayload(){
        var raw = $('#bcpo_payload').val();
        try{ return JSON.parse(raw||'[]'); }catch(e){ return []; }
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
            // min/max removed; not collected
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
