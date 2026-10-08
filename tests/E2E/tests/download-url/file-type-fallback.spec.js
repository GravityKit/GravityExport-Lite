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

// GEXPLIT-25. The file type can arrive two ways: from the feed's own setting, or appended to the
// download URL. Only the first was checked against the types the plugin offers, so an unsupported
// type in the URL fell through to a hardcoded xlsx and a form configured for CSV served an XLSX
// file instead of its own format.
test.describe( 'GravityExport Lite — Which file type a download serves', () => {
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

	test( 'an unsupported type in the URL falls back to the form\'s own setting, not to xlsx', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		// The form's own answer to "what should this export be".
		patchExportFeedMeta( data.form_id, { file_extension: 'csv' } );

		// `.html` is a type PhpSpreadsheet can write but the plugin does not offer.
		const response = await fetchDownload( request, `${ url }.html` );

		expect( response.status ).toBe( 200 );

		expect(
			response.headers[ 'content-type' ],
			'an unsupported type in the URL should leave the form\'s setting in charge'
		).toMatch( /text\/csv|application\/csv/i );

		expect(
			response.headers[ 'content-disposition' ],
			'the downloaded file should carry the form\'s own extension'
		).toMatch( /attachment;.*\.csv/i );
	} );

	test( 'a supported type in the URL still overrides the form\'s setting', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		patchExportFeedMeta( data.form_id, { file_extension: 'csv' } );

		// The point of the URL suffix: ask for a different, supported format on purpose.
		const response = await fetchDownload( request, `${ url }.xlsx` );

		expect( response.status ).toBe( 200 );

		expect(
			response.headers[ 'content-type' ],
			'an explicit, supported type should win over the form\'s setting'
		).toMatch( /spreadsheetml\.sheet/i );

		expect( response.headers[ 'content-disposition' ] ).toMatch(
			/attachment;.*\.xlsx/i
		);
	} );

	test( 'a form left on xlsx still serves xlsx when the URL asks for nothing', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		patchExportFeedMeta( data.form_id, { file_extension: 'xlsx' } );

		const response = await fetchDownload( request, url );

		expect( response.status ).toBe( 200 );
		expect( response.headers[ 'content-type' ] ).toMatch(
			/spreadsheetml\.sheet/i
		);
	} );
} );
