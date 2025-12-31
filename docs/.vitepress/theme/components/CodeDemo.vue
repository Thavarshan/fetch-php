<script setup lang="ts">
import { computed, ref } from 'vue';
import { Check, Copy } from 'lucide-vue-next';
import Icon from './Icon.vue';

const tabs = [
    {
        id: 'js',
        label: 'JavaScript',
        language: 'js',
        highlights: [1, 2],
        code: `const response = await fetch('https://api.example.com/users');\nconst users = await response.json();\n\nif (!response.ok) {\n  throw new Error('Request failed');\n}\n\nrender(users);`,
    },
    {
        id: 'php',
        label: 'PHP',
        language: 'php',
        highlights: [1, 2, 4],
        code: `use function Matrix\\Support\\async;\nuse function Matrix\\Support\\await;\n\n$response = await(async(fn() => fetch('https://api.example.com/users')));\n$users = $response->json();\n\nif (! $response->successful()) {\n    throw new RuntimeException('Request failed');\n}\n\nrender($users);`,
    },
];

const activeTab = ref(tabs[0]);
const copied = ref(false);
const copyLabel = computed(() => (copied.value ? 'Copied' : 'Copy'));

const lines = computed(() => activeTab.value.code.split('\n'));
const highlightSet = computed(() => new Set(activeTab.value.highlights));

const onCopy = async () => {
    try {
        await navigator.clipboard.writeText(activeTab.value.code);
        copied.value = true;
        window.setTimeout(() => {
            copied.value = false;
        }, 1600);
    } catch (error) {
        copied.value = false;
    }
};
</script>

<template>
    <div class="code-demo">
        <div class="code-tabs" role="tablist" aria-label="Code example tabs">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="code-tab"
                role="tab"
                :aria-selected="tab.id === activeTab.id"
                @click="activeTab = tab"
            >
                {{ tab.label }}
            </button>
            <div class="code-demo-spacer"></div>
            <button type="button" class="code-copy" @click="onCopy">
                <Icon :icon="copied ? Check : Copy" :size="16" />
                {{ copyLabel }}
            </button>
        </div>
        <div class="code-shell">
            <span class="code-language">{{ activeTab.language }}</span>
            <pre class="code-pre"><code>
<span
    v-for="(line, index) in lines"
    :key="index"
    class="code-line"
    :class="{ 'is-highlighted': highlightSet.has(index + 1) }"
>{{ line === '' ? ' ' : line }}</span>
</code></pre>
        </div>
    </div>
</template>
