<script setup lang="ts">
import type { TooltipContentEmits, TooltipContentProps } from "reka-ui";
import type { HTMLAttributes } from "vue";
import { computed } from "vue";
import { cn } from "@/lib/utils";
import {
    TooltipArrow,
    TooltipContent,
    TooltipPortal,
    useForwardPropsEmits,
} from "reka-ui";

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<TooltipContentProps & { class?: HTMLAttributes["class"] }>(),
    {
        sideOffset: 4,
    },
);

const emits = defineEmits<TooltipContentEmits>();

const delegatedProps = computed(() => {
    const { class: _, ...rest } = props;
    return rest;
});
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <TooltipPortal>
        <TooltipContent
            data-slot="tooltip-content"
            v-bind="{ ...forwarded, ...$attrs }"
            :class="
                cn(
                    `bg-foreground text-background z-50 w-fit rounded-md
        px-3 py-1.5 text-xs text-balance shadow-md`,
                    props.class,
                )
            "
        >
            <slot />

            <TooltipArrow
                class="bg-foreground fill-foreground z-50 size-2.5
                translate-y-[calc(-50%_-_2px)] rotate-45 rounded-xs"
            />
        </TooltipContent>
    </TooltipPortal>
</template>
