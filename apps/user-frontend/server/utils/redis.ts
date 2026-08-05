import Redis from "ioredis";

type RedisLike = {
  connect: () => Promise<void>;
  get: (key: string) => Promise<string | null>;
  set: (key: string, value: string, mode?: string, ttl?: number) => Promise<"OK" | null>;
};

let redis: Redis | RedisLike | null = null;

const createNoopRedis = (): RedisLike => ({
  connect: async () => {},
  get: async () => null,
  set: async () => null
});

export const useRedis = () => {
  if (!redis) {
    const config = useRuntimeConfig();
    const redisUrl = (config.redisUrl || "").trim();

    if (!redisUrl || redisUrl.includes("redis://redis")) {
      redis = createNoopRedis();
      return redis;
    }

    const client = new Redis(redisUrl, {
      maxRetriesPerRequest: 0,
      enableReadyCheck: false,
      lazyConnect: true,
      connectTimeout: 250,
      commandTimeout: 250,
      retryStrategy: () => null
    });

    client.on?.("error", () => {});

    let disabled = false;
    let connectTried = false;

    const safeConnect = async () => {
      if (disabled || client.status === "ready") {
        return;
      }

      if (connectTried && client.status !== "ready") {
        disabled = true;
        return;
      }

      connectTried = true;
      try {
        await Promise.race([
          client.connect(),
          new Promise((_, reject) => setTimeout(() => reject(new Error("redis-timeout")), 300))
        ]);
      } catch {
        disabled = true;
        try {
          client.disconnect(false);
        } catch {
          // ignore disconnect failures
        }
      }
    };

    redis = {
      connect: safeConnect,
      get: async (key: string) => {
        await safeConnect();
        if (disabled) return null;
        try {
          return await client.get(key);
        } catch {
          return null;
        }
      },
      set: async (key: string, value: string, mode?: string, ttl?: number) => {
        await safeConnect();
        if (disabled) return null;
        try {
          return await client.set(key, value, mode, ttl);
        } catch {
          return null;
        }
      }
    };
  }

  return redis;
};
