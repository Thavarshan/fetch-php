<script setup lang="ts">
import type { TabsTriggerProps } from "reka-ui";
import type { HTMLAttributes } from "vue";
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { TabsTrigger, useForwardProps } from "reka-ui";

const props = defineProps<
    TabsTriggerProps & { class?: HTMLAttributes["class"] }
>();

const delegatedProps = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <TabsTrigger
        data-slot="tabs-trigger"
        :class="
            cn(
                `
inline-flex items-center justify-center whitespace-nowrap
rounded-md px-3 py-1 text-sm font-medium ring-offset-background
transition-all focus-visible:outline-none focus-visible:ring-2
focus-visible:ring-ring focus-visible:ring-offset-2
disabled:pointer-events-none disabled:opacity-50
data-[state=active]:bg-background
data-[state=active]:text-foreground data-[state=active]:shadow
`,
                props.class,
            )
        "
        v-bind="forwardedProps"
    >
        <span class="truncate"><slot /></span>
    </TabsTrigger>
</template>
