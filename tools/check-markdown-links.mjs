import { readdir, readFile, stat } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const roots = [path.join(root, 'README.md'), path.join(root, 'docs')];
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
    else if (child.isFile() && child.name.endsWith('.md')) markdownFiles.push(childPath);
  }
}

for (const entry of roots) await collect(entry);

const failures = [];
for (const file of markdownFiles) {
  const markdown = await readFile(file, 'utf8');
  const links = /!?\[[^\]]*\]\(([^)]+)\)/g;
  for (const match of markdown.matchAll(links)) {
    let destination = match[1].trim().split(/\s+/, 1)[0];
    if (destination.startsWith('<') && destination.endsWith('>')) destination = destination.slice(1, -1);
    if (!destination || /^(?:[a-z][a-z\d+.-]*:|\/\/|#)/i.test(destination)) continue;

    const pathname = decodeURIComponent(destination.split(/[?#]/, 1)[0]);
    if (!pathname) continue;
    const isDocsFile = file.startsWith(path.join(root, 'docs') + path.sep);
    let target = pathname.startsWith('/')
      ? path.join(root, isDocsFile ? 'docs' : '', pathname.replace(/^\//, ''))
      : path.resolve(path.dirname(file), pathname);

    let exists = false;
    for (const candidate of [target, `${target}.md`, path.join(target, 'index.md')]) {
      try {
        if ((await stat(candidate)).isFile()) {
          exists = true;
          break;
        }
      } catch {}
    }

    if (!exists) {
      const line = markdown.slice(0, match.index).split('\n').length;
      failures.push(`${path.relative(root, file)}:${line} ${destination}`);
    }
  }
}

if (failures.length) {
  console.error(`Found ${failures.length} broken local Markdown link(s):`);
  for (const failure of failures) console.error(`  ${failure}`);
  process.exitCode = 1;
} else {
  console.log(`Checked local Markdown links in ${markdownFiles.length} files.`);
}
