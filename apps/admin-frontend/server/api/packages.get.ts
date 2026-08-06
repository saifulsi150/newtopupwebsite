import { defineEventHandler, getQuery } from 'h3';
import { readStore } from '../utils/mock-db';

export default defineEventHandler((event) => {
  const query = getQuery(event);
  const page = Math.max(1, Number(query.page || 1));
  const limit = Math.max(1, Number(query.limit || 20));
  const search = String(query.search || '').trim().toLowerCase();
  const productId = String(query.product_id || '').trim();
  const status = String(query.status || '').trim().toLowerCase();

  let list = [...readStore().packages].sort((a, b) => a.slot - b.slot || a.id - b.id);

  if (search) {
    list = list.filter((pkg) => [pkg.name, pkg.product_title, pkg.product_slug].join(' ').toLowerCase().includes(search));
  }
  if (productId) {
    list = list.filter((pkg) => String(pkg.product_id) === productId);
  }
  if (status === 'active') {
    list = list.filter((pkg) => Number(pkg.is_active) === 1);
  } else if (status === 'hidden') {
    list = list.filter((pkg) => Number(pkg.is_active) !== 1);
  }

  const total = list.length;
  const start = (page - 1) * limit;

  return {
    packages: list.slice(start, start + limit),
    total
  };
});
