import { createError, defineEventHandler, getRouterParam, readBody } from 'h3';
import { readCategories, writeCategories } from '../../../utils/categories-store';

export default defineEventHandler(async (event) => {
  const id = Number(getRouterParam(event, 'id'));
  const body = await readBody<{ direction?: 'up' | 'down' }>(event);
  const direction = body?.direction;

  const list = readCategories().sort((a, b) => a.slot - b.slot || a.id - b.id);
  const idx = list.findIndex((c) => c.id === id);
  if (idx === -1) throw createError({ statusCode: 404, statusMessage: 'Category not found.' });

  const swapIdx = direction === 'up' ? idx - 1 : idx + 1;
  if (swapIdx < 0 || swapIdx >= list.length) return { success: true };

  const tmp = list[idx].slot;
  list[idx].slot = list[swapIdx].slot;
  list[swapIdx].slot = tmp;

  // ensure unique slots if both were equal
  if (list[idx].slot === list[swapIdx].slot) {
    list.forEach((c, i) => { c.slot = i; });
  }

  writeCategories(list);
  return { success: true };
});
