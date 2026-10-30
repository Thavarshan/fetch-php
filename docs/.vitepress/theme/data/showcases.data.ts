import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { createHighlighter } from 'shiki';

type Example = { code: string; html: string };
type Showcases = Record<
    'request' | 'client' | 'async' | 'response', Example
> & { responseBody: Record<string, string | number> };

declare const data: Showcases;
export { data };

const fixture = (name: string, extension = 'php'): string => fileURLToPath(
    new URL(`../../../examples/snippets/${name}.${extension}`, import.meta.url),
);

export default {
    watch: [fixture('homepage-request'), fixture('homepage-client'),
        fixture('homepage-async'), fixture('homepage-response', 'json')],
    async load(): Promise<Showcases> {
        const highlighter = await createHighlighter({
            themes: ['github-light', 'github-dark'],
            langs: ['php', 'json'],
        });
        const render = (code: string, lang = 'php'): Example => ({
            code,
            html: highlighter.codeToHtml(code, {
                lang,
                themes: { light: 'github-light', dark: 'github-dark' },
                defaultColor: false,
            }),
        });
        const example = (name: string): Example => {
            const source = readFileSync(fixture(name), 'utf8');
            const regions = [...source.matchAll(
                /\/\/ @showcase\n([\s\S]*?)\/\/ @endshowcase/g,
            )];
            if (!regions.length) {
                throw new Error(`No showcase region in ${name}.php`);
            }
            return render(regions.map((match) => match[1].trim())
                .join('\n\n'));
        };
        try {
            const responseBody = JSON.parse(readFileSync(
                fixture('homepage-response', 'json'), 'utf8',
            )) as Showcases['responseBody'];
            return {
                request: example('homepage-request'),
                client: example('homepage-client'),
                async: example('homepage-async'),
                response: render(JSON.stringify(responseBody, null, 2),
                    'json'),
                responseBody,
            };
        } finally {
            highlighter.dispose();
        }
    },
};
