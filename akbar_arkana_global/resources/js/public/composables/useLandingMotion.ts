import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { onMounted, onUnmounted, type Ref } from 'vue';

let hasPlayedIntro = false;

export function useLandingMotion(page: Ref<HTMLElement | undefined>) {
    let media: ReturnType<typeof gsap.matchMedia> | undefined;

    onMounted(() => {
        if (!page.value) return;

        gsap.registerPlugin(ScrollTrigger);
        media = gsap.matchMedia(page.value);
        media.add(
            {
                motion: '(prefers-reduced-motion: no-preference)',
                desktop: '(min-width: 64rem)',
            },
            ({ conditions }) => {
                if (!conditions?.motion) return;

                if (!hasPlayedIntro) {
                    hasPlayedIntro = true;

                    if (
                        !window.location.hash ||
                        window.location.hash === '#home'
                    ) {
                        gsap.timeline({ defaults: { ease: 'power3.out' } })
                            .set('[data-intro]', { autoAlpha: 1 })
                            .from('[data-intro-mark]', {
                                clipPath: 'inset(0 100% 0 0)',
                                duration: 0.42,
                            })
                            .from(
                                '[data-intro-name]',
                                { y: 8, autoAlpha: 0, duration: 0.35 },
                                '-=0.2',
                            )
                            .to(
                                '[data-intro-sweep]',
                                {
                                    backgroundPosition: '0% 50%',
                                    duration: 0.54,
                                },
                                '-=0.22',
                            )
                            .to(
                                '[data-intro]',
                                { autoAlpha: 0, duration: 0.42 },
                                '-=0.14',
                            )
                            .from(
                                '.hero__headline',
                                {
                                    yPercent: 36,
                                    clipPath: 'inset(0 0 100% 0)',
                                    autoAlpha: 0,
                                    duration: 0.6,
                                    stagger: 0.09,
                                },
                                '-=0.20',
                            )
                            .from(
                                '[data-hero-enter]',
                                {
                                    y: 12,
                                    autoAlpha: 0,
                                    duration: 0.44,
                                    stagger: 0.06,
                                },
                                '-=0.39',
                            )
                            .from(
                                '[data-hero-mark]',
                                {
                                    clipPath: 'inset(100% 0 0 0)',
                                    duration: 0.65,
                                },
                                '<',
                            );
                    }
                }

                const reveals = gsap.utils.toArray<HTMLElement>(
                    '[data-phase-reveal], [data-service-reveal]',
                    page.value,
                );
                for (const element of reveals) {
                    const trigger = {
                        trigger: element,
                        start: 'top 94%',
                        once: true,
                    };
                    gsap.from(element, {
                        y: 17,
                        opacity: 0.68,
                        duration: 0.48,
                        ease: 'power2.out',
                        scrollTrigger: trigger,
                    });
                    if (element.hasAttribute('data-service-reveal')) {
                        gsap.from(element, {
                            '--service-line': 0,
                            duration: 0.55,
                            ease: 'power2.out',
                            scrollTrigger: trigger,
                        });
                    }
                }
                const gallery = page.value?.querySelector<HTMLElement>('.export-gallery');
                let stopGallery: (() => void) | undefined;
                if (gallery) {
                    const cards = Array.from(gallery.querySelectorAll('figure'));
                    const frame = gallery.closest('.export-frame')!;
                    const text = Array.from(frame.querySelectorAll('.phase-label, h2, .export-copy p, .export-copy a'));
                    let zones: DOMRect[] = [];
                    let cardSizes: { width: number; height: number }[] = [];
                    const measure = () => {
                        const origin = gallery.getBoundingClientRect();
                        zones = text.flatMap(element => {
                            const range = document.createRange();
                            range.selectNodeContents(element);
                            return Array.from(range.getClientRects(), rect =>
                                new DOMRect(rect.left - origin.left, rect.top - origin.top, rect.width, rect.height));
                        });
                        cardSizes = cards.map(card => ({ width: card.offsetWidth, height: card.offsetHeight }));
                    };
                    const layout = new ResizeObserver(measure);
                    [gallery, ...text, ...cards].forEach(element => layout.observe(element));
                    measure();
                    let position = 0.08;
                    let direction = 1;
                    let velocity = 1;
                    let lastScroll = window.scrollY;
                    let visible = false;
                    const draw = (_time: number, elapsedMs: number) => {
                        const dt = Math.min(elapsedMs, 50) / 1000;
                        velocity += (direction - velocity) * (1 - Math.exp(-3.5 * dt));
                        position = (position + velocity * dt / 42 + 1) % 1;
                        const width = gallery.clientWidth;
                        const height = gallery.clientHeight;
                        cards.forEach((card, index) => {
                            const phase = (position + index / cards.length) % 1;
                            const angle = phase * Math.PI * 4;
                            const depth = (1 - Math.cos(angle)) / 2;
                            const x = (width < 768 ? 0.65 : 0.68) + Math.sin(angle) * (width < 768 ? 0.17 : 0.22);
                            const y = 0.04 + phase * 0.92;
                            const tilt = -9 + Math.sin(angle) * 5;
                            const scale = 0.48 + depth * 0.52;
                            card.style.transform = `translate3d(${x * width}px,${y * height}px,0) translate(-50%,-50%) rotate(${tilt}deg) scale(${scale})`;
                            const halfWidth = scale * (cardSizes[index].width + 0.25 * cardSizes[index].height) / 2;
                            const halfHeight = scale * (cardSizes[index].height + 0.25 * cardSizes[index].width) / 2;
                            let clearance = 1;
                            for (const zone of zones) {
                                const dx = Math.max(zone.left - x * width, x * width - zone.right) - halfWidth;
                                const dy = Math.max(zone.top - y * height, y * height - zone.bottom) - halfHeight;
                                clearance = Math.min(clearance, Math.max(0, dx, dy) / 48);
                            }
                            card.style.opacity = String(clearance * Math.min(1, phase / 0.08, (1 - phase) / 0.16) * (0.16 + depth * depth * 0.84));
                            card.style.zIndex = String(Math.round(depth * 100));
                        });
                    };
                    const onScroll = () => {
                        const delta = window.scrollY - lastScroll;
                        if (Math.abs(delta) < 2) return;
                        direction = Math.sign(delta);
                        lastScroll = window.scrollY;
                    };
                    const sync = () => {
                        gsap.ticker.remove(draw);
                        if (visible && !document.hidden) gsap.ticker.add(draw);
                    };
                    const observer = new IntersectionObserver(([entry]) => {
                        visible = entry.isIntersecting;
                        sync();
                    });
                    draw(0, 0);
                    observer.observe(gallery);
                    window.addEventListener('scroll', onScroll, { passive: true });
                    document.addEventListener('visibilitychange', sync);
                    stopGallery = () => {
                        observer.disconnect();
                        layout.disconnect();
                        gsap.ticker.remove(draw);
                        window.removeEventListener('scroll', onScroll);
                        document.removeEventListener('visibilitychange', sync);
                        for (const card of cards) card.removeAttribute('style');
                    };
                }
                if (conditions.desktop) {
                    gsap.to('[data-hero-mark]', {
                        yPercent: -4,
                        ease: 'none',
                        scrollTrigger: {
                            trigger: '#home',
                            start: 'top top',
                            end: 'bottom top',
                            scrub: true,
                        },
                    });
                }

                return stopGallery;
            },
        );
    });

    onUnmounted(() => media?.revert());
}
