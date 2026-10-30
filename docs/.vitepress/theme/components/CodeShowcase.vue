<script setup lang="ts">
import { ref } from 'vue';
import CopyButton from './CopyButton.vue';
const failed = ref(false);

defineProps<{
    example: { code: string; html: string };
    filename: string;
    inverse?: boolean;
}>();
</script>

<template>
    <figure
        class="code-showcase"
        :class="{ 'code-showcase--inverse': inverse, dark: inverse }"
    >
        <figcaption>
            <span>{{ filename }}</span>
            <span class="copy-error" aria-hidden="true">
                {{ failed ? 'Copy failed' : '' }}
            </span>
            <CopyButton
                :text="example.code"
                :label="`Copy ${filename} example`"
                @feedback="failed = $event === 'error'"
            />
        </figcaption>
        <div
            class="highlighted-code"
            tabindex="0"
            role="region"
            :aria-label="`${filename} code`"
            v-html="example.html"
        />
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
    font-size: 13px;
}
.copy-error {
    margin-left: auto;
    color: var(--site-danger);
}
.highlighted-code {
    overflow-x: auto;
}
.highlighted-code:focus-visible {
    outline-offset: -3px;
}
.highlighted-code :deep(pre) {
    width: max-content;
    min-width: 100%;
    margin: 0;
    padding: 26px;
    overflow: visible;
    background: transparent;
    font-size: 14px;
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
:global(.dark .code-showcase .highlighted-code span),
.code-showcase--inverse .highlighted-code :deep(span) {
    color: var(--shiki-dark);
}

@media (max-width: 767px) {
    .highlighted-code :deep(pre) {
        padding: 20px;
        font-size: 13px;
    }
}
</style>
