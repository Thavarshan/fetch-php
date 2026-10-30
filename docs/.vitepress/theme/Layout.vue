<script setup lang="ts">
import DefaultTheme from 'vitepress/theme';
import { useData } from 'vitepress';
import LandingHero from './components/LandingHero.vue';

const { Layout } = DefaultTheme;
const { frontmatter } = useData();
</script>

<template>
    <Layout>
        <template #home-hero-before>
            <LandingHero v-if="frontmatter.fetchHome" />
        </template>
    </Layout>
</template>
