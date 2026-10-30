<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { TabsContent as PrimitiveTabsContent } from 'reka-ui';

interface Props {
    class?: string;
    forceMount?: boolean;
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
    <PrimitiveTabsContent
        data-slot="tabs-content"
        :class="cn(['flex-1', 'outline-none'], props.class)"
        v-bind="delegatedProps"
    >
        <slot />
    </PrimitiveTabsContent>
</template>
