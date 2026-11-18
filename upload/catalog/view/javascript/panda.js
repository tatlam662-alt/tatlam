/**
 * PanDaShop - Modern Tech Store Theme JavaScript
 * Lightweight, non-intrusive micro-interactions
 */

document.addEventListener('DOMContentLoaded', function() {
  const headerCart = document.querySelector('#cart');
  if (headerCart && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
    let cartCloseTimer = null;

    headerCart.addEventListener('mouseover', function(event) {
      const wrapper = event.target.closest('.pd-header-hover');
      if (!wrapper) return;
      window.clearTimeout(cartCloseTimer);
      wrapper.classList.add('pd-cart-hover-open');
    });

    headerCart.addEventListener('mouseout', function(event) {
      const wrapper = event.target.closest('.pd-header-hover');
      if (!wrapper || wrapper.contains(event.relatedTarget)) return;

      window.clearTimeout(cartCloseTimer);
      cartCloseTimer = window.setTimeout(function() {
        if (!wrapper.matches(':hover')) wrapper.classList.remove('pd-cart-hover-open');
      }, 240);
    });
  }

  // Keep category flyout panels beside the sidebar and inside the viewport.
  const categorySidebar = document.querySelector('#product-category #column-left');
  if (categorySidebar && window.matchMedia('(min-width: 992px)').matches) {
    const flyoutItems = categorySidebar.querySelectorAll('.flyout-cat-item');
    let activeFlyout = null;
    let flyoutCloseTimer = null;

    function positionFlyout(item) {
      const panel = item.querySelector('.flyout-menu-panel');
      if (!panel) return;

      const itemRect = item.getBoundingClientRect();
      const panelLeft = Math.max(8, Math.min(itemRect.right, window.innerWidth - 280));
      const panelWidth = Math.min(480, window.innerWidth - panelLeft - 8);
      panel.style.display = 'block';
      panel.style.position = 'fixed';
      panel.style.width = panelWidth + 'px';
      panel.style.maxHeight = 'calc(100dvh - 24px)';
      panel.style.overflowY = 'auto';
      panel.style.overscrollBehavior = 'contain';
      panel.style.left = panelLeft + 'px';
      panel.style.top = Math.max(8, Math.min(itemRect.top, window.innerHeight - panel.offsetHeight - 8)) + 'px';
      panel.style.zIndex = '1050';
      activeFlyout = item;
    }

    function clearFlyout(item) {
      window.clearTimeout(flyoutCloseTimer);
      const panel = item.querySelector('.flyout-menu-panel');
      if (!panel) return;
      panel.removeAttribute('style');
      if (activeFlyout === item) activeFlyout = null;
    }

    flyoutItems.forEach(function(item) {
      const panel = item.querySelector('.flyout-menu-panel');
      item.addEventListener('mouseenter', function() {
        window.clearTimeout(flyoutCloseTimer);
        if (activeFlyout && activeFlyout !== item) clearFlyout(activeFlyout);
        positionFlyout(item);
      });
      item.addEventListener('focusin', function() { positionFlyout(item); });
      item.addEventListener('mouseleave', function(event) {
        if (!item.contains(event.relatedTarget) && activeFlyout === item) {
          flyoutCloseTimer = window.setTimeout(function() { clearFlyout(item); }, 180);
        }
      });
      item.addEventListener('focusout', function(event) {
        if (!item.contains(event.relatedTarget)) clearFlyout(item);
      });
      if (panel) {
        panel.addEventListener('mouseenter', function() { window.clearTimeout(flyoutCloseTimer); });
        panel.addEventListener('mouseleave', function(event) {
          if (!item.contains(event.relatedTarget) && activeFlyout === item) {
            flyoutCloseTimer = window.setTimeout(function() { clearFlyout(item); }, 180);
          }
        });
      }
    });

    window.addEventListener('resize', function() {
      if (activeFlyout) positionFlyout(activeFlyout);
    }, { passive: true });
    window.addEventListener('scroll', function() {
      if (activeFlyout) positionFlyout(activeFlyout);
    }, { passive: true, capture: true });
    const categoryList = categorySidebar.querySelector('.flyout-cat-list');
    if (categoryList) categoryList.addEventListener('scroll', function() {
      if (activeFlyout) positionFlyout(activeFlyout);
    }, { passive: true });
  }

  // Product suggestions use the native OpenCart route and render API values as text.
  document.querySelectorAll('.pd-search-input-autocomplete').forEach(function(input) {
    const wrapper = input.closest('.pd-search-autocomplete');
    const list = wrapper && wrapper.querySelector('.pd-search-suggestions');
    if (!list) return;

    const messages = {
      loading: input.dataset.suggestionLoading || 'Searching products…',
      empty: input.dataset.suggestionEmpty || 'No matching products or categories found.',
      error: input.dataset.suggestionError || 'Could not load suggestions. Please try searching.',
      products: input.dataset.suggestionProducts || 'Products',
      categories: input.dataset.suggestionCategories || 'Categories'
    };
    let timer = null;
    let request = null;
    let activeIndex = -1;

    function closeSuggestions() {
      list.hidden = true;
      list.replaceChildren();
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      activeIndex = -1;
    }

    function showStatus(message) {
      list.replaceChildren();
      const status = document.createElement('li');
      status.className = 'pd-search-suggestion-status';
      status.setAttribute('role', 'option');
      status.textContent = message;
      list.appendChild(status);
      list.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      input.removeAttribute('aria-activedescendant');
      activeIndex = -1;
    }

    function choose(index) {
      const options = list.querySelectorAll('.pd-search-suggestion');
      if (!options.length) return;
      activeIndex = (index + options.length) % options.length;
      options.forEach(function(option, optionIndex) {
        const selected = optionIndex === activeIndex;
        option.setAttribute('aria-selected', selected ? 'true' : 'false');
        if (selected) {
          input.setAttribute('aria-activedescendant', option.id);
          option.scrollIntoView({ block: 'nearest' });
        }
      });
    }

    function addGroupHeading(label) {
      const heading = document.createElement('li');
      heading.className = 'pd-search-suggestion-heading';
      heading.setAttribute('role', 'presentation');
      heading.textContent = label;
      list.appendChild(heading);
    }

    function renderSuggestions(payload) {
      const suggestions = Array.isArray(payload) ? { products: payload, categories: [] } : (payload || {});
      const products = Array.isArray(suggestions.products) ? suggestions.products.slice(0, 5) : [];
      const categories = Array.isArray(suggestions.categories) ? suggestions.categories.slice(0, 3) : [];
      list.replaceChildren();

      if (products.length) addGroupHeading(messages.products);
      products.forEach(function(product, index) {
        const item = document.createElement('li');
        item.setAttribute('role', 'presentation');
        const link = document.createElement('a');
        link.className = 'pd-search-suggestion';
        link.href = product.href;
        link.id = 'pd-search-option-' + Math.random().toString(36).slice(2) + '-' + index;
        link.setAttribute('role', 'option');
        link.setAttribute('aria-selected', 'false');

        const image = document.createElement('img');
        image.src = product.image;
        image.alt = '';
        image.loading = 'lazy';
        const copy = document.createElement('span');
        copy.className = 'pd-search-suggestion-copy';
        const name = document.createElement('span');
        name.className = 'pd-search-suggestion-name';
        name.textContent = product.name;
        const price = document.createElement('span');
        price.className = 'pd-search-suggestion-price';
        price.textContent = product.price;
        copy.append(name);
        if (product.price) copy.appendChild(price);
        link.append(image, copy);
        item.appendChild(link);
        list.appendChild(item);
      });

      if (categories.length) addGroupHeading(messages.categories);
      categories.forEach(function(category, index) {
        const item = document.createElement('li');
        item.setAttribute('role', 'presentation');
        const link = document.createElement('a');
        link.className = 'pd-search-suggestion pd-search-suggestion-category';
        link.href = category.href;
        link.id = 'pd-search-category-' + Math.random().toString(36).slice(2) + '-' + index;
        link.setAttribute('role', 'option');
        link.setAttribute('aria-selected', 'false');

        if (category.image) {
          const image = document.createElement('img');
          image.src = category.image;
          image.alt = '';
          image.loading = 'lazy';
          link.appendChild(image);
        } else {
          const icon = document.createElement('span');
          icon.className = 'pd-search-category-icon';
          icon.setAttribute('aria-hidden', 'true');
          icon.innerHTML = '<i class="fa-solid fa-layer-group"></i>';
          link.appendChild(icon);
        }

        const name = document.createElement('span');
        name.className = 'pd-search-suggestion-name';
        name.textContent = category.name;
        link.appendChild(name);
        item.appendChild(link);
        list.appendChild(item);
      });

      const hasSuggestions = products.length > 0 || categories.length > 0;
      list.hidden = !hasSuggestions;
      input.setAttribute('aria-expanded', hasSuggestions ? 'true' : 'false');
      activeIndex = -1;
    }

    input.addEventListener('input', function() {
      window.clearTimeout(timer);
      if (request) request.abort();
      const query = input.value.trim();
      if (query.length < 2) {
        closeSuggestions();
        return;
      }

      showStatus(messages.loading);
      timer = window.setTimeout(function() {
        request = new AbortController();
        const url = new URL(input.dataset.suggestUrl, window.location.href);
        url.searchParams.set('search', query);
        fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: request.signal })
          .then(function(response) {
            if (!response.ok) throw new Error('Search request failed');
            return response.json();
          })
          .then(function(suggestions) {
            if (input.value.trim() !== query) return;
            const hasProducts = Array.isArray(suggestions) ? suggestions.length > 0 : !!(suggestions && ((suggestions.products && suggestions.products.length) || (suggestions.categories && suggestions.categories.length)));
            if (hasProducts) renderSuggestions(suggestions);
            else showStatus(messages.empty);
          })
          .catch(function(error) {
            if (error.name !== 'AbortError' && input.value.trim() === query) showStatus(messages.error);
          });
      }, 220);
    });

    input.addEventListener('keydown', function(event) {
      if (list.hidden) return;
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        choose(activeIndex + 1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        choose(activeIndex < 0 ? list.querySelectorAll('.pd-search-suggestion').length - 1 : activeIndex - 1);
      } else if (event.key === 'Escape') {
        closeSuggestions();
      } else if (event.key === 'Enter' && activeIndex >= 0) {
        const option = list.querySelectorAll('.pd-search-suggestion')[activeIndex];
        if (option) {
          event.preventDefault();
          window.pdNavigateWithinShop(option.href);
        }
      }
    });

    list.addEventListener('mousedown', function(event) { event.preventDefault(); });
    list.addEventListener('click', function(event) {
      const option = event.target.closest('.pd-search-suggestion');
      if (option) {
        event.preventDefault();
        window.pdNavigateWithinShop(option.href);
      }
    });
    document.addEventListener('click', function(event) {
      if (!wrapper.contains(event.target)) closeSuggestions();
    });
    input.addEventListener('blur', function() {
      window.setTimeout(function() {
        if (!wrapper.contains(document.activeElement)) closeSuggestions();
      }, 120);
    });
  });

  // 1. Mobile Offcanvas Menu Handlers
  const toggleBtn = document.querySelector('.pd-mobile-nav-toggle');
  const offcanvas = document.querySelector('.pd-offcanvas');
  const backdrop = document.querySelector('.pd-offcanvas-backdrop');
  const closeBtn = document.querySelector('.pd-offcanvas-close');

  function openMenu() {
    if (offcanvas && backdrop) {
      offcanvas.classList.add('show');
      offcanvas.removeAttribute('inert');
      offcanvas.setAttribute('aria-hidden', 'false');
      backdrop.classList.add('show');
      document.body.style.overflow = 'hidden';
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
      if (closeBtn) closeBtn.focus();
    }
  }

  function closeMenu() {
    if (offcanvas && backdrop) {
      offcanvas.classList.remove('show');
      offcanvas.setAttribute('aria-hidden', 'true');
      offcanvas.setAttribute('inert', '');
      backdrop.classList.remove('show');
      document.body.style.overflow = '';
      if (toggleBtn) {
        toggleBtn.setAttribute('aria-expanded', 'false');
        toggleBtn.focus();
      }
    }
  }

  if (toggleBtn) toggleBtn.addEventListener('click', openMenu);
  if (closeBtn) closeBtn.addEventListener('click', closeMenu);
  if (backdrop) backdrop.addEventListener('click', closeMenu);
  document.addEventListener('keydown', function(event) {
    if (!offcanvas || !offcanvas.classList.contains('show')) return;

    if (event.key === 'Escape') {
      event.preventDefault();
      closeMenu();
      return;
    }

    if (event.key === 'Tab') {
      const focusable = Array.from(offcanvas.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'))
        .filter(function(element) { return element.getClientRects().length > 0; });

      if (!focusable.length) {
        event.preventDefault();
        if (closeBtn) closeBtn.focus();
        return;
      }

      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });

  // 2. Mobile Sticky Buy Bar Observer for Product Detail
  const mainBuyBtn = document.getElementById('button-cart');
  const stickyBar = document.querySelector('.pd-mobile-sticky-bar');

  if (mainBuyBtn && stickyBar) {
    document.body.classList.add('has-sticky-bar');
    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          stickyBar.style.transform = 'translateY(100%)';
          stickyBar.style.transition = 'transform 0.25s ease-in-out';
        } else {
          stickyBar.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });

    observer.observe(mainBuyBtn);

    // Sync click from sticky button to real OpenCart cart button
    const stickyCartBtn = document.getElementById('pd-sticky-btn-cart');
    if (stickyCartBtn) {
      stickyCartBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        mainBuyBtn.click();
      });
    }
  }

  // Buy now uses the product form and OpenCart cart endpoint.
  // 3. Calculate Discount Percentage for Badges
  function calculateDiscountBadges() {
    const badges = document.querySelectorAll('[data-calc-sale]');
    badges.forEach(function(badge) {
      const priceStr = badge.getAttribute('data-price') || '';
      const specialStr = badge.getAttribute('data-special') || '';

      // VND strings use dots as thousands separators; parse all digits to avoid treating
      // a price such as 25.990.000₫ as the decimal number 25.99.
      const p1 = Number(priceStr.replace(/[^0-9]/g, ''));
      const p2 = Number(specialStr.replace(/[^0-9]/g, ''));

      if (p1 && p2 && p1 > p2) {
        const percent = Math.round(((p1 - p2) / p1) * 100);
        if (percent > 0 && percent < 100) {
          badge.textContent = '-' + percent + '%';
        }
      }
    });
  }
  calculateDiscountBadges();

  // Keep OpenCart's header counters in sync with wishlist and compare AJAX responses.
  if (window.jQuery) {
    window.jQuery(document).on('ajaxSuccess.pdHeaderCounters', function(event, xhr, settings, json) {
      if (!settings || !json || !json.total) return;

      const route = new URL(settings.url, window.location.href).searchParams.get('route') || '';
      const countMatch = json.total.match(/\((\d+)\)/);
      if (!countMatch) return;

      const selector = (route.indexOf('wishlist.add') !== -1 || route.indexOf('wishlist.remove') !== -1)
        ? '.pd-wishlist-count-badge'
        : route.indexOf('compare.add') !== -1
          ? '.pd-compare-count-badge, .pd-compare-total-text'
          : '';

      if (selector) document.querySelectorAll(selector).forEach(function(target) {
        target.textContent = target.classList.contains('pd-compare-total-text') ? json.total : countMatch[1];
      });
      if (selector.indexOf('.pd-compare-count-badge') !== -1) {
        document.querySelectorAll('#compare-total.ps-btn-compare').forEach(function(link) {
          link.classList.toggle('has-items', Number(countMatch[1]) > 0);
        });
      }
    });
  }

  // Present OpenCart success messages as short, accessible toast feedback.
  const alertContainer = document.getElementById('alert');
  if (alertContainer && window.MutationObserver) {
    new MutationObserver(function() {
      alertContainer.querySelectorAll('.alert-dismissible:not(.pd-toast)').forEach(function(alert) {
        alert.classList.add('pd-toast');
        if (alert.classList.contains('alert-success')) {
          alert.setAttribute('role', 'status');
          window.setTimeout(function() { alert.remove(); }, 2500);
        } else {
          alert.setAttribute('role', 'alert');
        }
      });
    }).observe(alertContainer, { childList: true, subtree: true });
  }

});
