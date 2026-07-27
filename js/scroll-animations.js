import gsap from 'gsap';

class ScrollAnimations {
    constructor() {
        this.elements = document.querySelectorAll('[data-animate]');
        this.init();
    }

    init() {
        this.elements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = this.getTransform(el.dataset.animate);
        });

        this.observeElements();
    }

    getTransform(animationType) {
        const transforms = {
            'fade-up': 'translateY(40px)',
            'fade-down': 'translateY(-40px)',
            'fade-left': 'translateX(-40px)',
            'fade-right': 'translateX(40px)',
            'scale': 'scale(0.9)',
            'none': 'none'
        };
        return transforms[animationType] || 'translateY(40px)';
    }

    observeElements() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateElement(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        });

        this.elements.forEach(el => observer.observe(el));
    }

    animateElement(element) {
        const animationType = element.dataset.animate || 'fade-up';
        const duration = parseFloat(element.dataset.duration) || 0.6;
        const delay = parseFloat(element.dataset.delay) || 0;

        gsap.to(element, {
            opacity: 1,
            x: 0,
            y: 0,
            scale: 1,
            duration: duration,
            delay: delay,
            ease: 'power2.out'
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new ScrollAnimations());
} else {
    new ScrollAnimations();
}

export default ScrollAnimations;
