import gsap from 'gsap';

document.addEventListener('DOMContentLoaded', () => {
    initScrollAnimations();
    initMobileMenu();
    initSmoothScroll();
    initLazyLoading();
});

function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
}

function initMobileMenu() {
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuOverlay = document.getElementById('menu-overlay');

    if (!menuToggle || !mobileMenu) return;

    menuToggle.addEventListener('change', () => {
        if (menuToggle.checked) {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    if (menuOverlay) {
        menuOverlay.addEventListener('click', () => {
            menuToggle.checked = false;
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            menuToggle.checked = false;
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    });
}

function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img[data-src]');

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));
}

export function animateElement(element, animationType = 'fade-in') {
    const animations = {
        'fade-in': () => {
            gsap.fromTo(element, 
                { opacity: 0, y: 30 },
                { opacity: 1, y: 0, duration: 0.6, ease: 'power2.out' }
            );
        },
        'slide-in-left': () => {
            gsap.fromTo(element,
                { opacity: 0, x: -50 },
                { opacity: 1, x: 0, duration: 0.5, ease: 'power2.out' }
            );
        },
        'scale-up': () => {
            gsap.fromTo(element,
                { opacity: 0, scale: 0.9 },
                { opacity: 1, scale: 1, duration: 0.4, ease: 'back.out(1.7)' }
            );
        }
    };

    if (animations[animationType]) {
        animations[animationType]();
    }
}

export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
