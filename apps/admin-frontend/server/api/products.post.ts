import { createError, defineEventHandler, readBody } from 'h3';
import { nextId, readStore, timestamp, writeStore } from '../utils/mock-db';
import { readCategories } from '../utils/categories-store';

function slugify(input: string) {
  return String(input || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '') || `product-${Date.now()}`;
}

export default defineEventHandler(async (event) => {
  const body = await readBody<any>(event);
  const title = String(body?.title || '').trim();
  if (!title) throw createError({ statusCode: 422, statusMessage: 'Title is required.' });

  const store = readStore();
  const id = nextId(store.products);
  const categoryId = Number(body?.category_id || 0);
  const categoryTitle = readCategories().find((c) => Number(c.id) === categoryId)?.title || `Category #${categoryId || 0}`;

  const product = {
    id,
    title,
    slug: slugify(body?.slug || title),
    image: String(body?.image || ''),
    category_id: categoryId,
    category_title: categoryTitle,
    slot: Number(body?.slot || id),
    status: Number(body?.status || 0) === 1 ? 1 : 0,
    uid_checker: 0,
    uid_checker_api: '',
    dynamic_fields: [],
    package_count: 0
  };

  store.products.push(product);
  writeStore(store);

  return { success: true, product, saved_at: timestamp() };
});
