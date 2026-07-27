import gsap from 'gsap';

class StatsCounter {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.counters = this.container.querySelectorAll('[data-count]');
        this.duration = options.duration || 2000;
        this.observer = null;
        
        this.init();
    }

    init() {
        this.counters.forEach(counter => {
            counter.textContent = '0';
        });

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounters();
                    this.observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        this.observer.observe(this.container);
    }

    animateCounters() {
        this.counters.forEach(counter => {
            const target = parseInt(counter.dataset.count);
            const suffix = counter.dataset.suffix || '';
            
            gsap.to(counter, {
                textContent: target,
                duration: this.duration / 1000,
                ease: 'power2.out',
                snap: { textContent: 1 },
                onUpdate: function() {
                    counter.textContent = Math.round(this.targets()[0].textContent) + suffix;
                }
            });
        });
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }
}

export default StatsCounter;
