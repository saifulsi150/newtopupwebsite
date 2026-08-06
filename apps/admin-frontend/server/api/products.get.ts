import { defineEventHandler, getQuery } from 'h3';
import { readStore } from '../utils/mock-db';
import { readCategories } from '../utils/categories-store';

export default defineEventHandler((event) => {
  const query = getQuery(event);
  const page = Math.max(1, Number(query.page || 1));
  const limit = Math.max(1, Number(query.limit || 20));

  const store = readStore();
  const categories = new Map(readCategories().map((c) => [Number(c.id), String(c.title || '-')]))

  const products = [...store.products]
    .sort((a, b) => a.slot - b.slot || a.id - b.id)
    .map((product) => ({
      ...product,
      category_title: categories.get(Number(product.category_id || 0)) || product.category_title || '-',
      package_count: store.packages.filter((pkg) => Number(pkg.product_id) === Number(product.id)).length
    }));

  const total = products.length;
  const start = (page - 1) * limit;

  return { products: products.slice(start, start + limit), total };
});
