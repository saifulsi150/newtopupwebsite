import { defineEventHandler, getRouterParam, readBody } from 'h3';
import { readStore, writeStore } from '../../../utils/mock-db';

export default defineEventHandler(async (event) => {
  const id = Number(getRouterParam(event, 'id'));
  const body = await readBody<{ direction?: 'up' | 'down' }>(event);
  const direction = body?.direction === 'down' ? 'down' : 'up';

  const store = readStore();
  const ordered = [...store.packages].sort((a, b) => a.slot - b.slot || a.id - b.id);
  const idx = ordered.findIndex((p) => p.id === id);
  if (idx === -1) return { success: false };

  const swapIdx = direction === 'up' ? idx - 1 : idx + 1;
  if (swapIdx < 0 || swapIdx >= ordered.length) return { success: true };

  const a = ordered[idx];
  const b = ordered[swapIdx];
  const tmp = a.slot;
  a.slot = b.slot;
  b.slot = tmp;

  const map = new Map(ordered.map((p) => [p.id, p]));
  store.packages = store.packages.map((p) => map.get(p.id) || p);
  writeStore(store);

  return { success: true };
});
