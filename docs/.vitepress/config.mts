import { defineConfig } from 'vitepress';

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "Fetch PHP",
    description: "The JavaScript fetch API for PHP.",
    head: [['link', { rel: 'icon', href: '/favicon.ico' }]],
    themeConfig: {
        logo: '/logo.png',
        nav: [
            { text: 'Home', link: '/' },
            { text: 'Guide', link: '/guide/' },
            { text: 'API Reference', link: '/api/' },
            { text: 'Examples', link: '/examples/' }
        ],
        sidebar: {
            '/guide/': [
                {
                    text: 'Introduction',
                    items: [
                        { text: 'Overview', link: '/guide/' },
                        { text: 'Installation', link: '/guide/installation' },
                        { text: 'Quickstart', link: '/guide/quickstart' }
                    ]
                },
                {
                    text: 'Core Concepts',
                    items: [
                        { text: 'Making Requests', link: '/guide/making-requests' },
                        { text: 'Helper Functions', link: '/guide/helper-functions' },
                        { text: 'Working with Responses', link: '/guide/working-with-responses' },
                        { text: 'Request Configuration', link: '/guide/request-configuration' },
                        { text: 'Authentication', link: '/guide/authentication' },
                        { text: 'Error Handling', link: '/guide/error-handling' },
                        { text: 'Logging', link: '/guide/logging' }
                    ]
                },
                {
                    text: 'Advanced Usage',
                    items: [
                        { text: 'Asynchronous Requests', link: '/guide/async-requests' },
                        { text: 'Promise Operations', link: '/guide/promise-operations' },
                        { text: 'Retry Handling', link: '/guide/retry-handling' },
                        { text: 'File Uploads', link: '/guide/file-uploads' },
                        { text: 'Custom Clients', link: '/guide/custom-clients' },
                        { text: 'Testing with Mocks', link: '/guide/testing' }
                    ]
                }
            ],
            '/api/': [
                {
                    text: 'API Reference',
                    items: [
                        { text: 'Overview', link: '/api/' }
                    ]
                },
                {
                    text: 'Helper Functions',
                    items: [
                        { text: 'fetch()', link: '/api/fetch' },
                        { text: 'fetch_client()', link: '/api/fetch-client' },
                        { text: 'HTTP Method Helpers', link: '/api/http-method-helpers' }
                    ]
                },
                {
                    text: 'Core Classes',
                    items: [
                        { text: 'Client', link: '/api/client' },
                        { text: 'ClientHandler', link: '/api/client-handler' },
                        { text: 'Request', link: '/api/request' },
                        { text: 'Response', link: '/api/response' }
                    ]
                },
                {
                    text: 'Enums',
                    items: [
                        { text: 'Method', link: '/api/method-enum' },
                        { text: 'ContentType', link: '/api/content-type-enum' },
                        { text: 'Status', link: '/api/status-enum' }
                    ]
                }
            ],
            '/examples/': [
                {
                    text: 'Examples',
                    items: [
                        { text: 'Basic Requests', link: '/examples/' },
                        { text: 'Working with APIs', link: '/examples/api-integration' },
                        { text: 'Async Request Patterns', link: '/examples/async-patterns' },
                        { text: 'Error Handling', link: '/examples/error-handling' },
                        { text: 'File Handling', link: '/examples/file-handling' },
                        { text: 'Authentication', link: '/examples/authentication' }
                    ]
                }
            ]
        },
        socialLinks: [
            { icon: 'github', link: 'https://github.com/Thavarshan/fetch-php' }
        ],
        footer: {
            message: 'Created by Jerome Thayananthajothy and released under the MIT License.',
            copyright: 'Copyright © ' + new Date().getFullYear() + ' Fetch PHP'
        },
        search: {
            provider: 'local'
        }
    }
});
