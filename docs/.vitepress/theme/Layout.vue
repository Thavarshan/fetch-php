<script setup lang="ts">
import DefaultTheme from 'vitepress/theme';
import { onBeforeUnmount, onMounted } from 'vue';
import BrandHeader from './components/BrandHeader.vue';
import SiteFooter from './components/SiteFooter.vue';

const handleScroll = () => {
    const isScrolled = window.scrollY > 6;
    document.documentElement.classList.toggle('nav-scrolled', isScrolled);
};

onMounted(() => {
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <DefaultTheme.Layout>
        <template #nav-bar-title>
            <BrandHeader />
        </template>
        <template #layout-bottom>
            <SiteFooter />
        </template>
    </DefaultTheme.Layout>
</template>
