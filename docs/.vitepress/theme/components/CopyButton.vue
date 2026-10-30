<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Button } from './ui/button';

const props = defineProps<{ text: string; label: string }>();
const emit = defineEmits<{
    feedback: [kind: 'idle' | 'success' | 'error'];
}>();
const copied = ref(false);
const announcement = ref('');
let resetTimer: ReturnType<typeof setTimeout> | undefined;
let operation = 0;
let mounted = true;

function reset(notify = true): void {
    operation++;
    clearTimeout(resetTimer);
    copied.value = false;
    announcement.value = '';
    if (notify) emit('feedback', 'idle');
}

async function copy(): Promise<void> {
    reset();
    const current = operation;
    let success = true;
    try {
        await navigator.clipboard.writeText(props.text);
    } catch {
        success = false;
    }
    if (!mounted || current !== operation) return;
    // Commit an empty live region before each fresh announcement.
    await nextTick();
    if (!mounted || current !== operation) return;
    copied.value = success;
    announcement.value = success ? 'Copied'
        : 'Copy failed. Select the text and copy it manually.';
    emit('feedback', success ? 'success' : 'error');
    resetTimer = setTimeout(() => reset(), 2800);
}

watch(() => props.text, () => reset());
onBeforeUnmount(() => {
    mounted = false;
    reset(false);
});
</script>

<template>
    <Button
        class="copy-control w-20 min-h-11 shrink-0 font-sans"
        size="sm"
        variant="outline"
        :aria-label="label"
        @click="copy"
    >
        {{ copied ? 'Copied' : 'Copy' }}
    </Button>
    <span
        class="visually-hidden"
        role="status"
        aria-live="polite"
        aria-atomic="true"
    >{{ announcement }}</span>
</template>
