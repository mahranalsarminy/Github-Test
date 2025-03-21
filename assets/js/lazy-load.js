document.addEventListener('DOMContentLoaded', () => {
    const lazyImages = document.querySelectorAll('.lazy');
    const lazyVideos = document.querySelectorAll('video.lazy');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                if (target.tagName === 'IMG') {
                    target.src = target.dataset.src;
                    target.onload = () => {
                        target.classList.remove('lazy');
                        target.classList.add('loaded');
                    };
                } else if (target.tagName === 'VIDEO') {
                    const source = target.querySelector('source');
                    source.src = source.dataset.src;
                    target.load();
                    target.classList.remove('lazy');
                    target.classList.add('loaded');
                }
                observer.unobserve(target);
            }
        });
    });

    lazyImages.forEach(img => observer.observe(img));
    lazyVideos.forEach(video => observer.observe(video));
});