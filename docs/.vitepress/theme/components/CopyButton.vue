<script setup lang="ts">
import { ref } from 'vue';
import { Button } from './ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from './ui/tooltip';

const props = defineProps<{ text: string; label: string }>();
const feedback = ref('');

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.text);
        feedback.value = 'Copied';
    } catch {
        feedback.value = 'Select the text to copy';
    }
}
</script>

<template>
    <TooltipProvider>
        <Tooltip>
            <TooltipTrigger as-child>
                <Button
                    class="copy-control"
                    size="sm"
                    variant="ghost"
                    :aria-label="label"
                    @click="copy"
                >
                    {{ feedback || 'Copy' }}
                </Button>
            </TooltipTrigger>
            <TooltipContent>{{ label }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>
    <span class="visually-hidden" role="status">{{ feedback }}</span>
</template>

<style scoped>
.copy-control {
    min-width: 44px;
    min-height: 44px;
    padding: 0 10px;
    color: inherit;
    font-size: 12px;
}
.copy-control:hover {
    background: var(--site-accent-soft);
    color: inherit;
}
</style>
