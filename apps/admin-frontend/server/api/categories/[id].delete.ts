import { createError, defineEventHandler, getRouterParam } from 'h3';
import { readCategories, writeCategories } from '../../utils/categories-store';

export default defineEventHandler((event) => {
  const id = Number(getRouterParam(event, 'id'));
  const list = readCategories();
  const idx = list.findIndex((c) => c.id === id);
  if (idx === -1) throw createError({ statusCode: 404, statusMessage: 'Category not found.' });
  list.splice(idx, 1);
  writeCategories(list);
  return { success: true };
});
