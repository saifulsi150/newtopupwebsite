import { createError, defineEventHandler, getRouterParam } from 'h3';
import { readStore, writeStore } from '../../utils/mock-db';

export default defineEventHandler((event) => {
  const id = Number(getRouterParam(event, 'id'));
  const store = readStore();

  const before = store.products.length;
  store.products = store.products.filter((p) => p.id !== id);
  if (store.products.length === before) throw createError({ statusCode: 404, statusMessage: 'Product not found.' });

  store.packages = store.packages.filter((pkg) => Number(pkg.product_id) !== id);
  writeStore(store);
  return { success: true };
});
