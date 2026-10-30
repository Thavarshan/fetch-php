<script setup lang="ts">
import CopyButton from './CopyButton.vue';
const command = 'composer require jerome/fetch-php';
</script>

<template>
    <div class="installation" aria-label="Install with Composer">
        <code><span aria-hidden="true">$ </span>{{ command }}</code>
        <CopyButton :text="command" label="Copy Composer install command" />
    </div>
</template>

<style scoped>
.installation {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    max-width: 400px;
    border-top: 1px solid var(--site-border);
    color: var(--site-muted);
}
code {
    min-width: 0;
    padding: 0;
    overflow-x: auto;
    color: var(--site-text);
    font-size: 12px;
    white-space: nowrap;
}
code span {
    color: var(--site-muted);
}
</style>
