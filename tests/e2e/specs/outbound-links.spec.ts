import { test, expect } from '@playwright/test';
import path from 'node:path';

test.use( { storageState: path.join( __dirname, '..', '.state', 'admin.json' ) } );


/**
 * Returns the gravitykit.com links the PLUGIN builds in its row.
 *
 * WordPress renders the Author URI header into the same cell, and that is
 * metadata rather than a call to action: it must stay untagged, so it is
 * excluded here by shape (bare host, no path, no query) rather than by
 * position, which would silently stop excluding it if the markup changed.
 */
async function builtLinks( page: import( '@playwright/test' ).Page ): Promise<string[]> {
	const hrefs = await page.locator(
		'tr[data-slug="gf-entries-in-excel"] a[href*="gravitykit.com"]'
	).evaluateAll( ( links ) => [ ...new Set( links.map( ( a ) => ( a as HTMLAnchorElement ).href ) ) ] );

	return hrefs.filter( ( href ) => {
		const url = new URL( href );

		return ! ( '/' === url.pathname && '' === url.search );
	} );
}

test.describe( 'outbound link tagging', () => {
	test( 'the plugin row links are tagged, and Documentation goes to documentation', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );

		const row = page.locator( 'tr[data-slug="gf-entries-in-excel"]' );
		const docs = row.getByRole( 'link', { name: 'Documentation' } );

		await expect( docs ).toBeVisible();
		const href = await docs.getAttribute( 'href' );

		// The bug this replaced: the link 301'd to the product page, so a user
		// clicking "Documentation" landed on a sales pitch.
		expect( href, 'The Documentation link points at a product page.' ).toContain( '/docs/' );
		expect( href ).not.toContain( '/products/' );

		const params = new URL( href! ).searchParams;
		expect( params.get( 'utm_source' ) ).toBe( 'gravityexport-lite' );
		expect( params.get( 'utm_medium' ) ).toBe( 'plugin' );
		expect( params.get( 'utm_campaign' ) ).toBe( 'docs' );
		expect( params.get( 'utm_content' ) ).toBe( 'plugin_meta_docs' );
	} );

	test( 'the upgrade link carries the upgrade campaign', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );

		const upgrade = page.locator( 'tr[data-slug="gf-entries-in-excel"] a[href*="utm_campaign=upgrade"]' );
		await expect( upgrade ).toHaveCount( 1 );

		const params = new URL( ( await upgrade.getAttribute( 'href' ) )! ).searchParams;
		expect( params.get( 'utm_source' ) ).toBe( 'gravityexport-lite' );
		expect( params.get( 'utm_content' ) ).toBe( 'plugin_meta_upgrade' );
	} );

	test( 'no outbound link carries a per-install identifier', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );

		const hrefs = await builtLinks( page );

		expect( hrefs.length ).toBeGreaterThan( 0 );

		for ( const href of hrefs ) {
			const params = new URL( href ).searchParams;

			// Only the four campaign keys are permitted. A site_id or token here
			// would re-identify the install inside Google Analytics.
			expect(
				[ ...params.keys() ].sort(),
				`Unexpected query parameters on ${ href }`
			).toEqual( [ 'utm_campaign', 'utm_content', 'utm_medium', 'utm_source' ] );
		}
	} );

	test( 'every outbound link resolves without losing its tags', async ( { page, request } ) => {
		await page.goto( '/wp-admin/plugins.php' );

		const hrefs = await builtLinks( page );

		for ( const href of hrefs ) {
			const response = await request.get( href, { maxRedirects: 5 } );

			expect( response.status(), `${ href } did not resolve` ).toBe( 200 );

			// The legacy gfexcel.com redirect drops the query string, so a link
			// routed through it arrives untagged and the campaign data is lost.
			expect( response.url(), `${ href } lost its tags in a redirect` )
				.toContain( 'utm_source=gravityexport-lite' );
		}
	} );
} );
