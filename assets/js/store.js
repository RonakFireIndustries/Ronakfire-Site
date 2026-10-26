/* ==========================================================================
   Ronak Fire store — storefront behaviour
   Progressive enhancement only: every form and link works without JS.
   ========================================================================== */
(function () {
    'use strict';

    var qs = function (selector, scope) {
        return (scope || document).querySelector(selector);
    };
    var qsa = function (selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    };

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function formatMoney(paise) {
        var rounded = Math.round(Number(paise) || 0);
        var whole = Math.floor(rounded / 100);
        var decimal = Math.abs(rounded % 100);
        var text = String(whole);
        var lastThree = text.slice(-3);
        var rest = text.slice(0, -3);
        if (rest !== '') {
            lastThree = ',' + lastThree;
            rest = rest.replace(/\B(?=(\d{2})+(?!\d))/g, ',');
        }
        var value = '\u20B9' + rest + lastThree;
        if (decimal !== 0) {
            value += '.' + String(decimal).padStart(2, '0');
        }
        return value;
    }

    function toast(message, type) {
        var stack = qs('#toastStack');
        if (!stack) {
            return;
        }
        var el = document.createElement('div');
        el.className = 'toast' + (type ? ' toast--' + type : '');
        el.setAttribute('role', 'status');
        el.textContent = message;
        stack.appendChild(el);
        window.setTimeout(function () {
            el.style.opacity = '0';
            el.style.transform = 'translateY(8px)';
            el.style.transition = 'opacity .2s, transform .2s';
            window.setTimeout(function () {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 220);
        }, 4200);
    }

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments;
            var context = this;
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                fn.apply(context, args);
            }, wait);
        };
    }

    /* ----------------------------------------------------------------------
       Header, dropdown, mobile navigation
       ---------------------------------------------------------------------- */

    function initHeader() {
        var header = qs('#siteHeader');
        if (header) {
            var onScroll = function () {
                header.classList.toggle('is-scrolled', window.scrollY > 8);
            };
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        }

        var dropdowns = qsa('[data-dropdown]');
        var closeDropdowns = function (except) {
            dropdowns.forEach(function (dropdown) {
                if (dropdown === except) {
                    return;
                }
                dropdown.classList.remove('is-open');
                var toggle = qs('[data-dropdown-toggle]', dropdown);
                if (toggle) {
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        };

        dropdowns.forEach(function (dropdown) {
            var toggle = qs('[data-dropdown-toggle]', dropdown);
            if (!toggle) {
                return;
            }
            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var open = !dropdown.classList.contains('is-open');
                closeDropdowns(dropdown);
                dropdown.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('[data-dropdown]')) {
                closeDropdowns(null);
            }
        });

        /* Mobile menu */
        var toggle = qs('[data-menu-toggle]');
        var mobileNav = qs('#mobileNav');
        var scrim = qs('[data-menu-close]');
        var closeTimer = null;

        function openMenu() {
            if (!mobileNav || !toggle) {
                return;
            }
            window.clearTimeout(closeTimer);
            mobileNav.hidden = false;
            if (scrim) {
                scrim.hidden = false;
            }
            document.body.classList.add('no-scroll');
            toggle.setAttribute('aria-expanded', 'true');
            window.requestAnimationFrame(function () {
                mobileNav.classList.add('is-open');
            });
        }

        function closeMenu() {
            if (!mobileNav || !toggle) {
                return;
            }
            mobileNav.classList.remove('is-open');
            document.body.classList.remove('no-scroll');
            toggle.setAttribute('aria-expanded', 'false');
            if (scrim) {
                scrim.hidden = true;
            }
            closeTimer = window.setTimeout(function () {
                mobileNav.hidden = true;
            }, 320);
        }

        if (toggle && mobileNav) {
            toggle.addEventListener('click', function () {
                if (mobileNav.classList.contains('is-open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
            if (scrim) {
                scrim.addEventListener('click', closeMenu);
            }
            qsa('a', mobileNav).forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });
        }

        /* Search overlay */
        var overlay = qs('#searchOverlay');
        var openSearch = function () {
            if (!overlay) {
                return;
            }
            overlay.hidden = false;
            document.body.classList.add('no-scroll');
            var input = qs('input[type="search"]', overlay);
            if (input) {
                window.setTimeout(function () {
                    input.focus();
                    input.select();
                }, 20);
            }
        };
        var closeSearch = function () {
            if (!overlay) {
                return;
            }
            overlay.hidden = true;
            document.body.classList.remove('no-scroll');
        };

        qsa('[data-search-open]').forEach(function (button) {
            button.addEventListener('click', openSearch);
        });
        qsa('[data-search-close]').forEach(function (button) {
            button.addEventListener('click', closeSearch);
        });
        if (overlay) {
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    closeSearch();
                }
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            closeDropdowns(null);
            if (overlay && !overlay.hidden) {
                closeSearch();
            }
            if (mobileNav && mobileNav.classList.contains('is-open')) {
                closeMenu();
            }
            var sidebar = qs('.catalog-sidebar.is-open');
            if (sidebar) {
                sidebar.classList.remove('is-open');
                var filterScrim = qs('.filter-scrim');
                if (filterScrim) {
                    filterScrim.hidden = true;
                }
                document.body.classList.remove('no-scroll');
            }
        });
    }

    /* ----------------------------------------------------------------------
       Flash messages
       ---------------------------------------------------------------------- */

    function initFlash() {
        qsa('[data-flash-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                var flash = button.closest('.flash');
                if (flash && flash.parentNode) {
                    flash.parentNode.removeChild(flash);
                }
            });
        });
    }

    /* ----------------------------------------------------------------------
       Catalog filters and auto-submit
       ---------------------------------------------------------------------- */

    function initCatalog() {
        var sidebar = qs('.catalog-sidebar');
        var scrim = qs('.filter-scrim');

        function openFilters() {
            if (!sidebar) {
                return;
            }
            sidebar.classList.add('is-open');
            if (scrim) {
                scrim.hidden = false;
            }
            document.body.classList.add('no-scroll');
        }

        function closeFilters() {
            if (!sidebar) {
                return;
            }
            sidebar.classList.remove('is-open');
            if (scrim) {
                scrim.hidden = true;
            }
            document.body.classList.remove('no-scroll');
        }

        qsa('[data-filter-toggle]').forEach(function (button) {
            button.addEventListener('click', openFilters);
        });
        qsa('[data-filter-close]').forEach(function (button) {
            button.addEventListener('click', closeFilters);
        });

        qsa('[data-auto-submit]').forEach(function (field) {
            field.addEventListener('change', function () {
                var form = field.closest('form');
                if (form) {
                    form.submit();
                }
            });
        });
    }

    /* ----------------------------------------------------------------------
       Gallery and tabs
       ---------------------------------------------------------------------- */

    function initGallery() {
        var gallery = qs('[data-gallery]');
        if (!gallery) {
            return;
        }
        var main = qs('[data-gallery-main]', gallery);
        var thumbs = qsa('[data-gallery-thumb]', gallery);
        if (!main || !thumbs.length) {
            return;
        }
        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                var src = thumb.getAttribute('data-src');
                if (!src) {
                    return;
                }
                var previous = main.getAttribute('src');
                main.setAttribute('src', src);
                if (previous && main.complete) {
                    main.style.opacity = '0';
                    window.requestAnimationFrame(function () {
                        main.style.transition = 'opacity .18s';
                        main.style.opacity = '1';
                    });
                }
                thumbs.forEach(function (other) {
                    other.classList.remove('is-active');
                });
                thumb.classList.add('is-active');
            });
        });
    }

    function initTabs() {
        qsa('[data-tabs]').forEach(function (tabs) {
            var buttons = qsa('[data-tab]', tabs);
            var panels = qsa('[data-panel]', tabs);
            buttons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var target = button.getAttribute('data-tab');
                    buttons.forEach(function (other) {
                        var active = other === button;
                        other.classList.toggle('is-active', active);
                        other.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    panels.forEach(function (panel) {
                        panel.classList.toggle('is-active', panel.getAttribute('data-panel') === target);
                    });
                });
            });
        });
    }

    /* ----------------------------------------------------------------------
       Quantity steppers
       ---------------------------------------------------------------------- */

    function initQuantity() {
        function bind(container, downSelector, upSelector) {
            var input = qs('input[type="number"]', container);
            if (!input) {
                return;
            }
            var min = parseInt(input.getAttribute('min'), 10);
            var max = parseInt(input.getAttribute('max'), 10);
            if (isNaN(min)) {
                min = 1;
            }
            if (isNaN(max)) {
                max = 999;
            }
            var step = function (direction) {
                var value = parseInt(input.value, 10);
                if (isNaN(value)) {
                    value = min;
                }
                value += direction;
                if (value < min) {
                    value = min;
                }
                if (value > max) {
                    value = max;
                }
                input.value = value;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            };
            var down = qs(downSelector, container);
            var up = qs(upSelector, container);
            if (down) {
                down.addEventListener('click', function () {
                    step(-1);
                });
            }
            if (up) {
                up.addEventListener('click', function () {
                    step(1);
                });
            }
        }

        qsa('[data-qty]').forEach(function (stepper) {
            bind(stepper, '[data-qty-down]', '[data-qty-up]');
        });
        qsa('[data-cart-qty-form]').forEach(function (form) {
            bind(form, '[data-cart-qty-down]', '[data-cart-qty-up]');
        });
    }

    /* ----------------------------------------------------------------------
       Checkout delivery toggle
       ---------------------------------------------------------------------- */

    function initCheckout() {
        var same = qs('[data-ship-same]');
        var fields = qs('[data-ship-fields]');
        if (!same || !fields) {
            return;
        }
        var sync = function () {
            fields.hidden = same.checked;
        };
        same.addEventListener('change', sync);
        sync();
    }

    /* ----------------------------------------------------------------------
       Confirmations
       ---------------------------------------------------------------------- */

    function initConfirmations() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form.matches || !form.matches('form[data-confirm]')) {
                return;
            }
            var message = form.getAttribute('data-confirm') || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    }

    /* ----------------------------------------------------------------------
       Product configurator
       ---------------------------------------------------------------------- */

    function initConfigurator() {
        var form = qs('[data-configurator]');
        if (!form) {
            return;
        }

        var base = Number(form.getAttribute('data-base-price')) || 0;
        var dimRate = Number(form.getAttribute('data-dim-rate')) || 0;
        var discountBp = Number(form.getAttribute('data-discount-bp')) || 0;
        var authed = form.getAttribute('data-authed') === '1';
        var csrf = form.getAttribute('data-csrf') || '';
        var priceEl = qs('[data-unit-price]', form);
        var summaryEl = qs('[data-config-summary]', form);
        var areaEl = qs('[data-dim-area]', form);
        var quoteLink = qs('a[href^="/quote"]', form);
        var addButton = qs('[data-add-to-cart]', form);

        function clamp(value, min, max) {
            if (isNaN(value)) {
                value = min;
            }
            return Math.max(min, Math.min(max, value));
        }

        function selectionData() {
            var selection = { groups: {}, dimensions: {} };
            var summary = [];
            var deltas = 0;
            var multBp = 0;
            var area = 0;

            qsa('[data-option-group]', form).forEach(function (group) {
                var slug = group.getAttribute('data-slug') || '';
                var name = group.getAttribute('data-name') || slug;
                var dimInputs = qsa('[data-dim]', group);

                if (dimInputs.length) {
                    var values = {};
                    dimInputs.forEach(function (input) {
                        var min = parseInt(input.getAttribute('min'), 10);
                        var max = parseInt(input.getAttribute('max'), 10);
                        if (isNaN(min)) {
                            min = 100;
                        }
                        if (isNaN(max)) {
                            max = 5000;
                        }
                        var value = clamp(parseInt(input.value, 10), min, max);
                        values[input.getAttribute('data-dim')] = value;
                    });
                    var w = values.w || 0;
                    var h = values.h || 0;
                    var d = values.d || 0;
                    var groupArea = 2 * ((w * h) + (w * d) + (h * d)) / 1000000;
                    area += groupArea;
                    selection.dimensions[slug] = { w: w, h: h, d: d };
                    summary.push(name + ': ' + w + ' \u00D7 ' + h + ' \u00D7 ' + d + ' mm');
                    return;
                }

                var checked = qsa('input:checked', group);
                var isCheckbox = !!qs('input[type="checkbox"]', group);
                checked.forEach(function (input) {
                    var id = parseInt(input.value, 10);
                    if (isCheckbox) {
                        if (!Array.isArray(selection.groups[slug])) {
                            selection.groups[slug] = [];
                        }
                        selection.groups[slug].push(id);
                    } else {
                        selection.groups[slug] = id;
                    }
                    deltas += Number(input.getAttribute('data-delta')) || 0;
                    multBp += Number(input.getAttribute('data-mult')) || 0;
                    summary.push(name + ': ' + (input.getAttribute('data-label') || input.value));
                });
            });

            return {
                selection: selection,
                summary: summary,
                area: area,
                deltas: deltas,
                multBp: multBp
            };
        }

        function renderSummary(lines) {
            if (!summaryEl) {
                return;
            }
            summaryEl.textContent = '';
            if (!lines.length) {
                return;
            }
            var label = document.createElement('span');
            label.className = 'config-summary__label';
            label.textContent = 'Your configuration';
            var list = document.createElement('ul');
            lines.forEach(function (line) {
                var item = document.createElement('li');
                item.textContent = line;
                list.appendChild(item);
            });
            summaryEl.appendChild(label);
            summaryEl.appendChild(list);
        }

        function calculate() {
            var data = selectionData();
            var dimensionCharge = Math.round(data.area * dimRate);
            var gross = base + dimensionCharge + data.deltas;
            if (data.multBp !== 0) {
                gross = Math.round(gross * (10000 + data.multBp) / 10000);
            }
            var unit = gross;
            if (discountBp > 0) {
                unit = Math.round(gross * (10000 - discountBp) / 10000);
            }
            unit = Math.max(0, unit);

            if (priceEl) {
                priceEl.textContent = formatMoney(unit);
            }
            if (areaEl) {
                areaEl.textContent = 'Surface area: ' + data.area.toFixed(2) + ' m\u00B2';
            }
            renderSummary(data.summary);

            if (quoteLink) {
                try {
                    var url = new URL(quoteLink.getAttribute('href'), window.location.origin);
                    url.searchParams.set('product', form.getAttribute('data-product-slug') || '');
                    if (data.summary.length) {
                        url.searchParams.set('configuration', data.summary.join(' \u00B7 '));
                    }
                    quoteLink.setAttribute('href', url.pathname + url.search);
                } catch (error) {
                    /* Older browsers: leave the plain quote link in place. */
                }
            }

            return data;
        }

        form.addEventListener('change', calculate);
        form.addEventListener('input', debounce(calculate, 120));
        calculate();

        if (!authed || !addButton) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var data = calculate();
            var qtyInput = qs('input[name="qty"]', form);
            var payload = {
                product_id: Number(form.getAttribute('data-product-id')) || 0,
                qty: qtyInput ? Math.max(1, parseInt(qtyInput.value, 10) || 1) : 1,
                selection: data.selection
            };

            addButton.disabled = true;
            var original = addButton.innerHTML;

            fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify(payload)
            }).then(function (response) {
                return response.json().then(function (body) {
                    return { ok: response.ok, body: body };
                });
            }).then(function (result) {
                if (!result.ok || !result.body || result.body.ok !== true) {
                    var message = result.body && result.body.message ? result.body.message : 'That configuration could not be added.';
                    toast(message, 'error');
                    return;
                }
                var counter = qs('[data-cart-count]');
                if (counter && typeof result.body.count !== 'undefined') {
                    counter.textContent = result.body.count;
                    counter.classList.toggle('is-visible', Number(result.body.count) > 0);
                }
                toast(result.body.message || 'Added to your order.', 'success');
            }).catch(function () {
                toast('We could not reach the server. Please try again.', 'error');
            }).finally(function () {
                addButton.disabled = false;
                addButton.innerHTML = original;
            });
        });
    }

    /* ----------------------------------------------------------------------
       Boot
       ---------------------------------------------------------------------- */

    onReady(function () {
        initHeader();
        initFlash();
        initCatalog();
        initGallery();
        initTabs();
        initQuantity();
        initCheckout();
        initConfirmations();
        initConfigurator();
    });
})();
