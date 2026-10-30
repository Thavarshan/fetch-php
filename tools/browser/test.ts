import { test, expect } from '@playwright/test';

export { test, expect };

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

