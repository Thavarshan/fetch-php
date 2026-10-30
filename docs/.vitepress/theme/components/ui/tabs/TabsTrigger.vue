<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { TabsTrigger as PrimitiveTabsTrigger } from 'reka-ui';

interface Props {
    class?: string;
    disabled?: boolean;
    value: string;
}

const props = defineProps<Props>();
const delegatedProps = computed(() => {
    const attributes = { ...props };
    delete attributes.class;

    return attributes;
});
</script>

<template>
    <PrimitiveTabsTrigger
        data-slot="tabs-trigger"
        v-bind="delegatedProps"
        :class="cn(
            [
                'inline-flex',
                'flex-1',
                'items-center',
                'justify-center',
                'gap-1.5',
                'rounded-sm',
                'border',
                'border-transparent',
                'px-3',
                'py-1.5',
                'text-sm',
                'font-medium',
                'whitespace-nowrap',
                'transition-colors',
                'disabled:pointer-events-none',
                'disabled:opacity-50',
                'focus-visible:outline-1',
                'focus-visible:outline-ring',
                'focus-visible:ring-3',
                'focus-visible:ring-ring/50',
                'data-[state=active]:bg-background',
                'data-[state=active]:text-foreground',
                'data-[state=active]:shadow-sm',
                'dark:text-muted-foreground',
                'dark:data-[state=active]:border-input',
                'dark:data-[state=active]:bg-input/30',
                '[&_svg]:pointer-events-none',
                '[&_svg]:shrink-0',
                '[&_svg:not([class*=size-])]:size-4',
            ],
            props.class,
        )"
    >
        <slot />
    </PrimitiveTabsTrigger>
</template>
