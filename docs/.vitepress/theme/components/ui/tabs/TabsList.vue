<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { TabsList as PrimitiveTabsList } from 'reka-ui';

interface Props {
    class?: string;
    loop?: boolean;
    orientation?: 'horizontal' | 'vertical';
}

const props = defineProps<Props>();
const delegatedProps = computed(() => {
    const attributes = { ...props };
    delete attributes.class;

    return attributes;
});
</script>

<template>
    <PrimitiveTabsList
        data-slot="tabs-list"
        v-bind="delegatedProps"
        :class="cn(
            ['inline-flex', 'h-9', 'w-fit', 'items-center', 'justify-center'],
            ['rounded-md', 'bg-muted', 'p-1', 'text-muted-foreground'],
            props.class,
        )"
    >
        <slot />
    </PrimitiveTabsList>
</template>
