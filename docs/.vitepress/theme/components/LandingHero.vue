<template>
    <section class="fetch-home-hero" aria-labelledby="fetch-home-title">
        <div class="fetch-home-hero-inner">
            <p class="fetch-home-eyebrow">
                A modern HTTP client for PHP
            </p>
            <h1 id="fetch-home-title">
                HTTP, the<br />
                <span>PHP way.</span>
            </h1>
            <p class="fetch-home-intro">
                The familiar fetch experience, grounded in Guzzle and built
                for the way PHP applications work.
            </p>
            <nav class="fetch-home-actions" aria-label="Get started">
                <Button as-child size="lg">
                    <a href="/guide/quickstart">
                        Start building
                        <span aria-hidden="true">↗</span>
                    </a>
                </Button>
                <Button
                    as-child
                    variant="ghost"
                    :class="[
                        'text-[var(--site-hero-text)]',
                        'hover:bg-white/10',
                        'hover:text-[var(--site-highlight)]',
                    ]"
                >
                    <a href="https://github.com/Thavarshan/fetch-php">
                        View on GitHub
                    </a>
                </Button>
            </nav>
            <div class="fetch-home-install">
                <p>Install with Composer</p>
                <InstallCommand />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Button } from './ui/button';
</script>
