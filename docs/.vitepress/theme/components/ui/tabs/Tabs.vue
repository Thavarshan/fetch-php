<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { TabsRoot } from 'reka-ui';

interface Props {
    activationMode?: 'automatic' | 'manual';
    class?: string;
    defaultValue?: string;
    dir?: 'ltr' | 'rtl';
    modelValue?: string;
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
    <TabsRoot
        data-slot="tabs"
        v-bind="delegatedProps"
        :class="cn(['flex', 'flex-col', 'gap-2'], props.class)"
    >
        <slot />
    </TabsRoot>
</template>
