import { test, expect } from './test';
import { textContrasts } from './contrast';

test.use({ reducedMotion: 'reduce' });

test('copy feedback resets and announces repeated successes',
    async ({ page, context }) => {
        await context.grantPermissions(['clipboard-read', 'clipboard-write']);
        await page.goto('/');
        await page.clock.install();
        const region = page.locator('.installation [role="status"]');
        await region.evaluate((el) => {
            const state = window as unknown as { copyMessages: string[] };
            state.copyMessages = [];
            new MutationObserver(() => {
                if (el.textContent?.trim()) {
                    state.copyMessages.push(el.textContent.trim());
                }
            }).observe(el, {
                childList: true, subtree: true, characterData: true,
            });
        });
        const button = page.getByRole('button', {
            name: 'Copy Composer install command', exact: true,
        });
        await button.click();
        await expect(region).toHaveText('Copied');
        await button.click();
        await expect.poll(() => page.evaluate(() => {
            const state = window as unknown as { copyMessages: string[] };
            return state.copyMessages
                .filter((text) => text === 'Copied').length;
        })).toBe(2);
        await page.clock.fastForward(2801);
        await expect(button).toHaveText('Copy');
        await expect(region).toBeEmpty();
    });

test('switching code examples clears pending copy state',
    async ({ page, context }) => {
        await context.grantPermissions(['clipboard-read', 'clipboard-write']);
        await page.goto('/');
        await page.clock.install();
        await page.getByRole('button', {
            name: 'Copy request.php example', exact: true,
        }).click();
        await expect(page.getByRole('tabpanel').getByRole('status'))
            .toHaveText('Copied');
        await page.getByRole('tab', { name: 'JSON response' }).click();
        await expect(page.getByRole('tabpanel').getByRole('status'))
            .toBeEmpty();
        await page.getByRole('tab', { name: 'PHP request' }).click();
        await page.clock.fastForward(3000);
        await expect(page.getByRole('tabpanel').getByRole('status'))
            .toBeEmpty();
    });

for (const width of [375, 1440]) {
    for (const theme of ['light', 'dark'] as const) {
        test(`installation states keep their bounds: ${width}px ${theme}`,
            async ({ page, context }, testInfo) => {
                await context.grantPermissions([
                    'clipboard-read', 'clipboard-write',
                ]);
                await page.setViewportSize({ width, height: 1000 });
                await page.emulateMedia({ colorScheme: theme });
                await page.goto('/');
                await page.locator('.VPNavBar.top').waitFor();
                await page.evaluate(() => document.fonts.ready);
                const card = page.locator('.installation');
                const button = card.getByRole('button');
                const initial = (await card.boundingBox())!;
                await expect(card).toContainText('Install with Composer');
                const textBounds = await card.locator('code').evaluate((el) => {
                    const range = document.createRange();
                    range.selectNodeContents(el);
                    const box = el.getBoundingClientRect();
                    return [...range.getClientRects()].every((rect) =>
                        rect.left >= box.left - 1
                        && rect.right <= box.right + 1);
                });
                expect(textBounds, 'complete command fits its text column')
                    .toBe(true);
                const cta = (await page.locator('.hero-actions')
                    .boundingBox())!;
                expect(cta.y + cta.height).toBeLessThan(initial.y);
                await button.hover();
                await expect(page.locator('.fetch-tooltip')).toHaveCount(0);
                await button.focus();
                expect(await button.evaluate((el) =>
                    getComputedStyle(el).boxShadow)).not.toBe('none');
                await button.click();
                await expect(button).toHaveText('Copied');
                const copied = (await card.boundingBox())!;
                expect(copied.width).toBeCloseTo(initial.width, 1);
                expect(copied.height).toBeCloseTo(initial.height, 1);
                await page.evaluate(() => {
                    Object.defineProperty(navigator.clipboard, 'writeText', {
                        configurable: true,
                        value: () => Promise.reject(
                            new Error('Clipboard denied'),
                        ),
                    });
                });
                await button.click();
                await expect(button).toHaveText('Copy');
                await expect(card.locator('.installation-error'))
                    .toHaveText('Copy failed');
                const errors = await textContrasts(
                    card.locator('.installation-error'),
                );
                expect(errors[0].ratio).toBeGreaterThanOrEqual(4.5);
                await expect(card.getByRole('status'))
                    .toContainText('manually');
                const failed = (await card.boundingBox())!;
                expect(failed.width).toBeCloseTo(initial.width, 1);
                expect(failed.height).toBeCloseTo(initial.height, 1);
                await testInfo.attach('clipboard-error', {
                    body: await card.screenshot(), contentType: 'image/png',
                });
            });
    }
}

