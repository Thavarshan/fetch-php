<script setup lang="ts">
import { withBase } from 'vitepress';
import { useMotion } from '../composables/useMotion';
import CodeDemo from './CodeDemo.vue';
import Icon from './Icon.vue';
import {
    ArrowUpRight,
    Braces,
    CheckCircle2,
    Code2,
    Database,
    Gauge,
    GitPullRequest,
    Globe,
    Layers,
    Network,
    Rocket,
    ShieldCheck,
    Terminal,
    Workflow,
    Wrench,
    Zap,
} from 'lucide-vue-next';

useMotion();

const badges = [
    { label: 'PHP 8.3+', icon: Terminal },
    { label: 'PSR-7/18/3', icon: ShieldCheck },
    { label: 'RFC 7234 Cache', icon: Database },
    { label: 'Async Helpers', icon: Zap },
];

const features = [
    {
        title: 'Familiar API',
        description: 'Write requests in PHP the same way you do in JavaScript with fetch(), async, and await.',
        icon: Code2,
    },
    {
        title: 'Promise-Based Async',
        description: 'Promise-first API with then(), catch(), finally(), and helpers for concurrency.',
        icon: Zap,
    },
    {
        title: 'Fluent Interface',
        description: 'Chain config and requests for readable, composable HTTP code.',
        icon: Workflow,
    },
    {
        title: 'Helper Functions',
        description: 'Global helpers like fetch(), get(), post() for quick calls.',
        icon: Wrench,
    },
    {
        title: 'PSR Compatible',
        description: 'Implements PSR-7 (HTTP Messages), PSR-18 (HTTP Client), and PSR-3 (Logging) standards.',
        icon: ShieldCheck,
    },
    {
        title: 'Powerful Responses',
        description: 'Rich response helpers for json(), text(), array(), object(), and status checks.',
        icon: Braces,
    },
    {
        title: 'RFC 7234 Caching',
        description: 'Sync-only cache with ETag/Last-Modified revalidation, stale-while-revalidate, and stale-if-error support.',
        icon: Database,
    },
    {
        title: 'Pooling & HTTP/2',
        description: 'Shared connection pooling, DNS cache, and optional HTTP/2 with validation and stats for debugging.',
        icon: Network,
    },
    {
        title: 'Debug & Profiling',
        description: 'Unified debug snapshots and optional profiler with timing, memory, and connection stats; configurable log level.',
        icon: Gauge,
    },
];

const trustedTags = [
    'Internal API platforms',
    'SaaS backends',
    'Laravel + Symfony services',
    'CLI automation',
    'Edge proxies',
    'Data pipelines',
];

const productionHighlights = [
    {
        title: 'Built on Guzzle',
        description: 'Proven HTTP engine with retries, middleware, and transport control.',
        icon: Layers,
    },
    {
        title: 'Connection Pooling',
        description: 'Reusable sockets and DNS cache for lower latency across requests.',
        icon: Network,
    },
    {
        title: 'HTTP/2 Ready',
        description: 'Optional HTTP/2 for faster multiplexed connections when curl supports it.',
        icon: Globe,
    },
    {
        title: 'Observability',
        description: 'Debug snapshots, structured logging, and profiler hooks built-in.',
        icon: Gauge,
    },
];

const dxHighlights = [
    'Type-safe enums for methods, content types, and status codes.',
    'Mock server and request recording for deterministic testing.',
    'Global client configuration for consistent defaults.',
    'Response helpers that mirror fetch().',
];

const stats = [
    { label: 'Retry backoff', value: 'Exponential', icon: GitPullRequest },
    { label: 'Caching', value: 'RFC 7234', icon: Database },
    { label: 'Standards', value: 'PSR-7/18/3', icon: ShieldCheck },
];
</script>

