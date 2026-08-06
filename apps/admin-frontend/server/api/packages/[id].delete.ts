import { createError, defineEventHandler, getRouterParam } from 'h3';
import { readStore, writeStore } from '../../utils/mock-db';

export default defineEventHandler((event) => {
  const id = Number(getRouterParam(event, 'id'));
  const store = readStore();

  const before = store.packages.length;
  store.packages = store.packages.filter((pkg) => pkg.id !== id);
  if (store.packages.length === before) throw createError({ statusCode: 404, statusMessage: 'Package not found.' });

  writeStore(store);
  return { success: true };
});
