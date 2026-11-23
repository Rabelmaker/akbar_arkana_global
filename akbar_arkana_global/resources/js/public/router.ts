import { createRouter, createWebHistory } from 'vue-router';
import HomePage from './HomePage.vue';

export default createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/contact', name: 'contact', component: () => import('./components/ContactSection.vue') },
        { path: '/', name: 'home', component: HomePage },
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: () => import('./NotFoundPage.vue'),
        },
    ],
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        if (!to.hash) return { top: 0 };

        const target = document.getElementById(to.hash.slice(1));
        if (!target) return { top: 0 };
        if (to.hash === '#home') return { top: 0, behavior: scrollBehavior() };

        const offset =
            Number.parseFloat(
                getComputedStyle(document.documentElement).scrollPaddingTop,
            ) || 0;
        return {
            el: target,
            top: offset,
            behavior: from.name === to.name ? scrollBehavior() : 'instant',
        };
    },
});

function scrollBehavior(): ScrollBehavior {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ? 'instant'
        : 'smooth';
}
