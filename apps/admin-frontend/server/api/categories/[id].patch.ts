import { createError, defineEventHandler, getRouterParam, readBody } from 'h3';
import { readCategories, writeCategories } from '../../utils/categories-store';

export default defineEventHandler(async (event) => {
  const id = Number(getRouterParam(event, 'id'));
  const body = await readBody<{ title?: string; slot?: number; status?: number }>(event);

  const list = readCategories();
  const idx = list.findIndex((c) => c.id === id);
  if (idx === -1) throw createError({ statusCode: 404, statusMessage: 'Category not found.' });

  list[idx] = {
    ...list[idx],
    title: String(body?.title ?? list[idx].title).trim() || list[idx].title,
    slot: body?.slot !== undefined ? Number(body.slot) : list[idx].slot,
    status: body?.status !== undefined ? (Number(body.status) === 1 ? 1 : 0) : list[idx].status
  };
  writeCategories(list);
  return { success: true, category: list[idx] };
});
