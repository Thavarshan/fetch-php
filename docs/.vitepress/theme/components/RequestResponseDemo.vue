<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import CodeShowcase from './CodeShowcase.vue';
import { data } from '../data/showcases.data';
</script>

<template>
    <div class="workbench" aria-label="Illustrative HTTP request and response">
        <div class="workbench-header">
            <p><b>GET</b><code>api.example.test/v1/users/42</code></p>
            <span>Illustrative example · no request is sent</span>
        </div>
        <div class="workbench-body">
            <Tabs default-value="request" class="request-tabs">
                <TabsList aria-label="HTTP example views">
                    <TabsTrigger value="request">PHP request</TabsTrigger>
                    <TabsTrigger value="response">JSON response</TabsTrigger>
                </TabsList>
                <TabsContent value="request">
                    <CodeShowcase
                        :example="data.request"
                        filename="request.php"
                        inverse
                    />
                </TabsContent>
                <TabsContent value="response">
                    <CodeShowcase
                        :example="data.response"
                        filename="response.json"
                        inverse
                    />
                </TabsContent>
            </Tabs>
            <aside class="response-inspector" aria-label="Response overview">
                <div class="response-topline">
                    <span>Response</span><b>200 OK</b>
                </div>
                <p class="response-type">application/json</p>
                <dl>
                    <div v-for="(value, key) in data.responseBody" :key="key">
                        <dt>{{ key }}</dt><dd>{{ value }}</dd>
                    </div>
                </dl>
                <p class="response-note">
                    A PSR-7 response.<br />
                    With the helpers you reach for every day.
                </p>
            </aside>
        </div>
        <div class="request-lifecycle" aria-label="Request lifecycle">
            <span><b>01</b> Compose a request</span>
            <i aria-hidden="true"></i>
            <span><b>02</b> Send with Guzzle</span>
            <i aria-hidden="true"></i>
            <span><b>03</b> Work with the response</span>
        </div>
        <p class="example-note">
            Autoloading and offline test fakes omitted for clarity.
            <a href="/examples/snippets/">View runnable examples →</a>
        </p>
    </div>
</template>

<style scoped>
.workbench {
    --site-accent: #9bc3ff;
    position: relative;
    border: 1px solid #31425f;
    border-radius: 8px;
    background: #111c2e;
    color: #e4edfb;
    box-shadow: var(--site-shadow);
}
.workbench-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 19px 26px;
    border-bottom: 1px solid #2d3b52;
}
.workbench-header p {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    min-width: 0;
    margin: 0;
}
.workbench-header b {
    padding: 3px 8px;
    border: 1px solid #436494;
    border-radius: 3px;
    color: #a8ceff;
    font-size: 11px;
    letter-spacing: 0.05em;
}
.workbench-header code {
    padding: 0;
    color: #e4edfb;
    font-size: 12px;
    overflow-wrap: anywhere;
}
.workbench-header > span {
    color: #a7b7d0;
    font-size: 11px;
}
.workbench-body {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
}
.request-tabs {
    min-width: 0;
    gap: 0;
    padding: 14px 0 0;
}
.request-tabs :deep([data-slot='tabs-list']) {
    justify-content: flex-start;
    gap: 25px;
    width: 100%;
    height: auto;
    padding: 0 26px;
    border-radius: 0;
    background: transparent;
}
.request-tabs :deep([data-slot='tabs-trigger']) {
    flex: none;
    min-height: 44px;
    padding: 0;
    border: 0;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    background: transparent;
    color: #a7b7d0;
    font-size: 12px;
    box-shadow: none;
}
.request-tabs :deep([data-slot='tabs-trigger'][data-state='active']) {
    border-bottom-color: #8dbbff;
    color: #f2f6ff;
}
.request-tabs :deep(.code-showcase) {
    border: 0;
    border-radius: 0;
}
.request-tabs :deep(figcaption) {
    min-height: 44px;
    border-top: 1px solid #2d3b52;
    border-bottom: 0;
    padding-left: 26px;
}
.request-tabs :deep(pre) {
    min-height: 200px;
    padding-top: 9px;
}
.response-inspector {
    display: flex;
    flex-direction: column;
    padding: 28px 32px;
    border-left: 1px solid #2d3b52;
    background: linear-gradient(135deg, #1a2b46, #142238);
}
.response-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
}
.response-topline b {
    color: #a8ceff;
    font-family: var(--vp-font-family-mono);
    font-size: 12px;
    font-weight: 400;
}
.response-type {
    margin: 6px 0 24px;
    color: #a7b7d0;
    font-family: var(--vp-font-family-mono);
    font-size: 11px;
}
dl {
    display: grid;
    gap: 14px;
    margin: 0;
    font-family: var(--vp-font-family-mono);
    font-size: 12px;
}
dl > div {
    display: grid;
    grid-template-columns: 65px 1fr;
    gap: 12px;
}
dt {
    color: #a7b7d0;
}
dd {
    margin: 0;
    color: #e3edff;
}
.response-note {
    margin: auto 0 0;
    padding-top: 26px;
    color: #a7b7d0;
    font-size: 12px;
    line-height: 1.8;
}
.request-lifecycle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
    padding: 20px 26px;
    border-top: 1px solid #2d3b52;
    font-size: 11px;
    color: #bccbe0;
}
.request-lifecycle span {
    white-space: nowrap;
}
.request-lifecycle b {
    margin-right: 9px;
    color: #83b6ff;
    font-family: var(--vp-font-family-mono);
    font-weight: 400;
}
.request-lifecycle i {
    flex: 1;
    height: 1px;
    background: #344762;
}
.example-note {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0;
    padding: 13px 26px;
    border-top: 1px solid #2d3b52;
    color: #a7b7d0;
    font-size: 11px;
}
.example-note a {
    color: #b6d4ff;
    text-underline-offset: 4px;
}
.example-note a:hover {
    text-decoration: underline;
}
@media (max-width: 767px) {
    .workbench-header {
        padding: 16px 20px;
    }
    .workbench-body {
        grid-template-columns: 1fr;
    }
    .response-inspector {
        padding: 22px;
        border-top: 1px solid #2d3b52;
        border-left: 0;
    }
    .response-note {
        display: none;
    }
    .response-type {
        margin-bottom: 18px;
    }
    dl {
        gap: 8px;
    }
    .request-lifecycle {
        display: none;
    }
    .example-note {
        padding: 15px 20px;
    }
    .request-tabs :deep(pre) {
        min-height: 215px;
    }
}
</style>
