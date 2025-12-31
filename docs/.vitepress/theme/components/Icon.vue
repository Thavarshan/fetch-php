<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        icon: unknown;
        size?: number;
        stroke?: number;
    }>(),
    {
        size: 20,
        stroke: 1.8,
    }
);

const iconSize = computed(() => props.size);
const strokeWidth = computed(() => props.stroke);
</script>

<template>
    <component
        :is="props.icon"
        :size="iconSize"
        :stroke-width="strokeWidth"
        class="fp-icon"
        aria-hidden="true"
    />
</template>
