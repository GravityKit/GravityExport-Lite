const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	readDownloadUrl,
	fetchDownload,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Download via URL (XLSX default)', () => {
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

	test( 'anonymous GET on the download URL returns a valid .xlsx attachment', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		// Use a fresh request context so we are NOT carrying the admin cookies.
		const response = await fetchDownload( request, url );

		expect( response.status ).toBe( 200 );

		expect(
			response.headers[ 'content-type' ],
			'Content-Type should be the Excel SpreadsheetML MIME'
		).toMatch(
			/application\/vnd\.openxmlformats-officedocument\.spreadsheetml\.sheet/i
		);

		expect(
			response.headers[ 'content-disposition' ],
			'Should be an attachment with a .xlsx filename'
		).toMatch( /attachment;.*\.xlsx/i );

		// XLSX files are ZIP containers — the magic bytes are "PK\x03\x04".
		expect(
			response.body.length,
			'Body should be a non-trivial binary'
		).toBeGreaterThan( 512 );
		expect( response.body.slice( 0, 2 ).toString( 'binary' ) ).toBe( 'PK' );
	} );
} );
