<script setup lang="ts">
import LandingHero from './LandingHero.vue';
import FeatureShowcase from './FeatureShowcase.vue';
import AsyncShowcase from './AsyncShowcase.vue';
import LandingCallToAction from './LandingCallToAction.vue';
</script>

<template>
    <main class="landing-page" aria-label="FetchPHP overview">
        <LandingHero />
        <FeatureShowcase />
        <AsyncShowcase />
        <LandingCallToAction />
    </main>
</template>

<style scoped>
.landing-page {
    overflow: clip;
}
</style>
