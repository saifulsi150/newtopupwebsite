import { useDb } from "../utils/db";
import { useRedis } from "../utils/redis";

export default defineEventHandler(async () => {
  const db = useDb();
  const redis = useRedis();

  await db.query("SELECT 1");

  try {
    await redis.connect();
  } catch {
    // Already connected.
  }
  await redis.ping();

  return {
    status: "ok",
    time: new Date().toISOString()
  };
});
