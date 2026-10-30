import { test, expect } from '@playwright/test';

const problems = new WeakMap<object, string[]>();

test.beforeEach(async ({ page }) => {
    const errors: string[] = [];
    problems.set(page, errors);
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error'
            || /hydration|Failed to resolve component/i.test(message.text())) {
            errors.push(message.text());
        }
    });
    page.on('response', (response) => {
        if (response.status() >= 400) {
            errors.push(`${response.status()} ${response.url()}`);
        }
    });
    await page.route('**/*', (route) => {
        const url = new URL(route.request().url());
        if (url.protocol.startsWith('http')
            && url.origin !== 'http://127.0.0.1:4173') {
            errors.push(`Unexpected external request: ${url.origin}`);
            return route.abort();
        }
        return route.continue();
    });
});

test.afterEach(async ({ page }) => {
    expect(problems.get(page), 'browser errors and external requests')
        .toEqual([]);
});

test('homepage is rendered on the server with highlighted examples',
    async ({ page, request }) => {
        const response = await request.get('/');
        const html = await response.text();
        expect(html).toContain('Every good idea');
        expect(html).toContain('shiki');
        expect(html).not.toContain('landing-tab-request');
        await page.goto('/');
        await expect(page.locator('h1')).toContainText('a request.');
        await expect(page.locator('.workbench')).toBeVisible();
        await expect(page.getByRole('link', {
            name: 'Get started', exact: false,
        })).toHaveAttribute('href', '/guide/quickstart');
        await expect(page.getByRole('link', {
            name: 'GitHub', exact: false,
        }).first()).toHaveAttribute('href',
            'https://github.com/Thavarshan/fetch-php');
        const links = await page.locator('main a[href^="/"]')
            .evaluateAll((items) => [...new Set(items.map((item) =>
                item.getAttribute('href')!))]);
        for (const link of links) {
            expect((await request.get(link)).status(), link).toBe(200);
        }
    });

test('request tabs support mouse, keyboard, and visible focus',
    async ({ page }) => {
        await page.goto('/');
        const request = page.getByRole('tab', { name: 'PHP request' });
        const response = page.getByRole('tab', { name: 'JSON response' });
        await response.click();
        await expect(response).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tabpanel')).toContainText('Ada Lovelace');
        await response.press('ArrowLeft');
        await expect(request).toHaveAttribute('aria-selected', 'true');
        await expect(request).toBeFocused();
        const outline = await request.evaluate((el) =>
            getComputedStyle(el).outlineStyle);
        expect(outline).not.toBe('none');
        await request.press('End');
        await expect(response).toHaveAttribute('aria-selected', 'true');
    });

test('installation and code copy provide clipboard text and feedback',
    async ({ page, context }) => {
        await context.grantPermissions(['clipboard-read', 'clipboard-write']);
        await page.goto('/');
        await page.getByRole('button', {
            name: 'Copy Composer install command', exact: true,
        }).click();
        await expect(page.locator('.installation [role="status"]'))
            .toHaveText('Copied');
        expect(await page.evaluate(() => navigator.clipboard.readText()))
            .toBe('composer require jerome/fetch-php');
        await page.getByRole('button', {
            name: 'Copy request.php example', exact: true,
        }).click();
        expect(await page.evaluate(() => navigator.clipboard.readText()))
            .toContain('https://api.example.test/v1/users/42');
    });

