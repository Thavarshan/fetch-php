<script setup lang="ts">
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import CodeShowcase from './CodeShowcase.vue';
import { data } from '../data/showcases.data';
</script>

<template>
    <div
        class="workbench dark"
        aria-label="Illustrative HTTP request and response"
    >
        <div class="workbench-header">
            <p><b>GET</b><code>api.example.test/v1/users/42</code></p>
            <span>Illustrative example · no request is sent</span>
        </div>
        <div class="workbench-body">
            <Tabs default-value="request" class="request-tabs">
                <TabsList
                    class="mx-5 self-start"
                    aria-label="HTTP example views"
                >
                    <TabsTrigger value="request">
                        PHP request
                    </TabsTrigger>
                    <TabsTrigger value="response">
                        JSON response
                    </TabsTrigger>
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
    position: relative;
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    background: var(--background);
    color: var(--foreground);
    box-shadow: var(--site-shadow);
}
.workbench-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 19px 26px;
    border-bottom: 1px solid var(--border);
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
    border: 1px solid var(--border);
    border-radius: var(--radius-xs);
    color: var(--primary);
    font-size: 13px;
    letter-spacing: 0.05em;
}
.workbench-header code {
    padding: 0;
    color: var(--foreground);
    font-size: 13px;
    overflow-wrap: anywhere;
}
.workbench-header > span {
    color: var(--muted-foreground);
    font-size: 13px;
}
.workbench-body {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
}
.request-tabs {
    min-width: 0;
    padding: 14px 0 0;
}
.request-tabs :deep(.code-showcase) {
    border: 0;
    border-radius: 0;
}
.request-tabs :deep(figcaption) {
    min-height: 44px;
    border-top: 1px solid var(--border);
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
    border-left: 1px solid var(--border);
    background: var(--secondary);
}
.response-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
}
.response-topline b {
    color: var(--primary);
    font-family: var(--vp-font-family-mono);
    font-size: 13px;
    font-weight: 400;
}
.response-type {
    margin: 6px 0 24px;
    color: var(--muted-foreground);
    font-family: var(--vp-font-family-mono);
    font-size: 13px;
}
dl {
    display: grid;
    gap: 14px;
    margin: 0;
    font-family: var(--vp-font-family-mono);
    font-size: 13px;
}
dl > div {
    display: grid;
    grid-template-columns: 65px 1fr;
    gap: 12px;
}
dt {
    color: var(--muted-foreground);
}
dd {
    margin: 0;
    color: var(--foreground);
}
.response-note {
    margin: auto 0 0;
    padding-top: 26px;
    color: var(--muted-foreground);
    font-size: 13px;
    line-height: 1.8;
}
.request-lifecycle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
    padding: 20px 26px;
    border-top: 1px solid var(--border);
    font-size: 13px;
    color: var(--muted-foreground);
}
.request-lifecycle span {
    white-space: nowrap;
}
.request-lifecycle b {
    margin-right: 9px;
    color: var(--primary);
    font-family: var(--vp-font-family-mono);
    font-weight: 400;
}
.request-lifecycle i {
    flex: 1;
    height: 1px;
    background: var(--border);
}
.example-note {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0;
    padding: 13px 26px;
    border-top: 1px solid var(--border);
    color: var(--muted-foreground);
    font-size: 13px;
}
.example-note a {
    color: var(--primary);
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
        border-top: 1px solid var(--border);
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
