import { bundledThemes, type ThemeRegistration } from 'shiki';

export async function getSyntaxThemes(): Promise<{
    light: ThemeRegistration; dark: ThemeRegistration;
}> {
    const [light, dark] = await Promise.all([
        bundledThemes['github-light'](), bundledThemes['github-dark'](),
    ]);
    const comments = ['comment', 'punctuation.definition.comment',
        'string.comment'];
    const lightColors: Record<string, string> = {
        '#d73a49': '#b52d3b', '#e36209': '#994300',
    };
    return {
        light: {
            ...light.default,
            name: 'fetch-light',
            tokenColors: [...(light.default.tokenColors || []).map((token) => {
                const color = token.settings?.foreground;
                return { ...token, settings: { ...token.settings,
                    foreground: color
                        ? lightColors[color.toLowerCase()] || color : color,
                } };
            }), {
                scope: comments, settings: { foreground: '#536176' },
            }],
        },
        dark: {
            ...dark.default,
            name: 'fetch-dark',
            tokenColors: [...(dark.default.tokenColors || []), {
                scope: comments, settings: { foreground: '#a9b8ce' },
            }],
        },
    };
}
