import { readdir, readFile, stat } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const docsRoot = path.join(root, 'docs');
const roots = [path.join(root, 'README.md'), docsRoot];
const markdownFiles = [];

async function collect(entry) {
    const info = await stat(entry);
    if (info.isFile()) {
        if (entry.endsWith('.md')) markdownFiles.push(entry);
        return;
    }

    for (const child of await readdir(entry, { withFileTypes: true })) {
        const childPath = path.join(entry, child.name);
        if (child.isDirectory()) await collect(childPath);
        else if (child.isFile() && child.name.endsWith('.md'))
            markdownFiles.push(childPath);
    }
}

for (const entry of roots) await collect(entry);

function slugify(heading) {
    return heading
        .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
        .replace(/`([^`]+)`/g, '$1')
        .replace(/\s+\{#[^}]+\}\s*$/, '')
        .toLocaleLowerCase()
        .replace(/[^\p{L}\p{N}\s-]/gu, '')
        .trim()
        .replace(/\s+/g, '-');
}

function anchorsIn(markdown) {
    const content = markdown.replace(/^```[\s\S]*?^```\s*$/gm, '');
    const anchors = new Set();
    const counts = new Map();

    for (const match of content.matchAll(/^#{1,6}\s+(.+?)\s*#*\s*$/gm)) {
        const heading = match[1];
        const explicit = heading.match(/\{#([^}]+)\}\s*$/)?.[1];
        const base = slugify(heading);
        const count = counts.get(base) ?? 0;
        counts.set(base, count + 1);
        anchors.add(explicit ?? (count === 0 ? base : `${base}-${count}`));
    }

    for (const match of content.matchAll(
        /<a\s+[^>]*id=["']([^"']+)["'][^>]*>/gi,
    )) {
        anchors.add(match[1]);
    }

    return anchors;
}

async function resolveTarget(file, pathname) {
    const isDocsFile = file.startsWith(`${docsRoot}${path.sep}`);
    const target = pathname.startsWith('/')
        ? path.join(isDocsFile ? docsRoot : root, pathname.replace(/^\//, ''))
        : path.resolve(path.dirname(file), pathname);

    for (const candidate of [
        target,
        `${target}.md`,
        path.join(target, 'index.md'),
    ]) {
        try {
            if ((await stat(candidate)).isFile()) return candidate;
        } catch {}
    }

    return null;
}

const failures = [];
for (const file of markdownFiles) {
    const markdown = await readFile(file, 'utf8');
    const content = markdown.replace(/^```[\s\S]*?^```\s*$/gm, '');
    const links = /!?\[[^\]]*\]\(([^)]+)\)/g;
    for (const match of content.matchAll(links)) {
        let destination = match[1].trim().split(/\s+/, 1)[0];
        if (destination.startsWith('<') && destination.endsWith('>'))
            destination = destination.slice(1, -1);
        if (!destination || /^(?:[a-z][a-z\d+.-]*:|\/\/)/i.test(destination))
            continue;

        const [rawPath, rawFragment] = destination.split('#', 2);
        const pathname = decodeURIComponent(rawPath.split('?', 1)[0]);
        const fragment = rawFragment ? decodeURIComponent(rawFragment) : null;
        const target = pathname ? await resolveTarget(file, pathname) : file;
        const line = content
            .slice(0, match.index)
            .split('\n').length;

        if (!target) {
            failures.push(
                `${path.relative(root, file)}:${line} missing target ` +
                    destination,
            );
            continue;
        }

        if (fragment) {
            const targetMarkdown = await readFile(target, 'utf8');
            if (!anchorsIn(targetMarkdown).has(fragment)) {
                failures.push(
                    `${path.relative(root, file)}:${line} missing anchor ` +
                        destination,
                );
            }
        }
    }
}

if (failures.length) {
    console.error(`Found ${failures.length} broken local Markdown link(s):`);
    for (const failure of failures) console.error(`  ${failure}`);
    process.exitCode = 1;
} else {
    console.log(
        `Checked local Markdown paths and fragments in ${markdownFiles.length}`,
        'files.',
    );
}
