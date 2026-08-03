import { test, expect } from '@playwright/test';
import { readProbe, resetProbe, events } from '../support/probe';
import { readFileSync } from 'node:fs';
import path from 'node:path';

const fixture = JSON.parse(
	readFileSync( path.join( __dirname, '..', '.state', 'fixture.json' ), 'utf8' )
) as { downloadUrl: string; formId: string };

test.use( { storageState: path.join( __dirname, '..', '.state', 'admin.json' ) } );

/** Grants consent through the real UI, so the flow under test is the real one. */
async function optIn( page: import( '@playwright/test' ).Page ) {
	await page.goto( '/wp-admin/' );
	await page.getByRole( 'button', { name: 'Share usage data' } ).click();
	await page.waitForLoadState( 'networkidle' );
}

test.describe( 'export activation flow', () => {
	test.beforeEach( async ( { request } ) => {
		await resetProbe( request );
	} );

	test( 'a download by an anonymous visitor with no consent transmits nothing', async ( { request } ) => {
		// The download URL is public and unauthenticated. A fresh context with no
		// cookies is exactly how a real visitor reaches it.
		const anon = await request.get( fixture.downloadUrl );

		expect( anon.status() ).toBe( 200 );
		expect( anon.headers()[ 'content-type' ] ).toContain( 'csv' );

		const probe = await readProbe( request );
		expect( probe.count, 'A public download transmitted analytics without consent.' ).toBe( 0 );
	} );

	test( 'a completed download emits export_completed with the registered props', async ( { page, request } ) => {
		await optIn( page );
		await resetProbe( request );

		// Re-grant, since reset clears consent as well as the log.
		await optIn( page );

		const response = await request.get( fixture.downloadUrl );
		expect( response.status() ).toBe( 200 );

		// The event is sent on shutdown, after the file has streamed.
		await expect.poll(
			async () => events( await readProbe( request ) ).map( ( e ) => e.event ),
			{ timeout: 10_000, message: 'export_completed never arrived' }
		).toContain( 'export_completed' );

		const exportEvent = events( await readProbe( request ) ).find( ( e ) => e.event === 'export_completed' )!;

		expect( exportEvent.properties.file_format ).toBe( 'csv' );
		expect( exportEvent.properties.object_type ).toBe( 'export' );
		expect( exportEvent.properties.gk_product ).toBe( 'gravityexport-lite' );
		expect( exportEvent.properties.is_activation_event ).toBe( true );
		expect( exportEvent.properties.activated_product ).toBe( 'gravityexport-lite' );
		expect( typeof exportEvent.properties.column_count ).toBe( 'number' );
	} );

	test( 'the payload carries no entry data, email, or raw site address', async ( { page, request } ) => {
		await optIn( page );
		await request.get( fixture.downloadUrl );

		await expect.poll(
			async () => ( await readProbe( request ) ).count,
			{ timeout: 10_000 }
		).toBeGreaterThan( 0 );

		const raw = JSON.stringify( await readProbe( request ) );

		// Values that exist in the exported entries and must never be transmitted.
		for ( const secret of [ 'ada@example.com', 'alan@example.com', 'Ada Lovelace', 'Alan Turing' ] ) {
			expect( raw, `Entry data "${ secret }" reached the payload.` ).not.toContain( secret );
		}

		// The site address must appear only as a hash, never in the clear.
		expect( raw, 'The raw site URL reached the payload.' ).not.toContain( 'localhost:8943' );

		// And the secret download URL itself must never travel.
		expect( raw, 'The secret download hash reached the payload.' )
			.not.toContain( fixture.downloadUrl.split( '/' ).pop() );
	} );

	test( 'site_id is a non-reversible hash and is the site group key', async ( { page, request } ) => {
		await optIn( page );
		await request.get( fixture.downloadUrl );

		await expect.poll( async () => ( await readProbe( request ) ).count, { timeout: 10_000 } )
			.toBeGreaterThan( 0 );

		const probe = await readProbe( request );
		const captured = events( probe )[ 0 ];

		expect( probe.salt_set, 'No identity salt was minted.' ).toBe( true );
		expect( captured.properties.site_id ).toMatch( /^[0-9a-f]{64}$/ );
		expect( captured.groups.site ).toBe( captured.properties.site_id );
	} );

	test( 'repeat downloads of the same form on the same day count once', async ( { page, request } ) => {
		await optIn( page );

		await request.get( fixture.downloadUrl );
		await request.get( fixture.downloadUrl );
		await request.get( fixture.downloadUrl );

		await expect.poll( async () => ( await readProbe( request ) ).count, { timeout: 10_000 } )
			.toBeGreaterThan( 0 );

		const exports = events( await readProbe( request ) ).filter( ( e ) => e.event === 'export_completed' );

		expect(
			exports,
			'The activation event was counted more than once for the same form on the same day.'
		).toHaveLength( 1 );
	} );

	test( 'a request that announces itself as a crawler is not counted', async ( { page, request, playwright } ) => {
		await optIn( page );

		const bot = await playwright.request.newContext( {
			extraHTTPHeaders: { 'User-Agent': 'Mozilla/5.0 (compatible; Googlebot/2.1)' },
		} );
		const response = await bot.get( fixture.downloadUrl );
		expect( response.status() ).toBe( 200 );
		await bot.dispose();

		// Give the shutdown flush a chance to have run before asserting absence.
		await page.waitForTimeout( 2000 );

		const exports = events( await readProbe( request ) ).filter( ( e ) => e.event === 'export_completed' );
		expect( exports, 'Crawler traffic was counted as an activation.' ).toHaveLength( 0 );
	} );
} );
