import { defineEventHandler, getQuery } from 'h3';
import { readCategories } from '../utils/categories-store';

export default defineEventHandler((event) => {
  const categories = readCategories();
  const { all } = getQuery(event);
  const sorted = [...categories].sort((a, b) => a.slot - b.slot || a.id - b.id);
  if (all === '1') return { categories: sorted };
  return { categories: sorted.filter((c) => c.status === 1) };
});
