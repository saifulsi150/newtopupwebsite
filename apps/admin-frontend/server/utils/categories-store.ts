import { mkdirSync, existsSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

type Category = { id: number; title: string; slot: number; status: number };

const PATH = join(process.cwd(), '.data', 'categories.json');

function ensureDir() {
  mkdirSync(join(process.cwd(), '.data'), { recursive: true });
}

export function readCategories(): Category[] {
  ensureDir();
  if (!existsSync(PATH)) return [];
  try {
    const parsed = JSON.parse(readFileSync(PATH, 'utf8'));
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

export function writeCategories(list: Category[]) {
  ensureDir();
  writeFileSync(PATH, JSON.stringify(list, null, 2));
}

export function nextId(list: Category[]) {
  return list.length ? Math.max(...list.map((c) => c.id)) + 1 : 1;
}
