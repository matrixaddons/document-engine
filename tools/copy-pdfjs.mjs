// Copies the self-hosted PDF.js runtime (legacy build for older Safari/Android) into assets/vendor/pdfjs.
import { cpSync, mkdirSync, rmSync, readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const src = join(root, 'node_modules/pdfjs-dist');
const dest = join(root, 'assets/vendor/pdfjs');
const version = JSON.parse(readFileSync(join(src, 'package.json'), 'utf8')).version;

rmSync(dest, { recursive: true, force: true });
mkdirSync(dest, { recursive: true });
cpSync(join(src, 'legacy/build/pdf.min.mjs'), join(dest, 'pdf.min.js'));
cpSync(join(src, 'legacy/build/pdf.worker.min.mjs'), join(dest, 'pdf.worker.min.js'));
cpSync(join(src, 'cmaps'), join(dest, 'cmaps'), { recursive: true });
cpSync(join(src, 'standard_fonts'), join(dest, 'standard_fonts'), { recursive: true });
cpSync(join(src, 'wasm'), join(dest, 'wasm'), { recursive: true, filter: (file) => !/quickjs/.test(file) });
cpSync(join(src, 'LICENSE'), join(dest, 'LICENSE'));
console.log(`PDF.js ${version} copied to assets/vendor/pdfjs`);
