import DefaultTheme from 'vitepress/theme-without-fonts';
import type { Theme } from 'vitepress';
import LandingPage from './components/LandingPage.vue';
import AccessibleLayout from './AccessibleLayout.vue';
import './tailwind.css';
import './style.css';

const theme: Theme = {
    ...DefaultTheme,
    Layout: AccessibleLayout,
    async enhanceApp(context) {
        await DefaultTheme.enhanceApp?.(context);
        context.app.component('landing', LandingPage);
    },
};

export default theme;
