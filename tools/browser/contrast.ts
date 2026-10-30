import type { Locator } from '@playwright/test';

// Evaluate actual inherited/composited colors, including gradient endpoints.
// This is sampled text contrast, not a full accessibility audit.
export async function textContrasts(locator: Locator): Promise<{
    text: string; foreground: string; ratio: number;
}[]> {
    return locator.evaluateAll((elements) => {
        type Color = [number, number, number, number];
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = 1;
        const context = canvas.getContext('2d')!;
        const parse = (color: string): Color => {
            context.clearRect(0, 0, 1, 1);
            context.fillStyle = color;
            context.fillRect(0, 0, 1, 1);
            const data = context.getImageData(0, 0, 1, 1).data;
            return [data[0], data[1], data[2], data[3] / 255];
        };
        const over = (top: Color, bottom: Color): Color => [
            top[0] * top[3] + bottom[0] * (1 - top[3]),
            top[1] * top[3] + bottom[1] * (1 - top[3]),
            top[2] * top[3] + bottom[2] * (1 - top[3]), 1,
        ];
        const luminance = (color: Color): number => {
            const rgb = color.slice(0, 3).map((v) => v / 255)
                .map((v) => v <= 0.04045 ? v / 12.92
                    : ((v + 0.055) / 1.055) ** 2.4);
            return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
        };
        return elements.filter((el) => {
            const style = getComputedStyle(el);
            return el.getClientRects().length && style.visibility !== 'hidden'
                && [...el.childNodes].some((node) =>
                    node.nodeType === Node.TEXT_NODE
                        && node.textContent?.trim());
        }).map((el) => {
            const ancestors: Element[] = [];
            for (let node: Element | null = el; node;
                node = node.parentElement) {
                ancestors.unshift(node);
            }
            let backgrounds: Color[] = [[255, 255, 255, 1]];
            for (const node of ancestors) {
                const style = getComputedStyle(node);
                backgrounds = backgrounds.map((bg) =>
                    over(parse(style.backgroundColor), bg));
                const stops = style.backgroundImage.match(/rgba?\([^)]*\)/g);
                if (stops) backgrounds = backgrounds.flatMap((bg) =>
                    stops.map((color) => over(parse(color), bg)));
            }
            const color = getComputedStyle(el).color;
            const ratios = backgrounds.map((bg) => {
                const values = [luminance(over(parse(color), bg)),
                    luminance(bg)].sort((a, b) => b - a);
                return (values[0] + 0.05) / (values[1] + 0.05);
            });
            return { text: el.textContent!.trim().slice(0, 60),
                foreground: color, ratio: Math.min(...ratios) };
        });
    });
}