test('search contains keyboard focus and restores its trigger on dismissal',
    async ({ page }) => {
        await page.goto('/');
        const trigger = page.getByRole('button', {
            name: 'Search', exact: true,
        });
        await trigger.focus();
        await trigger.press('Enter');
        await expect(page.locator('#localsearch-input')).toBeFocused();
        for (let i = 0; i < 8; i++) {
            await page.keyboard.press('Tab');
            expect(await page.evaluate(() => !!document.activeElement
                ?.closest('.VPLocalSearchBox'))).toBe(true);
        }
        await page.keyboard.press('Escape');
        await expect(page.locator('.VPLocalSearchBox')).toHaveCount(0);
        await expect(trigger).toBeFocused();
    });

test('mobile main menu contains focus and restores the menu button',
    async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 900 });
        await page.goto('/');
        const trigger = page.getByRole('button', { name: 'mobile navigation' });
        await trigger.click();
        await expect(page.locator('#VPNavScreen')).toBeVisible();
        for (let i = 0; i < 10; i++) {
            await page.keyboard.press('Tab');
            expect(await page.evaluate(() => !!document.activeElement
                ?.closest('#VPNavScreen, .VPNavBarHamburger'))).toBe(true);
        }
        await page.keyboard.press('Escape');
        await expect(page.locator('#VPNavScreen')).toHaveCount(0);
        await expect(trigger).toBeFocused();
    });

test('mobile sidebar contains focus and restores the documentation menu',
    async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 900 });
        await page.goto('/guide/async-requests');
        const trigger = page.locator('.VPLocalNav .menu');
        await trigger.click();
        await expect(page.locator('.VPSidebar')).toHaveClass(/open/);
        for (let i = 0; i < 12; i++) {
            await page.keyboard.press('Tab');
            expect(await page.evaluate(() => !!document.activeElement
                ?.closest('.VPSidebar'))).toBe(true);
        }
        await page.keyboard.press('Escape');
        await expect(page.locator('.VPSidebar')).not.toHaveClass(/open/);
        await expect(trigger).toBeFocused();
    });

for (const path of ['/', '/guide/quickstart']) {
    test(`skip link focuses the main content destination on ${path}`,
        async ({ page }) => {
            await page.goto(path);
            await page.keyboard.press('Tab');
            const skip = page.getByRole('link', { name: 'Skip to content' });
            await expect(skip).toBeFocused();
            await skip.press('Enter');
            await expect(page.locator('#VPContent')).toBeFocused();
            await expect(page.locator('#VPContent main')).toBeVisible();
        });
}

test('code scroll regions are reachable and scrollable with the keyboard',
    async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 900 });
        await page.goto('/');
        await page.getByRole('button', {
            name: 'Copy request.php example', exact: true,
        }).focus();
        await page.keyboard.press('Tab');
        const code = page.getByRole('region', { name: 'request.php code' });
        await expect(code).toBeFocused();
        await page.keyboard.press('ArrowRight');
        await expect.poll(() => code.evaluate((el) => el.scrollLeft))
            .toBeGreaterThan(0);
        await page.goto('/api/client-handler');
        const signature = page.locator('.vp-doc pre').first();
        await signature.focus();
        await page.keyboard.press('ArrowRight');
        await expect.poll(() => signature.evaluate((el) => el.scrollLeft))
            .toBeGreaterThan(0);
    });

