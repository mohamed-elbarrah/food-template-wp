(function(){
    var container = null;
    var hideTimer = null;

    function ensure(){
        container = container || document.querySelector('.bcpo-add-to-cart-toast');
        if (container && container.parentNode && container.parentNode !== document.body) {
            try { document.body.appendChild(container); } catch (e) { /* ignore */ }
        }
    }

    function closeToast(){
        ensure(); if(!container) return;
        container.classList.remove('bcpo-show');
        container.setAttribute('aria-hidden', 'true');
        if(hideTimer){ clearTimeout(hideTimer); hideTimer = null; }

        // Wait for the CSS transition to finish before hiding the element
        // This ensures the close uses the same smooth transform/opacity effect
        var once = false;
        function hideNow(){
            if(once) return; once = true;
            try{ container.style.display = 'none'; }catch(e){}
        }

        try{
            var cs = window.getComputedStyle(container);
            var td = cs && (cs.transitionDuration || cs['transition-duration']);
            var maxMs = 0;
            if(td){
                td.split(',').forEach(function(s){ var n = parseFloat(s) || 0; if(n>maxMs) maxMs = n; });
                maxMs = Math.ceil(maxMs * 1000) + 50;
            }
            if(maxMs > 20){
                var handler = function(ev){ if(ev && ev.target === container){ container.removeEventListener('transitionend', handler); hideNow(); } };
                container.addEventListener('transitionend', handler);
                setTimeout(hideNow, maxMs + 50);
            } else {
                setTimeout(hideNow, 260);
            }
        }catch(e){
            setTimeout(hideNow, 260);
        }
    }

    function cleanupWooInsertions(){
        try{
            var sels = ['.wc-forward', '.woocommerce-message', '.woocommerce-notices-wrapper', '.added_to_cart', 'a.added_to_cart', '.woocommerce-error', '.woocommerce-info'];
            sels.forEach(function(s){ document.querySelectorAll(s).forEach(function(n){ n.remove(); }); });
            document.querySelectorAll('a,button').forEach(function(el){
                try{ if(el.textContent && el.textContent.trim() === 'عرض السلة'){ el.remove(); } }catch(e){}
            });
            var overlay = document.querySelector('.bcpo-features-overlay');
            if(overlay){ sels.forEach(function(s){ overlay.querySelectorAll && overlay.querySelectorAll(s).forEach(function(n){ n.remove(); }); }); }
        }catch(e){}
    }

    function showToast(title, subtitle){
        ensure(); if(!container) return;
        var titleEl = container.querySelector('.bcpo-toast-title');
        var subEl = container.querySelector('.bcpo-toast-sub');
        if(titleEl && title) titleEl.textContent = title;
        if(subEl && subtitle) subEl.textContent = subtitle;
        try{ document.querySelectorAll('.woocommerce-message, .woocommerce-notices-wrapper').forEach(function(n){ n.remove(); }); }catch(e){}
        try{ container.style.display = 'flex'; }catch(e){}
        container.setAttribute('aria-hidden', 'false');
        void container.offsetWidth;
        container.classList.add('bcpo-show');
        var keep = container.querySelector('.bcpo-go-checkout');
        if(keep) keep.focus();
        if(hideTimer) clearTimeout(hideTimer);
        hideTimer = setTimeout(closeToast, 7000);
    }

    document.addEventListener('click', function(e){
        if(e.target.closest('.bcpo-toast-close') || e.target.closest('.bcpo-continue')){ e.preventDefault(); closeToast(); }
    });

    function bindWC(){
        if(window.jQuery){
            jQuery(function($){
                $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button){
                    var title = 'تم إضافة طلبك إلى السلة بنجاح';
                    var subtitle = 'يمكنك المتابعة لإتمام الطلب أو مواصلة التصفح.';
                    try{ var overlay = document.querySelector('.bcpo-features-overlay.bcpo-show'); if(overlay){ overlay.classList.remove('bcpo-show'); document.body.style.overflow = ''; } }catch(e){}
                    try{
                        var name = '';
                        if($button && $button.length){ var p = $button.closest('.product'); if(p && p.length){ name = p.find('.product_title').text() || p.find('h1.product_title').text() || ''; } }
                        if(name) title = name + ' تم إضافته إلى السلة';
                    }catch(e){}
                    cleanupWooInsertions();
                    showToast(title, subtitle);
                });
            });
        } else {
            document.body.addEventListener('added_to_cart', function(e){
                var detail = e && e.detail ? e.detail : {};
                var name = detail.name || '';
                var title = name ? (name + ' تم إضافته إلى السلة') : 'تم إضافة طلبك إلى السلة بنجاح';
                cleanupWooInsertions();
                showToast(title, 'يمكنك المتابعة لإتمام الطلب أو مواصلة التصفح.');
            });
        }

        document.addEventListener('click', function(e){
            var btn = e.target.closest('button.add-btn.full-width-btn');
            if(!btn) return;
            var form = btn.closest('form');
            if(!form) return;
            try{ var overlay = document.querySelector('.bcpo-features-overlay.bcpo-show'); if(overlay){ overlay.classList.remove('bcpo-show'); document.body.style.overflow = ''; } }catch(e){}
            if(btn.getAttribute('data-bcpo-handled')) return;
            e.preventDefault(); btn.setAttribute('data-bcpo-handled', '1');
            var fd = new FormData(form);
            var ajaxUrl = '';
            if(typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url){ ajaxUrl = wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%','add_to_cart'); }
            else { ajaxUrl = window.location.origin + window.location.pathname + '?wc-ajax=add_to_cart'; }
            if(!fd.get('quantity')){ var qty = form.querySelector('input[name="quantity"]'); if(qty) fd.set('quantity', qty.value || 1); }
            fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r){ return r.text(); })
                .then(function(text){
                    var json = null; try{ json = JSON.parse(text); }catch(err){ json = null; }
                    var name = '';
                    try{ var p = btn.closest('.product'); if(p){ var title = p.querySelector('.product_title') || p.querySelector('h1.product_title') || p.querySelector('.woocommerce-loop-product__title'); if(title) name = title.textContent.trim(); } }catch(e){}
                    try{ if(window.jQuery){ jQuery(document.body).trigger('added_to_cart', [ (json && json.fragments) || {}, (json && json.cart_hash) || '', jQuery(btn) ]); }
                    else { var ev = new CustomEvent('added_to_cart', { detail: { fragments: (json && json.fragments) || {}, cart_hash: (json && json.cart_hash) || '', button: btn, name: name } }); document.body.dispatchEvent(ev); } }catch(e){}
                    cleanupWooInsertions();
                    var titleText = name ? (name + ' تم إضافته إلى السلة') : 'تم إضافة طلبك إلى السلة بنجاح';
                    showToast(titleText, 'يمكنك المتابعة لإتمام الطلب أو مواصلة التصفح.');
                })
                .catch(function(){ showToast('حدث خطأ أثناء الإضافة','حاول مرة أخرى'); })
                .finally(function(){ setTimeout(function(){ btn.removeAttribute('data-bcpo-handled'); }, 500); });
        }, true);
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') bindWC();
    else document.addEventListener('DOMContentLoaded', bindWC);

})();
