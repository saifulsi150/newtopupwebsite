(function () {
  if (typeof window === 'undefined') return;

  var cache = {};
  var currentUrl = window.location.href;
  var pending = null;
  var prefetchQueue = [];
  var prefetchedUrls = {};

  function shouldHandle(url) {
    if (!url) return false;
    if (url.startsWith('http://') || url.startsWith('https://')) {
      try {
        var parsed = new URL(url);
        return parsed.origin === window.location.origin;
      } catch (e) {
        return false;
      }
    }
    if (url.startsWith('mailto:') || url.startsWith('tel:')) return false;
    return url.startsWith('/') && !url.startsWith('//') && !url.startsWith('/#') && !url.startsWith('#');
  }

  function normalize(url) {
    var parsed = new URL(url, window.location.href);
    return parsed.pathname + parsed.search;
  }

  function extractPageContent(html) {
    if (!html) return null;
    var parser = new DOMParser();
    var doc = parser.parseFromString(html, 'text/html');
    return doc.querySelector('[data-page-content]');
  }

  function runScripts(container) {
    var scripts = Array.from(container.querySelectorAll('script'));
    scripts.forEach(function (script) {
      var newScript = document.createElement('script');
      if (script.src) {
        newScript.src = script.src;
        newScript.async = false;
      } else {
        newScript.textContent = script.textContent;
      }
      if (script.type) {
        newScript.type = script.type;
      }
      script.parentNode.replaceChild(newScript, script);
    });
  }

  function swapPage(url, html) {
    var target = document.querySelector('[data-page-content]');
    var next = extractPageContent(html);
    if (!target || !next) {
      window.location.assign(url);
      return;
    }

    target.classList.add('is-swapping');
    if (target.dataset.placeholder !== 'true') {
      target.dataset.placeholder = 'true';
      target.innerHTML = '<div style="padding: 24px 0; color: #666;">Loading...</div>';
    }

    setTimeout(function () {
      target.innerHTML = next.innerHTML;
      runScripts(target);
      window.dispatchEvent(new Event('ffuid:content-swapped'));
      window.history.pushState({}, '', url);
      currentUrl = url;
      document.documentElement.scrollTop = 0;
      target.classList.remove('is-swapping');
      target.dataset.placeholder = 'false';
    }, 40);
  }

  function fetchPage(url, useCache) {
    if (useCache && cache[url]) {
      return Promise.resolve(cache[url]);
    }

    if (pending) {
      pending.abort();
    }

    var controller = new AbortController();
    pending = controller;

    return fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      signal: controller.signal
    })
      .then(function (response) {
        if (!response.ok) throw new Error('not-ok');
        return response.text();
      })
      .then(function (html) {
        cache[url] = html;
        return html;
      })
      .catch(function (err) {
        if (err.name !== 'AbortError') {
          window.location.assign(url);
        }
        return null;
      });
  }

  function prefetched(url) {
    var normalized = normalize(url);
    if (!normalized || prefetchedUrls[normalized] || cache[normalized]) return;
    prefetchedUrls[normalized] = true;
    fetchPage(normalized, false);
  }

  function navigate(url) {
    var normalized = normalize(url);
    if (normalized === normalize(window.location.href)) return;
    fetchPage(normalized, true).then(function (html) {
      if (html) swapPage(normalized, html);
    });
  }

  document.addEventListener('click', function (event) {
    var link = event.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!shouldHandle(href)) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target === '_blank' || link.getAttribute('data-no-swap') === 'true') return;
    event.preventDefault();
    navigate(href);
  }, true);

  document.addEventListener('mouseover', function (event) {
    var link = event.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!shouldHandle(href)) return;
    prefetched(href);
  }, { passive: true });

  document.addEventListener('touchstart', function (event) {
    var link = event.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!shouldHandle(href)) return;
    prefetched(href);
  }, { passive: true });

  window.addEventListener('popstate', function () {
    if (window.location.href !== currentUrl) {
      navigate(window.location.pathname + window.location.search);
    }
  });
})();
