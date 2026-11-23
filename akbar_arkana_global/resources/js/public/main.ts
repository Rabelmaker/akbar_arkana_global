import 'lenis/dist/lenis.css';
import '../../css/public.css';
import '../../css/landing.css';
import { createApp } from 'vue';
import PublicLayout from './PublicLayout.vue';
import router from './router';

createApp(PublicLayout).use(router).mount('#aag-app');
