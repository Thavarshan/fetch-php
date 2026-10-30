<script setup lang="ts">
import { Button } from './ui/button';
</script>

<template>
    <section class="closing" aria-labelledby="closing-title">
        <div class="site-container closing-inner">
            <div>
                <p class="eyebrow">Your next request starts here</p>
                <h2 id="closing-title">
                    Less ceremony.<br />More possibility.
                </h2>
            </div>
            <div class="closing-actions">
                <p>
                    Start with one request. Find your rhythm.
                    The documentation takes it from there.
                </p>
                <div class="flex flex-wrap gap-3">
                    <Button as-child size="lg" class="min-h-11">
                        <a href="/guide/quickstart">Read the quickstart →</a>
                    </Button>
                    <Button
                        as-child size="lg"
                        class="min-h-11" variant="outline"
                    >
                        <a href="/api/">Explore the API</a>
                    </Button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.closing {
    padding: 86px 0;
    border-bottom: 1px solid var(--site-border);
    background: var(--site-wash);
}
.closing-inner {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 50px;
    align-items: center;
}
h2 {
    margin: 0;
    font-size: clamp(36px, 4vw, 58px);
    font-weight: 500;
    letter-spacing: -0.055em;
    line-height: 1.12;
}
.closing-actions p {
    max-width: 350px;
    margin: 0 0 24px;
    color: var(--site-muted);
    font-size: 16px;
    line-height: 1.8;
}
@media (max-width: 767px) {
    .closing {
        padding: 56px 0;
    }
    .closing-inner {
        grid-template-columns: 1fr;
        gap: 28px;
    }
}
</style>
