const fs = require("fs");
const path = require("path");
const puppeteer = require("puppeteer-extra");
const logger = require("./app/utils/logger");

const logsDir = path.join(process.cwd(), "logs");
if (!fs.existsSync(logsDir)) {
    fs.mkdirSync(logsDir, { recursive: true });
}

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const captchaLogger = {
    info(message) {
        console.log(message);
        logger.info(`[CAPTCHA-TEST] ${message}`);
    },
    warn(message) {
        console.warn(message);
        logger.warn(`[CAPTCHA-TEST] ${message}`);
    },
    error(message) {
        console.error(message);
        logger.error(`[CAPTCHA-TEST] ${message}`);
    },
    success(message) {
        console.log(message);
        logger.info(`[CAPTCHA-TEST] ${message}`);
    },
};

class CaptchaTestService {
    constructor() {
        this.browser = null;
        this.isRunning = false;
    }

    async detectChromeExecutable() {
        const paths = {
            win32: [
                "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe",
                "C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe",
                `${process.env.LOCALAPPDATA}\\Google\\Chrome\\Application\\chrome.exe`,
            ],
        }[process.platform] || [];

        for (const executablePath of paths) {
            try {
                await fs.promises.access(executablePath);
                return executablePath;
            } catch {
            }
        }

        return null;
    }

    async initializeBrowser() {
        const executablePath = await this.detectChromeExecutable();
        const launchOptions = {
            headless: false,
            executablePath: executablePath || undefined,
            userDataDir: path.join(process.cwd(), "Chrome"),
            ignoreHTTPSErrors: true,
            defaultViewport: null,
            timeout: 60000,
            args: [
                "--disable-infobars",
                "--no-sandbox",
                "--disable-setuid-sandbox",
                "--disable-dev-shm-usage",
                "--disable-gpu",
                "--window-size=1280,720",
            ],
        };

        captchaLogger.info(`Launching browser with ${JSON.stringify(launchOptions)}`);
        this.browser = await puppeteer.launch(launchOptions);
        const page = (await this.browser.pages())[0] || await this.browser.newPage();
        await page.setViewport({ width: 1280, height: 720 });
        return page;
    }

    async navigateToGarena(page) {
        await page.goto("https://shop.garena.my/?app=100067&channel=202953", {
            waitUntil: "domcontentloaded",
            timeout: 60000,
        });
    }

    async enterUID(page, uid) {
        const selector = 'input[placeholder="Please enter player ID here"]';
        await page.waitForSelector(selector, { visible: true, timeout: 15000 });
        const input = await page.$(selector);
        if (!input) {
            throw new Error("Player ID input not found");
        }

        await input.click({ clickCount: 3 });
        await page.keyboard.press("Backspace");
        await input.type(uid, { delay: 20 });
        await input.press("Enter");
    }

    async waitForLoginResult(page) {
        const deadline = Date.now() + 180000;

        while (Date.now() < deadline) {
            try {
                const bodyText = await page.evaluate(() => document.body.innerText.toLowerCase());

                if (bodyText.includes("captcha") || bodyText.includes("verify")) {
                    captchaLogger.warn("CAPTCHA detected. Solve it manually in the opened browser window.");
                    await wait(1000);
                    continue;
                }

                if (bodyText.includes("logout") || bodyText.includes("proceed to payment") || bodyText.includes("nickname")) {
                    return { success: true, message: "Login successful" };
                }

                if (bodyText.includes("wrong uid") || bodyText.includes("invalid") || bodyText.includes("not bd server")) {
                    return { success: false, message: "Invalid UID or invalid region" };
                }

                await wait(1000);
            } catch (error) {
                if (String(error.message).includes("Target closed") || String(error.message).includes("detached Frame")) {
                    return { success: false, message: "Browser window was closed before CAPTCHA could be solved" };
                }

                await wait(1000);
            }
        }

        return { success: false, message: "Timeout reached - CAPTCHA may need solving" };
    }

    async runCaptchaTest(uid = "2312730961") {
        if (this.isRunning) {
            return { success: false, message: "CAPTCHA test already running" };
        }

        this.isRunning = true;
        let page = null;
        let result = { success: false, message: "Unknown error" };

        try {
            captchaLogger.info(`Starting CAPTCHA test with UID: ${uid}`);
            page = await this.initializeBrowser();
            await this.navigateToGarena(page);
            await this.enterUID(page, uid);
            result = await this.waitForLoginResult(page);

            if (result.success) {
                captchaLogger.success(result.message);
            } else {
                captchaLogger.warn(result.message);
            }

            return result;
        } catch (error) {
            captchaLogger.error(error.message);
            return { success: false, message: error.message };
        } finally {
            try {
                if (result.success) {
                    if (page) {
                        await page.close().catch(() => {});
                    }
                    if (this.browser) {
                        await this.browser.close().catch(() => {});
                    }
                }
            } finally {
                this.browser = null;
                this.isRunning = false;
            }
        }
    }

    isTestRunning() {
        return this.isRunning;
    }
}

const captchaTestService = new CaptchaTestService();
module.exports = captchaTestService;

if (require.main === module) {
    captchaTestService.runCaptchaTest().then((result) => {
        console.log(result);
        process.exit(result.success ? 0 : 1);
    }).catch((error) => {
        console.error(error);
        process.exit(1);
    });
}