<template>
    <div class="home-page">
        <section class="hero" data-reveal>
            <div class="hero-content">
                <p class="eyebrow">Fetch PHP</p>
                <h1>Write fetch() in PHP</h1>
                <p class="lead">
                    A modern HTTP client that mirrors the JavaScript fetch API while staying native to
                    PHP 8.3+. Compose requests fluently, run async flows, and ship production-grade
                    HTTP without the ceremony.
                </p>
                <div class="hero-actions">
                    <a class="button primary" :href="withBase('/guide/quickstart')">
                        Get Started
                        <Icon :icon="ArrowUpRight" :size="16" />
                    </a>
                    <a class="button ghost" href="https://github.com/Thavarshan/fetch-php" target="_blank" rel="noreferrer">
                        View on GitHub
                    </a>
                </div>
                <div class="badge-row">
                    <span v-for="badge in badges" :key="badge.label" class="badge">
                        <Icon :icon="badge.icon" :size="14" />
                        {{ badge.label }}
                    </span>
                </div>
                <div class="meta-badges">
                    <span class="meta-pill">Latest: v1.x</span>
                    <span class="meta-pill">GitHub Stars</span>
                    <span class="meta-pill">MIT License</span>
                </div>
            </div>
            <div class="hero-panel">
                <div class="glow"></div>
                <div class="panel-card">
                    <img class="panel-logo" :src="withBase('/logo.png')" alt="Fetch PHP logo" />
                    <div class="panel-header">
                        <Icon :icon="Rocket" />
                        <div>
                            <p class="panel-title">Quickstart</p>
                            <p class="panel-subtitle">Install and ship in minutes</p>
                        </div>
                    </div>
                    <div class="panel-code">
                        <span>$</span>
                        <code>composer require jerome/fetch-php</code>
                    </div>
                    <ul class="panel-list">
                        <li><Icon :icon="CheckCircle2" :size="16" /> Drop-in global fetch() helper</li>
                        <li><Icon :icon="CheckCircle2" :size="16" /> Async helpers included</li>
                        <li><Icon :icon="CheckCircle2" :size="16" /> PSR compliant out of the box</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="trusted" data-reveal>
            <div>
                <p class="section-title">Trusted by PHP teams</p>
                <p class="section-subtitle">
                    Fetch PHP is designed for teams shipping APIs, SaaS backends, and internal tools
                    who want a modern HTTP experience without sacrificing PHP standards.
                </p>
            </div>
            <div class="trusted-tags">
                <span v-for="tag in trustedTags" :key="tag" class="trusted-pill">{{ tag }}</span>
            </div>
        </section>

        <section class="features" data-reveal>
            <div class="section-heading">
                <p class="section-title">Why Fetch PHP</p>
                <h2>Modern DX, serious production guarantees</h2>
            </div>
            <div class="feature-grid">
                <article v-for="feature in features" :key="feature.title" class="feature-card">
                    <div class="feature-icon">
                        <Icon :icon="feature.icon" :size="22" />
                    </div>
                    <h3>{{ feature.title }}</h3>
                    <p>{{ feature.description }}</p>
                </article>
            </div>
        </section>

        <section class="code-section" data-reveal>
            <div class="code-text">
                <p class="section-title">Copy/paste ready</p>
                <h2>Same mental model as JavaScript</h2>
                <p>
                    Use the Matrix async helpers to keep promise-based workflows familiar. The
                    response helpers map directly to fetch(), so JSON parsing feels identical.
                </p>
                <ul class="checklist">
                    <li><Icon :icon="CheckCircle2" :size="16" /> Promise chaining or async/await</li>
                    <li><Icon :icon="CheckCircle2" :size="16" /> Fluent request builder</li>
                    <li><Icon :icon="CheckCircle2" :size="16" /> Test utilities built-in</li>
                </ul>
            </div>
            <CodeDemo />
        </section>

        <section class="production" data-reveal>
            <div class="production-header">
                <div>
                    <p class="section-title">Performance & compatibility</p>
                    <h2>Designed for production from day one</h2>
                </div>
                <div class="stat-grid">
                    <div v-for="stat in stats" :key="stat.label" class="stat-card">
                        <Icon :icon="stat.icon" :size="18" />
                        <div>
                            <p class="stat-value">{{ stat.value }}</p>
                            <p class="stat-label">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="production-grid">
                <article v-for="item in productionHighlights" :key="item.title" class="production-card">
                    <Icon :icon="item.icon" :size="20" />
                    <div>
                        <h3>{{ item.title }}</h3>
                        <p>{{ item.description }}</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="dx" data-reveal>
            <div class="dx-card">
                <div>
                    <p class="section-title">DX highlights</p>
                    <h2>Built for real-world PHP teams</h2>
                </div>
                <ul class="dx-list">
                    <li v-for="item in dxHighlights" :key="item">
                        <Icon :icon="CheckCircle2" :size="16" />
                        {{ item }}
                    </li>
                </ul>
            </div>
            <div class="dx-card accent">
                <div>
                    <p class="section-title">Next steps</p>
                    <h2>Install, then explore the guide</h2>
                </div>
                <div class="panel-code">
                    <span>$</span>
                    <code>composer require jerome/fetch-php</code>
                </div>
                <a class="button primary" :href="withBase('/guide/quickstart')">
                    Read the Quickstart
                    <Icon :icon="ArrowUpRight" :size="16" />
                </a>
            </div>
        </section>

        <section class="final-cta" data-reveal>
            <div>
                <p class="section-title">Ready to ship?</p>
                <h2>Fetch PHP gives your team a clean HTTP foundation</h2>
                <p class="section-subtitle">
                    Install with Composer, then dive into the guide for async patterns, retries, and
                    advanced configuration.
                </p>
            </div>
            <div class="cta-actions">
                <div class="panel-code">
                    <span>$</span>
                    <code>composer require jerome/fetch-php</code>
                </div>
                <div class="cta-links">
                    <a class="button primary" :href="withBase('/guide/quickstart')">Get Started</a>
                    <a class="button ghost" :href="withBase('/guide/')">Explore the Docs</a>
                </div>
            </div>
        </section>
    </div>
</template>