test('API tables and long identifiers remain reachable on mobile',
    async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 900 });
        await page.goto('/api/client-handler');
        const heading = page.locator('h1 code');
        expect(await heading.evaluate((el) => {
            const range = document.createRange();
            range.selectNodeContents(el);
            return [...range.getClientRects()].every((rect) =>
                rect.left >= 0 && rect.right <= innerWidth);
        })).toBe(true);
        const table = page.locator('.vp-doc table');
        await table.focus();
        for (let i = 0; i < 15; i++) await page.keyboard.press('ArrowRight');
        await expect.poll(() => table.evaluate((el) => el.scrollLeft))
            .toBeGreaterThan(0);
        const bounds = await table.evaluate((el) => ({
            left: el.getBoundingClientRect().left,
            right: el.getBoundingClientRect().right,
            viewport: innerWidth,
        }));
        expect(bounds.left).toBeGreaterThanOrEqual(0);
        expect(bounds.right).toBeLessThanOrEqual(bounds.viewport);
    });

for (const theme of ['light', 'dark'] as const) {
    test(`representative text and syntax contrast in ${theme} mode`,
        async ({ page }) => {
            await page.emulateMedia({ colorScheme: theme });
            const cases = [
                ['/', '.installation-label span, .installation code, '
                    + '.copy-control, .response-note, .response-type, '
                    + '.response-inspector dt, .response-inspector dd, '
                    + '.example-note, .example-caption, .async-example > p, '
                    + '.highlighted-code span'],
                ['/guide/quickstart', '.vp-doc p, .vp-doc span[style], '
                    + '.VPSidebarItem.is-active .text, .outline-link'],
                ['/api/client-handler', '.vp-doc span[style], .vp-doc td, '
                    + '.vp-doc th, .vp-doc h1 code'],
                ['/examples/api-integration', '.vp-doc span[style]'],
            ];
            for (const [path, selector] of cases) {
                await page.goto(path);
                await page.locator('.VPNavBar.top').waitFor();
                const results = await textContrasts(page.locator(selector));
                expect(results.length).toBeGreaterThan(0);
                for (const result of results) {
                    expect(result.ratio, `${path}: ${JSON.stringify(result)}`)
                        .toBeGreaterThanOrEqual(4.5);
                }
            }
        });
}


test('hero columns align at the start and theme survives reload',
    async ({ page }) => {
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto('/');
        const left = (await page.locator('.hero-intro > div').first()
            .boundingBox())!;
        const right = (await page.locator('.hero-description')
            .boundingBox())!;
        expect(right.y).toBeCloseTo(left.y, 1);
        await page.getByRole('switch').click();
        const dark = await page.locator('html').evaluate((el) =>
            el.classList.contains('dark'));
        await page.reload();
        expect(await page.locator('html').evaluate((el) =>
            el.classList.contains('dark'))).toBe(dark);
    });


for (const theme of ['light', 'dark'] as const) {
    test(`outline borders and shared radius integrate in ${theme}`,
        async ({ page }) => {
            await page.emulateMedia({ colorScheme: theme });
            await page.goto('/');
            const button = page.locator('.installation .copy-control');
            const colors = await button.evaluate((el) => {
                const probe = document.createElement('span');
                probe.style.color = 'var(--border)';
                el.append(probe);
                const token = getComputedStyle(probe).color;
                probe.remove();
                const style = getComputedStyle(el);
                return { token, border: style.borderColor,
                    width: style.borderWidth, kind: style.borderStyle };
            });
            expect(colors.border).toBe(colors.token);
            expect(colors.width).toBe('1px');
            expect(colors.kind).toBe('solid');
            const selectors = ['.installation', '.copy-control',
                '.workbench', '[role=tablist]'];
            const radii = () => Promise.all(selectors.map((selector) =>
                page.locator(selector).first().evaluate((el) =>
                    parseFloat(getComputedStyle(el).borderRadius))));
            const initial = await radii();
            await page.locator('html').evaluate((el) => {
                el.style.setProperty('--radius', '18px');
            });
            // Buttons transition their radius; assert the settled result.
            await expect.poll(radii).toEqual(
                initial.map((radius) => radius + 8),
            );
        });
}
