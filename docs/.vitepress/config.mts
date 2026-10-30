import { defineConfig } from 'vitepress';

const siteUrl = 'https://fetch-php.thavarshan.com/';
const siteTitle = 'FetchPHP';
const siteDescription =
    'A Guzzle-based HTTP client with a familiar fetch API for PHP.';

export default defineConfig({
    title: siteTitle,
    description: siteDescription,
    base: '/',
    srcExclude: ['ai/**'],
    sitemap: {
        hostname: 'https://fetch-php.thavarshan.com',
    },
    lastUpdated: true,
    head: [
        ['link', { rel: 'icon', href: '/favicon.ico' }],
        ['link', { rel: 'manifest', href: '/site.webmanifest' }],
        ['link', { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' }],
        [
            'link',
            {
                rel: 'preload',
                href: '/fonts/ibm-plex-sans-latin.woff2',
                as: 'font',
                type: 'font/woff2',
                crossorigin: '',
            },
        ],
        ['meta', { name: 'author', content: 'Jerome Thayananthajothy' }],
        ['meta', { name: 'twitter:card', content: 'summary' }],
    ],
    transformPageData(pageData) {
        const canonicalPath = pageData.relativePath
            .replace(/(^|\/)index\.md$/, '$1')
            .replace(/\.md$/, '');
        const canonicalUrl = new URL(canonicalPath, siteUrl).toString();
        const existingHead = pageData.frontmatter.head ?? [];

        pageData.frontmatter.head = [
            ...existingHead.filter(([tag, attrs]) => {
                if (tag === 'link' && attrs.rel === 'canonical') {
                    return false;
                }

                return !(
                    tag === 'meta' &&
                    ['og:title', 'og:description', 'og:url'].includes(
                        attrs.property,
                    )
                );
            }),
            ['link', { rel: 'canonical', href: canonicalUrl }],
            [
                'meta',
                {
                    property: 'og:site_name',
                    content: siteTitle,
                },
            ],
            [
                'meta',
                {
                    property: 'og:type',
                    content: 'website',
                },
            ],
            [
                'meta',
                {
                    property: 'og:title',
                    content: pageData.title || siteTitle,
                },
            ],
            [
                'meta',
                {
                    property: 'og:description',
                    content: pageData.description || siteDescription,
                },
            ],
            ['meta', { property: 'og:url', content: canonicalUrl }],
            [
                'meta',
                {
                    name: 'twitter:title',
                    content: pageData.title || siteTitle,
                },
            ],
            [
                'meta',
                {
                    name: 'twitter:description',
                    content: pageData.description || siteDescription,
                },
            ],
        ];
    },
    titleTemplate: ':title | FetchPHP',
    cleanUrls: true,
    themeConfig: {
        siteTitle,
        nav: [
            { text: 'Guide', link: '/guide/' },
            { text: 'API', link: '/api/' },
            { text: 'Examples', link: '/examples/' },
        ],
        sidebar: {
            '/guide/': [
                {
                    text: 'Getting started',
                    collapsible: true,
                    items: [
                        { text: 'Overview', link: '/guide/' },
                        { text: 'Installation', link: '/guide/installation' },
                        { text: 'Quickstart', link: '/guide/quickstart' },
                    ],
                },
                {
                    text: 'Requests and responses',
                    collapsible: true,
                    items: [
                        {
                            text: 'Making requests',
                            link: '/guide/making-requests',
                        },
                        {
                            text: 'Helper functions',
                            link: '/guide/helper-functions',
                        },
                        {
                            text: 'Responses',
                            link: '/guide/working-with-responses',
                        },
                        { text: 'Enums', link: '/guide/working-with-enums' },
                        {
                            text: 'Authentication',
                            link: '/guide/authentication',
                        },
                        {
                            text: 'Error handling',
                            link: '/guide/error-handling',
                        },
                    ],
                },
                {
                    text: 'Async and streaming',
                    collapsible: true,
                    items: [
                        {
                            text: 'Async and Matrix',
                            link: '/guide/async-requests',
                        },
                        {
                            text: 'Promise operations',
                            link: '/guide/promise-operations',
                        },
                        { text: 'Streaming and SSE', link: '/guide/streaming' },
                    ],
                },
                {
                    text: 'Request policies',
                    collapsible: true,
                    items: [
                        { text: 'Retries', link: '/guide/retry-handling' },
                        { text: 'HTTP caching', link: '/guide/http-caching' },
                        { text: 'Validation', link: '/guide/validation' },
                        { text: 'Middleware', link: '/guide/middleware' },
                        { text: 'Lifecycle events', link: '/guide/events' },
                    ],
                },
                {
                    text: 'Integration and operations',
                    collapsible: true,
                    items: [
                        { text: 'File uploads', link: '/guide/file-uploads' },
                        {
                            text: 'Connection pool and HTTP/2',
                            link: '/guide/connection-pooling',
                        },
                        { text: 'Logging', link: '/guide/logging' },
                        {
                            text: 'Debugging and profiling',
                            link: '/guide/debugging-and-profiling',
                        },
                        {
                            text: 'Custom clients',
                            link: '/guide/custom-clients',
                        },
                        { text: 'Testing', link: '/guide/testing' },
                        { text: 'Architecture', link: '/guide/architecture' },
                        {
                            text: 'Troubleshooting',
                            link: '/guide/troubleshooting',
                        },
                        { text: 'Upgrading', link: '/guide/upgrading' },
                    ],
                },
            ],
            '/api/': [
                {
                    text: 'Functions',
                    collapsible: true,
                    items: [
                        { text: 'Overview', link: '/api/' },
                        { text: 'Global functions', link: '/api/fetch' },
                        { text: 'fetch_client()', link: '/api/fetch-client' },
                        {
                            text: 'HTTP method helpers',
                            link: '/api/http-method-helpers',
                        },
                    ],
                },
                {
                    text: 'Clients and messages',
                    collapsible: true,
                    items: [
                        { text: 'Client', link: '/api/client' },
                        { text: 'ClientHandler', link: '/api/client-handler' },
                        { text: 'Request', link: '/api/request' },
                        { text: 'Response', link: '/api/response' },
                        { text: 'Streams and events', link: '/api/streams' },
                    ],
                },
                {
                    text: 'Contracts and support',
                    collapsible: true,
                    items: [
                        {
                            text: 'Middleware and events',
                            link: '/api/middleware-events',
                        },
                        { text: 'Exceptions', link: '/api/exceptions' },
                        { text: 'Testing utilities', link: '/api/testing' },
                    ],
                },
                {
                    text: 'Enums',
                    collapsible: true,
                    items: [
                        { text: 'Method', link: '/api/method-enum' },
                        { text: 'ContentType', link: '/api/content-type-enum' },
                        { text: 'Status', link: '/api/status-enum' },
                    ],
                },
            ],
            '/examples/': [
                {
                    text: 'Examples',
                    collapsible: true,
                    items: [
                        { text: 'Overview', link: '/examples/' },
                        {
                            text: 'API integration',
                            link: '/examples/api-integration',
                        },
                        {
                            text: 'Async patterns',
                            link: '/examples/async-patterns',
                        },
                        {
                            text: 'Error handling',
                            link: '/examples/error-handling',
                        },
                        {
                            text: 'File handling',
                            link: '/examples/file-handling',
                        },
                        {
                            text: 'Authentication',
                            link: '/examples/authentication',
                        },
                    ],
                },
            ],
        },
        socialLinks: [
            { icon: 'github', link: 'https://github.com/Thavarshan/fetch-php' },
        ],
        footer: {
            message: 'Released under the MIT License.',
            copyright: 'Copyright © 2024-present Jerome Thayananthajothy',
        },
        outline: {
            level: [2, 3],
            label: 'On this page',
        },
        search: {
            provider: 'local',
        },
        lastUpdated: {
            text: 'Updated at',
            formatOptions: {
                dateStyle: 'medium',
            },
        },
        editLink: {
            pattern:
                'https://github.com/Thavarshan/fetch-php/edit/main/docs/:path',
            text: 'Edit this page on GitHub',
        },
        docFooter: {
            prev: 'Previous page',
            next: 'Next page',
        },
    },
    markdown: {
        theme: {
            light: 'github-light',
            dark: 'github-dark',
        },
    },
    vite: {
        server: {
            fs: {
                strict: true,
            },
        },
    },
});
