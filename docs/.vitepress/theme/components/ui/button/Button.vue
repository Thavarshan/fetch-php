<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Primitive } from 'reka-ui';
import { buttonVariants } from '.';

type ButtonVariant =
    | 'default'
    | 'destructive'
    | 'outline'
    | 'secondary'
    | 'ghost'
    | 'link';

type ButtonSize =
    | 'default'
    | 'xs'
    | 'sm'
    | 'lg'
    | 'icon'
    | 'icon-xs'
    | 'icon-sm'
    | 'icon-lg';

interface Props {
    as?: string | object;
    asChild?: boolean;
    class?: string | string[] | Record<string, boolean>;
    disabled?: boolean;
    size?: ButtonSize;
    type?: 'button' | 'submit' | 'reset';
    variant?: ButtonVariant;
}

const props = withDefaults(defineProps<Props>(), {
    as: 'button',
    type: 'button',
});
</script>

<template>
    <Primitive
        data-slot="button"
        :data-variant="variant"
        :data-size="size"
        :as="as"
        :as-child="asChild"
        :type="asChild ? undefined : type"
        :disabled="disabled"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </Primitive>
</template>
