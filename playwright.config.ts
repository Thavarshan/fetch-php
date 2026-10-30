import { defineConfig } from '@playwright/test';
import { existsSync } from 'node:fs';

const localChrome =
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const executablePath = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
    || (process.platform === 'darwin' && existsSync(localChrome)
        ? localChrome : undefined);

export default defineConfig({
    testDir: './tools/browser',
    outputDir: '.artifacts/browser-results',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', {
        outputFolder: '.artifacts/browser-report', open: 'never',
    }]],
    use: {
        baseURL: 'http://127.0.0.1:4173',
        viewport: { width: 1440, height: 1000 },
        colorScheme: 'light',
        launchOptions: { executablePath },
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'npm run preview -- --host 127.0.0.1 --port 4173',
        url: 'http://127.0.0.1:4173',
        reuseExistingServer: !process.env.CI,
    },
});
