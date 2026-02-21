/* bcpo frontend interactions: toggle active class and sync input checked state */
(function($){
  $(document).ready(function(){
    // Price formatting config from wrapper data (fallbacks will be used)
    var $bcpo = $('.bcpo-frontend').first();
    var bcpoCfg = {
      basePrice: parseFloat( $bcpo.data('base-price') || 0 ),
      decimals: parseInt( $bcpo.data('price-decimals') || 2 ),
      thousand: ( $bcpo.data('price-thousand') || ',' ) + '',
      decimal: ( $bcpo.data('price-decimal') || '.' ) + '',
      symbol: ( $bcpo.data('price-symbol') || '' ) + '',
      pos: ( $bcpo.data('price-pos') || 'left' ) + ''
    };

    function formatPrice( amount ){ 
      var decimals = isNaN(bcpoCfg.decimals) ? 2 : bcpoCfg.decimals;
      var thousand = bcpoCfg.thousand || ',';
      var decimal = bcpoCfg.decimal || '.';
      var negative = amount < 0;
      amount = Math.abs( parseFloat( amount || 0 ) );
      var parts = amount.toFixed(decimals).split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousand);
      var formatted = parts.join(decimal);
      var pos = bcpoCfg.pos || 'left';
      if (pos === 'left') return (negative?'-':'') + bcpoCfg.symbol + formatted;
      if (pos === 'left_space') return (negative?'-':'') + bcpoCfg.symbol + ' ' + formatted;
      if (pos === 'right') return (negative?'-':'') + formatted + bcpoCfg.symbol;
      return (negative?'-':'') + formatted + ' ' + bcpoCfg.symbol;
    }

    // Determine the DOM scope for this bcpo instance so updates affect only
    // the related product (or modal) and not every price on the page.
    var $scope = $bcpo.length ? (
      ($bcpo.closest('.single-left').length ? $bcpo.closest('.single-left') : $bcpo.closest('.product-card, .product, body'))
    ) : $('body');

    // price elements to update (scoped to the product/modal)
    function getPriceElements(){
      // prefer scoped price elements (summary, card, inline) within $scope
      var $els = $();
      $els = $els.add( $scope.find('.card-footer .price') );
      $els = $els.add( $scope.find('.summary .price') );
      $els = $els.add( $scope.find('.price') );
      // filter to ensure elements are in document
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

      // update price display after any change (scoped)
      updateDisplayedPrice();
    });

    // initialize based on checked inputs within scope (in case of pre-populated values)
    $scope.find('.food-options-container').each(function(){
      var $c = $(this);
      $c.find('input.bcpo-hidden-input:checked').each(function(){
        $(this).closest('.option-item').addClass('active');
      });
    });

    // recalc price on any input change (covers keyboard changes)
    $(document).on('change', '.food-options-container input.bcpo-hidden-input', function(){
      updateDisplayedPrice();
    });

    function updateDisplayedPrice(){
      var $els = getPriceElements();
      if (! $els || $els.length === 0) return;
      var base = parseFloat( bcpoCfg.basePrice || 0 );
      var extra = 0;
      // only consider option inputs within this scope (product/modal)
      $scope.find('.food-options-container').each(function(){
        $(this).find('input.bcpo-hidden-input:checked').each(function(){
          var p = parseFloat( $(this).data('price') || $(this).closest('.option-item').data('price') || 0 );
          if (!isNaN(p)) extra += p;
        });
      });
      var total = parseFloat( base ) + parseFloat( extra );
      var newHtml = formatPrice( total );
      // update all matched price elements
      $els.each(function(){ animateNumberChange( $(this), newHtml ); });
    }

    // initialize displayed price
    updateDisplayedPrice();
    
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
