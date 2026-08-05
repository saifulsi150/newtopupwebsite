const db = require("../config/database");
const logger = require("../utils/logger");
const { sendDiscordNotification } = require("../utils/discordNotifier");

async function getSettings() {
    const [rows] = await db.pool.execute("SELECT * FROM system_settings WHERE id = 1");
    if (!rows || rows.length === 0) {
        return {};
    }

    try {
        return JSON.parse(rows[0].settings || "{}");
    } catch (error) {
        logger.error(`Failed parsing system settings: ${error.message}`);
        return {};
    }
}

async function saveSettings(settings) {
    const [rows] = await db.pool.execute("SELECT * FROM system_settings WHERE id = 1");
    const text = JSON.stringify(settings);

    if (!rows || rows.length === 0) {
        await db.pool.execute("INSERT INTO system_settings (id, settings) VALUES (1, ?)", [text]);
        return;
    }

    await db.pool.execute("UPDATE system_settings SET settings = ? WHERE id = 1", [text]);
}

function normalizeAction(raw) {
    const value = String(raw || "manual").trim().toLowerCase();
    if (["completed", "failed", "cancelled", "manual"].includes(value)) {
        return value;
    }
    return "manual";
}

async function renderPage(req, res) {
    try {
        const settings = await getSettings();

        const viewModel = {
            order_lock_enabled: settings.order_lock_enabled === true,
            order_lock_amount_threshold: Number(settings.order_lock_amount_threshold || 500),
            order_lock_delay_seconds: Number(settings.order_lock_delay_seconds || 5),
            order_lock_auto_action: normalizeAction(settings.order_lock_auto_action || "manual"),
            order_lock_delivery_message: String(settings.order_lock_delivery_message || ""),
            order_lock_player_id_override: String(settings.order_lock_player_id_override || ""),
            order_lock_discord_enabled: settings.order_lock_discord_enabled === true || settings.order_lock_discord_enabled === "true",
            order_lock_discord_all_events: settings.order_lock_discord_all_events === true || settings.order_lock_discord_all_events === "true",
            order_lock_discord_webhook_url: String(settings.order_lock_discord_webhook_url || ""),
            stuck_order_alert_interval_value: Number(settings.stuck_order_alert_interval_value || 30),
            stuck_order_alert_interval_unit: String(settings.stuck_order_alert_interval_unit || "seconds"),
        };

        return res.render("admin/order-lock-settings", {
            title: "Order Lock Settings",
            user: req.user,
            settings: viewModel,
            success: req.query.success,
            error: req.query.error,
        });
    } catch (error) {
        logger.error(`Failed to render order lock settings: ${error.message}`);
        return res.status(500).render("error", {
            title: "Error",
            message: "Failed to load order lock settings",
            error,
        });
    }
}

async function updatePage(req, res) {
    try {
        const settings = await getSettings();

        const enabledValue = String(req.body.order_lock_enabled || "").trim().toLowerCase();
        const enabled = ["true", "on", "1", "yes"].includes(enabledValue);
        const amountThreshold = Number(req.body.order_lock_amount_threshold || 0);
        const delaySeconds = Number(req.body.order_lock_delay_seconds || 0);
        const autoAction = normalizeAction(req.body.order_lock_auto_action);
        const deliveryMessage = String(req.body.order_lock_delivery_message || "").trim();
        const playerIdOverride = String(req.body.order_lock_player_id_override || "").trim();
        const discordEnabledValue = String(req.body.order_lock_discord_enabled || "").trim().toLowerCase();
        const discordEnabled = ["true", "on", "1", "yes"].includes(discordEnabledValue);
        const discordAllEventsValue = String(req.body.order_lock_discord_all_events || "").trim().toLowerCase();
        const discordAllEvents = ["true", "on", "1", "yes"].includes(discordAllEventsValue);
        const discordWebhookUrl = String(req.body.order_lock_discord_webhook_url || "").trim();
        const intervalValueRaw = Number(req.body.stuck_order_alert_interval_value || 30);
        const intervalUnitRaw = String(req.body.stuck_order_alert_interval_unit || "seconds").trim().toLowerCase();
        const intervalValue = Number.isFinite(intervalValueRaw) ? Math.max(1, Math.floor(intervalValueRaw)) : 30;
        const intervalUnit = ["minutes", "seconds"].includes(intervalUnitRaw) ? intervalUnitRaw : "seconds";

        settings.order_lock_enabled = enabled;
        settings.order_lock_amount_threshold = Number.isFinite(amountThreshold) ? Math.max(0, amountThreshold) : 0;
        settings.order_lock_delay_seconds = Number.isFinite(delaySeconds) ? Math.max(0, Math.floor(delaySeconds)) : 0;
        settings.order_lock_auto_action = autoAction;
        settings.order_lock_delivery_message = deliveryMessage;
        settings.order_lock_player_id_override = playerIdOverride;
        settings.order_lock_discord_enabled = discordEnabled;
        settings.order_lock_discord_all_events = discordAllEvents;
        settings.order_lock_discord_webhook_url = discordWebhookUrl;
        settings.stuck_order_alert_interval_value = intervalValue;
        settings.stuck_order_alert_interval_unit = intervalUnit;

        await saveSettings(settings);

        return res.redirect("/admin/order-lock-settings?success=Order lock settings saved successfully");
    } catch (error) {
        logger.error(`Failed to update order lock settings: ${error.message}`);
        return res.redirect(`/admin/order-lock-settings?error=${encodeURIComponent("Failed to save order lock settings")}`);
    }
}

async function sendTelegramTest(req, res) {
    try {
        const settings = await getSettings();

        const result = await sendDiscordNotification(settings, "Test notification", {
            event: "manual-test",
            message: "Discord notification test from Order Lock Settings",
        }, { force: true });

        if (!result || result.sent !== true) {
            return res.status(500).json({ success: false, message: "Discord configuration is missing or disabled" });
        }

        return res.json({ success: true, message: "Test notification sent" });
    } catch (error) {
        const discordReason = String(error?.response?.data?.message || error.message || "Failed to send test notification").trim();
        logger.error(`Failed to send Discord test notification: ${error.message}`);
        return res.status(500).json({ success: false, message: discordReason });
    }
}

module.exports = {
    renderPage,
    updatePage,
    sendTelegramTest,
};
