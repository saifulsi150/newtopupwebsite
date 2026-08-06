import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

type Product = {
  id: number;
  title: string;
  slug: string;
  image: string;
  category_id: number;
  category_title: string;
  slot: number;
  status: number;
  uid_checker: number;
  uid_checker_api: string;
  dynamic_fields: Array<{ label: string; key: string }>;
  package_count: number;
};

type Package = {
  id: number;
  product_id: number;
  product_title: string;
  product_slug: string;
  name: string;
  buy_price: number | null;
  sell_price: number;
  slot: number;
  is_active: number;
  auto_forward_enabled: number;
  auto_forward_api_name: string;
  updated_at: string;
};

type User = {
  id: number;
  name: string;
  email: string;
  phone: string;
  balance: number;
  total_order: number;
  user_type: 'admin' | 'user';
  status: number;
  created_at: string;
};

type Order = {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  account_type: string;
  package_title: string;
  product_title: string;
  player_id: string;
  code: string;
  amount: number;
  payment_method: string;
  status: string;
  delivery_message: string;
  created_at: string;
  updated_at: string;
};

type Transaction = {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  amount: number;
  method: string;
  type: string;
  status: string;
  invoice_id: string;
  created_at: string;
};

type Store = {
  products: Product[];
  packages: Package[];
  users: User[];
  orders: Order[];
  transactions: Transaction[];
};

const DATA_DIR = join(process.cwd(), '.data');
const STORE_PATH = join(DATA_DIR, 'mock-db.json');

function nowIso() {
  return new Date().toISOString();
}

function ensureDir() {
  mkdirSync(DATA_DIR, { recursive: true });
}

function baseStore(): Store {
  const ts = nowIso();
  return {
    products: [
      {
        id: 1,
        title: 'Free Fire Topup',
        slug: 'free-fire-topup',
        image: '/uploads/products/default-product.png',
        category_id: 1,
        category_title: 'TAST',
        slot: 1,
        status: 1,
        uid_checker: 0,
        uid_checker_api: '',
        dynamic_fields: [],
        package_count: 1
      }
    ],
    packages: [
      {
        id: 1,
        product_id: 1,
        product_title: 'Free Fire Topup',
        product_slug: 'free-fire-topup',
        name: '100 Diamonds',
        buy_price: 80,
        sell_price: 95,
        slot: 1,
        is_active: 1,
        auto_forward_enabled: 0,
        auto_forward_api_name: '',
        updated_at: ts
      }
    ],
    users: [
      {
        id: 1,
        name: 'Ghost Admin',
        email: 'admin@ghostbazar.online',
        phone: '01700000000',
        balance: 0,
        total_order: 0,
        user_type: 'admin',
        status: 1,
        created_at: ts
      },
      {
        id: 2,
        name: 'Demo User',
        email: 'user@example.com',
        phone: '01800000000',
        balance: 120,
        total_order: 1,
        user_type: 'user',
        status: 1,
        created_at: ts
      }
    ],
    orders: [
      {
        id: 1,
        user_id: 2,
        user_name: 'Demo User',
        user_email: 'user@example.com',
        account_type: 'user',
        package_title: '100 Diamonds',
        product_title: 'Free Fire Topup',
        player_id: '1234567890',
        code: 'ABCD12',
        amount: 95,
        payment_method: 'bkash',
        status: 'pending',
        delivery_message: '',
        created_at: ts,
        updated_at: ts
      }
    ],
    transactions: [
      {
        id: 1,
        user_id: 2,
        user_name: 'Demo User',
        user_email: 'user@example.com',
        amount: 95,
        method: 'bkash',
        type: 'topup',
        status: 'completed',
        invoice_id: 'INV-0001',
        created_at: ts
      }
    ]
  };
}

export function readStore(): Store {
  ensureDir();
  if (!existsSync(STORE_PATH)) {
    const seed = baseStore();
    writeFileSync(STORE_PATH, JSON.stringify(seed, null, 2));
    return seed;
  }

  try {
    const parsed = JSON.parse(readFileSync(STORE_PATH, 'utf8')) as Partial<Store>;
    const merged: Store = {
      ...baseStore(),
      ...parsed,
      products: Array.isArray(parsed.products) ? parsed.products : [],
      packages: Array.isArray(parsed.packages) ? parsed.packages : [],
      users: Array.isArray(parsed.users) ? parsed.users : [],
      orders: Array.isArray(parsed.orders) ? parsed.orders : [],
      transactions: Array.isArray(parsed.transactions) ? parsed.transactions : []
    };
    return merged;
  } catch {
    const seed = baseStore();
    writeFileSync(STORE_PATH, JSON.stringify(seed, null, 2));
    return seed;
  }
}

export function writeStore(store: Store) {
  ensureDir();
  writeFileSync(STORE_PATH, JSON.stringify(store, null, 2));
}

export function nextId(items: Array<{ id: number }>) {
  return items.length ? Math.max(...items.map((item) => Number(item.id || 0))) + 1 : 1;
}

export function timestamp() {
  return nowIso();
}
