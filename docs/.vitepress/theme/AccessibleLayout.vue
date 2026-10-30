<script setup lang="ts">
import { Button } from './components/ui/button';
import DefaultTheme from 'vitepress/theme-without-fonts';
import { useMenuFocus } from './composables/useMenuFocus';
const { Layout } = DefaultTheme;
useMenuFocus();
function closeMenu(): void {
    document.querySelector<HTMLElement>('.VPNavBarHamburger')?.click();
}
</script>

<template>
    <Layout>
        <template #nav-screen-content-before>
            <Button variant="outline" class="min-h-11" @click="closeMenu">
                Close menu
            </Button>
        </template>
    </Layout>
</template>
