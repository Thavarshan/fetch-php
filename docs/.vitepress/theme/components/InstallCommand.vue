<script setup lang="ts">
import { ref } from 'vue';
import CopyButton from './CopyButton.vue';
const command = 'composer require jerome/fetch-php';
const failed = ref(false);
</script>

<template>
    <div class="installation" aria-label="Install with Composer">
        <div class="installation-label">
            <span>Install with Composer</span>
            <span class="installation-error" aria-hidden="true">
                {{ failed ? 'Copy failed' : '' }}
            </span>
        </div>
        <div class="installation-command">
            <code>{{ command }}</code>
            <CopyButton
                :text="command"
                label="Copy Composer install command"
                @feedback="failed = $event === 'error'"
            />
        </div>
    </div>
</template>

<style scoped>
.installation {
    width: 100%;
    max-width: 420px;
    padding: 13px 15px;
    border: 1px solid var(--site-border);
    border-radius: var(--site-radius);
    background: var(--site-surface);
    box-shadow: 0 2px 5px rgb(14 29 52 / 3%);
    color: var(--site-text);
}
.installation-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    min-height: 22px;
    margin-bottom: 7px;
    font-family: var(--vp-font-family-base);
    font-size: 13px;
    font-weight: 500;
    line-height: 1.5;
}
.installation-error {
    color: var(--site-danger);
    font-size: 12px;
    white-space: nowrap;
}
.installation-command {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 44px;
}
code {
    min-width: 0;
    padding: 0;
    color: var(--site-text);
    font-family: var(--vp-font-family-mono);
    font-size: 13px;
    line-height: 1.65;
    white-space: normal;
    overflow-wrap: normal;
}
</style>
