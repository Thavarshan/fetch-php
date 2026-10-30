<script setup lang="ts">
import type { TabsListProps } from "reka-ui";
import type { HTMLAttributes } from "vue";
import { computed } from "vue";
import { cn } from "@/lib/utils";
import { TabsList } from "reka-ui";

const props = defineProps<
    TabsListProps & { class?: HTMLAttributes["class"] }
>();

const delegatedProps = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
</script>

<template>
    <TabsList
        data-slot="tabs-list"
        v-bind="delegatedProps"
        :class="
            cn(
                `
inline-flex items-center justify-center rounded-lg bg-muted p-1
text-muted-foreground
`,
                props.class,
            )
        "
    >
        <slot />
    </TabsList>
</template>
