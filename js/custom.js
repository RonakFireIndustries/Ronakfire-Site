/* ==========================================================================
   Ronak Fire India Pvt. Ltd. - shared site scripts
   ========================================================================== */
(function () {
  'use strict';

  var DESKTOP_MIN = 980;

  function $(selector, ctx) { return (ctx || document).querySelector(selector); }
  function $$(selector, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(selector)); }

  document.addEventListener('DOMContentLoaded', function () {
    initHeader();
    initReturnToTop();
    initNavDropdowns();
    initActiveNav();
    initProductSwipers();
    initProductDeepLink();
    initEnquiryForms();
    initCounters();
    initManufacturingPreview();
    initAos();
  });

  /* --- sticky / shrinking header ------------------------------------------ */
  function initHeader() {
    var header = $('.g_header');
    if (!header) return;
    function update() {
      var y = window.pageYOffset || document.documentElement.scrollTop;
      header.classList.toggle('g_shrink', y > 0);
    }
    window.addEventListener('scroll', update, { passive: true });
    update();
  }

  /* --- back to top -------------------------------------------------------- */
  function initReturnToTop() {
    var btn = $('#return-to-top');
    if (!btn) return;
    function update() {
      var y = window.pageYOffset || document.documentElement.scrollTop;
      btn.style.display = y >= 50 ? 'block' : 'none';
    }
    window.addEventListener('scroll', update, { passive: true });
    update();
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* --- navigation dropdowns (hover on desktop, tap on mobile) ------------- */
  function initNavDropdowns() {
    $$('.dropdown, .dropdown-submenu').forEach(function (el) {
      el.addEventListener('mouseenter', function () {
        if (window.innerWidth < DESKTOP_MIN) return;
        el.classList.add('show');
        var menu = el.querySelector(':scope > .dropdown-menu');
        if (menu) menu.classList.add('show');
      });
      el.addEventListener('mouseleave', function () {
        if (window.innerWidth < DESKTOP_MIN) return;
        el.classList.remove('show');
        var menu = el.querySelector(':scope > .dropdown-menu');
        if (menu) menu.classList.remove('show');
      });
    });

    $$('.dropdown > .dropdown-toggle').forEach(function (toggle) {
      toggle.addEventListener('click', function (e) {
        if (window.innerWidth >= DESKTOP_MIN) return;
        e.preventDefault();
        var parent = toggle.parentElement;
        var menu = parent.querySelector(':scope > .dropdown-menu');
        var isOpen = parent.classList.contains('show');
        $$('.dropdown.show').forEach(function (open) {
          if (open === parent) return;
          open.classList.remove('show');
          var m = open.querySelector(':scope > .dropdown-menu');
          if (m) m.classList.remove('show');
        });
        parent.classList.toggle('show', !isOpen);
        if (menu) menu.classList.toggle('show', !isOpen);
      });
    });

    $$('.dropdown-submenu > .dropdown-toggle').forEach(function (toggle) {
      toggle.addEventListener('click', function (e) {
        if (window.innerWidth >= DESKTOP_MIN) return;
        e.preventDefault();
        var parent = toggle.parentElement;
        var menu = parent.querySelector(':scope > .dropdown-menu');
        parent.classList.toggle('show');
        if (menu) menu.classList.toggle('show');
      });
    });
  }

  /* --- highlight the current page in the main nav ------------------------- */
  function initActiveNav() {
    var nav = $('.main-nav .navbar-nav');
    if (!nav) return;
    var path = (window.location.pathname.split('/').pop() || 'index.html').toLowerCase();
    var productPages = [
      'products.html', 'extinguisher.html', 'hose-reel-drums.html',
      'hose-boxes-and-fire-shaft-door.html', 'fire-hose.html', 'breeching-inlet.html',
      'landing-valve.html', 'sprinkler.html', 'fire-hose-delivery-coupling.html',
      'branch-pipe-nozzle.html'
    ];
    function activate(el) { if (el) el.classList.add('active'); }
    var homeItem = nav.querySelector('a[href="index.html"]');
    var aboutItem = nav.querySelector('a[href="about-us.html"]');
    var dropdownItem = nav.querySelector('.nav-item.dropdown');
    var connectLink = nav.querySelector('.view-more-btn');

    if (!path || path === 'index.html') {
      activate(homeItem && homeItem.parentElement);
    } else if (path === 'contact-us.html') {
      activate(connectLink);
    } else if (productPages.indexOf(path) !== -1) {
      activate(dropdownItem);
    } else {
      activate(aboutItem && aboutItem.parentElement);
    }
  }

  /* --- product carousels -------------------------------------------------- */
  function initProductSwipers() {
    if (typeof Swiper === 'undefined') return;

    if ($('.product-swiper')) {
      new Swiper('.product-swiper', {
        spaceBetween: 30,
        slidesPerView: 3,
        cssMode: true,
        loop: true,
        autoplay: { delay: 6000, disableOnInteraction: false },
        pagination: { el: '.swiper-pagination', clickable: true, dynamicBullets: true },
        breakpoints: {
          480: { slidesPerView: 1, spaceBetween: 0 },
          767: { slidesPerView: 2, spaceBetween: 10 },
          991: { slidesPerView: 2 },
          1024: { slidesPerView: 3 },
          1600: { slidesPerView: 3, spaceBetween: 30 }
        }
      });
    }

    $$('.product-slider').forEach(function (sliderEl) {
      new Swiper(sliderEl, {
        spaceBetween: 0,
        slidesPerView: 1,
        cssMode: true,
        loop: true,
        autoplay: { delay: 4000, disableOnInteraction: false },
        pagination: { el: sliderEl.querySelector('.swiper-pagination'), clickable: true, dynamicBullets: true },
        observer: true,
        observeParents: true
      });
    });
  }

  /* --- open the correct product tab from a ?id=N deep link ---------------- */
  function initProductDeepLink() {
    var id = new URLSearchParams(window.location.search).get('id');
    if (!id) return;

    var tabBtn = document.getElementById('product-tab-' + id);
    if (!tabBtn) return;

    var paneId = (tabBtn.getAttribute('href') || '').replace('#', '');
    var pane = paneId ? document.getElementById(paneId) : null;
    if (!pane) return;

    $$('.list-group-item').forEach(function (el) { el.classList.remove('active'); });
    $$('.tab-pane').forEach(function (el) { el.classList.remove('active', 'show'); });
    tabBtn.classList.add('active');
    pane.classList.add('active', 'show');
  }

  /* --- enquiry / contact forms (AJAX with graceful fallback) -------------- */
  function initEnquiryForms() {
    $$('form.js-ajax-form').forEach(function (form) {
      var status = form.querySelector('.form-status');
      var submitBtn = form.querySelector('[type="submit"]');

      // Let the server know which page the enquiry came from.
      if (!form.querySelector('input[name="page"]')) {
        var pageField = document.createElement('input');
        pageField.type = 'hidden';
        pageField.name = 'page';
        pageField.value = window.location.pathname;
        form.appendChild(pageField);
      }

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!form.checkValidity()) { form.reportValidity(); return; }

        if (submitBtn) submitBtn.disabled = true;
        setStatus(status, 'Sending\u2026', '');

        fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data && data.success) {
              setStatus(status, data.message || 'Thank you! We will contact you shortly.', 'success');
              form.reset();
            } else {
              setStatus(status, (data && data.message) || 'Sorry, something went wrong. Please try again.', 'error');
            }
          })
          .catch(function () {
            setStatus(status, 'Sorry, something went wrong. Please email us at info@ronakfire.com.', 'error');
          })
          .then(function () {
            if (submitBtn) submitBtn.disabled = false;
          });
      });
    });
  }

  function setStatus(el, message, state) {
    if (!el) return;
    el.textContent = message;
    el.className = 'form-status' + (state ? ' ' + state : '');
  }

  /* --- animated statistics counter ---------------------------------------- */
  function initCounters() {
    var counter = $('#counter');
    if (!counter) return;
    var done = false;

    function run() {
      if (done) return;
      if (counter.getBoundingClientRect().top >= window.innerHeight) return;
      done = true;
      $$('.counter-value').forEach(function (el) {
        animateCount(el, parseInt(el.getAttribute('data-count'), 10) || 0, 3000);
      });
    }

    window.addEventListener('scroll', run, { passive: true });
    run();
  }

  function animateCount(el, target, duration) {
    var start = null;
    function step(timestamp) {
      if (start === null) start = timestamp;
      var progress = Math.min((timestamp - start) / duration, 1);
      el.textContent = Math.floor(progress * target);
      if (progress < 1) {
        window.requestAnimationFrame(step);
      } else {
        el.textContent = target;
      }
    }
    window.requestAnimationFrame(step);
  }

  /* --- about page: manufacturing unit image/text preview ------------------ */
  function initManufacturingPreview() {
    var items = $$('.manufacturing-items li');
    var imgEl = document.getElementById('unitImage');
    var txtEl = document.getElementById('unitText');
    if (!items.length || !imgEl || !txtEl) return;

    var timer = null;

    items.forEach(function (li) {
      var src = li.dataset.image;
      if (src) { var preload = new Image(); preload.src = src; }
    });

    function swap(newSrc, newText) {
      if (timer) clearTimeout(timer);
      imgEl.classList.add('hide');
      txtEl.classList.add('hide');
      timer = setTimeout(function () {
        if (newSrc) imgEl.src = newSrc;
        if (typeof newText !== 'undefined') txtEl.textContent = newText;
        imgEl.classList.remove('hide');
        txtEl.classList.remove('hide');
      }, 220);
    }

    items.forEach(function (li) {
      var src = li.dataset.image;
      var text = li.dataset.text || '';
      li.addEventListener('mouseenter', function () { swap(src, text); });
      li.addEventListener('focus', function () { swap(src, text); });
      li.addEventListener('touchstart', function (e) { e.preventDefault(); swap(src, text); });
      li.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); swap(src, text); }
      });
    });

    imgEl.classList.add('show');
    txtEl.classList.add('show');
  }

  /* --- scroll animations -------------------------------------------------- */
  function initAos() {
    if (typeof AOS !== 'undefined') {
      AOS.init({ once: true, offset: 50, startEvent: 'load', disable: 'mobile' });
    }
  }
})();
