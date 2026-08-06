import { defineEventHandler } from 'h3';
import { readStore } from '../utils/mock-db';

export default defineEventHandler(() => {
  const store = readStore();
  const revenue = store.transactions
    .filter((tx) => tx.status === 'completed' || tx.status === 'paid')
    .reduce((sum, tx) => sum + Number(tx.amount || 0), 0);

  return {
    stats: {
      users: store.users.length,
      orders: store.orders.length,
      transactions: store.transactions.length,
      revenue,
      pendingOrders: store.orders.filter((o) => o.status === 'pending').length
    },
    recentUsers: [...store.users]
      .sort((a, b) => Number(new Date(b.created_at)) - Number(new Date(a.created_at)))
      .slice(0, 8)
      .map((u) => ({
        id: u.id,
        name: u.name,
        email: u.email,
        balance: u.balance,
        joined: u.created_at
      }))
  };
});
