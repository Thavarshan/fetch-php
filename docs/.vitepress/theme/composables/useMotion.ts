import { onBeforeUnmount, onMounted } from 'vue';

const prefersReduced = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function useMotion(): void {
    let observer: IntersectionObserver | null = null;

    onMounted(() => {
        const elements = Array.from(document.querySelectorAll<HTMLElement>('[data-reveal]'));
        if (!elements.length) {
            return;
        }

        if (prefersReduced()) {
            elements.forEach((el) => el.classList.add('is-visible'));
            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer?.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.2, rootMargin: '0px 0px -10% 0px' }
        );

        elements.forEach((el) => observer?.observe(el));
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
    });
}
