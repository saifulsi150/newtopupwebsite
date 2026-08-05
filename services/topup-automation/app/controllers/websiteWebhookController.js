const orderProcessingService = require("../services/orderProcessingService");
const logger = require("../utils/logger");
const db = require("../config/database");
const axios = require("axios");
const https = require("https");
const { sendDiscordNotification } = require("../utils/discordNotifier");

const webhookCache = new Map();
const stuckOrderAlertCache = new Map();
const WEBHOOK_CACHE_TTL = 30_000;
let lockProcessorStarted = false;
let lockProcessorBusy = false;
let stuckAlertProcessorStarted = false;
let stuckAlertProcessorBusy = false;

function normalizeUrl(url) {
    if (!url || typeof url !== "string") {
        return "";
    }

    return url.trim().replace(/\/+$/, "");
}

async function ensureRoutingTable() {
    await db.pool.execute(`
        CREATE TABLE IF NOT EXISTS website_order_routes (
            order_id VARCHAR(100) NOT NULL PRIMARY KEY,
            api_key VARCHAR(255) NULL,
            source_site_name VARCHAR(255) NULL,
            source_site_url VARCHAR(500) NULL,
            callback_url VARCHAR(500) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    `);
}

async function ensureOrderLockTable() {
    await db.pool.execute(`
        CREATE TABLE IF NOT EXISTS order_locks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_row_id INT NOT NULL,
            source_order_id VARCHAR(100) NOT NULL,
            lock_amount_threshold DECIMAL(16,2) NOT NULL DEFAULT 0,
            detected_balance_amount DECIMAL(16,2) NOT NULL DEFAULT 0,
            detected_product_amount DECIMAL(16,2) NOT NULL DEFAULT 0,
            delay_seconds INT NOT NULL DEFAULT 0,
            auto_action VARCHAR(30) NOT NULL DEFAULT 'manual',
            delivery_message TEXT NULL,
            execute_after DATETIME NULL,
            processed TINYINT(1) NOT NULL DEFAULT 0,
            processed_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_source_order_lock (source_order_id),
            INDEX idx_lock_due (processed, execute_after),
            INDEX idx_lock_order_row (order_row_id)
        )
    `);
}

async function ensureLockedStatusSupport() {
    const [rows] = await db.pool.execute("SHOW COLUMNS FROM orders LIKE 'status'");
    const typeValue = String(rows?.[0]?.Type || "").toLowerCase();

    if (typeValue.includes("'locked'")) {
        return;
    }

    await db.pool.execute("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','processing','locked','completed','failed','cancelled') NOT NULL DEFAULT 'pending'");
}

async function getSystemSettings() {
    const [rows] = await db.pool.execute("SELECT * FROM system_settings WHERE id = 1");

    if (!rows || rows.length === 0) {
        return {};
    }

    try {
        return JSON.parse(rows[0].settings || "{}");
    } catch (error) {
        logger.error(`Error parsing settings JSON: ${error.message}`);
        return {};
    }
}

async function sendTelegramProblemAlert(title, details = {}) {
    try {
        const settings = await getSystemSettings();
        await sendDiscordNotification(settings, title || "Problem Alert", {
            event: "problem",
            ...details,
        });
    } catch (error) {
        logger.error(`Discord alert send failed: ${error.message}`);
    }
}

async function sendTelegramInfoAlert(title, details = {}) {
    try {
        const settings = await getSystemSettings();
        const allEvents = settings.order_lock_discord_all_events === true || settings.order_lock_discord_all_events === "true";
        const statusText = String(details.status || "").trim().toLowerCase();

        if (!allEvents) {
            return;
        }

        if (["completed", "cancelled"].includes(statusText)) {
            return;
        }

        await sendDiscordNotification(settings, title || "Event", {
            event: "info",
            ...details,
        });
    } catch (error) {
        logger.error(`Discord info alert send failed: ${error.message}`);
    }
}

function getStuckAlertIntervalMs(settings) {
    const rawValue = Number(settings.stuck_order_alert_interval_value || 30);
    const unit = String(settings.stuck_order_alert_interval_unit || "seconds").trim().toLowerCase();
    const safeValue = Number.isFinite(rawValue) ? Math.max(1, Math.floor(rawValue)) : 30;
    const multiplier = unit === "minutes" ? 60_000 : 1_000;
    return safeValue * multiplier;
}

function toFiniteNumber(value) {
    const n = Number(value);
    return Number.isFinite(n) ? n : 0;
}

