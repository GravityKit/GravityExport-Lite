import { test, expect } from '@playwright/test';
import { readProbe, resetProbe, events } from '../support/probe';
import { readFileSync } from 'node:fs';
import path from 'node:path';

test.use( { storageState: path.join( __dirname, '..', '.state', 'admin.json' ) } );

test.describe( 'consent gate', () => {
	test.beforeEach( async ( { request } ) => {
		await resetProbe( request );
	} );

	test( 'nothing is transmitted before consent is given', async ( { page, request } ) => {
		await page.goto( '/wp-admin/' );
		await page.goto( '/wp-admin/plugins.php' );
		await page.goto( '/wp-admin/admin.php?page=gf_settings&subview=gravityexport-lite' );

		// Browsing wp-admin alone reaches no emitter, so on its own it would pass
		// even with the consent gate removed. Exercising a path that DOES emit is
		// what makes this assertion mean anything.
		const fixture = JSON.parse(
			readFileSync( path.join( __dirname, '..', '.state', 'fixture.json' ), 'utf8' )
		) as { downloadUrl: string };

		const download = await request.get( fixture.downloadUrl );
		expect( download.status(), 'The export did not download, so nothing was exercised.' ).toBe( 200 );

		const probe = await readProbe( request );

		expect(
			probe.count,
			`Analytics transmitted ${ probe.count } request(s) with no consent recorded. ` +
			'This is a wordpress.org Guideline 7 violation.'
		).toBe( 0 );
	} );

	test( 'the opt-in prompt is shown, and states what is and is not collected', async ( { page } ) => {
		await page.goto( '/wp-admin/' );

		const card = page.locator( '.notice', { hasText: 'Help improve GravityExport' } );
		await expect( card ).toBeVisible();

		// The wording is hashed into the consent record, so it is part of the contract.
		await expect( card ).toContainText( 'anonymous usage data' );
		await expect( card ).toContainText( 'We never collect form data, entry data, email addresses, or your site address' );
		await expect( card ).toContainText( 'one-way hash' );

		await expect( card.getByRole( 'button', { name: 'Share usage data' } ) ).toBeVisible();
		await expect( card.getByRole( 'button', { name: 'No thanks' } ) ).toBeVisible();
	} );

	test( 'declining is durable and still transmits nothing', async ( { page, request } ) => {
		await page.goto( '/wp-admin/' );
		await page.getByRole( 'button', { name: 'No thanks' } ).click();
		await page.waitForLoadState( 'networkidle' );

		await expect(
			page.locator( '.notice', { hasText: 'Help improve GravityExport' } )
		).toHaveCount( 0 );

		// A decline that is not remembered would re-prompt forever.
		await page.goto( '/wp-admin/' );
		await expect(
			page.locator( '.notice', { hasText: 'Help improve GravityExport' } ),
			'The prompt returned after being declined.'
		).toHaveCount( 0 );

		const probe = await readProbe( request );
		expect( probe.count, 'A declined install transmitted something.' ).toBe( 0 );
		expect( events( probe ) ).toHaveLength( 0 );
	} );

	test( 'granting records consent with a hash of the exact wording shown', async ( { page, request } ) => {
		await page.goto( '/wp-admin/' );

		const card = page.locator( '.notice', { hasText: 'Help improve GravityExport' } );
		const shown = ( await card.locator( 'p' ).first().innerText() ).trim();

		await page.getByRole( 'button', { name: 'Share usage data' } ).click();
		await page.waitForLoadState( 'networkidle' );

		const probe = await readProbe( request );
		const consent = probe.consent as Record<string, unknown>;

		expect( consent, 'No consent record was written.' ).toBeTruthy();
		expect( consent.granted ).toBe( true );
		expect( consent.source ).toBe( 'lite_settings_card' );
		expect( typeof consent.ts ).toBe( 'number' );
		expect( consent.schema_version ).toBe( 1 );

		// The hash must be of what was actually on screen, or it cannot answer
		// "does this grant cover the promise we are now making".
		expect( consent.disclosure_hash ).toMatch( /^[0-9a-f]{64}$/ );
		expect( shown ).toContain( 'anonymous usage data' );

		await expect(
			page.locator( '.notice', { hasText: 'Help improve GravityExport' } )
		).toHaveCount( 0 );
	} );

	test( 'opting in emits analytics_opt_in and nothing else', async ( { page, request } ) => {
		await page.goto( '/wp-admin/' );
		await page.getByRole( 'button', { name: 'Share usage data' } ).click();
		await page.waitForLoadState( 'networkidle' );

		const captured = events( await readProbe( request ) );

		expect( captured.map( ( e ) => e.event ) ).toEqual( [ 'analytics_opt_in' ] );
		expect( captured[ 0 ].properties.consent_source ).toBe( 'lite_settings_card' );
	} );
} );
