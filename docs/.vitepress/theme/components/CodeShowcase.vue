<script setup lang="ts">
import CopyButton from './CopyButton.vue';

defineProps<{
    example: { code: string; html: string };
    filename: string;
    inverse?: boolean;
}>();
</script>

<template>
    <figure
        class="code-showcase"
        :class="{ 'code-showcase--inverse': inverse }"
    >
        <figcaption>
            <span>{{ filename }}</span>
            <CopyButton
                :text="example.code"
                :label="`Copy ${filename} example`"
            />
        </figcaption>
        <div class="highlighted-code" v-html="example.html" />
    </figure>
</template>

<style scoped>
.code-showcase {
    min-width: 0;
    margin: 0;
    overflow: hidden;
    border: 1px solid var(--site-border);
    border-radius: var(--site-radius);
    background: var(--site-code-bg);
    color: var(--site-text);
}
figcaption {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 49px;
    padding: 0 16px 0 22px;
    border-bottom: 1px solid var(--site-border);
    color: var(--site-muted);
    font-family: var(--vp-font-family-mono);
    font-size: 11px;
}
.highlighted-code :deep(pre) {
    margin: 0;
    padding: 26px;
    overflow-x: auto;
    background: transparent;
    font-size: 13px;
    line-height: 1.9;
    tab-size: 4;
}
.highlighted-code :deep(code) {
    padding: 0;
    border: 0;
    background: transparent;
    font-family: var(--vp-font-family-mono);
}
.highlighted-code :deep(span) {
    color: var(--shiki-light);
}
:global(.dark) .highlighted-code :deep(span),
.code-showcase--inverse .highlighted-code :deep(span) {
    color: var(--shiki-dark);
}
.code-showcase--inverse {
    --site-accent: #9bc3ff;
    --site-border: #2d3b52;
    --site-text: #e4edfb;
    --site-muted: #a8b8d0;
    --site-accent-soft: rgb(133 180 255 / 14%);
    --site-code-bg: #111c2e;
}
@media (max-width: 767px) {
    .highlighted-code :deep(pre) {
        padding: 20px;
        font-size: 12px;
    }
}
</style>
