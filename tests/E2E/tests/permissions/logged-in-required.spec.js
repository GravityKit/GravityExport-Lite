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

	test( 'a new link is restricted until the site owner opens it', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		// A link the site owner never asked for by name must not be public.
		const baseline = await anonGet( url );
		expect(
			baseline.status,
			'A newly created link must not serve the export anonymously'
		).not.toBe( 200 );

		// Admin (the `request` fixture inherits the bootstrap login
		// storage state) gets the file from the same URL.
		const adminResponse = await fetchDownload( request, url );
		expect(
			adminResponse.status,
			'Admin should be able to download a restricted link'
		).toBe( 200 );
		expect( adminResponse.body.length ).toBeGreaterThan( 512 );

		// Opening it up is the deliberate step.
		patchExportFeedMeta( data.form_id, { is_secured: '0' } );

		const anonAfter = await anonGet( url );
		expect(
			anonAfter.status,
			'Once opened, anonymous receives the export'
		).toBe( 200 );
	} );
} );
