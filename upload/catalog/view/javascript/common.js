function getURLVar(key) {
    var value = [];

    var query = String(document.location).split('?');

    if (query[1]) {
        var part = query[1].split('&');

        for (var i = 0; i < part.length; i++) {
            var data = part[i].split('=');

            if (data[0] && data[1]) {
                value[data[0]] = data[1];
            }
        }

        if (value[key]) {
            return value[key];
        } else {
            return '';
        }
    }
}

// Observe
+function($) {
    $.fn.observe = function(callback) {
        var observer = new MutationObserver(callback);

        observer.observe($(this)[0], {
            characterData: false,
            childList: true,
            attributes: false
        });
    };
}(jQuery);

window.pdNavigateWithinShop = function(url) {
    if (typeof pdCheckoutRequiresLogin === 'function'
        && typeof pdIsCheckoutUrl === 'function'
        && typeof window.pdShowSmemberPrompt === 'function'
        && pdCheckoutRequiresLogin()
        && pdIsCheckoutUrl(url)
        && window.pdShowSmemberPrompt(url)) {
        return;
    }

    if (typeof window.pdPreserveSessionForNavigation === 'function') {
        window.pdPreserveSessionForNavigation(url);
    }
    window.location.assign(url);
};

window.pdReloadWithinShop = function() {
    if (typeof window.pdPreserveSessionForNavigation === 'function') {
        window.pdPreserveSessionForNavigation(window.location.href);
    }
    window.location.reload();
};

$(document).ready(function() {
    // Tooltip
    var oc_tooltip = function() {
        // Get tooltip instance
        var tooltip = bootstrap.Tooltip.getInstance(this);

        if (!tooltip) {
            // Apply to current element
            tooltip = bootstrap.Tooltip.getOrCreateInstance(this);
            tooltip.show();
        }
    }

    $(document).on('mouseenter', '[data-bs-toggle=\'tooltip\']', oc_tooltip);

    $(document).on('click', 'button', function() {
        $('.tooltip').remove();
    });

    $('#alert').observe(function() {
        window.setTimeout(function() {
            $('#alert .alert-dismissible').fadeTo(3000, 0, function() {
                $(this).remove();
            });
        }, 3000);
    });
});

// Button
$(document).ready(function() {
    +function($) {
        $.fn.button = function(state) {
            return this.each(function() {
                var element = this;

                if (state == 'loading') {
                    this.html = $(element).html();
                    this.state = $(element).prop('disabled');

                    $(element).prop('disabled', true).width($(element).width()).html('<i class="fa-solid fa-circle-notch fa-spin text-light"></i>');
                }

                if (state == 'reset') {
                    $(element).prop('disabled', this.state).width('').html(this.html);
                }
            });
        };
    }(jQuery);
});

// Forms
// Keep product-card actions explicit: all three buttons sit in one visual card,
// so do not rely on the browser's submitter value to identify the chosen action.
$(document).on('click', '.member-tier-help-toggle[aria-controls]', function () {
    var button = this;
    var panel = document.getElementById(button.getAttribute('aria-controls'));

    if (!panel) {
        return;
    }

    var expanded = button.getAttribute('aria-expanded') === 'true';
    button.setAttribute('aria-expanded', String(!expanded));
    panel.hidden = expanded;
});

