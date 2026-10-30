import { test, expect } from './test';

test.use({ reducedMotion: 'reduce' });

for (const width of [375, 1440]) {
    for (const theme of ['light', 'dark'] as const) {
        test(`homepage tab states: ${width}px ${theme}`,
            async ({ page }, testInfo) => {
                await page.setViewportSize({ width, height: 1000 });
                await page.emulateMedia({ colorScheme: theme });
                await page.goto('/');
                await page.evaluate(() => document.fonts.ready);
                const request = page.getByRole('tab', { name: 'PHP request' });
                const response = page.getByRole('tab', {
                    name: 'JSON response',
                });
                const list = page.getByRole('tablist');
                const panel = page.getByRole('tabpanel');
                const appearance = (tab: typeof request) =>
                    tab.evaluate((el) => {
                        const style = getComputedStyle(el);
                        return { background: style.backgroundColor,
                            color: style.color, shadow: style.boxShadow,
                            outline: style.outlineStyle,
                            offset: style.outlineOffset,
                            radius: parseFloat(style.borderRadius),
                            keyboard: el.matches(':focus-visible') };
                    });
                const capture = async (state: string) => {
                    const path = testInfo.outputPath(`${state}.png`);
                    await page.locator('.request-tabs').screenshot({
                        path, animations: 'disabled',
                    });
                    await testInfo.attach(state, {
                        path, contentType: 'image/png',
                    });
                };
                const verifySelection = async (
                    selected: typeof request, other: typeof request,
                ) => {
                    await expect(selected)
                        .toHaveAttribute('aria-selected', 'true');
                    await expect(other)
                        .toHaveAttribute('aria-selected', 'false');
                    const background = await page.locator('.workbench')
                        .evaluate((el) => getComputedStyle(el).backgroundColor);
                    await expect(selected).toHaveCSS(
                        'background-color', background,
                    );
                    await expect(other).toHaveCSS(
                        'background-color', 'rgba(0, 0, 0, 0)',
                    );
                    const active = await appearance(selected);
                    const inactive = await appearance(other);
                    expect(active.background).not.toBe(inactive.background);
                    expect(active.color).not.toBe(inactive.color);
                    expect(active.shadow).not.toBe('none');
                    expect(active.radius).toBeGreaterThan(0);
                    for (const tab of [selected, other]) {
                        const box = (await tab.boundingBox())!;
                        expect(box.width).toBeGreaterThanOrEqual(24);
                        expect(box.height).toBeGreaterThanOrEqual(24);
                    }
                    const box = (await list.boundingBox())!;
                    const content = (await panel.boundingBox())!;
                    const gap = content.y - box.y - box.height;
                    expect(gap).toBeGreaterThanOrEqual(8);
                    expect(gap).toBeLessThanOrEqual(16);
                };
                await verifySelection(request, response);
                await expect(request).not.toBeFocused();
                await capture('request-selected-unfocused');
                for (const [tab, other, name] of [
                    [response, request, 'response'],
                    [request, response, 'request'],
                ] as const) {
                    await tab.click();
                    await verifySelection(tab, other);
                    expect((await appearance(tab)).keyboard).toBe(false);
                    await capture(`${name}-mouse`);
                }
                for (const [key, tab, other, name] of [
                    ['ArrowRight', response, request, 'response'],
                    ['ArrowLeft', request, response, 'request'],
                ] as const) {
                    await page.keyboard.press(key);
                    await expect(tab).toBeFocused();
                    await verifySelection(tab, other);
                    const focused = await appearance(tab);
                    expect(focused.keyboard).toBe(true);
                    expect(focused.outline).toBe('none');
                    expect(focused.offset).toBe('0px');
                    expect(focused.shadow).not.toBe('none');
                    await capture(`${name}-keyboard`);
                }
                await response.press('Home');
                await expect(request).toHaveAttribute('aria-selected', 'true');
                await request.press('End');
                await expect(response).toHaveAttribute('aria-selected', 'true');
            });
    }
}
