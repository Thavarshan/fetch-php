import DefaultTheme from 'vitepress/theme';
import Layout from './Layout.vue';
import HomePage from './components/HomePage.vue';
import './style.css';

export default {
    extends: DefaultTheme,
    Layout,
    enhanceApp({ app }) {
        app.component('HomePage', HomePage);
    },
};
