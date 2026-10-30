<script setup lang="ts">
import CodeShowcase from './CodeShowcase.vue';
import { data } from '../data/showcases.data';
</script>

<template>
    <section class="async-section" aria-labelledby="async-title">
        <div class="site-container async-composition">
            <div class="async-copy">
                <p class="eyebrow">Concurrency, with intention</p>
                <h2 id="async-title">
                    More requests.<br /><em>One clear flow.</em>
                </h2>
                <p>
                    Enable async transport on a handler. Compose its promises
                    with Matrix. Work with the results when they’re ready.
                </p>
                <a class="text-link" href="/guide/async-requests">
                    Meet the async API <span aria-hidden="true">→</span>
                </a>
                <div class="async-boundary">
                    <code>async()</code>
                    <span>
                        An explicit boundary. Synchronous helpers stay
                        synchronous.
                    </span>
                </div>
            </div>
            <div class="async-example">
                <CodeShowcase
                    :example="data.async"
                    filename="concurrent.php"
                    inverse
                />
                <p>
                    Illustrative · autoloading and offline test fakes omitted.
                    <a href="/examples/snippets/">Runnable source ↗</a>
                </p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.async-section {
    --site-accent: #a4caff;
    --site-muted: #b0bfd6;
    --site-border: #2e405c;
    padding: 104px 0;
    color: #f0f5ff;
    background:
        linear-gradient(115deg, #10213c, #0e1829 65%);
}
.async-composition {
    display: grid;
    grid-template-columns: 0.9fr minmax(0, 1.3fr);
    gap: 88px;
    align-items: center;
}
h2 {
    margin: 0 0 25px;
    font-size: clamp(38px, 3.9vw, 56px);
    font-weight: 500;
    letter-spacing: -0.055em;
    line-height: 1.1;
}
h2 em {
    color: #9bc4ff;
    font-style: normal;
}
.async-copy > p:not(.eyebrow) {
    max-width: 365px;
    margin: 0 0 22px;
    color: var(--site-muted);
    font-size: 16px;
    line-height: 1.8;
}
.async-boundary {
    display: flex;
    align-items: flex-start;
    gap: 18px;
    max-width: 365px;
    margin-top: 38px;
    padding-top: 24px;
    border-top: 1px solid var(--site-border);
    color: var(--site-muted);
    font-size: 12px;
    line-height: 1.8;
}
.async-boundary code {
    color: #b9d7ff;
    font-size: 12px;
}
.async-example {
    min-width: 0;
}
.async-example > p {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    margin: 15px 0 0;
    color: var(--site-muted);
    font-size: 11px;
}
.async-example > p a {
    color: var(--site-accent);
}
@media (max-width: 1023px) {
    .async-composition {
        gap: 38px;
    }
}
@media (max-width: 767px) {
    .async-section {
        padding: 64px 0;
    }
    .async-composition {
        grid-template-columns: 1fr;
        gap: 36px;
    }
    .async-copy > p:not(.eyebrow) {
        max-width: 520px;
    }
    .async-boundary {
        margin-top: 26px;
    }
}
</style>