function normalizeLockAction(rawAction) {
    const action = String(rawAction || "manual").trim().toLowerCase();
    if (["manual", "completed", "failed", "cancelled"].includes(action)) {
        return action;
    }

    return "manual";
}

function getOrderLockSettings(settings) {
    const lockAmount = Math.max(0, toFiniteNumber(settings.order_lock_amount_threshold || 0));
    const delaySeconds = Math.max(0, Math.floor(toFiniteNumber(settings.order_lock_delay_seconds || 0)));
    const enabledByToggle = settings.order_lock_enabled === true || settings.order_lock_enabled === "true";
    const enabled = enabledByToggle || lockAmount > 0;

    return {
        enabled,
        lockAmount,
        delaySeconds,
        autoAction: normalizeLockAction(settings.order_lock_auto_action),
        deliveryMessage: String(settings.order_lock_delivery_message || ""),
    };
}

function evaluateOrderLock(payload, lockSettings) {
    const balances = [
        toFiniteNumber(payload.source_balance_before),
        toFiniteNumber(payload.source_balance_current),
        toFiniteNumber(payload.source_balance_after),
    ];

    const detectedBalanceAmount = Math.max(...balances);

    const amountCandidates = [
        payload.source_balance_deducted,
        payload.order_amount,
        payload.source_order_amount,
        payload.product_amount,
        payload.amount,
        payload.total,
        payload.grand_total,
        payload.subtotal,
        payload.price,
    ].map(toFiniteNumber);

    const textAmountMatch = String(payload.diamond_quantity || "").match(/(\d+(?:\.\d+)?)/);
    if (textAmountMatch && textAmountMatch[1]) {
        amountCandidates.push(toFiniteNumber(textAmountMatch[1]));
    }

    const detectedProductAmount = Math.max(...amountCandidates, 0);

    const shouldLock = lockSettings.enabled &&
        lockSettings.lockAmount > 0 &&
        (
            detectedBalanceAmount >= lockSettings.lockAmount ||
            detectedProductAmount >= lockSettings.lockAmount
        );

    return {
        shouldLock,
        detectedBalanceAmount,
        detectedProductAmount,
        reason: `Detected balance ${detectedBalanceAmount.toFixed(2)}, product amount ${detectedProductAmount.toFixed(2)}, lock amount ${lockSettings.lockAmount.toFixed(2)}`,
    };
}

