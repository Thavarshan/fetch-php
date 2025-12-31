<script setup lang="ts">
import { useData, withBase } from 'vitepress';

const { site } = useData();
</script>

<template>
    <a class="brand-header" :href="withBase('/')">
        <img class="brand-logo" :src="withBase('/logo.png')" alt="Fetch PHP logo" />
        <span class="brand-text">
            <span class="brand-name">{{ site.title }}</span>
            <span class="brand-subtitle">fetch() for PHP</span>
        </span>
    </a>
</template>
