// resources/js/scroll-animations.js
document.addEventListener('DOMContentLoaded', () => {
    const observerOptions = {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
            });
        }, observerOptions);

        // Observer tous les éléments à animer
        
        document.querySelectorAll('.scroll-reveal, .scroll-reveal-left, .scroll-reveal-right, .scroll-reveal-scale').forEach((el) => {
            observer.observe(el);
        });

        // Animation en cascade pour les cartes produits
        const productCards = document.querySelectorAll('.product-card');
        const productObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                entry.target.classList.add('active');
                }, index * 150);
                productObserver.unobserve(entry.target);
            }
            });
        }, { threshold: 0.1 });

        productCards.forEach((card) => {
            productObserver.observe(card);
        });
    });     