/**
 * Custom JavaScript for Default Theme
 * Enhanced with modern animations and effects
 */

(function() {
    'use strict';
    
    // Scroll to Top Button
    const scrollToTopBtn = document.createElement('button');
    scrollToTopBtn.className = 'scroll-to-top';
    scrollToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollToTopBtn.setAttribute('aria-label', 'Прокрутити вгору');
    document.body.appendChild(scrollToTopBtn);
    
    // Show/hide scroll to top button with fade animation
    let ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                if (window.pageYOffset > 300) {
                    scrollToTopBtn.classList.add('show');
                } else {
                    scrollToTopBtn.classList.remove('show');
                }
                ticking = false;
            });
            ticking = true;
        }
    });
    
    // Smooth scroll to top
    scrollToTopBtn.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.length > 1) {
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // Enhanced Navbar scroll effect
    let lastScroll = 0;
    const navbar = document.querySelector('.navbar');
    const header = document.querySelector('header');
    
    if (navbar && header) {
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
                header.style.boxShadow = '0 4px 12px rgba(0,0,0,0.12)';
            } else {
                navbar.classList.remove('scrolled');
                header.style.boxShadow = '0 2px 4px rgba(0,0,0,0.08)';
            }
            
            lastScroll = currentScroll;
        }, { passive: true });
    }
    
    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe cards and sections
    document.querySelectorAll('.card, section').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(el);
    });
    
    // Enhanced dropdown menu
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            const dropdown = this.nextElementSibling;
            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                // Close other dropdowns with animation
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    if (menu !== dropdown) {
                        menu.style.opacity = '0';
                        menu.style.transform = 'translateY(-10px)';
                        setTimeout(() => {
                            menu.classList.remove('show');
                            menu.style.opacity = '';
                            menu.style.transform = '';
                        }, 200);
                    }
                });
                
                // Open current dropdown with animation
                if (!dropdown.classList.contains('show')) {
                    dropdown.classList.add('show');
                    dropdown.style.opacity = '0';
                    dropdown.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        dropdown.style.opacity = '1';
                        dropdown.style.transform = 'translateY(0)';
                    }, 10);
                }
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.style.opacity = '0';
                menu.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    menu.classList.remove('show');
                    menu.style.opacity = '';
                    menu.style.transform = '';
                }, 200);
            });
        }
    });
    
    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * 0.5;
            heroSection.style.transform = `translateY(${rate}px)`;
        }, { passive: true });
    }
    
    // Lazy loading images with fade-in
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.style.opacity = '0';
                        img.onload = function() {
                            this.style.transition = 'opacity 0.5s ease-in';
                            this.style.opacity = '1';
                        };
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        }, {
            rootMargin: '50px'
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // Add hover effect to cards
    document.querySelectorAll('.card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    });
    
    // Add ripple effect to buttons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Add CSS for ripple effect
    const style = document.createElement('style');
    style.textContent = `
        .btn {
            position: relative;
            overflow: hidden;
        }
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Console log for debugging
    console.log('Default Theme JavaScript loaded successfully');
    
    // Dispatch custom event when theme is ready
    document.dispatchEvent(new CustomEvent('themeReady'));
})();
