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
bg-muted text-muted-foreground inline-flex h-9 w-fit
items-center justify-center rounded-lg p-0.75
`,
                props.class,
            )
        "
    >
        <slot />
    </TabsList>
</template>
