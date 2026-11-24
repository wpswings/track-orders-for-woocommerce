/**
 * Playwright global setup: prepares the wp-env site once per run and saves the
 * admin login (storageState) used by every spec.
 */
import { chromium, FullConfig } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { activatePlugins, loginAsAdmin, PLUGIN_SLUG, setupPlugin, setupStore } from './utils';

export const ADMIN_STATE = path.join( __dirname, '.auth', 'admin.json' );

export default async function globalSetup( config: FullConfig ): Promise< void > {
	const baseURL = config.projects[ 0 ].use.baseURL as string;

	activatePlugins( 'woocommerce', PLUGIN_SLUG );
	setupStore();
	setupPlugin();

	fs.mkdirSync( path.dirname( ADMIN_STATE ), { recursive: true } );
	const browser = await chromium.launch();
	const page = await browser.newPage( { baseURL } );
	await loginAsAdmin( page );
	await page.context().storageState( { path: ADMIN_STATE } );
	await browser.close();
}
