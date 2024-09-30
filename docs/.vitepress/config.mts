import { defineConfig } from 'vitepress';

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "Fetch PHP",
    description: "The JavaScript fetch API for PHP.",
    head: [['link', { rel: 'icon', href: '/favicon.ico' }]],
    themeConfig: {
        nav: [
            { text: 'Guide', link: '/guide/getting-started' },
            { text: 'API Reference', link: '/api/' }
        ],
        sidebar: {
            '/guide/': [
                {
                    text: 'Guide',
                    items: [
                        { text: 'Getting Started', link: '/guide/getting-started' },
                        { text: 'Installation', link: '/guide/installation' },
                        { text: 'Synchronous Requests', link: '/guide/sync-requests' },
                        { text: 'Asynchronous Requests', link: '/guide/async-requests' },
                        { text: 'Error Handling', link: '/guide/error-handling' },
                    ]
                }
            ],
            '/api/': [
                {
                    text: 'API Reference',
                    items: [
                        { text: 'Overview', link: '/api/' },
                        { text: 'fetch()', link: '/api/fetch' },
                        { text: 'ClientHandler', link: '/api/client-handler' },
                        { text: 'Response', link: '/api/response' }
                    ]
                }
            ]
        },
        socialLinks: [
            { icon: 'github', link: 'https://github.com/Thavarshan/fetch-php' }
        ]
    }
});