test('local search finds and opens a documentation page', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    const input = page.locator('#localsearch-input');
    await expect(input).toBeFocused();
    await input.fill('quickstart');
    await page.locator('.VPLocalSearchBox .result')
        .filter({ hasText: 'Quickstart' }).first().click();
    await expect(page).toHaveURL(/\/guide\/quickstart(?:#quickstart)?$/);
    await expect(page.locator('h1')).toContainText('Quickstart');
    await page.keyboard.press('ControlOrMeta+k');
    await expect(page.locator('#localsearch-input')).toBeFocused();
    await page.keyboard.press('Escape');
    await page.getByRole('link', { name: 'FetchPHP', exact: true }).click();
    await expect(page.locator('h1')).toContainText('a request.');
});

test('theme switching updates VitePress and shared component tokens',
    async ({ page }) => {
        await page.goto('/');
        const contrast = () => page.locator(
            '.hero-actions [data-variant="default"]',
        ).evaluate((el) => {
            const style = getComputedStyle(el);
            const luminance = (color: string) => {
                const rgb = color.match(/[\d.]+/g)!.slice(0, 3)
                    .map((value) => Number(value) / 255)
                    .map((v) => v <= 0.04045 ? v / 12.92
                        : ((v + 0.055) / 1.055) ** 2.4);
                return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
            };
            const values = [luminance(style.color),
                luminance(style.backgroundColor)].sort((a, b) => b - a);
            return (values[0] + 0.05) / (values[1] + 0.05);
        });
        expect(await contrast(), 'light primary button contrast')
            .toBeGreaterThanOrEqual(4.5);
        await page.getByRole('switch').click();
        await expect(page.locator('html')).toHaveClass(/dark/);
        await expect(page.getByRole('switch'))
            .toHaveAttribute('aria-checked', 'true');
        expect(await contrast(), 'dark primary button contrast')
            .toBeGreaterThanOrEqual(4.5);
    });

test('default theme Badge initialization survives extension',
    async ({ page, request }) => {
        const response = await request.get('/guide/');
        expect(await response.text()).toContain('VPBadge');
        await page.goto('/guide/');
        await expect(page.locator('.VPBadge')).toHaveText('PHP 8.3+');
    });

test('mobile documentation menu navigates and closes', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/guide/async-requests');
    await page.locator('.VPLocalNav .menu').click();
    await expect(page.locator('.VPSidebar')).toHaveClass(/open/);
    await page.locator('.VPSidebar').getByRole('link', {
        name: 'Quickstart', exact: true,
    }).click();
    await expect(page).toHaveURL(/\/guide\/quickstart$/);
    await expect(page.locator('.VPSidebar')).not.toHaveClass(/open/);
});

for (const width of [375, 768, 1024, 1440, 1920]) {
    for (const scheme of ['light', 'dark'] as const) {
        test(`${width}px ${scheme}: homepage and article fit the viewport`,
            async ({ page }, testInfo) => {
                await page.setViewportSize({ width, height: 1000 });
                await page.emulateMedia({ colorScheme: scheme });
                for (const path of ['/', '/guide/async-requests']) {
                    await page.goto(path);
                    await expect(page.locator('h1')).toBeVisible();
                    await page.locator('.VPNavBar.top').waitFor();
                    await page.evaluate(() => document.fonts.ready);
                    const dimensions = await page.evaluate(() => ({
                        document: document.documentElement.scrollWidth,
                        viewport: innerWidth,
                    }));
                    expect(dimensions.document).toBeLessThanOrEqual(
                        dimensions.viewport,
                    );
                    if (path === '/' && width >= 768) {
                        await expect(page.locator('.workbench')).toBeVisible();
                    }
                    if (path !== '/' && width >= 1440) {
                        await expect(page.locator('.VPDocAsideOutline'))
                            .toBeVisible();
                    }
                    const name = path === '/' ? 'home' : 'article';
                    await testInfo.attach(`${name}-${width}-${scheme}`, {
                        body: await page.screenshot({ fullPage: true }),
                        contentType: 'image/png',
                    });
                }
            });
    }
}

for (const path of ['/api/client-handler', '/examples/api-integration',
    '/guide/architecture', '/guide/troubleshooting']) {
    test(`documentation route ${path} renders`, async ({ page }) => {
        await page.goto(path);
        await expect(page.locator('.vp-doc h1')).toBeVisible();
        await expect(page.locator('.VPSidebar')).toBeVisible();
        await expect(page.locator('.VPDocFooter')).toBeVisible();
    });
}
