import { defineEventHandler, getQuery } from 'h3';
import { readStore } from '../utils/mock-db';

export default defineEventHandler((event) => {
  const query = getQuery(event);
  const page = Math.max(1, Number(query.page || 1));
  const limit = Math.max(1, Number(query.limit || 20));
  const search = String(query.search || '').trim().toLowerCase();

  let users = [...readStore().users].sort((a, b) => b.id - a.id);
  if (search) {
    users = users.filter((u) => [u.name, u.email, u.phone].join(' ').toLowerCase().includes(search));
  }

  const total = users.length;
  const start = (page - 1) * limit;
  return {
    users: users.slice(start, start + limit),
    total
  };
});
