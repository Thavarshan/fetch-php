import { defineConfig } from "vitepress";
import fs from "fs";
import path from "path";

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "Fetch PHP",
    description:
        "A modern HTTP client library that brings JavaScript's fetch API experience to PHP with async/await patterns, promise-based API, and powerful retry mechanics.",

    // IMPORTANT: Set canonical URL base to avoid duplicate content issues
    base: "/",

    sitemap: {
        hostname: "https://fetch-php.thavarshan.com",
    },

    // Enable lastUpdated for <lastmod> tags in sitemap
    lastUpdated: true,

    head: [
        // Basic meta tags
        ["link", { rel: "icon", href: "/favicon.ico" }],
        ["meta", { name: "author", content: "Jerome Thayananthajothy" }],
        [
            "meta",
            {
                name: "keywords",
                content:
                    "php, http client, fetch api, javascript fetch, guzzle, async php, http requests, promise-based, psr-7, psr-18",
            },
        ],

        // Open Graph tags for social sharing
        ["meta", { property: "og:type", content: "website" }],
        [
            "meta",
            {
                property: "og:title",
                content: "Fetch PHP - The JavaScript fetch API for PHP",
            },
        ],
        [
            "meta",
            {
                property: "og:description",
                content:
                    "Modern HTTP client for PHP with JavaScript-like syntax, async/await patterns, and powerful retry mechanics.",
            },
        ],
        [
            "meta",
            {
                property: "og:image",
                content: "https://fetch-php.thavarshan.com/og-image.png",
            },
        ],
        [
            "meta",
            { property: "og:url", content: "https://fetch-php.thavarshan.com" },
        ],

        // Twitter Card tags
        ["meta", { name: "twitter:card", content: "summary_large_image" }],
        [
            "meta",
            {
                name: "twitter:title",
                content: "Fetch PHP - The JavaScript fetch API for PHP",
            },
        ],
        [
            "meta",
            {
                name: "twitter:description",
                content:
                    "Modern HTTP client for PHP with JavaScript-like syntax, async/await patterns, and powerful retry mechanics.",
            },
        ],
        [
            "meta",
            {
                name: "twitter:image",
                content: "https://fetch-php.thavarshan.com/og-image.png",
            },
        ],

        // Canonical URL to avoid duplicate content issues
        [
            "link",
            { rel: "canonical", href: "https://fetch-php.thavarshan.com" },
        ],

        // Robots tag to ensure indexing
        ["meta", { name: "robots", content: "index, follow" }],

        // Preload critical assets
        ["link", { rel: "preload", href: "/logo.png", as: "image" }],

        // Structured data for rich results (JSON-LD)
        [
            "script",
            { type: "application/ld+json" },
            `{
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "Fetch PHP",
            "applicationCategory": "DeveloperApplication",
            "operatingSystem": "PHP 8.3+",
            "offers": {
                "@type": "Offer",
                "price": "0.00",
                "priceCurrency": "USD"
            },
            "description": "A modern HTTP client library that brings JavaScript's fetch API experience to PHP with async/await patterns, promise-based API, and powerful retry mechanics.",
            "author": {
                "@type": "Person",
                "name": "Jerome Thayananthajothy"
            }
        }`,
        ],
    ],

    // Customize title format for SEO
    titleTemplate: ":title | Fetch PHP - Modern HTTP Client",

    // Use clean URLs (no .html extension)
    cleanUrls: true,

    themeConfig: {
        logo: "/logo.png",
        siteTitle: "Fetch PHP",

        // Improve navigation (keeping your existing structure)
        nav: [
            { text: "Home", link: "/" },
            { text: "Guide", link: "/guide/" },
            { text: "API Reference", link: "/api/" },
            { text: "Examples", link: "/examples/" },
            {
                text: "Changelog",
                link: "https://github.com/jerome/fetch-php/blob/main/CHANGELOG.md",
            },
        ],

        // Keep your existing sidebar structure
        sidebar: {
            "/guide/": [
                {
                    text: "Introduction",
                    items: [
                        { text: "Overview", link: "/guide/" },
                        { text: "Installation", link: "/guide/installation" },
                        { text: "Quickstart", link: "/guide/quickstart" },
                    ],
                },
                {
                    text: "Core Concepts",
                    items: [
                        {
                            text: "Making Requests",
                            link: "/guide/making-requests",
                        },
                        {
                            text: "Working with Enums",
                            link: "/guide/working-with-enums",
                        },
                        {
                            text: "Helper Functions",
                            link: "/guide/helper-functions",
                        },
                        {
                            text: "Working with Responses",
                            link: "/guide/working-with-responses",
                        },
                        {
                            text: "Authentication",
                            link: "/guide/authentication",
                        },
                        {
                            text: "Error Handling",
                            link: "/guide/error-handling",
                        },
                        { text: "Logging", link: "/guide/logging" },
                    ],
                },
                {
                    text: "Advanced Usage",
                    items: [
                        {
                            text: "Asynchronous Requests",
                            link: "/guide/async-requests",
                        },
                        {
                            text: "Promise Operations",
                            link: "/guide/promise-operations",
                        },
                        {
                            text: "Retry Handling",
                            link: "/guide/retry-handling",
                        },
                        { text: "File Uploads", link: "/guide/file-uploads" },
                        {
                            text: "Custom Clients",
                            link: "/guide/custom-clients",
                        },
                        { text: "Testing with Mocks", link: "/guide/testing" },
                    ],
                },
            ],
            "/api/": [
                {
                    text: "API Reference",
                    items: [{ text: "Overview", link: "/api/" }],
                },
                {
                    text: "Helper Functions",
                    items: [
                        { text: "fetch()", link: "/api/fetch" },
                        { text: "fetch_client()", link: "/api/fetch-client" },
                        {
                            text: "HTTP Method Helpers",
                            link: "/api/http-method-helpers",
                        },
                    ],
                },
                {
                    text: "Core Classes",
                    items: [
                        { text: "Client", link: "/api/client" },
                        { text: "ClientHandler", link: "/api/client-handler" },
                        { text: "Request", link: "/api/request" },
                        { text: "Response", link: "/api/response" },
                    ],
                },
                {
                    text: "Enums",
                    items: [
                        { text: "Method", link: "/api/method-enum" },
                        { text: "ContentType", link: "/api/content-type-enum" },
                        { text: "Status", link: "/api/status-enum" },
                    ],
                },
                {
                    text: "Testing",
                    items: [
                        { text: "Testing Utilities", link: "/api/testing" },
                    ],
                },
            ],
            "/examples/": [
                {
                    text: "Examples",
                    items: [
                        { text: "Basic Requests", link: "/examples/" },
                        {
                            text: "Working with APIs",
                            link: "/examples/api-integration",
                        },
                        {
                            text: "Async Request Patterns",
                            link: "/examples/async-patterns",
                        },
                        {
                            text: "Error Handling",
                            link: "/examples/error-handling",
                        },
                        {
                            text: "File Handling",
                            link: "/examples/file-handling",
                        },
                        {
                            text: "Authentication",
                            link: "/examples/authentication",
                        },
                    ],
                },
            ],
        },

        // Social links (keeping your GitHub link)
        socialLinks: [
            { icon: "github", link: "https://github.com/Thavarshan/fetch-php" },
        ],

        // Footer content with additional keywords naturally included
        footer: {
            message:
                "Released under the MIT License. A modern HTTP client for PHP developers.",
            copyright: "Copyright © 2024-present Jerome Thayananthajothy",
        },

        // Improved search configuration
        search: {
            provider: "local",
            options: {
                detailedView: true,
                // Add common misspellings and related terms here if supported in the future
            },
        },

        // Configuration for showing the last updated time (freshness signal)
        lastUpdated: {
            text: "Updated at",
            formatOptions: {
                dateStyle: "medium",
                timeStyle: "short",
            },
        },

        // Add edit link to encourage contributions (helps with engagement metrics)
        editLink: {
            pattern: "https://github.com/Thavarshan/fetch-php/edit/main/docs/:path",
            text: "Edit this page on GitHub",
        },

        // Add carbon ads if you want to monetize your documentation
        // carbonAds: {
        //   code: 'your-carbon-code',
        //   placement: 'your-carbon-placement'
        // }
    },
    buildEnd: async ({ outDir }) => {
        const robotsTxt = `User-agent: *
Allow: /
Sitemap: https://fetch-php.thavarshan.com/sitemap.xml`;

        fs.writeFileSync(path.join(outDir, "robots.txt"), robotsTxt);
        console.log("✓ robots.txt generated");
    },

    // Performance optimizations
    vite: {
        build: {
            minify: true, // Use the default minifier
            cssMinify: true,
        },
        server: {
            fs: {
                strict: true,
            },
        },
    },
});