async function createLockedOrder(payload, parsedResult, lockSettings, lockInfo) {
    await ensureOrderLockTable();
    await ensureLockedStatusSupport();

    const [existingOrders] = await db.pool.execute("SELECT id, status FROM orders WHERE order_id = ? LIMIT 1", [payload.order_id.toString()]);
    if (existingOrders && existingOrders.length > 0) {
        await db.pool.execute(
            "INSERT INTO logs (order_id, order_reference, message, type) VALUES (?, ?, ?, ?)",
            [existingOrders[0].id, payload.order_id.toString(), "Duplicate locked order webhook ignored", "info"]
        );
        return { created: false, orderRowId: existingOrders[0].id };
    }

    const [mx] = await db.pool.execute("SELECT MAX(id) AS maxId FROM orders");
    const newId = (mx[0]?.maxId || 0) + 1;

    await db.pool.execute(
        `INSERT INTO orders (
            id, order_id, uid, diamond_quantity, status, api_key, source_site_name, source_site_url, callback_url,
            source_balance_before, source_balance_after, source_balance_current, source_balance_deducted
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
            newId,
            payload.order_id.toString(),
            payload.uid.toString(),
            parsedResult,
            "locked",
            payload.stored_api_key || null,
            payload.source_site_name || null,
            payload.source_site_url || null,
            payload.callback_url || null,
            toFiniteNumber(payload.source_balance_before),
            toFiniteNumber(payload.source_balance_after),
            toFiniteNumber(payload.source_balance_current),
            toFiniteNumber(payload.source_balance_deducted),
        ]
    );

    const autoAction = lockSettings.autoAction;
    const executeAfterSql = autoAction === "manual" ? null : new Date(Date.now() + lockSettings.delaySeconds * 1000);

    await db.pool.execute(
        `INSERT INTO order_locks (
            order_row_id, source_order_id, lock_amount_threshold, detected_balance_amount, detected_product_amount,
            delay_seconds, auto_action, delivery_message, execute_after, processed
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ON DUPLICATE KEY UPDATE
            lock_amount_threshold = VALUES(lock_amount_threshold),
            detected_balance_amount = VALUES(detected_balance_amount),
            detected_product_amount = VALUES(detected_product_amount),
            delay_seconds = VALUES(delay_seconds),
            auto_action = VALUES(auto_action),
            delivery_message = VALUES(delivery_message),
            execute_after = VALUES(execute_after),
            processed = 0,
            processed_at = NULL`,
        [
            newId,
            payload.order_id.toString(),
            lockSettings.lockAmount,
            lockInfo.detectedBalanceAmount,
            lockInfo.detectedProductAmount,
            lockSettings.delaySeconds,
            autoAction,
            lockSettings.deliveryMessage,
            executeAfterSql,
        ]
    );

    const modeText = autoAction === "manual"
        ? "manual mode (admin must resume automation)"
        : `auto-${autoAction} after ${lockSettings.delaySeconds}s`;

    await db.pool.execute(
        "INSERT INTO logs (order_id, order_reference, message, type) VALUES (?, ?, ?, ?)",
        [newId, payload.order_id.toString(), `Order locked by rule. ${lockInfo.reason}. Mode: ${modeText}`, "warning"]
    );

    await sendTelegramInfoAlert("Order locked by threshold rule", {
        orderId: payload.order_id.toString(),
        uid: payload.uid,
        amount: lockInfo.detectedProductAmount,
        status: "locked",
        message: lockInfo.reason,
    });

    return { created: true, orderRowId: newId };
}

async function processDueLockedOrders() {
    if (lockProcessorBusy) {
        return;
    }

    lockProcessorBusy = true;

    try {
        await ensureOrderLockTable();

        const [dueLocks] = await db.pool.execute(
            `SELECT l.*, o.order_id, o.uid
             FROM order_locks l
             INNER JOIN orders o ON o.id = l.order_row_id
             WHERE l.processed = 0
               AND l.auto_action <> 'manual'
               AND l.execute_after IS NOT NULL
               AND l.execute_after <= NOW()
             ORDER BY l.id ASC
             LIMIT 25`
        );

        for (const lock of dueLocks) {
            const settings = await getSystemSettings();
            const dbStatus = lock.auto_action === "completed"
                ? "completed"
                : lock.auto_action === "failed"
                    ? "failed"
                    : "cancelled";

            const callbackStatus = lock.auto_action === "completed"
                ? "Completed"
                : lock.auto_action === "failed"
                    ? "Failed"
                    : "Cancelled";

            const deliveryMessage = (lock.delivery_message || "").toString();
            const playerIdOverride = String(settings.order_lock_player_id_override || "").trim();
            const deliveredUid = playerIdOverride || String(lock.uid || "");
            const callbackMessage = deliveryMessage;

            await db.pool.execute("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?", [dbStatus, lock.order_row_id]);

            await sendResultToWebsite(lock.source_order_id, callbackStatus, 0, null, {
                uid: deliveredUid,
                player_id: deliveredUid,
                delivered_uid: deliveredUid,
                replacement_uid: deliveredUid,
                delivery_message: callbackMessage,
                message: callbackMessage,
                note: callbackMessage,
            });

            await db.pool.execute(
                "INSERT INTO logs (order_id, order_reference, message, type) VALUES (?, ?, ?, ?)",
                [lock.order_row_id, lock.source_order_id, `Locked order auto ${lock.auto_action} executed after ${lock.delay_seconds}s`, "success"]
            );

            await db.pool.execute("UPDATE order_locks SET processed = 1, processed_at = NOW() WHERE id = ?", [lock.id]);
        }
    } catch (error) {
        logger.error(`Order lock processor error: ${error.message}`);
        await sendTelegramProblemAlert("Order lock processor error", {
            message: error.message,
        });
    } finally {
        lockProcessorBusy = false;
    }
}

function startOrderLockProcessor() {
    if (lockProcessorStarted) {
        return;
    }

    lockProcessorStarted = true;
    setInterval(() => {
        processDueLockedOrders().catch((error) => {
            logger.error(`Order lock interval failure: ${error.message}`);
        });
    }, 2000);
}

async function processStuckOrderAlerts() {
    if (stuckAlertProcessorBusy) {
        return;
    }

    stuckAlertProcessorBusy = true;

    try {
        const settings = await getSystemSettings();
        const intervalMs = getStuckAlertIntervalMs(settings);
        const threshold = new Date(Date.now() - intervalMs);

        const [rows] = await db.pool.execute(
            `SELECT id, order_id, uid, status, updated_at
             FROM orders
             WHERE status IN ('processing', 'failed')
               AND updated_at <= ?
             ORDER BY updated_at ASC
             LIMIT 100`,
            [threshold]
        );

        const activeKeys = new Set();

        for (const row of rows) {
            const key = `${row.id}:${row.status}`;
            activeKeys.add(key);

            const lastSent = stuckOrderAlertCache.get(key) || 0;
            if (Date.now() - lastSent < intervalMs) {
                continue;
            }

            const ageSeconds = Math.max(0, Math.floor((Date.now() - new Date(row.updated_at).getTime()) / 1000));

            await sendTelegramProblemAlert("Order stuck alert", {
                orderId: row.order_id,
                uid: row.uid,
                status: row.status,
                message: `Order ${row.status} অবস্থায় ${ageSeconds} seconds ধরে আছে`,
            });

            stuckOrderAlertCache.set(key, Date.now());
        }

        for (const cacheKey of Array.from(stuckOrderAlertCache.keys())) {
            if (!activeKeys.has(cacheKey)) {
                stuckOrderAlertCache.delete(cacheKey);
            }
        }
    } catch (error) {
        logger.error(`Stuck order alert processor error: ${error.message}`);
        await sendTelegramProblemAlert("Stuck order alert processor error", {
            message: error.message,
            endpoint: "stuck-order-alert-processor",
        });
    } finally {
        stuckAlertProcessorBusy = false;
    }
}

function startStuckOrderAlertProcessor() {
    if (stuckAlertProcessorStarted) {
        return;
    }

    stuckAlertProcessorStarted = true;
    setInterval(() => {
        processStuckOrderAlerts().catch((error) => {
            logger.error(`Stuck order alert interval failure: ${error.message}`);
        });
    }, 5000);
}

function buildSourceRoutePayload(body) {
    const sourceSiteName = (body.source_site_name || body.website_name || body.site_name || body.source_name || "").toString().trim();
    const sourceSiteUrl = normalizeUrl(body.source_site_url || body.website_url || body.source_url || body.site_url || "");

    let callbackUrl = normalizeUrl(body.callback_url || body.callback || body.url || "");
    if (!callbackUrl && sourceSiteUrl) {
        callbackUrl = `${sourceSiteUrl}/api/tastnow/order-callback`;
    }

    return {
        sourceSiteName,
        sourceSiteUrl,
        callbackUrl,
    };
}

async function saveWebsiteOrderRoute(orderId, apiKey, routeData) {
    await ensureRoutingTable();

    await db.pool.execute(
        `
            INSERT INTO website_order_routes (order_id, api_key, source_site_name, source_site_url, callback_url)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                api_key = VALUES(api_key),
                source_site_name = VALUES(source_site_name),
                source_site_url = VALUES(source_site_url),
                callback_url = VALUES(callback_url)
        `,
        [
            orderId,
            apiKey || null,
            routeData.sourceSiteName || null,
            routeData.sourceSiteUrl || null,
            routeData.callbackUrl || null,
        ]
    );
}

function extractDiamondQuantityFromProductName(name) {
    const raw = (name || "").toString().trim();
    const lower = raw.toLowerCase();

    if (!raw) {
        return { type: "unsupported", reason: "Missing product name" };
    }

    if (lower.includes("lite")) {
        return { type: "unsupported", reason: "Weekly Lite not supported" };
    }

    if (lower.includes("monthly") && lower.includes("weekly")) {
        const combo = lower.match(/monthly\s*x?(\d+)?\s*\+?\s*weekly\s*x?(\d+)?/);
        if (combo) {
            return {
                type: "supported",
                result: `Monthly x${combo[1] || "1"} + Weekly x${combo[2] || "1"}`,
            };
        }
    }

    if (lower.includes("monthly")) {
        const monthly = lower.match(/monthly.*?x(\d+)|x(\d+).*?monthly/);
        if (monthly) {
            return { type: "supported", result: `Monthly x${monthly[1] || monthly[2]}` };
        }

        return { type: "supported", result: "Monthly Membership" };
    }

    if (lower.includes("weekly")) {
        const weekly = lower.match(/weekly.*?x(\d+)|x(\d+).*?weekly/);
        if (weekly) {
            return { type: "supported", result: `Weekly x${weekly[1] || weekly[2]}` };
        }

        return { type: "supported", result: "Weekly Membership" };
    }

    const diamond = lower.match(/(\d{2,4}).*?diamond|diamond.*?(\d{2,4})/) || lower.match(/^(\d{2,4})$/);
    if (diamond) {
        return { type: "supported", result: `${diamond[1] || diamond[2]} Diamond` };
    }

    return { type: "unsupported", reason: "Unknown product format" };
}

async function isAutomationEnabled() {
    try {
        const settings = await getSystemSettings();
        return settings.automation_enabled !== false;
    } catch (error) {
        logger.error(`Error checking automation setting: ${error.message}`);
        return true;
    }
}

function dedupeWebhook(orderId) {
    const cacheKey = `webhook_${orderId}`;
    const now = Date.now();

    if (webhookCache.has(cacheKey) && now - webhookCache.get(cacheKey) < WEBHOOK_CACHE_TTL) {
        return true;
    }

    webhookCache.set(cacheKey, now);

    if (webhookCache.size > 1000) {
        for (const [key, value] of webhookCache.entries()) {
            if (now - value > WEBHOOK_CACHE_TTL) {
                webhookCache.delete(key);
            }
        }
    }

    return false;
}

async function validateApiKey(requestApiKey) {
    const settings = await getSystemSettings();
    const configuredKey = (settings.website_api_key || process.env.WEBSITE_API_KEY || "").toString().trim();

    if (!configuredKey || configuredKey !== requestApiKey.trim()) {
        return { ok: false, settings };
    }

    return { ok: true, settings };
}

async function handleWebsiteOrderWebhook(req, res) {
    try {
        const payload = req.body || {};

        if (!payload || Object.keys(payload).length === 0) {
            await sendTelegramProblemAlert("Empty webhook data received", {
                endpoint: "handleWebsiteOrderWebhook",
                message: "Request body is empty",
            });
            return res.status(400).json({ success: false, message: "Empty webhook data" });
        }

        const incomingApiKey = (
            payload.api_key ||
            req.headers.authorization?.replace("Bearer ", "")?.trim() ||
            req.headers["x-api-key"]?.trim() ||
            ""
        ).toString();

        if (!incomingApiKey) {
            await sendTelegramProblemAlert("Webhook rejected: API key missing", {
                orderId: payload.order_id?.toString?.() || payload.order_id,
                endpoint: "handleWebsiteOrderWebhook",
            });
            return res.status(401).json({ success: false, message: "API key is required" });
        }

        const apiValidation = await validateApiKey(incomingApiKey);
        if (!apiValidation.ok) {
            await db.pool.execute(
                "INSERT INTO logs (order_reference, message, type) VALUES (?, ?, ?)",
                [payload.order_id?.toString() || "unknown", "API request rejected: Invalid API key", "warning"]
            );
            await sendTelegramProblemAlert("Webhook rejected: Invalid API key", {
                orderId: payload.order_id?.toString?.() || payload.order_id,
                endpoint: "handleWebsiteOrderWebhook",
            });
            return res.status(401).json({ success: false, message: "Invalid API key" });
        }

        payload.diamond_quantity = payload.diamond_quantity || payload.product_variation_name || payload.variation_name || null;
        payload.order_time = payload.order_time || new Date().toISOString();

        if (!(payload.order_id && payload.diamond_quantity && payload.uid && payload.status)) {
            await sendTelegramProblemAlert("Webhook rejected: required fields missing", {
                orderId: payload.order_id?.toString?.() || payload.order_id,
                endpoint: "handleWebsiteOrderWebhook",
                message: "order_id/diamond_quantity/uid/status missing",
            });
            return res.status(400).json({
                success: false,
                message: "Invalid webhook data: missing required fields (order_id, diamond_quantity/product_variation_name, uid, status)",
            });
        }

        if (String(payload.status || "").toLowerCase() !== "processing") {
            return res.status(200).json({ success: true, message: "Order status not relevant for processing" });
        }

        if (dedupeWebhook(payload.order_id)) {
            return res.status(200).json({ success: true, message: "Duplicate webhook ignored" });
        }

        const routeData = buildSourceRoutePayload(payload);
        await saveWebsiteOrderRoute(payload.order_id.toString(), incomingApiKey, routeData);

        await sendTelegramInfoAlert("New order captured", {
            orderId: payload.order_id.toString(),
            uid: payload.uid,
            status: payload.status,
            amount: payload.source_balance_deducted || payload.amount || payload.order_amount || payload.diamond_quantity,
        });

        if (!(await isAutomationEnabled())) {
            await db.pool.execute(
                "INSERT INTO logs (order_reference, message, type) VALUES (?, ?, ?)",
                [payload.order_id.toString(), "Order received while automation is disabled", "warning"]
            );
            return res.status(200).json({ success: true, message: "Webhook received, but automation is disabled" });
        }

        processWebsiteOrderAsync({
            ...payload,
            stored_api_key: incomingApiKey,
            source_site_name: routeData.sourceSiteName,
            source_site_url: routeData.sourceSiteUrl,
            callback_url: routeData.callbackUrl,
        }).catch((error) => {
            logger.error(`Async processing error: ${error.message}`);
        });

        return res.status(200).json({ success: true, message: "Webhook received, order processing initiated" });
    } catch (error) {
        logger.error(`Error handling website webhook: ${error.message}`);
        await sendTelegramProblemAlert("Website webhook handler error", {
            message: error.message,
            endpoint: "handleWebsiteOrderWebhook",
        });
        return res.status(500).json({ success: false, message: "Internal server error" });
    }
}

async function processWebsiteOrderAsync(payload) {
    try {
        const parsed = extractDiamondQuantityFromProductName(payload.diamond_quantity);

        if (parsed.type === "unsupported") {
            await db.pool.execute(
                "INSERT INTO logs (order_reference, message, type) VALUES (?, ?, ?)",
                [payload.order_id.toString(), `Unsupported product ignored: ${payload.diamond_quantity}`, "info"]
            );
            return;
        }

        const toNullableNumber = (value) => {
            if (value === undefined || value === null || value === "") {
                return null;
            }

            const parsedNumber = Number(value);
            return Number.isFinite(parsedNumber) ? parsedNumber : null;
        };

        const order = {
            id: payload.order_id,
            status: payload.status,
            api_key: payload.stored_api_key || null,
            source_site_name: payload.source_site_name || null,
            source_site_url: payload.source_site_url || null,
            callback_url: payload.callback_url || null,
            source_balance_before: toNullableNumber(payload.source_balance_before),
            source_balance_after: toNullableNumber(payload.source_balance_after),
            source_balance_current: toNullableNumber(payload.source_balance_current),
            source_balance_deducted: toNullableNumber(payload.source_balance_deducted),
            meta_data: [{ key: "Player ID Code", value: payload.uid }],
            line_items: [{ name: parsed.result, meta_data: [{ key: "Player ID Code", value: payload.uid }] }],
            created_at: payload.order_time || new Date().toISOString(),
        };

        const systemSettings = await getSystemSettings();
        const lockSettings = getOrderLockSettings(systemSettings);
        const lockInfo = evaluateOrderLock(payload, lockSettings);

        if (lockInfo.shouldLock) {
            await createLockedOrder(payload, parsed.result, lockSettings, lockInfo);
            return;
        }

        const result = await orderProcessingService.processOrder(order);

        if (result.success) {
            await sendResultToWebsite(payload.order_id, "Completed");
            await sendTelegramInfoAlert("Order processed successfully", {
                orderId: payload.order_id.toString(),
                uid: payload.uid,
                status: "Completed",
            });
            return;
        }

        if (result.status === "pending") {
            await sendTelegramInfoAlert("Order queued as pending", {
                orderId: payload.order_id.toString(),
                uid: payload.uid,
                status: "pending",
                message: result.message || "Added to queue",
            });
            return;
        }

        await sendTelegramProblemAlert("Order processing failed", {
            orderId: payload.order_id.toString(),
            uid: payload.uid,
            status: result.status || "failed",
            message: result.message || "Unknown processing error",
        });

        const lowerMessage = (result.message || "").toLowerCase();
        const invalidUid = result.isInvalidUidError ||
            lowerMessage.includes("invalid uid") ||
            lowerMessage.includes("not bd server") ||
            lowerMessage.includes("wrong uid");

        if (invalidUid) {
            await sendResultToWebsite(payload.order_id, "Cancelled", 0, "invalid uid or invalid region");
            return;
        }

        await sendResultToWebsite(
            payload.order_id,
            "Failed",
            0,
            result.message || "order processing failed"
        );
    } catch (error) {
        logger.error(`Error in website order processing: ${error.message}`);
        await sendTelegramProblemAlert("Website order processing exception", {
            orderId: payload?.order_id?.toString?.() || payload?.order_id,
            status: payload?.status,
            message: error.message,
        });
        const lowerMessage = (error.message || "").toLowerCase();
        const invalidUid =
            lowerMessage.includes("invalid uid") ||
            lowerMessage.includes("not bd server") ||
            lowerMessage.includes("wrong uid");

        if (payload?.order_id && invalidUid) {
            await sendResultToWebsite(payload.order_id, "Cancelled", 0, "invalid uid or invalid region");
            return;
        }
        if (payload?.order_id) {
            await sendResultToWebsite(payload.order_id, "Failed", 0, error.message || "order processing failed");
        }
    }
}

async function loadCallbackRoute(orderId) {
    await ensureRoutingTable();
    const [rows] = await db.pool.execute("SELECT * FROM website_order_routes WHERE order_id = ?", [orderId.toString()]);
    return rows?.[0] || null;
}

async function sendStatus(url, apiKey, data) {
    const requestConfig = {
        method: "post",
        url,
        data,
        headers: {
            "Content-Type": "application/json",
            "User-Agent": "VNBazer-AutoPanel/1.0",
            ...(apiKey ? { Authorization: `Bearer ${apiKey}`, "x-api-key": apiKey } : {}),
        },
        timeout: 15000,
    };

    try {
        return await axios(requestConfig);
    } catch (error) {
        const errorCode = String(error?.code || "").toUpperCase();
        const errorMessage = String(error?.message || "").toLowerCase();
        const tlsErrorCodes = new Set([
            "DEPTH_ZERO_SELF_SIGNED_CERT",
            "SELF_SIGNED_CERT_IN_CHAIN",
            "UNABLE_TO_VERIFY_LEAF_SIGNATURE",
            "CERT_HAS_EXPIRED",
        ]);
        const isTlsVerificationError =
            String(url || "").toLowerCase().startsWith("https://") &&
            (tlsErrorCodes.has(errorCode) ||
                errorMessage.includes("self-signed certificate") ||
                errorMessage.includes("unable to verify the first certificate") ||
                errorMessage.includes("certificate"));

        if (!isTlsVerificationError) {
            throw error;
        }

        logger.warn(`TLS verify failed for callback (${url}), retrying with relaxed TLS: ${error.message}`);
        return axios({
            ...requestConfig,
            httpsAgent: new https.Agent({ rejectUnauthorized: false }),
        });
    }
}

async function sendResultToWebsite(orderId, status, retryCount = 0, errorMessage = null, extraPayload = {}) {
    const retryDelay = 10_000;

    try {
        const route = await loadCallbackRoute(orderId);
        const settings = await getSystemSettings();

        const apiKey = (route?.api_key || settings.website_api_key || process.env.WEBSITE_API_KEY || "").toString().trim();
        const sourceSiteUrl = normalizeUrl(route?.source_site_url || "");

        const callbackUrl = normalizeUrl(
            route?.callback_url ||
            (sourceSiteUrl ? `${sourceSiteUrl}/api/tastnow/order-callback` : "") ||
            (settings.website_api_url ? `${normalizeUrl(settings.website_api_url)}/api/tastnow/order-callback` : "")
        );

        if (!callbackUrl) {
            logger.error(`No callback URL configured for order ${orderId}`);
            await sendTelegramProblemAlert("Callback URL missing", {
                orderId: orderId?.toString?.() || orderId,
                message: "No callback URL configured",
                endpoint: "website callback",
            });
            return;
        }

        const configuredUidOverride = String(settings.order_lock_player_id_override || "").trim();
        const payloadUidCandidate = String(
            extraPayload?.player_id ||
            extraPayload?.uid ||
            extraPayload?.delivered_uid ||
            extraPayload?.replacement_uid ||
            ""
        ).trim();
        const callbackUid = payloadUidCandidate || configuredUidOverride;

        const payload = {
            ...extraPayload,
            order_id: orderId,
            status,
        };
        if (callbackUid) {
            payload.uid = callbackUid;
            payload.player_id = callbackUid;
            payload.delivered_uid = callbackUid;
            payload.replacement_uid = callbackUid;
        }
        if (errorMessage) {
            payload.message = errorMessage;
            payload.error_message = errorMessage;
        }

        const response = await sendStatus(callbackUrl, apiKey, payload);

        if (response.status >= 200 && response.status < 300) {
            await db.pool.execute(
                "INSERT INTO logs (order_reference, message, type) VALUES (?, ?, ?)",
                [orderId.toString(), `Result sent to source website: ${status}`, "success"]
            );
            await sendTelegramInfoAlert("Callback sent to source website", {
                orderId: orderId?.toString?.() || orderId,
                status,
                endpoint: callbackUrl,
            });
            return;
        }

        throw new Error(`HTTP ${response.status}`);
    } catch (error) {
        logger.error(`Error sending result to website: ${error.message}`);

        if (retryCount < 3 && !["ENOTFOUND", "ECONNREFUSED"].includes(error.code)) {
            await new Promise((resolve) => setTimeout(resolve, retryDelay));
            return sendResultToWebsite(orderId, status, retryCount + 1, errorMessage, extraPayload);
        }

        await db.pool.execute(
            "INSERT INTO logs (order_reference, message, type) VALUES (?, ?, ?)",
            [orderId.toString(), `Failed to send result to website: ${error.message}`, "warning"]
        );

        await sendTelegramProblemAlert("Callback send failed", {
            orderId: orderId?.toString?.() || orderId,
            message: error.message,
            endpoint: "website callback",
        });
    }
}

async function handleHumayunWebhook(req, res) {
    try {
        const payload = req.body || {};

        const incomingApiKey = (
            payload.api_key ||
            req.headers.authorization?.replace("Bearer ", "")?.trim() ||
            req.headers["x-api-key"]?.trim() ||
            ""
        ).toString();

        if (!incomingApiKey) {
            return res.status(401).json({ success: false, message: "API key is required" });
        }

        const apiValidation = await validateApiKey(incomingApiKey);
        if (!apiValidation.ok) {
            return res.status(401).json({ success: false, message: "Invalid API key" });
        }

        if (!(payload.order_id && payload.uid && payload.variation_name && payload.status)) {
            return res.status(400).json({ success: false, message: "Invalid webhook data" });
        }

        if (payload.status !== "Processing" && payload.status !== "processing") {
            return res.status(200).json({ success: true, message: "Order status not relevant for processing" });
        }

        if (!(await isAutomationEnabled())) {
            return res.status(200).json({ success: true, message: "Webhook received, but automation is disabled" });
        }

        const routeData = buildSourceRoutePayload(payload);
        await saveWebsiteOrderRoute(payload.order_id.toString(), incomingApiKey, routeData);

        const order = {
            id: payload.order_id,
            status: payload.status,
            api_key: incomingApiKey,
            source_site_name: routeData.sourceSiteName,
            source_site_url: routeData.sourceSiteUrl,
            callback_url: routeData.callbackUrl,
            meta_data: [{ key: "Player ID Code", value: payload.uid }],
            line_items: [{ name: payload.variation_name || "Unknown Product", meta_data: [{ key: "Player ID Code", value: payload.uid }] }],
            created_at: new Date().toISOString(),
        };

        processHumayunOrderAsync(payload, order).catch((error) => {
            logger.error(`Humayun async process error: ${error.message}`);
        });

        return res.status(200).json({ success: true, message: "Webhook received, order processing initiated" });
    } catch (error) {
        logger.error(`Error handling Humayun webhook: ${error.message}`);
        await sendTelegramProblemAlert("Humayun webhook handler error", {
            message: error.message,
            endpoint: "handleHumayunWebhook",
        });
        return res.status(500).json({ success: false, message: "Internal server error" });
    }
}

async function processHumayunOrderAsync(payload, order) {
    try {
        const result = await orderProcessingService.processOrder(order);

        if (result.success) {
            await sendHumayunResultToWebsite(payload.order_id, "Completed");
        }
    } catch (error) {
        logger.error(`Error in Humayun order processing: ${error.message}`);
    }
}

async function sendHumayunResultToWebsite(orderId, status, retryCount = 0) {
    if (status !== "Completed" && status !== "completed") {
        return;
    }

    try {
        const route = await loadCallbackRoute(orderId);
        const settings = await getSystemSettings();
        const apiKey = (route?.api_key || settings.website_api_key || process.env.WEBSITE_API_KEY || "").toString().trim();
        const sourceSiteUrl = normalizeUrl(route?.source_site_url || "");

        const callbackUrl = normalizeUrl(
            route?.callback_url ||
            (sourceSiteUrl ? `${sourceSiteUrl}/api/humayun/webhook` : "") ||
            (settings.website_api_url ? `${normalizeUrl(settings.website_api_url)}/api/humayun/webhook` : "")
        );

        if (!callbackUrl) {
            logger.error(`No Humayun callback URL configured for order ${orderId}`);
            return;
        }

        await sendStatus(callbackUrl, apiKey, { order_id: orderId, status: "Completed" });

        await db.pool.execute(
            "INSERT INTO logs (order_reference, message, type) VALUES (?, ?, ?)",
            [orderId.toString(), "Humayun result sent to source website", "success"]
        );
    } catch (error) {
        logger.error(`Error sending Humayun result to website: ${error.message}`);

        if (retryCount < 3 && !["ENOTFOUND", "ECONNREFUSED"].includes(error.code)) {
            await new Promise((resolve) => setTimeout(resolve, 10_000));
            return sendHumayunResultToWebsite(orderId, status, retryCount + 1);
        }
    }
}

module.exports = {
    handleWebsiteOrderWebhook,
    handleHumayunWebhook,
    processWebsiteOrderAsync,
    processHumayunOrderAsync,
    sendResultToWebsite,
    sendHumayunResultToWebsite,
};

startOrderLockProcessor();
startStuckOrderAlertProcessor();
