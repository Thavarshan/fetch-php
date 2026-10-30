import { defineConfig } from 'vitepress';
import fs from 'fs';
import path from 'path';

export default defineConfig({
    title: 'Fetch PHP',
    description:
        'Guides and API reference for the Guzzle-based Fetch PHP HTTP client.',
    base: '/',
    srcExclude: ['ai/**'],
    sitemap: {
        hostname: 'https://fetch-php.thavarshan.com',
    },
    lastUpdated: true,
    head: [
        ['link', { rel: 'icon', href: '/favicon.ico' }],
        ['meta', { name: 'author', content: 'Jerome Thayananthajothy' }],
        [
            'meta',
            {
                property: 'og:title',
                content: 'Fetch PHP documentation',
            },
        ],
        [
            'meta',
            {
                property: 'og:description',
                content: 'Reference and guides for the Fetch PHP HTTP client.',
            },
        ],
        [
            'link',
            { rel: 'canonical', href: 'https://fetch-php.thavarshan.com' },
        ],
    ],
    titleTemplate: ':title | Fetch PHP',
    cleanUrls: true,
    themeConfig: {
        logo: '/logo.png',
        siteTitle: 'Fetch PHP',
        nav: [
            { text: 'Home', link: '/' },
            { text: 'Guide', link: '/guide/' },
            { text: 'API Reference', link: '/api/' },
            { text: 'Examples', link: '/examples/' },
            {
                text: 'Changelog',
                link: [
                    'https://github.com/Thavarshan/fetch-php/blob/main/',
                    'CHANGELOG.md',
                ].join(''),
            },
        ],
        sidebar: {
            '/guide/': [
                {
                    text: 'Getting started',
                    items: [
                        { text: 'Overview', link: '/guide/' },
                        { text: 'Installation', link: '/guide/installation' },
                        { text: 'Quickstart', link: '/guide/quickstart' },
                    ],
                },
                {
                    text: 'Core usage',
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
                    text: 'Advanced usage',
                    items: [
                        {
                            text: 'Async and Matrix',
                            link: '/guide/async-requests',
                        },
                        {
                            text: 'Promise operations',
                            link: '/guide/promise-operations',
                        },
                        { text: 'Retries', link: '/guide/retry-handling' },
                        { text: 'HTTP caching', link: '/guide/http-caching' },
                        { text: 'Validation', link: '/guide/validation' },
                        { text: 'Middleware', link: '/guide/middleware' },
                        { text: 'Lifecycle events', link: '/guide/events' },
                        { text: 'Streaming and SSE', link: '/guide/streaming' },
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
                    text: 'Reference',
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
    },
    buildEnd: async ({ outDir }) => {
        const robotsTxt = [
            'User-agent: *',
            'Allow: /',
            'Sitemap: https://fetch-php.thavarshan.com/sitemap.xml',
        ].join('\n');

        fs.writeFileSync(path.join(outDir, 'robots.txt'), robotsTxt);
    },
    vite: {
        build: {
            minify: true,
            cssMinify: true,
        },
        server: {
            fs: {
                strict: true,
            },
        },
    },
});
