<script setup lang="ts">
import { ref } from 'vue';
import { Button } from './ui/button';

const command = 'composer require jerome/fetch-php';
const feedback = ref('');

async function copyCommand(): Promise<void> {
    try {
        await navigator.clipboard.writeText(command);
        feedback.value = 'Copied';
    } catch {
        feedback.value = 'Select the command to copy';
    }
}
</script>

<template>
    <div class="fetch-install-command">
        <code>{{ command }}</code>
        <Button
            size="sm"
            variant="ghost"
            :class="[
                'min-h-11',
                'min-w-11',
                'rounded-sm',
                'border',
                'border-white/25',
                'px-2',
                'text-[var(--site-hero-text)]',
                'hover:bg-white/10',
                'hover:text-[var(--site-highlight)]',
            ]"
            type="button"
            :aria-label="feedback || 'Copy Composer install command'"
            @click="copyCommand"
        >
            {{ feedback || 'Copy' }}
        </Button>
        <span class="visually-hidden" aria-live="polite">
            {{ feedback }}
        </span>
    </div>
</template>
