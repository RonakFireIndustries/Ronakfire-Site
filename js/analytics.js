/* Ronak Fire - Google Analytics 4 + conversion tracking (external file so it satisfies our CSP). */
(function () {
  var ID = 'G-MPFE8MRB23';

  var s = document.createElement('script');
  s.async = true;
  s.src = 'https://www.googletagmanager.com/gtag/js?id=' + ID;
  document.head.appendChild(s);

  window.dataLayer = window.dataLayer || [];
  function gtag() { window.dataLayer.push(arguments); }
  window.gtag = gtag;
  gtag('js', new Date());
  gtag('config', ID, { anonymize_ip: true });

  function track(name, params) {
    if (typeof window.gtag === 'function') { window.gtag('event', name, params || {}); }
  }

  document.addEventListener('click', function (e) {
    var el = e.target;
    var a = el && el.closest ? el.closest('a') : null;
    if (!a) { return; }
    var href = a.getAttribute('href') || '';
    if (href.indexOf('wa.me') !== -1) { track('contact', { method: 'whatsapp' }); }
    else if (href.indexOf('tel:') === 0) { track('contact', { method: 'phone' }); }
    else if (href.indexOf('mailto:') === 0) { track('contact', { method: 'email' }); }
  });

  /* Called by js/custom.js after a successful form submission. */
  window.ronakTrackLead = function (form) { track('generate_lead', { form: form || 'enquiry' }); };
})();