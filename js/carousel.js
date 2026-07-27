import gsap from 'gsap';

class ModernCarousel {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.slides = this.container.querySelectorAll('.carousel-slide');
        this.dots = this.container.querySelectorAll('.carousel-dot');
        this.prevBtn = this.container.querySelector('.carousel-prev');
        this.nextBtn = this.container.querySelector('.carousel-next');
        
        this.currentIndex = 0;
        this.autoplayDelay = options.autoplayDelay || 5000;
        this.autoplay = options.autoplay !== false;
        this.autoplayTimer = null;
        
        this.init();
    }

    init() {
        this.slides.forEach((slide, index) => {
            slide.style.opacity = index === 0 ? '1' : '0';
            slide.style.transform = index === 0 ? 'scale(1)' : 'scale(1.05)';
        });

        if (this.dots.length > 0) {
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goToSlide(index));
            });
        }

        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.prev());
        }

        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.next());
        }

        this.initTouchEvents();
        
        if (this.autoplay) {
            this.startAutoplay();
        }

        this.container.addEventListener('mouseenter', () => this.stopAutoplay());
        this.container.addEventListener('mouseleave', () => {
            if (this.autoplay) this.startAutoplay();
        });
    }

    initTouchEvents() {
        let startX = 0;
        let endX = 0;

        this.container.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        this.container.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            const diff = startX - endX;

            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    this.next();
                } else {
                    this.prev();
                }
            }
        });
    }

    goToSlide(index) {
        if (index === this.currentIndex) return;

        const prevSlide = this.slides[this.currentIndex];
        const nextSlide = this.slides[index];

        gsap.to(prevSlide, {
            opacity: 0,
            scale: 1.05,
            duration: 0.8,
            ease: 'power2.inOut'
        });

        gsap.fromTo(nextSlide,
            { opacity: 0, scale: 1.05 },
            { opacity: 1, scale: 1, duration: 0.8, ease: 'power2.inOut' }
        );

        this.currentIndex = index;
        this.updateDots();
    }

    next() {
        const nextIndex = (this.currentIndex + 1) % this.slides.length;
        this.goToSlide(nextIndex);
    }

    prev() {
        const prevIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
        this.goToSlide(prevIndex);
    }

    updateDots() {
        this.dots.forEach((dot, index) => {
            if (index === this.currentIndex) {
                dot.classList.add('active');
                gsap.to(dot, { scale: 1.2, duration: 0.3 });
            } else {
                dot.classList.remove('active');
                gsap.to(dot, { scale: 1, duration: 0.3 });
            }
        });
    }

    startAutoplay() {
        this.stopAutoplay();
        this.autoplayTimer = setInterval(() => this.next(), this.autoplayDelay);
    }

    stopAutoplay() {
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }

    destroy() {
        this.stopAutoplay();
    }
}

export default ModernCarousel;
