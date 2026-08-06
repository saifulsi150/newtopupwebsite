import { createError, defineEventHandler, getRouterParam, readBody } from 'h3';
import { readStore, writeStore } from '../../utils/mock-db';
import { readCategories } from '../../utils/categories-store';

function slugify(input: string) {
  return String(input || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '') || `product-${Date.now()}`;
}

export default defineEventHandler(async (event) => {
  const id = Number(getRouterParam(event, 'id'));
  const body = await readBody<any>(event);

  const store = readStore();
  const idx = store.products.findIndex((p) => p.id === id);
  if (idx === -1) throw createError({ statusCode: 404, statusMessage: 'Product not found.' });

  const current = store.products[idx];
  if (body?.title !== undefined) current.title = String(body.title || '').trim() || current.title;
  if (body?.slug !== undefined) current.slug = slugify(body.slug || current.title);
  if (body?.slot !== undefined) current.slot = Number(body.slot || 0);
  if (body?.status !== undefined) current.status = Number(body.status || 0) === 1 ? 1 : 0;
  if (body?.image !== undefined) current.image = String(body.image || '');
  if (body?.category_id !== undefined) {
    const categoryId = Number(body.category_id || 0);
    current.category_id = categoryId;
    current.category_title = readCategories().find((c) => Number(c.id) === categoryId)?.title || current.category_title;
  }
  if (body?.uid_checker !== undefined) current.uid_checker = Number(body.uid_checker || 0) === 1 ? 1 : 0;
  if (body?.uid_checker_api !== undefined) current.uid_checker_api = String(body.uid_checker_api || '');
  if (body?.dynamic_fields !== undefined) {
    current.dynamic_fields = Array.isArray(body.dynamic_fields)
      ? body.dynamic_fields
          .map((f: any) => ({ label: String(f?.label || '').trim(), key: String(f?.key || '').trim() }))
          .filter((f: any) => f.label && f.key)
      : [];
  }

  store.packages = store.packages.map((pkg) =>
    Number(pkg.product_id) === id
      ? { ...pkg, product_title: current.title, product_slug: current.slug }
      : pkg
  );

  writeStore(store);
  return { success: true, product: current };
});
