const jwt = require("jsonwebtoken");
const logger = require("../utils/logger");
const crypto = require("crypto");
const db = require("../config/database");

function wantsJson(req) {
    const accept = (req.headers.accept || "").toLowerCase();
    const contentType = (req.headers["content-type"] || "").toLowerCase();
    const requestedWith = (req.headers["x-requested-with"] || "").toLowerCase();

    return req.xhr || requestedWith === "xmlhttprequest" || accept.includes("application/json") || contentType.includes("application/json");
}

function rejectAuth(req, res, statusCode) {
    if (wantsJson(req)) {
        return res.status(statusCode).json({
            success: false,
            message: "Authentication required",
            redirect: "/",
        });
    }

    return res.status(statusCode).redirect("/");
}

function authenticateToken(req, res, next) {
    if (req.session && req.session.user) {
        req.user = req.session.user;
        return next();
    }

    const token = req.cookies.token;
    if (!token) {
        return rejectAuth(req, res, 401);
    }

    try {
        const user = jwt.verify(token, process.env.SESSION_SECRET);
        req.user = user;
        req.session.user = user;
        return next();
    } catch (error) {
        logger.error(`Authentication error: ${error.message}`);
        res.clearCookie("token");
        return rejectAuth(req, res, 403);
    }
}

function isAuthenticated(req, res, next) {
    if (req.session && req.session.user) {
        return res.redirect("/admin/dashboard");
    }

    const token = req.cookies.token;
    if (token) {
        try {
            jwt.verify(token, process.env.SESSION_SECRET);
            return res.redirect("/admin/dashboard");
        } catch (error) {
            req.clearCookie?.("token");
            res.clearCookie("token");
        }
    }

    return next();
}

function authenticate(req, res, next) {
    if (req.session && req.session.user) {
        return next();
    }

    return res.redirect("/");
}

async function authenticateApiRequest(req, res, next) {
    try {
        const apiKey = req.headers["x-api-key"];
        const signature = req.headers["x-api-signature"];
        const timestamp = req.headers["x-api-timestamp"];

        if (!apiKey) {
            logger.warn("API request rejected: Missing API key");
            return res.status(401).json({ success: false, message: "Authentication failed: Missing API key" });
        }

        const [rows] = await db.pool.execute("SELECT * FROM system_settings WHERE id = 1");
        if (!rows || rows.length === 0) {
            logger.error("API authentication failed: System settings not found");
            return res.status(500).json({ success: false, message: "Internal server error: Settings not configured" });
        }

        let settings;
        try {
            settings = JSON.parse(rows[0].settings);
        } catch (error) {
            logger.error(`Error parsing settings JSON: ${error.message}`);
            return res.status(500).json({ success: false, message: "Internal server error" });
        }

        if (apiKey !== settings.api_key) {
            logger.warn(`API request rejected: Invalid API key provided: ${apiKey}`);
            return res.status(401).json({ success: false, message: "Authentication failed: Invalid API key" });
        }

        if (signature && timestamp) {
            const now = Math.floor(Date.now() / 1000);
            if (Math.abs(now - parseInt(timestamp, 10)) > 300) {
                logger.warn("API request rejected: Request timestamp too old");
                return res.status(401).json({ success: false, message: "Authentication failed: Request expired" });
            }

            const payload = apiKey + timestamp + JSON.stringify(req.body);
            const expectedSignature = crypto.createHmac("sha256", settings.api_secret).update(payload).digest("hex");

            if (signature !== expectedSignature) {
                logger.warn("API request rejected: Invalid signature");
                return res.status(401).json({ success: false, message: "Authentication failed: Invalid signature" });
            }
        }

        logger.info(`API request authenticated successfully: ${req.originalUrl}`);
        return next();
    } catch (error) {
        logger.error(`API authentication error: ${error.message}`);
        return res.status(500).json({ success: false, message: "Authentication error" });
    }
}

module.exports = {
    authenticateToken,
    isAuthenticated,
    authenticate,
    authenticateApiRequest,
};
