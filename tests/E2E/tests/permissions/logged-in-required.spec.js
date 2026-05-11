const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	readDownloadUrl,
	fetchDownload,
	patchExportFeedMeta,
} = require( '../../helpers/test-helpers' );

/**
 * Plain unauthenticated GET via Node's global fetch. Playwright's
 * APIRequestContext can carry browser cookies from the worker even when
 * created via `request.newContext()` in some configurations, so we use the
 * Node fetch primitive to guarantee an anonymous request.
 */
async function anonGet( url ) {
	const response = await fetch( url, { redirect: 'manual' } );
	return { status: response.status, body: Buffer.from( await response.arrayBuffer() ) };
}

test.describe( 'GravityExport Lite — Restrict download to logged-in users', () => {
	let data;

	test.beforeEach( async () => {
		data = await fixtures.createFromTemplate( {
			template: 'simple',
			skipView: true,
		} );
	} );

	test.afterEach( async () => {
		if ( data?.test_id ) {
			await cleanup( data.test_id );
		}
	} );

	test( 'restricting access denies anonymous GETs but allows admin', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		const baseline = await anonGet( url );
		expect(
			baseline.status,
			'Baseline anonymous request should succeed when permissions are open'
		).toBe( 200 );

		// Flip permissions to "Logged-in only" via the persisted feed meta.
		patchExportFeedMeta( data.form_id, { is_secured: '1' } );

		// Anonymous request is now denied.
		const anonAfter = await anonGet( url );
		expect(
			anonAfter.status,
			'After enabling restriction, anonymous must not receive the export'
		).not.toBe( 200 );

		// Admin (the `request` fixture inherits the bootstrap login
		// storage state) still gets the file.
		const adminResponse = await fetchDownload( request, url );
		expect(
			adminResponse.status,
			'Admin should still be able to download'
		).toBe( 200 );
		expect( adminResponse.body.length ).toBeGreaterThan( 512 );
	} );
} );
