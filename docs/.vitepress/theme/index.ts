import DefaultTheme from 'vitepress/theme';
import type { Theme } from 'vitepress';
import Layout from './Layout.vue';
import InstallCommand from './components/InstallCommand.vue';
import ResponsePreview from './components/ResponsePreview.vue';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from './components/ui/tabs';
import './tailwind.css';
import './style.css';

const theme: Theme = {
    ...DefaultTheme,
    Layout,
    enhanceApp({ app }) {
        app.component('InstallCommand', InstallCommand);
        app.component('ResponsePreview', ResponsePreview);
        app.component('Tabs', Tabs);
        app.component('TabsContent', TabsContent);
        app.component('TabsList', TabsList);
        app.component('TabsTrigger', TabsTrigger);
    },
};

export default theme;
