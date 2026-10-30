<script setup lang="ts">
import type { TabsRootEmits, TabsRootProps } from "reka-ui";
import type { HTMLAttributes } from "vue";
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { TabsRoot, useForwardPropsEmits } from "reka-ui";

const props = defineProps<
    TabsRootProps & { class?: HTMLAttributes["class"] }
>();
const emits = defineEmits<TabsRootEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <TabsRoot
        v-slot="slotProps"
        data-slot="tabs"
        v-bind="forwarded"
        :class="cn('flex flex-col gap-2', props.class)"
    >
        <slot v-bind="slotProps" />
    </TabsRoot>
</template>
