// Copy lucide icons into resources/icons (minified) for <x-icon>. Usage: node scripts/icons.mjs bell x trash-2
import { readFileSync, writeFileSync, existsSync } from 'node:fs';

for (const name of process.argv.slice(2)) {
  const out = `resources/icons/${name}.svg`;
  if (existsSync(out)) continue;
  const src = readFileSync(`node_modules/lucide-static/icons/${name}.svg`, 'utf8');
  writeFileSync(out, src.replace(/<!--.*?-->/s, '').replace(/\s*\n\s*/g, ' ').trim());
  console.log('added', name);
}
