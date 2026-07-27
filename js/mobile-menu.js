import gsap from 'gsap';

class MobileMenu {
    constructor() {
        this.toggle = document.getElementById('menu-toggle');
        this.menu = document.getElementById('mobile-menu');
        this.overlay = document.getElementById('menu-overlay');
        this.isOpen = false;
        
        this.init();
    }

    init() {
        if (!this.toggle || !this.menu) return;

        this.toggle.addEventListener('change', () => this.toggleMenu());
        
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.closeMenu());
        }

        this.menu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => this.closeMenu());
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeMenu();
            }
        });
    }

    toggleMenu() {
        if (this.toggle.checked) {
            this.openMenu();
        } else {
            this.closeMenu();
        }
    }

    openMenu() {
        this.isOpen = true;
        document.body.style.overflow = 'hidden';
        
        gsap.to(this.menu, {
            x: 0,
            duration: 0.4,
            ease: 'power3.out'
        });

        if (this.overlay) {
            gsap.to(this.overlay, {
                opacity: 1,
                duration: 0.3,
                ease: 'power2.out'
            });
        }

        gsap.fromTo(
            this.menu.querySelectorAll('a'),
            { x: -30, opacity: 0 },
            {
                x: 0,
                opacity: 1,
                duration: 0.3,
                stagger: 0.05,
                ease: 'power2.out',
                delay: 0.2
            }
        );
    }

    closeMenu() {
        this.isOpen = false;
        this.toggle.checked = false;
        document.body.style.overflow = '';
        
        gsap.to(this.menu, {
            x: '-100%',
            duration: 0.3,
            ease: 'power3.in'
        });

        if (this.overlay) {
            gsap.to(this.overlay, {
                opacity: 0,
                duration: 0.2,
                ease: 'power2.in'
            });
        }
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new MobileMenu());
} else {
    new MobileMenu();
}

export default MobileMenu;
