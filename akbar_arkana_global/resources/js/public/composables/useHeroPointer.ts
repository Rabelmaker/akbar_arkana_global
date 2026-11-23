import gsap from 'gsap';
import { onMounted, onUnmounted, type Ref } from 'vue';

export function useHeroPointer(stage: Ref<HTMLElement | undefined>) {
    const media = gsap.matchMedia();

    onMounted(() => {
        const element = stage.value;
        if (!element) return;
        const hero = element;

        media.add(
            '(hover: hover) and (pointer: fine) and (prefers-reduced-motion: no-preference)',
            () => {
                const links = Array.from(
                    hero.querySelectorAll<HTMLElement>('.hero-button'),
                );
                let activeLink: HTMLElement | null = null;

                function resetLink() {
                    activeLink?.style.removeProperty('--magnet-x');
                    activeLink?.style.removeProperty('--magnet-y');
                    activeLink = null;
                }

                function update(event: PointerEvent) {
                    if (event.pointerType === 'touch') return;
                    const rect = hero.getBoundingClientRect();
                    const x = event.clientX - rect.left;
                    const y = event.clientY - rect.top;
                    hero.style.setProperty(
                        '--light-x',
                        `${(x / rect.width) * 100}%`,
                    );
                    hero.style.setProperty(
                        '--light-y',
                        `${(y / rect.height) * 100}%`,
                    );
                    hero.classList.add('hero--pointer');

                    const link = links.find((candidate) =>
                        candidate.contains(event.target as Node),
                    );
                    if (link !== activeLink) resetLink();
                    hero.classList.toggle('hero--over-link', Boolean(link));
                    if (!link) return;

                    activeLink = link;
                    const box = link.getBoundingClientRect();
                    link.style.setProperty(
                        '--magnet-x',
                        `${(event.clientX - box.left - box.width / 2) * 0.1}px`,
                    );
                    link.style.setProperty(
                        '--magnet-y',
                        `${(event.clientY - box.top - box.height / 2) * 0.24}px`,
                    );
                }

                function clear() {
                    resetLink();
                    hero.classList.remove('hero--pointer', 'hero--over-link');
                    hero.style.removeProperty('--light-x');
                    hero.style.removeProperty('--light-y');
                }

                hero.addEventListener('pointermove', update, { passive: true });
                hero.addEventListener('pointerleave', clear);
                hero.addEventListener('pointercancel', clear);
                return () => {
                    hero.removeEventListener('pointermove', update);
                    hero.removeEventListener('pointerleave', clear);
                    hero.removeEventListener('pointercancel', clear);
                    clear();
                };
            },
        );
    });

    onUnmounted(() => media.revert());
}
