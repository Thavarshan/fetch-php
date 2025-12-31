<script setup lang="ts">
import { withBase } from 'vitepress';
import { ArrowUpRight, Github } from 'lucide-vue-next';
import Icon from './Icon.vue';
</script>

<template>
    <div class="nav-actions">
        <a class="nav-action primary" :href="withBase('/guide/quickstart')">
            Get Started
            <Icon :icon="ArrowUpRight" :size="16" />
        </a>
        <a
            class="nav-action ghost"
            href="https://github.com/Thavarshan/fetch-php"
            target="_blank"
            rel="noreferrer"
        >
            <Icon :icon="Github" :size="16" />
            GitHub
        </a>
    </div>
</template>
