<script setup lang="ts">
import { Button } from './ui/button';
import InstallCommand from './InstallCommand.vue';
import RequestResponseDemo from './RequestResponseDemo.vue';
</script>

<template>
    <section class="hero" aria-labelledby="landing-title">
        <div class="site-container hero-frame">
            <div class="hero-intro">
                <div>
                    <p class="eyebrow">FetchPHP / HTTP for modern PHP</p>
                    <h1 id="landing-title">
                        Every good idea<br />
                        starts with <em>a request.</em>
                    </h1>
                </div>
                <div class="hero-description">
                    <p>
                        A familiar fetch API. The power of Guzzle.
                        From your first request to concurrent workflows,
                        write HTTP that feels at home in PHP.
                    </p>
                    <div class="hero-actions flex flex-wrap gap-3">
                        <Button as-child size="lg">
                            <a href="/guide/quickstart">
                                Get started <span aria-hidden="true">→</span>
                            </a>
                        </Button>
                        <Button as-child size="lg" variant="outline">
                            <a href="https://github.com/Thavarshan/fetch-php">
                                GitHub <span aria-hidden="true">↗</span>
                            </a>
                        </Button>
                    </div>
                    <InstallCommand />
                </div>
            </div>
            <RequestResponseDemo />
            <div class="hero-foundations" aria-label="Built on">
                <span>Small API. Strong foundations.</span>
                <p>
                    PHP 8.3+ <b>Guzzle 7.9+</b>
                    Matrix 4 <b>PSR-7 / PSR-18</b>
                </p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero {
    background:
        radial-gradient(ellipse at 90% 0%, var(--site-wash), transparent 60%),
        var(--site-bg);
}
.hero-frame {
    padding-top: 76px;
}
.hero-intro {
    display: grid;
    grid-template-columns: 1.55fr 1fr;
    gap: 60px;
    align-items: end;
    padding-bottom: 52px;
}
h1 {
    max-width: 800px;
    margin: 0;
    font-size: clamp(52px, 5.6vw, 80px);
    font-weight: 500;
    letter-spacing: -0.065em;
    line-height: 1.04;
}
h1 em {
    color: var(--site-accent);
    font-style: normal;
    white-space: nowrap;
}
.hero-description > p {
    max-width: 390px;
    margin: 0 0 24px;
    color: var(--site-muted);
    font-size: 17px;
    line-height: 1.7;
}
.hero-actions {
    margin-bottom: 22px;
}
.hero-foundations {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 23px 0;
    color: var(--site-muted);
    font-size: 12px;
}
.hero-foundations p {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
    margin: 0;
    font-family: var(--vp-font-family-mono);
    font-size: 11px;
}
.hero-foundations b {
    font-weight: 400;
}
@media (max-width: 1100px) {
    .hero-intro {
        grid-template-columns: 1.3fr 1fr;
        gap: 36px;
    }
    h1 {
        font-size: 62px;
    }
}
@media (min-width: 768px) and (max-width: 959px) {
    .hero-intro {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    .hero-description {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 28px;
        align-items: start;
    }
    .hero-description > p {
        grid-row: 1 / 3;
        margin: 0;
        font-size: 16px;
    }
    .hero-actions {
        margin-bottom: 16px;
    }
}
@media (max-width: 767px) {
    .hero-frame {
        padding-top: 46px;
    }
    .hero-intro {
        grid-template-columns: 1fr;
        gap: 28px;
        padding-bottom: 32px;
    }
    h1 {
        font-size: clamp(44px, 8.4vw, 62px);
    }
    .hero-description > p {
        max-width: 520px;
        font-size: 16px;
    }
    .hero-foundations {
        padding: 20px 0;
    }
    .hero-foundations p {
        gap: 10px 20px;
    }
}
</style>
