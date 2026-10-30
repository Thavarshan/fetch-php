<script setup lang="ts">
import CodeShowcase from './CodeShowcase.vue';
import { data } from '../data/showcases.data';

const capabilities = [
    {
        number: '01', title: 'A fluent vocabulary.',
        text: 'Set headers, authentication, timeouts, and request defaults. '
            + 'Keep the details close to the call.',
        link: '/guide/making-requests', label: 'Configure requests',
    },
    {
        number: '02', title: 'Responses you can work with.',
        text: 'Read JSON, inspect status codes, or work directly with '
            + 'the underlying PSR-7 message.',
        link: '/guide/working-with-responses', label: 'Handle responses',
    },
    {
        number: '03', title: 'Your policies, in the pipeline.',
        text: 'Add retries, opt-in HTTP caching, middleware, and '
            + 'request validation as your application grows.',
        link: '/guide/', label: 'Explore the guides',
    },
];
</script>

<template>
    <section class="features" aria-labelledby="features-title">
        <div class="site-container">
            <div class="section-heading">
                <p class="eyebrow">A small surface. A capable client.</p>
                <h2 id="features-title">Start simple.<br />Stay in control.</h2>
                <p>
                    One request shouldn’t need a ceremony. A growing
                    application shouldn’t need a different HTTP client.
                </p>
            </div>
            <div class="feature-composition">
                <div class="configuration-example">
                    <CodeShowcase
                        :example="data.client"
                        filename="client.php"
                    />
                    <p class="example-caption">
                        Real API. Offline-tested examples.
                        <a href="/examples/snippets/">See the full source →</a>
                    </p>
                </div>
                <div class="capabilities">
                    <article v-for="item in capabilities" :key="item.number">
                        <span class="capability-number">{{ item.number }}</span>
                        <div>
                            <h3>{{ item.title }}</h3>
                            <p>{{ item.text }}</p>
                            <a class="text-link" :href="item.link">
                                {{ item.label }}
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </article>
                </div>
            </div>
            <div class="principles">
                <p>
                    Familiar by design.<br />
                    <strong>Flexible by default.</strong>
                </p>
                <a href="/guide/testing">
                    <span>Test without the network</span>
                    Fake responses and prevent stray requests.
                    <b aria-hidden="true">↗</b>
                </a>
                <a href="/guide/streaming">
                    <span>Read it as it arrives</span>
                    Streams and Server-Sent Events, on demand.
                    <b aria-hidden="true">↗</b>
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
.features {
    padding: 110px 0 0;
    border-top: 1px solid var(--site-border);
    background: var(--site-surface);
}
.section-heading {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 0 80px;
    align-items: end;
    margin-bottom: 50px;
}
.section-heading .eyebrow {
    grid-column: 1 / -1;
}
h2 {
    margin: 0;
    font-size: clamp(38px, 4.3vw, 62px);
    font-weight: 500;
    letter-spacing: -0.055em;
    line-height: 1.1;
}
.section-heading > p:last-child {
    max-width: 390px;
    margin: 0 0 5px;
    color: var(--site-muted);
    font-size: 17px;
    line-height: 1.8;
}
.feature-composition {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    gap: 80px;
    align-items: start;
}
.configuration-example {
    min-width: 0;
}
.configuration-example :deep(.code-showcase) {
    box-shadow: 0 10px 35px rgb(12 30 62 / 5%);
}
.example-caption {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 8px;
    margin: 16px 0 0;
    color: var(--site-muted);
    font-size: 13px;
}
.example-caption a {
    color: var(--site-accent);
}
.capabilities article {
    display: grid;
    grid-template-columns: 28px 1fr;
    gap: 14px;
    padding: 24px 0;
    border-top: 1px solid var(--site-border);
}
.capabilities article:first-child {
    padding-top: 0;
    border: 0;
}
.capability-number {
    padding-top: 4px;
    color: var(--site-accent);
    font-family: var(--vp-font-family-mono);
    font-size: 13px;
}
h3 {
    margin: 0 0 8px;
    font-size: 21px;
    font-weight: 500;
    letter-spacing: -0.025em;
}
.capabilities p {
    margin: 0 0 12px;
    color: var(--site-muted);
    font-size: 15px;
    line-height: 1.75;
}
.principles {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    margin-top: 76px;
    border-top: 1px solid var(--site-border);
}
.principles > * {
    position: relative;
    margin: 0;
    padding: 28px 28px 32px;
    border-right: 1px solid var(--site-border);
    color: var(--site-muted);
    font-size: 13px;
    line-height: 1.8;
}
.principles > :first-child {
    padding-left: 0;
}
.principles > :last-child {
    border: 0;
}
.principles span,
.principles strong {
    display: block;
    color: var(--site-text);
    font-size: 16px;
    font-weight: 500;
}
.principles b {
    position: absolute;
    top: 29px;
    right: 24px;
    color: var(--site-accent);
    font-weight: 400;
}
.principles a:hover {
    background: var(--site-accent-soft);
}
@media (max-width: 1023px) {
    .feature-composition {
        gap: 38px;
    }
}
@media (max-width: 767px) {
    .features {
        padding-top: 64px;
    }
    .section-heading,
    .feature-composition {
        grid-template-columns: 1fr;
        gap: 26px;
    }
    .section-heading {
        margin-bottom: 32px;
    }
    .section-heading .eyebrow {
        margin-bottom: -8px;
    }
    .section-heading > p:last-child {
        font-size: 16px;
    }
    .capabilities {
        margin-top: 12px;
    }
    .principles {
        grid-template-columns: 1fr;
        margin-top: 36px;
    }
    .principles > * {
        padding: 22px 0;
        border-right: 0;
        border-bottom: 1px solid var(--site-border);
    }
    .principles b {
        right: 0;
    }
}
</style>