$(document).on('click', '.pd-btn-wishlist-add[data-product-id][data-wishlist-add], .pd-btn-compare-add[data-product-id][data-compare-add]', function (e) {
    e.preventDefault();

    var button = this;
    var productId = parseInt($(button).attr('data-product-id'), 10) || 0;
    var isWishlist = $(button).hasClass('pd-btn-wishlist-add');
    var isWishlistRemove = isWishlist && $(button).hasClass('is-wishlisted');
    var action = isWishlist
        ? $(button).attr(isWishlistRemove ? 'data-wishlist-remove' : 'data-wishlist-add')
        : $(button).attr('data-compare-add');

    if (!productId || !action) {
        return;
    }

    action = action.replaceAll('&amp;', '&');
    var requestUrl = action;

    if (!isWishlistRemove) {
        var queryIndex = action.indexOf('?');
        var actionPath = queryIndex === -1 ? action : action.substring(0, queryIndex);
        var actionQuery = queryIndex === -1 ? '' : action.substring(queryIndex);
        if (!actionPath.endsWith('/')) {
            actionPath += '/';
        }
        requestUrl = actionPath + actionQuery;
    }

    $.ajax({
        url: requestUrl,
        type: isWishlistRemove ? 'get' : 'post',
        data: isWishlistRemove ? {} : { product_id: productId },
        dataType: 'json',
        beforeSend: function () {
            $(button).prop('disabled', true).addClass('disabled');
        },
        complete: function () {
            $(button).prop('disabled', false).removeClass('disabled');
        },
        success: function (json) {
            $('.alert-dismissible').remove();

            if (isWishlist && !json.error && json.success) {
                var wishlistLabel = $(button).attr(isWishlistRemove ? 'data-wishlist-label' : 'data-wishlist-added-label');
                $('.pd-btn-wishlist-add[data-product-id="' + productId + '"]').toggleClass('is-wishlisted', !isWishlistRemove).attr({
                    title: wishlistLabel,
                    'aria-label': wishlistLabel
                }).find('i').each(function () {
                    $(this).toggleClass('fa-solid', !isWishlistRemove).toggleClass('fa-regular', isWishlistRemove);
                });
            }

            var message = json.error || json.success;
            if (message) {
                var alertType = json.error ? 'danger' : 'success';
                $('#alert').prepend('<div class="alert alert-' + alertType + ' alert-dismissible"><i class="fa-solid fa-' + (json.error ? 'circle-exclamation' : 'circle-check') + '"></i> ' + message + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            }

            if (!json.error && json.total) {
                var countMatch = json.total.match(/\((\d+)\)/);
                if (countMatch) {
                    var selector = isWishlist ? '.pd-wishlist-count-badge' : '.pd-compare-count-badge';
                    $(selector).text(countMatch[1]);
                }
            }
        },
        error: function (xhr) {
            console.error('Product action failed:', xhr.status, xhr.responseText);
        }
    });
});

// Guest Smember prompt for actions that require an authenticated account.
window.pdShowSmemberPrompt = function (returnUrl, resumeUrl) {
    var modal = document.getElementById('pd-smember-prompt');
    if (!modal) return false;
    if (!modal.hidden) return true;

    var loginLink = modal.querySelector('[data-smember-login]');
    var registerLink = modal.querySelector('[data-smember-register]');
    var safeReturnUrl = returnUrl || window.location.href;
    var withRedirect = function (href) {
        try {
            var url = new URL(String(href).replace(/&amp;/g, '&'), window.location.href);
            url.searchParams.set('redirect', String(safeReturnUrl).replace(/&amp;/g, '&'));
            return url.toString();
        } catch (error) {
            return href;
        }
    };

    if (loginLink) loginLink.href = withRedirect(modal.getAttribute('data-login-url') || loginLink.href);
    if (registerLink) registerLink.href = withRedirect(modal.getAttribute('data-register-url') || registerLink.href);
    modal.dataset.resumeUrl = resumeUrl || '';

    if (!modal.dataset.eventsReady) {
        modal.addEventListener('click', function (event) {
            if (!event.target.closest('[data-smember-close]')) return;
            event.preventDefault();
            window.pdCloseSmemberPrompt();
        });
        document.addEventListener('keydown', function (event) {
            if (modal.hidden) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                window.pdCloseSmemberPrompt();
            } else if (event.key === 'Tab') {
                var focusable = Array.prototype.slice.call(modal.querySelectorAll('a[href],button:not([disabled])'));
                if (!focusable.length) return;
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
        modal.dataset.eventsReady = '1';
    }

    modal._previousFocus = document.activeElement && document.activeElement !== document.body
        ? document.activeElement : null;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('pd-smember-open');
    var closeButton = modal.querySelector('.pd-smember-close');
    if (closeButton) closeButton.focus();
    return true;
};

window.pdCloseSmemberPrompt = function () {
    var modal = document.getElementById('pd-smember-prompt');
    if (!modal || modal.hidden) return;
    var resumeUrl = modal.dataset.resumeUrl || '';
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('pd-smember-open');
    if (resumeUrl) {
        window.pdNavigateWithinShop(resumeUrl);
        return;
    }
    var previousFocus = modal._previousFocus;
    if (previousFocus) previousFocus.focus();
};

var pdCheckoutRequiresLogin = function () {
    if (!document.body || document.body.getAttribute('data-pd-checkout-login-required') !== 'true') {
        return false;
    }

    var route = new URL(window.location.href).searchParams.get('route');
    return route !== 'account/login' && route !== 'account/register';
};

var pdIsCheckoutUrl = function (url) {
    try {
        var destination = new URL(String(url).replace(/&amp;/g, '&'), window.location.href);
        return destination.origin === window.location.origin
            && destination.searchParams.get('route') === 'checkout/checkout';
    } catch (error) {
        return false;
    }
};

$(document).on('click', 'a[href]', function (event) {
    if (!pdCheckoutRequiresLogin() || event.defaultPrevented || event.button !== 0
        || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
        || (this.target && this.target !== '_self')) {
        return;
    }

    var href = this.getAttribute('href');
    if (!pdIsCheckoutUrl(href)) return;

    event.preventDefault();
    window.pdShowSmemberPrompt(new URL(String(href).replace(/&amp;/g, '&'), window.location.href).toString());
});

// One cart handler for product cards, PDP, and the mobile sticky purchase bar.
$(document).on('click', '.btn-add-cart[data-product-id][data-cart-add], .btn-buy-now[data-product-id][data-cart-add]', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var button = this;
    var $button = $(button);
    var buyNow = $button.hasClass('btn-buy-now');
    var productId = parseInt($button.attr('data-product-id'), 10) || 0;
    var $form = $button.closest('#form-product');
    if (!$form.length && (button.id === 'pd-sticky-btn-cart' || button.id === 'pd-sticky-btn-buy')) {
        $form = $('#form-product');
    }

    if (!productId) return;

    var cartUrl = ($button.attr('data-cart-add') || '').replaceAll('&amp;', '&');
    var queryIndex = cartUrl.indexOf('?');
    var cartPath = queryIndex === -1 ? cartUrl : cartUrl.substring(0, queryIndex);
    var cartQuery = queryIndex === -1 ? '' : cartUrl.substring(queryIndex);
    if (!cartPath.endsWith('/')) cartPath += '/';

    var cardForm = button.form;
    var cardQuantity = cardForm ? (parseInt($(cardForm).find('[name="quantity"]').val(), 10) || 1) : 1;
    var data = $form.length ? $form.serialize() : { product_id: productId, quantity: cardQuantity };
    var refreshUrl = ($button.attr('data-cart-info') || ($form.length ? $form.attr('data-cart-info') : '') || '').replaceAll('&amp;', '&');
    var checkoutUrl = ($button.attr('data-checkout') || '').replaceAll('&amp;', '&');
    var successText = $button.attr('data-cart-success') || 'Added to cart.';
    var $relatedButtons = $form.length
        ? $form.find('.btn-add-cart, .btn-buy-now').add($('#pd-sticky-btn-cart, #pd-sticky-btn-buy'))
        : $button;

    $.ajax({
        url: cartPath + cartQuery,
        type: 'post',
        data: data,
        dataType: 'json',
        beforeSend: function () {
            $relatedButtons.prop('disabled', true).addClass('is-loading');
        },
        complete: function () {
            $relatedButtons.prop('disabled', false).removeClass('is-loading');
        },
        success: function (json) {
            $('.alert-dismissible').remove();

            if (json.error) {
                if ($form.length) {
                    $form.find('.is-invalid').removeClass('is-invalid');
                    $form.find('.invalid-feedback').removeClass('d-block');

                    if (typeof json.error === 'object') {
                        $.each(json.error, function (key, message) {
                            var inputId = '#input-' + key.replaceAll('_', '-');
                            $form.find(inputId).addClass('is-invalid').find('.form-control, .form-select, .form-check-input, .form-check-label').addClass('is-invalid');
                            $form.find('#error-' + key.replaceAll('_', '-')).html(message).addClass('d-block');
                        });
                    }
                }
                var errorMessage = typeof json.error === 'string' ? json.error : (json.error.warning || Object.values(json.error)[0]);
                if (errorMessage) $('#alert').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ' + errorMessage + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            }

            if (json.success) {
                $('#alert').prepend('<div class="alert alert-success alert-dismissible"><i class="fa-solid fa-circle-check"></i> ' + successText + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                if (refreshUrl) $('#cart').load(refreshUrl);
                if (buyNow && checkoutUrl) window.pdNavigateWithinShop(checkoutUrl);
            }

            if (json.redirect) {
                window.pdNavigateWithinShop(json.redirect);
            }
        },
        error: function (xhr) {
            console.error('Cart request failed:', xhr.status, xhr.responseText);
        }
    });
});

$(document).on('submit', 'form', function (e) {
    var element = this;
    var button = (e.originalEvent !== undefined && e.originalEvent.submitter !== undefined) ? e.originalEvent.submitter : '';

    if ($(element).attr('data-oc-toggle') == 'ajax' || $(button).attr('data-oc-toggle') == 'ajax') {
        e.preventDefault();

        var form = e.target;
        var action = $(button).attr('formaction') || $(form).attr('action');
        var method = $(button).attr('formmethod') || $(form).attr('method') || 'post';
        var enctype = $(button).attr('formenctype') || $(form).attr('enctype') || 'application/x-www-form-urlencoded';

        // https://github.com/opencart/opencart/issues/9690
        if (typeof CKEDITOR != 'undefined') {
            for (var instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].updateElement();
            }
        }

        $.ajax({
            url: action.replaceAll('&amp;', '&'),
            type: method,
            data: $(form).serialize(),
            dataType: 'json',
            contentType: enctype,
            beforeSend: function () {
                $(button).button('loading');
            },
            complete: function () {
                $(button).button('reset');
            },
            success: function (json, textStatus) {
                $('.alert-dismissible').remove();
                $(element).find('.is-invalid').removeClass('is-invalid');
                $(element).find('.invalid-feedback').removeClass('d-block');

                if (json['redirect']) {
                    window.pdNavigateWithinShop(json['redirect']);
                }

                if (typeof json['error'] == 'string') {
                    $('#alert').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ' + json['error'] + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                }

                if (typeof json['error'] == 'object') {
                    if (json['error']['warning']) {
                        $('#alert').prepend('<div class="alert alert-danger alert-dismissible"><i class="fa-solid fa-circle-exclamation"></i> ' + json['error']['warning'] + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    }

                    for (var key in json['error']) {
                        $('#input-' + key.replaceAll('_', '-')).addClass('is-invalid').find('.form-control, .form-select, .form-check-input, .form-check-label').addClass('is-invalid');
                        $('#error-' + key.replaceAll('_', '-')).html(json['error'][key]).addClass('d-block');
                    }
                }

                if (json['success']) {
                    $('#alert').prepend('<div class="alert alert-success alert-dismissible"><i class="fa-solid fa-circle-check"></i> ' + json['success'] + ' <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');

                    // Refresh
                    var url = $(form).attr('data-oc-load');
                    var target = $(form).attr('data-oc-target');

                    if (url !== undefined && target !== undefined) {
                        $(target).load(url);
                    }
                }

                // Replace any form values that correspond to form names.
                for (var key in json) {
                    $(element).find('[name=\'' + key + '\']').val(json[key]);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    }
});

$(document).on('submit', '#cart form.pd-cart-item-remove-form', function (e) {
    e.preventDefault();

    var form = this;
    var $form = $(form);
    var $cart = $('#cart');
    var wrapper = $cart.find('.pd-header-hover')[0];
    var keepOpen = wrapper && (
        wrapper.matches(':hover') ||
        wrapper.classList.contains('pd-cart-hover-open') ||
        !!wrapper.querySelector('.pd-cart-dropdown.show')
    );
    var button = form.querySelector('button[type="submit"]');
    var refreshing = false;

    if (button.disabled) return;

    button.disabled = true;
    form.setAttribute('aria-busy', 'true');

    $.ajax({
        url: form.action.replaceAll('&amp;', '&'),
        type: form.method || 'post',
        data: $form.serialize(),
        dataType: 'json'
    }).done(function (json) {
        if (json.error) {
            $cart.find('.pd-cart-dropdown').prepend(
                $('<div class="alert alert-danger mb-2" role="alert">').text(json.error)
            );
            return;
        }

        if (!json.success) {
            $cart.find('.pd-cart-dropdown').prepend(
                $('<div class="alert alert-danger mb-2" role="alert">').text('Không thể cập nhật giỏ hàng. Vui lòng thử lại.')
            );
            return;
        }

        var listUrl = ($form.attr('data-oc-load') || '').replaceAll('&amp;', '&');

        if (!listUrl) {
            console.error('Cart remove succeeded, but the cart refresh URL is missing.');
            return;
        }

        refreshing = true;
        $cart.load(listUrl, function (response, status, xhr) {
            if (status === 'error') {
                console.error('Cart refresh failed:', xhr.status, xhr.responseText);
                button.disabled = false;
                form.removeAttribute('aria-busy');
                return;
            }

            if (!keepOpen) return;

            var newWrapper = $cart.find('.pd-header-hover')[0];

            if (!newWrapper) return;

            if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                newWrapper.classList.add('pd-cart-hover-open');
            } else {
                var toggle = newWrapper.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle) bootstrap.Dropdown.getOrCreateInstance(toggle).show();
            }
        });
    }).fail(function (xhr) {
        console.error('Cart remove failed:', xhr.status, xhr.responseText);
        var message = xhr.responseJSON && xhr.responseJSON.error
            ? xhr.responseJSON.error
            : 'Không xóa được sản phẩm. Vui lòng thử lại.';
        $cart.find('.pd-cart-dropdown').prepend(
            $('<div class="alert alert-danger mb-2" role="alert">').text(message)
        );
    }).always(function () {
        if (!refreshing) {
            button.disabled = false;
            form.removeAttribute('aria-busy');
        }
    });
});

// Upload
$(document).on('click', 'button[data-oc-toggle=\'upload\']', function() {
    var element = this;

    if (!$(element).prop('disabled')) {
        $('#form-upload').remove();

        $('body').prepend('<form enctype="multipart/form-data" id="form-upload" style="display: none;"><input type="file" name="file" value=""/></form>');

        $('#form-upload input[name=\'file\']').trigger('click');

        $('#form-upload input[name=\'file\']').on('change', function(e) {
            if ((this.files[0].size / 1024) > parseInt($(element).attr('data-oc-size-max'), 10)) {
                window.pdAlert($(element).attr('data-oc-size-error'), { title: 'Tệp tải lên không hợp lệ', variant: 'error' });

                $(this).val('');
            }
        });

        if (typeof timer !== 'undefined') {
            clearInterval(timer);
        }

        var timer = setInterval(function() {
            if ($('#form-upload input[name=\'file\']').val() != '') {
                clearInterval(timer);

                $.ajax({
                    url: $(element).attr('data-oc-url'),
                    type: 'post',
                    data: new FormData($('#form-upload')[0]),
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $(element).button('loading');
                    },
                    complete: function() {
                        $(element).button('reset');
                    },
                    success: function(json) {
                        if (json['error']) {
                            window.pdAlert(json['error'], { title: 'Không thể tải tệp lên', variant: 'error' });
                        }

                        if (json['success']) {
                            window.pdAlert(json['success'], { title: 'Tải tệp thành công', variant: 'success' });
                        }

                        if (json['code']) {
                            $($(element).attr('data-oc-target')).attr('value', json['code']);
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });
            }
        }, 500);
    }
});

// Chain ajax calls.
class Chain {
    constructor() {
        this.start = false;
        this.data = [];
    }

    attach(call) {
        this.data.push(call);

        if (!this.start) {
            this.execute();
        }
    }

    execute() {
        if (this.data.length) {
            this.start = true;

            var call = this.data.shift();

            var jqxhr = call();

            jqxhr.done(function() {
                chain.execute();
            });
        } else {
            this.start = false;
        }
    }
}

var chain = new Chain();

// Autocomplete
+function($) {
    $.fn.autocomplete = function(option) {
        return this.each(function() {
            var element = this;
            var $dropdown = $('#' + $(element).attr('data-oc-target'));

            this.timer = null;
            this.items = [];

            $.extend(this, option);

            // Focus in
            $(element).on('focusin', function() {
                element.request();
            });

            // Focus out
            $(element).on('focusout', function(e) {
                if (!e.relatedTarget || !$(e.relatedTarget).hasClass('dropdown-item')) {
                    this.timer = setTimeout(function(object) {
                        object.removeClass('show');
                    }, 150, $dropdown);
                }
            });

            // Input
            $(element).on('input', function(e) {
                element.request();
            });

            // Click
            $dropdown.on('click', 'a', function(e) {
                e.preventDefault();

                var value = $(this).attr('href');

                if (element.items[value] !== undefined) {
                    element.select(element.items[value]);

                    $dropdown.removeClass('show');
                }
            });

            // Request
            this.request = function() {
                clearTimeout(this.timer);

                $('#autocomplete-loading').remove();
                $dropdown.find('li').remove();

                $dropdown.prepend('<li id="autocomplete-loading"><span class="dropdown-item text-center disabled"><i class="fa-solid fa-circle-notch fa-spin"></i></span></li>');
                $dropdown.addClass('show');

                this.timer = setTimeout(function(object) {
                    object.source($(object).val(), $.proxy(object.response, object));
                }, 150, this);
            }

            // Response
            this.response = function(json) {
                var html = '';
                var category = {};
                var name;
                var i = 0, j = 0;

                if (json.length) {
                    for (i = 0; i < json.length; i++) {
                        // update element items
                        this.items[json[i]['value']] = json[i];

                        if (!json[i]['category']) {
                            // ungrouped items
                            html += '<li><a href="' + json[i]['value'] + '" class="dropdown-item">' + json[i]['label'] + '</a></li>';
                        } else {
                            // grouped items
                            name = json[i]['category'];

                            if (!category[name]) {
                                category[name] = [];
                            }

                            category[name].push(json[i]);
                        }
                    }

                    for (name in category) {
                        html += '<li><h6 class="dropdown-header">' + name + '</h6></li>';

                        for (j = 0; j < category[name].length; j++) {
                            html += '<li><a href="' + category[name][j]['value'] + '" class="dropdown-item">' + category[name][j]['label'] + '</a></li>';
                        }
                    }

                    $dropdown.html(html);
                } else {
                    $dropdown.removeClass('show');
                }
            }
        });
    }
}(jQuery);

$(document).ready(function() {
    var exitLogoutUrl = document.body.getAttribute('data-pd-exit-logout-url');
    var exitLogoutToken = document.body.getAttribute('data-pd-exit-logout-token');
    if (exitLogoutUrl && exitLogoutToken) {
        var leavingForShopPage = false;
        window.pdPreserveSessionForNavigation = function(url) {
            try {
                leavingForShopPage = new URL(url, window.location.href).origin === window.location.origin;
            } catch (error) {
                leavingForShopPage = false;
            }
        };

        document.addEventListener('click', function(event) {
            if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            var link = event.target.closest('a[href]');
            if (!link || link.hasAttribute('download') || (link.target && link.target.toLowerCase() !== '_self')) return;

            try {
                var destination = new URL(link.href, window.location.href);
                if (destination.origin !== window.location.origin) return;
                destination.hash = '';
                var current = new URL(window.location.href);
                current.hash = '';
                if (destination.href === current.href) return;

                leavingForShopPage = true;
                window.setTimeout(function() {
                    if (event.defaultPrevented) leavingForShopPage = false;
                }, 0);
            } catch (error) {
                leavingForShopPage = false;
            }
        }, true);

        document.addEventListener('submit', function(event) {
            var form = event.target;
            var target = form.getAttribute('target');
            if (target && target.toLowerCase() !== '_self') return;

            leavingForShopPage = true;
            window.setTimeout(function() {
                if (event.defaultPrevented) leavingForShopPage = false;
            }, 0);
        }, true);

        window.addEventListener('keydown', function(event) {
            if (event.key === 'F5' || ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'r')) {
                leavingForShopPage = true;
            }
        }, true);

        window.addEventListener('pageshow', function() {
            leavingForShopPage = false;
        });

        if (window.navigation && typeof window.navigation.addEventListener === 'function') {
            window.navigation.addEventListener('navigate', function(event) {
                if (!event.destination || !event.destination.url) return;

                try {
                    leavingForShopPage = new URL(event.destination.url).origin === window.location.origin;
                } catch (error) {
                    leavingForShopPage = false;
                }
            });
        }

        window.addEventListener('pagehide', function(event) {
            if (leavingForShopPage || event.persisted) return;

            var payload = new URLSearchParams({token: exitLogoutToken});
            if (navigator.sendBeacon) {
                navigator.sendBeacon(exitLogoutUrl, payload);
            } else {
                fetch(exitLogoutUrl, {method: 'POST', body: payload, credentials: 'same-origin', keepalive: true});
            }
        });
    }

    // Currency
    $('.pd-currency-form .dropdown-item').on('click', function(e) {
        e.preventDefault();

        var form = $(this).closest('.pd-currency-form');
        form.find('input[name=\'code\']').val($(this).data('code'));

        if (typeof window.pdPreserveSessionForNavigation === 'function') {
            window.pdPreserveSessionForNavigation(form.attr('action'));
        }
        form.submit();
    });

    // Share the selected layout across product listing pages without inheriting
    // OpenCart's legacy "display=list" preference.
    var productViewKey = 'pd_product_view';
    var isCategoryListing = $('#product-category').length > 0;
    var setProductView = function(view) {
        var isList = !isCategoryListing && view === 'list';
        $('#product-list').toggleClass('ps-view-list', isList).toggleClass('ps-view-grid', !isList);
        $('#button-list').toggleClass('active is-active', isList).attr('aria-pressed', String(isList));
        $('#button-grid').toggleClass('active is-active', !isList).attr('aria-pressed', String(!isList));
        localStorage.setItem(productViewKey, isList ? 'list' : 'grid');
    };

    $('#button-list').on('click', function() { setProductView('list'); });
    $('#button-grid').on('click', function() { setProductView('grid'); });
    if ($('#product-list').length) {
        setProductView(!isCategoryListing && localStorage.getItem(productViewKey) === 'list' ? 'list' : 'grid');
    }

    /* Agree to Terms */
    $('body').on('click', '.modal-link', function(e) {
        e.preventDefault();

        var element = this;

        $('#modal-information').remove();

        $.ajax({
            url: $(element).attr('href'),
            dataType: 'html',
            success: function(html) {
                $('body').append(html);

                $('#modal-information').modal('show');
            }
        });
    });

    // Cookie Policy
    $('#cookie button').on('click', function() {
        var element = this;

        $.ajax({
            url: $(this).val(),
            type: 'get',
            dataType: 'json',
            beforeSend: function() {
                $(element).button('loading');
            },
            complete: function() {
                $(element).button('reset');
            },
            success: function(json) {
                if (json['success']) {
                    $('#cookie').fadeOut(400, function() {
                        $('#cookie').remove();
                    });
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });
});
