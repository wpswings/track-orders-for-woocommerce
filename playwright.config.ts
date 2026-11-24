import { defineConfig, devices } from '@playwright/test';
import path from 'path';

/**
 * E2E tests run against the wp-env development site (http://localhost:8888).
 * Override with WP_BASE_URL to point at another site.
 */
export default defineConfig( {
	testDir: './tests/e2e/specs',
	globalSetup: require.resolve( './tests/e2e/global-setup' ),
	outputDir: './test-results',
	fullyParallel: false,
	// WordPress state is shared, so specs run one at a time.
	workers: 1,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 1 : 0,
	timeout: 60_000,
	expect: { timeout: 10_000 },
	reporter: process.env.CI
		? [ [ 'list' ], [ 'html', { open: 'never', outputFolder: 'playwright-report' } ], [ 'github' ] ]
		: [ [ 'list' ], [ 'html', { open: 'never', outputFolder: 'playwright-report' } ] ],
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		storageState: path.join( __dirname, 'tests/e2e/.auth/admin.json' ),
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
