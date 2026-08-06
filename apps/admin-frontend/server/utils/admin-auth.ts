import { mkdirSync, existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { randomBytes, scryptSync, timingSafeEqual } from 'node:crypto';

type StoredAdmin = {
  id: number;
  name: string;
  email: string;
  passwordHash: string;
  updatedAt: string;
};

const STORAGE_PATH = join(process.cwd(), '.data', 'admin-auth.json');

function hashPassword(password: string, salt?: string) {
  const resolvedSalt = salt || randomBytes(16).toString('hex');
  const hash = scryptSync(password, resolvedSalt, 64).toString('hex');
  return `${resolvedSalt}:${hash}`;
}

function verifyPassword(password: string, storedHash: string) {
  const [salt, hash] = storedHash.split(':');
  if (!salt || !hash) return false;
  const calculated = scryptSync(password, salt, 64);
  const original = Buffer.from(hash, 'hex');
  if (calculated.length !== original.length) return false;
  return timingSafeEqual(calculated, original);
}

function buildDefaultAdmin() {
  const config = useRuntimeConfig();
  return {
    id: 1,
    name: String(config.adminName || 'GhostBazar Admin'),
    email: String(config.adminEmail || 'admin@ghostbazar.online').trim().toLowerCase(),
    passwordHash: hashPassword(String(config.adminPassword || 'Admin@12345')),
    updatedAt: new Date().toISOString()
  } satisfies StoredAdmin;
}

function ensureStorageDir() {
  mkdirSync(dirname(STORAGE_PATH), { recursive: true });
}

export function loadAdminAuth() {
  ensureStorageDir();
  if (!existsSync(STORAGE_PATH)) {
    const fallback = buildDefaultAdmin();
    writeFileSync(STORAGE_PATH, JSON.stringify(fallback, null, 2));
    return fallback;
  }

  try {
    const parsed = JSON.parse(readFileSync(STORAGE_PATH, 'utf8')) as Partial<StoredAdmin>;
    if (!parsed.email || !parsed.passwordHash) {
      throw new Error('Invalid admin auth storage');
    }
    return {
      id: Number(parsed.id || 1),
      name: String(parsed.name || 'GhostBazar Admin'),
      email: String(parsed.email).trim().toLowerCase(),
      passwordHash: String(parsed.passwordHash),
      updatedAt: String(parsed.updatedAt || new Date().toISOString())
    } satisfies StoredAdmin;
  } catch {
    const fallback = buildDefaultAdmin();
    writeFileSync(STORAGE_PATH, JSON.stringify(fallback, null, 2));
    return fallback;
  }
}

export function saveAdminAuth(nextAdmin: Omit<StoredAdmin, 'updatedAt'>) {
  ensureStorageDir();
  const payload: StoredAdmin = {
    ...nextAdmin,
    email: nextAdmin.email.trim().toLowerCase(),
    updatedAt: new Date().toISOString()
  };
  writeFileSync(STORAGE_PATH, JSON.stringify(payload, null, 2));
  return payload;
}

export function checkAdminPassword(password: string, storedHash: string) {
  return verifyPassword(password, storedHash);
}

export function createAdminPasswordHash(password: string) {
  return hashPassword(password);
}