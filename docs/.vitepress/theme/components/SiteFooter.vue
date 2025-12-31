<script setup lang="ts">
import { withBase } from 'vitepress';
</script>

<template>
    <footer class="site-footer">
        <div class="site-footer-inner">
            <div>
                <p class="footer-title">Fetch PHP</p>
                <p class="footer-text">
                    MIT licensed. Built for PHP teams who want the JavaScript fetch API feel without
                    the ceremony.
                </p>
            </div>
            <div class="footer-meta">
                <span>PHP 8.3+</span>
                <span>PSR-7/18/3</span>
                <span>RFC 7234 Cache</span>
                <span>Async Helpers</span>
            </div>
            <div class="footer-links">
                <a :href="withBase('/guide/')">Guide</a>
                <a :href="withBase('/api/')">API Reference</a>
                <a href="https://github.com/Thavarshan/fetch-php" target="_blank" rel="noreferrer">
                    GitHub
                </a>
            </div>
        </div>
    </footer>
</template>
