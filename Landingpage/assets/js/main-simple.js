/**
 * Simplified JavaScript for MacJ Pest Control
 */

(function() {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (selectHeader) {
      window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
    }
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');
  if (mobileNavToggleBtn) {
    function mobileNavToogle() {
      document.querySelector('body').classList.toggle('mobile-nav-active');
      mobileNavToggleBtn.classList.toggle('bi-list');
      mobileNavToggleBtn.classList.toggle('bi-x');
    }
    mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
  }

  /**
   * Initialize Bootstrap Carousels
   */
  function initCarousels() {
    console.log('Initializing carousels...');

    // Testimonial carousel
    const testimonialCarousel = document.getElementById('testimonialCarousel');
    if (testimonialCarousel) {
      new bootstrap.Carousel(testimonialCarousel, {
        interval: 6000,  // Change slides every 6 seconds
        wrap: true,
        touch: true
      });
    }
  }

  /**
   * Service card animations on scroll
   */
  function initServiceAnimations() {
    const serviceCards = document.querySelectorAll('.service-card');

    if (serviceCards.length > 0) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1 });

      serviceCards.forEach(card => {
        observer.observe(card);
      });
    }
  }

  // Initialize carousels and service animations when DOM is fully loaded
  document.addEventListener('DOMContentLoaded', function() {
    initCarousels();
    initServiceAnimations();
  });

  /**
   * Scroll top button
   */
  const scrollTop = document.querySelector('.scroll-top');
  if (scrollTop) {
    function toggleScrollTop() {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
    scrollTop.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });

    window.addEventListener('load', toggleScrollTop);
    document.addEventListener('scroll', toggleScrollTop);
  }

  /**
   * Toggle navigation items in dropdown based on screen size
   * Show nav items on mobile (<=1199px), hide on desktop
   */
  function toggleDropdownNav() {
    const dropdownMenu = document.querySelector('.dropdown-menu');
    if (!dropdownMenu) return;

    const navItems = dropdownMenu.querySelectorAll('li:not(:last-child)');
    const isMobile = window.innerWidth <= 1199;
    console.log('Toggling dropdown nav, isMobile:', isMobile, 'window width:', window.innerWidth);

    navItems.forEach(item => {
      if (item.querySelector('a[href="SignIn.php"]')) return; // Skip Admin Login
      item.classList.toggle('d-none', !isMobile);
    });
  }

  // Initialize on load and resize
  document.addEventListener('DOMContentLoaded', toggleDropdownNav);
  window.addEventListener('resize', toggleDropdownNav);

})();
