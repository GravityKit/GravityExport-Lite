import { chromium, request as pwRequest, type FullConfig } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { writeFileSync, mkdirSync } from 'node:fs';
import path from 'node:path';

const SITEMINTER = process.env.GK_E2E_SITEMINTER;
const SITE_DIR = process.env.GK_E2E_SITE_DIR;

/**
 * Seeds the fixture and captures the artifacts the specs need: an authenticated
 * admin session and the public download URL.
 *
 * The fixture is seeded through wp-cli rather than the browser because clicking
 * a form builder is slow, brittle, and tests Gravity Forms rather than this
 * plugin. What the browser tests exercise is the consent surface and the links.
 */
export default async function globalSetup( config: FullConfig ) {
	const baseURL = config.projects[ 0 ].use.baseURL!;
	const outDir = path.join( __dirname, '..', '.state' );
	mkdirSync( outDir, { recursive: true } );

	if ( ! SITEMINTER || ! SITE_DIR ) {
		throw new Error(
			'GK_E2E_SITEMINTER and GK_E2E_SITE_DIR must be set so the fixture can be seeded via wp-cli.'
		);
	}

	const seeded = execFileSync(
		path.join( SITEMINTER, 'node_modules/.bin/wp-env' ),
		[
			'run', 'cli', 'wp', 'eval-file',
			'/var/www/html/wp-content/plugins/gf-entries-in-excel/tests/e2e/support/seed.php',
		],
		{ cwd: SITE_DIR, encoding: 'utf8' }
	);

	const downloadUrl = seeded.match( /DOWNLOAD_URL=(\S+)/ )?.[ 1 ];
	const formId = seeded.match( /FORM_ID=(\d+)/ )?.[ 1 ];

	if ( ! downloadUrl || ! formId ) {
		throw new Error( `Seeding produced no download URL. Output:\n${ seeded }` );
	}

	writeFileSync( path.join( outDir, 'fixture.json' ), JSON.stringify( { downloadUrl, formId }, null, 2 ) );

	// Authenticated admin session, reused by every spec.
	const browser = await chromium.launch();
	const page = await browser.newPage( { baseURL } );

	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'admin' );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );

	await page.context().storageState( { path: path.join( outDir, 'admin.json' ) } );
	await browser.close();

	// Start every run from a known state: no consent, no recorded transmissions.
	const api = await pwRequest.newContext( { baseURL } );
	await api.post( '/?rest_route=/gk-probe/v1/reset', { headers: { 'x-gk-probe': 'gk-e2e-probe' } } );
	await api.dispose();
}
