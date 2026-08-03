import { defineConfig } from '@playwright/test';

/**
 * The site under test is minted by Siteminter and its port varies, so the base
 * URL is supplied by the environment rather than hardcoded.
 *
 *   GK_E2E_URL=http://localhost:8940 npm test
 */
const baseURL = process.env.GK_E2E_URL;

if ( ! baseURL ) {
	throw new Error(
		'GK_E2E_URL is not set. Mint a site first:\n' +
		'  cd <siteminter> && npm run cli -- mint --name=gexplit-19 --plugins=<gravityforms>,<gf-entries-in-excel>\n' +
		'then re-run with GK_E2E_URL=http://localhost:<port>'
	);
}

export default defineConfig( {
	testDir: './specs',
	// Serial: every spec mutates the same site-wide consent option, so parallel
	// workers would race each other's reset step and produce flaky verdicts.
	workers: 1,
	fullyParallel: false,
	forbidOnly: !! process.env.CI,
	retries: 0,
	reporter: [ [ 'list' ], [ 'html', { open: 'never' } ] ],
	use: {
		baseURL,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		ignoreHTTPSErrors: true,
	},
	globalSetup: require.resolve( './support/global-setup.ts' ),
} );
