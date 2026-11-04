/**
 * Wellness Theme JavaScript
 * Handles: Quick Exit, Mobile Navigation Drawer, Accessible Modals, Skip Link Focus Fix
 *
 * @package Wellness_Theme
 */

(function () {
  'use strict';

  // Configuration passed from wp_localize_script or sensible defaults
  const config = window.wellnessThemeConfig || {
    quickExitUrl: 'https://www.google.com',
    strings: {
      menuOpen: 'Open menu',
      menuClose: 'Close menu'
    }
  };
  const defaultThemeMode = (function () {
    const mode = document.documentElement.getAttribute('data-default-theme');
    return mode === 'light' || mode === 'auto' || mode === 'dark' ? mode : 'dark';
  })();

  /**
   * 1. QUICK EXIT FUNCTIONALITY (FR-2)
   * Instantly navigates to a neutral site and replaces history so Back button cannot return.
   */
  function triggerQuickExit() {
    try {
      // Clear sensitive session storage
      sessionStorage.clear();
    } catch (e) {
      // Fallback if blocked by browser policy
    }
    // Replace URL immediately in history
    window.location.replace(config.quickExitUrl || 'https://www.google.com');
  }

  // Attach to all quick exit buttons (header, utility bar, mobile menu)
  document.addEventListener('click', function (e) {
    const exitBtn = e.target.closest('.quick-exit-btn');
    if (exitBtn) {
      e.preventDefault();
      triggerQuickExit();
    }
  });

  // Hotkey: Triple Escape key press triggers Quick Exit for privacy safety
  let escapePressCount = 0;
  let escapeTimeout = null;

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      escapePressCount++;
      clearTimeout(escapeTimeout);

      if (escapePressCount >= 3) {
        triggerQuickExit();
        return;
      }

      escapeTimeout = setTimeout(function () {
        escapePressCount = 0;
      }, 1200);
    }
  });

  /**
   * 2. MOBILE NAVIGATION DRAWER
   */
  const mobileToggle = document.getElementById('mobile-menu-toggle');
  const mobileDrawer = document.getElementById('mobile-nav-drawer');
  const mobileClose = document.getElementById('mobile-nav-close');

  function openMobileNav() {
    if (!mobileDrawer || !mobileToggle) return;
    mobileDrawer.classList.add('is-open');
    mobileToggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';

    // Focus first focusable element inside drawer
    const focusable = mobileDrawer.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (focusable.length) {
      setTimeout(() => focusable[0].focus(), 100);
    }
  }

  function closeMobileNav() {
    if (!mobileDrawer || !mobileToggle) return;
    mobileDrawer.classList.remove('is-open');
    mobileToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    mobileToggle.focus();
  }

  if (mobileToggle) {
    mobileToggle.addEventListener('click', function () {
      const isOpen = mobileDrawer.classList.contains('is-open');
      if (isOpen) {
        closeMobileNav();
      } else {
        openMobileNav();
      }
    });
  }

  if (mobileClose) {
    mobileClose.addEventListener('click', closeMobileNav);
  }

  // Close drawer if backdrop clicked
  if (mobileDrawer) {
    mobileDrawer.addEventListener('click', function (e) {
      if (e.target === mobileDrawer) {
        closeMobileNav();
      }
    });
  }

  /**
   * 3. SEARCH MODAL TOGGLE
   */
  const searchToggle = document.getElementById('search-toggle-btn');
  const searchModal = document.getElementById('search-modal');
  const searchInput = searchModal ? searchModal.querySelector('input[type="search"]') : null;

  if (searchToggle && searchModal) {
    searchToggle.addEventListener('click', function () {
      const isActive = searchModal.classList.toggle('is-active');
      searchToggle.setAttribute('aria-expanded', isActive ? 'true' : 'false');
      if (isActive && searchInput) {
        setTimeout(() => searchInput.focus(), 150);
      }
    });

    // Close search on clicking outside
    document.addEventListener('click', function (e) {
      if (!searchModal.contains(e.target) && !searchToggle.contains(e.target) && searchModal.classList.contains('is-active')) {
        searchModal.classList.remove('is-active');
        searchToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /**
   * 4. KEYBOARD ESCAPE LISTENER (Modals & Menus)
   */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (mobileDrawer && mobileDrawer.classList.contains('is-open')) {
        closeMobileNav();
      }
      if (searchModal && searchModal.classList.contains('is-active')) {
        searchModal.classList.remove('is-active');
        if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
      }
    }
  });

  /**
   * 5. BACK TO TOP BUTTON
   */
  const backToTopBtn = document.getElementById('back-to-top');
  if (backToTopBtn) {
    backToTopBtn.addEventListener('click', function (e) {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  /**
   * 6. SKIP LINK FOCUS FIX (Accessibility)
   */
  window.addEventListener('hashchange', function () {
    const id = location.hash.substring(1);
    const element = document.getElementById(id);
    if (element) {
      if (!/^(?:a|select|input|button|textarea)$/i.test(element.tagName)) {
        element.tabIndex = -1;
      }
      element.focus();
    }
  });

  /**
   * 7. AUTO / LIGHT / DARK MODE SWITCHER
   */
  function syncThemeColor() {
    const meta = document.querySelector('meta[name="theme-color"]');
    if (!meta) return;
    const background = getComputedStyle(document.documentElement).getPropertyValue('--bg').trim();
    if (background) {
      meta.setAttribute('content', background);
    }
  }

  function applyThemeMode(mode, rememberChoice) {
    if (mode !== 'light' && mode !== 'dark' && mode !== 'auto') {
      mode = defaultThemeMode;
    }

    if (rememberChoice) {
      try {
        localStorage.setItem('wellness_theme_mode', mode);
      } catch (e) {}
    }

    if (mode === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
    } else if (mode === 'light') {
      document.documentElement.setAttribute('data-theme', 'light');
    } else {
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-theme', 'dark');
      } else {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    }
    document.documentElement.setAttribute('data-theme-mode', mode);
    syncThemeColor();

    // Sync button UI states across header and mobile drawer
    document.querySelectorAll('.theme-mode-btn').forEach(function (btn) {
      if (btn.getAttribute('data-mode') === mode) {
        btn.classList.add('is-active');
        btn.setAttribute('aria-pressed', 'true');
      } else {
        btn.classList.remove('is-active');
        btn.setAttribute('aria-pressed', 'false');
      }
    });
  }

  // Bind click listeners for theme toggle buttons
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.theme-mode-btn');
    if (btn) {
      e.preventDefault();
      const mode = btn.getAttribute('data-mode') || 'auto';
      applyThemeMode(mode, true);
    }
  });

  // Init button states on load
  const savedMode = (function() {
    try {
      return localStorage.getItem('wellness_theme_mode') || defaultThemeMode;
    } catch (e) {
      return defaultThemeMode;
    }
  })();
  applyThemeMode(savedMode, false);

  // OS theme change listener when in auto mode
  if (window.matchMedia) {
    const colorScheme = window.matchMedia('(prefers-color-scheme: dark)');
    const updateAutoTheme = function (e) {
      const mode = (function() {
        try {
          return localStorage.getItem('wellness_theme_mode') || defaultThemeMode;
        } catch (err) {
          return defaultThemeMode;
        }
      })();
      if (mode === 'auto') {
        document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
        syncThemeColor();
      }
    };
    if (colorScheme.addEventListener) {
      colorScheme.addEventListener('change', updateAutoTheme);
    } else if (colorScheme.addListener) {
      colorScheme.addListener(updateAutoTheme);
    }
  }

})();
