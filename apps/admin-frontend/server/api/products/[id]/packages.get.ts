import { createError, defineEventHandler, getRouterParam } from 'h3';
import { readStore } from '../../../utils/mock-db';

export default defineEventHandler((event) => {
  const id = Number(getRouterParam(event, 'id'));
  const store = readStore();
  const product = store.products.find((p) => p.id === id);

  if (!product) throw createError({ statusCode: 404, statusMessage: 'Product not found.' });

  const packages = store.packages
    .filter((pkg) => Number(pkg.product_id) === id)
    .sort((a, b) => a.slot - b.slot || a.id - b.id);

  return { product, packages };
});
