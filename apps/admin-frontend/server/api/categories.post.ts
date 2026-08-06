import { createError, defineEventHandler, readBody } from 'h3';
import { nextId, readCategories, writeCategories } from '../utils/categories-store';

export default defineEventHandler(async (event) => {
  const body = await readBody<{ title?: string; slot?: number; status?: number }>(event);
  const title = String(body?.title || '').trim();
  if (!title) throw createError({ statusCode: 422, statusMessage: 'Category title is required.' });

  const list = readCategories();
  const item = { id: nextId(list), title, slot: Number(body?.slot ?? 0), status: Number(body?.status ?? 1) };
  list.push(item);
  writeCategories(list);
  return { success: true, category: item };
});
