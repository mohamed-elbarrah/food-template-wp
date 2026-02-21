/* bcpo frontend interactions: toggle active class and sync input checked state */
(function($){
  $(document).ready(function(){
    // Helper: read bcpo config from a given scope (element or jQuery)
    function readConfigFromScope( el ){
      var $el = el ? $(el) : $();
      var $wrap = $el.closest('.single-left, .product-card, .product, body');
      if ( !$wrap || $wrap.length === 0 ) $wrap = $('body');
      var $bc = $wrap.find('.bcpo-frontend').first();
      var cfg = {
        basePrice: 0,
        decimals: 2,
        thousand: ',',
        decimal: '.',
        symbol: '',
        pos: 'left'
      };
      if ( $bc && $bc.length ){
        cfg.basePrice = parseFloat( $bc.data('base-price') || 0 );
        cfg.decimals  = parseInt( $bc.data('price-decimals') || cfg.decimals );
        cfg.thousand  = ( $bc.data('price-thousand') || cfg.thousand ) + '';
        cfg.decimal   = ( $bc.data('price-decimal') || cfg.decimal ) + '';
        cfg.symbol    = ( $bc.data('price-symbol') || cfg.symbol ) + '';
        cfg.pos       = ( $bc.data('price-pos') || cfg.pos ) + '';
      }
      return { wrap: $wrap, cfg: cfg };
    }

    // Format price using per-scope config. Accepts optional cfg returned by readConfigFromScope.
    function formatPrice( amount, cfg ){ 
      var info = cfg ? { cfg: cfg } : readConfigFromScope();
      var c = info.cfg || {};
      var decimals = isNaN(c.decimals) ? 2 : c.decimals;
      var thousand = c.thousand || ',';
      var decimal = c.decimal || '.';
      var symbol = c.symbol || '';
      var pos = c.pos || 'left';
      var negative = amount < 0;
      amount = Math.abs( parseFloat( amount || 0 ) );
      var parts = amount.toFixed(decimals).split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
      var formatted = parts.join(decimal);
      if (pos === 'left') return (negative?'-':'') + symbol + formatted;
      if (pos === 'left_space') return (negative?'-':'') + symbol + ' ' + formatted;
      if (pos === 'right') return (negative?'-':'') + formatted + symbol;
      return (negative?'-':'') + formatted + ' ' + symbol;
    }

    // price elements to update (scoped to the product/modal)
    function getPriceElements( $scope ){
      var $els = $();
      $els = $els.add( $scope.find('.card-footer .price') );
      $els = $els.add( $scope.find('.summary .price') );
      $els = $els.add( $scope.find('.price') );
      return $els.filter(function(i,el){ return $(el).closest('body').length; });
    }

    $(document).on('click', '.food-options-container .option-item', function(e){
      var $label = $(this);
      var $container = $label.closest('.food-options-container');
      var type = $container.data('type');
      var $input = $label.find('input.bcpo-hidden-input');

      if (!$input.length) {
        return;
      }

      if ('checkbox' === type) {
        var checked = !$input.prop('checked');
        $input.prop('checked', checked).trigger('change');
        $label.toggleClass('active', checked);
      } else {
        // radio / single choice
        $container.find('.option-item').removeClass('active').find('input.bcpo-hidden-input').prop('checked', false);
        $input.prop('checked', true).trigger('change');
        $label.addClass('active');
      }

      // update price display after any change (scoped to this option's product)
      updateDisplayedPrice( this );
    });

    // initialize based on checked inputs within each scope (in case of pre-populated values)
    // Only initialize scopes that contain `.bcpo-frontend` or are `.single-left` (modal/single product)
    $('.bcpo-frontend, .single-left').each(function(){
      var $s = $(this).closest('.single-left, .product-card, .product');
      if ( !$s.length ) $s = $(this);
      $s.find('.food-options-container').each(function(){
        $(this).find('input.bcpo-hidden-input:checked').each(function(){
          $(this).closest('.option-item').addClass('active');
        });
      });
      // update price for this scope
      updateDisplayedPrice( $s );
    });

    // recalc price on any input change (covers keyboard changes)
    $(document).on('change', '.food-options-container input.bcpo-hidden-input', function(){
      updateDisplayedPrice( this );
    });

    // Client-side validation: ensure required groups have selection before add-to-cart
    // Expose validator so other scripts (AJAX add-to-cart) can call it.
    window.bcpoValidateForm = function(form){
      var $form = $(form);
      var $scope = $form.closest('.single-left, .product-card, .product');
      if (!$scope || $scope.length === 0) $scope = $form.closest('body');
      // remove any previous inline errors
      $scope.find('.bcpo-inline-error').remove();

      var invalid = null;
      $scope.find('.bcpo-group-frontend[data-required="1"]').each(function(){
        if (invalid) return; // stop after first invalid
        var $fs = $(this);
        var type = $fs.data('type') || '';
        if (type === 'text'){
          var $input = $fs.find('input.bcpo-text');
          var val = $input.val() ? $input.val().trim() : '';
          if (val === '') invalid = { el: $input.get(0) || $fs.get(0), title: $fs.find('.bcpo-group-title').text() };
        } else {
          var $checked = $fs.find('input.bcpo-hidden-input:checked');
          if (!$checked.length) invalid = { el: $fs.find('.option-item').get(0) || $fs.get(0), title: $fs.find('.bcpo-group-title').text() };
        }
      });

      if (invalid){
        var title = (invalid.title || '').replace(/\*\s*$/, '').trim();
        var msg = title ? ( 'الرجاء اختيار: ' + title ) : 'الرجاء اختيار الخيار المطلوب.';
        var $ins = $( '<div class="bcpo-inline-error" role="alert" style="color:#b00020;margin:6px 0;font-weight:600;">' + msg + '</div>' );
        var $target = $( invalid.el );
        // If target is an input, insert after it; otherwise insert before the fieldset legend
        if ($target.is('input,textarea,select')){
          $target.first().focus();
          $target.first().closest('.option-item').addClass('bcpo-invalid');
          $target.first().after($ins);
        } else {
          var $fs = $target.closest('.bcpo-group-frontend');
          if ($fs.length) {
            $fs.find('.bcpo-group-title').first().after($ins);
            // focus first interactive element in this group
            var $focusEl = $fs.find('input.bcpo-hidden-input, input.bcpo-text').first();
            if ($focusEl && $focusEl.length) { $focusEl.get(0).focus(); }
          } else {
            $target.before($ins);
            try{ $target.get(0).focus(); }catch(e){}
          }
        }
        // scroll into view smoothly
        try{ $ins.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' }); }catch(e){}
        // remove after a few seconds
        setTimeout(function(){ $ins.fadeOut(200, function(){ $(this).remove(); }); }, 4000);
        return { valid: false, message: msg, el: invalid.el };
      }
      return { valid: true };
    };

    // Before submit: validate and copy bcpo inputs into the form so they are posted
    function bcpo_copy_inputs_into_form(form){
      var $form = $(form);
      // remove previously appended markers
      $form.find('input[data-bcpo-clone], textarea[data-bcpo-clone]').remove();
      var $scope = $form.closest('.single-left, .product-card, .product');
      if (!$scope || $scope.length === 0) $scope = $form.closest('body');
      // find any named inputs inside the bcpo frontend for this product
      $scope.find('.bcpo-frontend').find('input[name], textarea[name], select[name]').each(function(){
        var $el = $(this);
        var name = $el.attr('name');
        if (!name) return;
        var type = ($el.attr('type') || '').toLowerCase();
        if ((type === 'radio' || type === 'checkbox')){
          if (!$el.prop('checked')) return; // only append checked
        }
        // append a cloned hidden input into the form to ensure it's submitted
        var val = $el.val();
        var $clone = $('<input>').attr('type','hidden').attr('name', name).val( val === undefined || val === null ? '' : val ).attr('data-bcpo-clone','1');
        $form.append( $clone );
        try{ console.debug('[bcpo] clone into form', name, val); }catch(e){}
      });
      // Do NOT append empty markers for radio/checkbox groups here — leave them absent so server validation treats them as missing when appropriate.
    }

    $(document).on('submit', '.custom-single-add-to-cart', function(e){
      var res = window.bcpoValidateForm(this);
      if (!res.valid){ e.preventDefault(); return false; }
      // copy bcpo inputs so server receives them even if the markup is outside the form
      try{ bcpo_copy_inputs_into_form(this); }catch(err){ /* ignore */ }
      return true;
    });

    function updateDisplayedPrice( context ){
      // determine scope from context element or default to body
      var $ctx = context ? $(context) : $();
      var info = readConfigFromScope( $ctx );
      var $scope = info.wrap;
      var cfg = info.cfg;
      var $els = getPriceElements( $scope );
      if (! $els || $els.length === 0) return;
      var base = parseFloat( cfg.basePrice || 0 );
      var extra = 0;
      $scope.find('.food-options-container').each(function(){
        $(this).find('input.bcpo-hidden-input:checked').each(function(){
          var p = parseFloat( $(this).data('price') || $(this).closest('.option-item').data('price') || 0 );
          if (!isNaN(p)) extra += p;
        });
      });
      var total = parseFloat( base ) + parseFloat( extra );
      var newHtml = formatPrice( total, cfg );
      $els.each(function(){ animateNumberChange( $(this), newHtml ); });
    }

    // initialize displayed price for each scope is handled above per-scope
    
    // animate only numeric part of the price when it changes
    function animateNumberChange($el, newHtml){
      if (!$el || $el.length === 0) return;
      // Find or create a numeric target inside $el. Prefer elements that already contain digits.
      var $target = $el.find('.bcpo-price-num-target').first();
      if (!$target.length){
        // search descendants containing digits (first match)
        var $candidates = $el.find('*').addBack().filter(function(){
          return /\d/.test($(this).text());
        });
        $target = $candidates.first();
        if (!$target.length) {
          // fallback: replace whole html
          if ($el.html() !== newHtml) { $el.html(newHtml); }
          return;
        }
        // wrap current content in a span wrapper for numeric animation
        var current = $target.html();
        $target.html('<span class="bcpo-num-wrap"><span class="bcpo-num current">' + current + '</span></span>');
        $target.addClass('bcpo-price-num-target');
      }

      var $wrap = $target.find('.bcpo-num-wrap');
      if (!$wrap.length) return; // safety
      var $cur = $wrap.find('.bcpo-num.current').first();
      var oldText = $cur.length ? $cur.text() : $wrap.text();
      if (oldText === newHtml) return; // nothing to do

      // create new span and animate it in while animating old out
      var $new = $('<span class="bcpo-num enter"></span>').text(newHtml).appendTo($wrap);
      // force reflow
      $new[0].offsetHeight;
      $new.addClass('active');

      if ($cur.length){
        $cur.removeClass('current').addClass('exit active');
      }

      // cleanup after animation
      setTimeout(function(){
        $wrap.find('.exit').remove();
        $new.removeClass('enter active').addClass('current');
      }, 340);
    }

    /* ---------- Rating UI & AJAX ---------- */
    // inject rating widget if not present
    function renderRatingWidget(){
      // place the rating widget above the card footer inside .single-left
      var $container = $('.single-left .bcpo-frontend').first();
      if (!$container.length) return;
      // avoid duplicate
      if ($container.next('.bcpo-rating').length) return;
      var html = '<div class="bcpo-rating" aria-label="Product rating">'
        + '<div class="bcpo-rating-prompt">كيف تقييم هذه الخدمة؟</div>';
      html += '<div class="bcpo-stars" role="radiogroup" aria-label="Rate this product">';
      for(var i=1;i<=5;i++){ html += '<button type="button" class="bcpo-star" data-value="'+i+'" aria-label="'+i+' stars">☆</button>'; }
      html += '</div>';
      html += '<div class="bcpo-rating-message" aria-hidden="true" style="display:none;"></div>';
      html += '</div>';
      /* ---------- Static rating form handling (placed under .single-product-layout) ---------- */
      // star click -> set rating input and visual state
      $(document).on('click', '.bcpo-stars .bcpo-star', function(){
        var $btn = $(this);
        var val = parseInt( $btn.data('value') || 0 );
        if (val < 1) return;
        var $group = $btn.closest('.bcpo-stars');
        $group.find('.bcpo-star').each(function(){
          var v = parseInt( $(this).data('value') );
          $(this).text( v <= val ? '★' : '☆' );
        });
        // set hidden input
        var $form = $btn.closest('form.bcpo-rating-form');
        $form.find('input[name="rating"]').val(val);
      });

      // submit rating form via AJAX
      $(document).on('submit', '.bcpo-rating-form', function(e){
        e.preventDefault();
        var $form = $(this);
        var productId = parseInt( $form.find('input[name="product_id"]').val() || 0 );
        var rating = parseInt( $form.find('input[name="rating"]').val() || 0 );
        var comment = $form.find('textarea[name="bcpo_comment"]').val() || '';
        var commentId = parseInt( $form.find('input[name="comment_id"]').val() || 0 );
        if (rating < 1 || rating > 5) {
          alert('الرجاء اختيار تقييم من 1 إلى 5');
          return;
        }
        var data = {
          action: 'bcpo_submit_rating',
          product_id: productId,
          rating: rating,
          comment: comment,
          comment_id: commentId,
          nonce: (typeof bcpo_frontend !== 'undefined' ? bcpo_frontend.nonce : '')
        };
        $.post( (typeof bcpo_frontend !== 'undefined' ? bcpo_frontend.ajax_url : '/wp-admin/admin-ajax.php'), data ).done(function(resp){
          if (resp && resp.success) {
              // Render success message replacement (design provided) including comment_id so user can edit
              var commentId = resp.data && resp.data.comment_id ? parseInt(resp.data.comment_id, 10) : 0;
              var stars = '';
              for(var s=1;s<=5;s++){
                stars += '<span class="bcpo-thank-star" style="color:#2e7d32;margin-left:6px;font-size:1.05em;">' + (s <= rating ? '★' : '☆') + '</span>';
              }
              // encode comment safely into attribute
              var encComment = encodeURIComponent( comment || '' );
              var html = '';
              html += '<div class="bcpo-rating-thanks-bar single-product-container" role="status" aria-live="polite" data-comment-id="'+commentId+'" data-comment="'+ encComment +'" data-rating="'+rating+'" data-product-id="'+ (productId || '') +'" style="border:1px dashed #b19cd9;padding:8px 12px;display:flex;align-items:center;justify-content:space-between;">';
              html += '<div class="bcpo-thanks-left" style="display:flex;align-items:center;gap:10px;color:#2e7d32;font-weight:600;">';
              html += '<span class="bcpo-thanks-check" style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#e8f5e9;color:#2e7d32;font-size:14px;">✓</span>';
              html += '<span>تم إرسال ردك بنجاح</span>';
              html += '</div>';
              html += '<div class="bcpo-thanks-right" style="display:flex;align-items:center;gap:12px;">';
              html += '<div class="bcpo-thank-stars" style="display:flex;align-items:center;">' + stars + '</div>';
              html += '<span class="bcpo-thanks-score" style="margin-right:8px;color:#333;">(' + (rating).toFixed(1) + ')</span>';
              html += '<button type="button" class="bcpo-edit-review" style="background:transparent;border:1px solid #ccc;padding:6px 8px;border-radius:4px;">تعديل تقييمي</button>';
              html += '</div>';
              html += '</div>';
              $form.closest('.bcpo-rating-section').replaceWith( html );
              // update any rating value displays on the page with new average if provided
              if ( resp.data && typeof resp.data.average !== 'undefined' ) {
                var avg = parseFloat( resp.data.average ) || 0;
                // format to one decimal as server uses
                var avgText = avg.toFixed(1);
                $('.rating-value').each(function(){
                  $(this).text( avgText );
                });
              }
            } else {
            alert( (resp && resp.data && resp.data.message) ? resp.data.message : 'خطأ في الإرسال' );
          }
        }).fail(function(){ alert('Network error'); });
      });
      var height = $footer.outerHeight() || 0;
      // add a little space so content isn't obscured
      $('body').css('padding-bottom', (height + 12) + 'px');
    }

    // debounce helper
    function debounce(fn, wait){
      var t;
      return function(){
        var args = arguments, ctx = this;
        clearTimeout(t);
        t = setTimeout(function(){ fn.apply(ctx,args); }, wait);
      };
    }

    // init fixed footer handling
    /* ---------- Rating hover/keyboard/ARIA helpers ---------- */
    function updateStars($group, val){
      $group.find('.bcpo-star').each(function(){
        var $s = $(this);
        var v = parseInt( $s.data('value') || 0 );
        var on = v <= (parseInt(val) || 0);
        $s.text( on ? '★' : '☆' ).toggleClass('selected', on).attr('aria-checked', on ? 'true' : 'false').attr('aria-pressed', on ? 'true' : 'false');
      });
    }

    // ensure stars are keyboard focusable and have radio semantics
    $(document).on('mouseenter', '.bcpo-stars', function(){
      $(this).find('.bcpo-star').attr('role','radio').attr('tabindex', 0);
    });

    // hover / focus preview
    $(document).on('mouseover focus', '.bcpo-stars .bcpo-star', function(){
      var val = parseInt( $(this).data('value') || 0 );
      updateStars( $(this).closest('.bcpo-stars'), val );
    });

    // click -> persist selection (store in hidden input and keep visuals)
    $(document).on('click', '.bcpo-stars .bcpo-star', function(){
      var val = parseInt( $(this).data('value') || 0 );
      if (val < 1) return;
      var $form = $(this).closest('form.bcpo-rating-form');
      $form.find('input[name="rating"]').val(val);
      updateStars( $(this).closest('.bcpo-stars'), val );
    });

    // mouseout / blur restore selected value from hidden input
    $(document).on('mouseout blur', '.bcpo-stars .bcpo-star', function(){
      var $form = $(this).closest('form.bcpo-rating-form');
      var sel = parseInt( $form.find('input[name="rating"]').val() || 0 ) || 0;
      updateStars( $(this).closest('.bcpo-stars'), sel );
    });

    // keyboard navigation and activation
    $(document).on('keydown', '.bcpo-stars .bcpo-star', function(e){
      var key = e.key;
      var cur = parseInt( $(this).data('value') || 0 );
      if(key === 'ArrowRight' || key === 'ArrowUp'){
        e.preventDefault();
        var nxt = Math.min(5, cur + 1);
        $(this).closest('.bcpo-stars').find('.bcpo-star[data-value="' + nxt + '"]').focus();
      } else if(key === 'ArrowLeft' || key === 'ArrowDown'){
        e.preventDefault();
        var prev = Math.max(1, cur - 1);
        $(this).closest('.bcpo-stars').find('.bcpo-star[data-value="' + prev + '"]').focus();
      } else if(key === 'Enter' || key === ' '){
        e.preventDefault();
        $(this).trigger('click');
      }
    });

    // initialize any existing forms on page load
    $('form.bcpo-rating-form').each(function(){
      var val = parseInt( $(this).find('input[name="rating"]').val() || 0 ) || 0;
      var $g = $(this).find('.bcpo-stars');
      if($g.length) updateStars($g, val);
    });

    // Edit review: rebuild form from success bar data
    $(document).on('click', '.bcpo-edit-review', function(){
      var $bar = $(this).closest('.bcpo-rating-thanks-bar');
      // comment was stored URL-encoded in data-comment
      var comment = '';
      if ( $bar.attr('data-comment') ) {
        try { comment = decodeURIComponent( $bar.attr('data-comment') ); } catch(e) { comment = $bar.attr('data-comment'); }
      } else if ( $bar.find('.bcpo-comment-data').length ) {
        // server-rendered hidden JSON encoded string
        try { comment = JSON.parse( $bar.find('.bcpo-comment-data').text() ); } catch(e) { comment = $bar.find('.bcpo-comment-data').text(); }
      }
      var rating = parseInt( $bar.attr('data-rating') || 0 );
      var commentId = parseInt( $bar.attr('data-comment-id') || 0 );
      var productId = parseInt( $bar.attr('data-product-id') || 0 );
      // Build form HTML (same structure as server-rendered form)
      var formHtml = '';
      formHtml += '<form class="bcpo-rating-form" method="post" action="#">';
      formHtml += '<div class="bcpo-rating-grid">';
      formHtml += '<div class="bcpo-rating-left">';
      formHtml += '<label class="bcpo-rating-label">أخبرنا عن رأيك في هذه الخدمة</label>';
      formHtml += '<textarea name="bcpo_comment" class="bcpo-comment" placeholder="النص المدخل" rows="4">'+ $('<div/>').text(comment).html() +'</textarea>';
      formHtml += '<div class="bcpo-rating-actions"><button type="submit" class="bcpo-rating-submit add-btn">إرسال</button></div>';
      formHtml += '</div>';
      formHtml += '<div class="bcpo-rating-right">';
      formHtml += '<div class="bcpo-rating-prompt">كيف تقييم هذه الخدمة؟</div>';
      formHtml += '<div class="bcpo-stars" role="radiogroup" aria-label="Rate this product">';
      for(var i=1;i<=5;i++){ formHtml += '<button type="button" class="bcpo-star" data-value="'+i+'" aria-label="'+i+' stars">☆</button>'; }
      formHtml += '</div></div></div>';
      formHtml += '<input type="hidden" name="product_id" value="'+ ( productId || ( $bar.closest('.single-product-container').find('input[name="product_id"]').val() || '' ) ) +'">';
      formHtml += '<input type="hidden" name="rating" value="'+ (rating || '') +'">';
      formHtml += '<input type="hidden" name="comment_id" value="'+ (commentId || 0) +'">';
      formHtml += '</form>';
      // Wrap form in the same container class so submit handler can replace it
      var wrapped = '<div class="bcpo-rating-section single-product-container" aria-label="Product rating section">' + formHtml + '</div>';
      var $wrapped = $( wrapped );
      $bar.replaceWith( $wrapped );
      var $newForm = $wrapped.find('form.bcpo-rating-form');
      updateStars( $newForm.find('.bcpo-stars'), rating );
    });

    updateFixedFooter();
    $(window).on('resize', debounce(updateFixedFooter, 150));
  });
})(jQuery);
