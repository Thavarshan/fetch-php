<script setup lang="ts">
import { TooltipContent, TooltipPortal } from 'reka-ui';

withDefaults(defineProps<{
    side?: 'top' | 'right' | 'bottom' | 'left';
    sideOffset?: number;
}>(), { side: 'top', sideOffset: 6 });
</script>

<template>
    <TooltipPortal>
        <TooltipContent
            data-slot="tooltip-content"
            class="tooltip-content"
            :side="side"
            :side-offset="sideOffset"
        >
            <slot />
        </TooltipContent>
    </TooltipPortal>
</template>

<style scoped>
.tooltip-content {
    z-index: 100;
    max-width: 250px;
    padding: 7px 11px;
    border: 1px solid var(--site-border);
    border-radius: var(--site-radius);
    background: var(--site-text);
    color: var(--site-bg);
    font-size: 11px;
    line-height: 1.6;
}
</style>
