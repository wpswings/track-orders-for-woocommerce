/**
 * Authentication helpers.
 */
import { expect, Page } from '@playwright/test';

export const ADMIN_USER = process.env.WP_USERNAME || 'admin';
export const ADMIN_PASSWORD = process.env.WP_PASSWORD || 'password';

/**
 * Log in through wp-login.php. Specs normally do not need this: the default
 * project already uses the admin storageState saved by global setup.
 */
export async function loginAsAdmin( page: Page, username = ADMIN_USER, password = ADMIN_PASSWORD ): Promise< void > {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( username );
	await page.locator( '#user_pass' ).fill( password );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( /\/wp-admin\/?/ );
	await expect( page.locator( '#wpadminbar' ) ).toBeVisible();
}

/** Storage state with no cookies: use in specs that must run as a logged-out guest. */
export const GUEST_STORAGE_STATE = { cookies: [], origins: [] };
