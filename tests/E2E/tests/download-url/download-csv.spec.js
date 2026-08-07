const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	readDownloadUrl,
	fetchDownload,
	parseCsv,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Download via URL (CSV extension swap)', () => {
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

	test( 'appending .csv to the download URL returns text/csv with the right rows', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );
		const csvUrl = `${ url }.csv`;

		const response = await fetchDownload( request, csvUrl );

		expect( response.status ).toBe( 200 );
		expect(
			response.headers[ 'content-type' ],
			'Content-Type should be CSV'
		).toMatch( /text\/csv|application\/csv/i );
		expect(
			response.headers[ 'content-disposition' ],
			'Filename should end in .csv'
		).toMatch( /attachment;.*\.csv/i );

		const rows = parseCsv( response.body );

		// simple.json has 4 entries — 1 header + 4 data rows.
		expect(
			rows.length,
			'Row count should be header + 4 entries'
		).toBe( 5 );

		const header = rows[ 0 ];
		expect(
			header,
			'Header should include the form\'s two visible fields'
		).toEqual( expect.arrayContaining( [ 'First Name', 'Email' ] ) );

		// Spot-check a known entry value.
		const firstNameCol = header.indexOf( 'First Name' );
		const names = rows.slice( 1 ).map( ( r ) => r[ firstNameCol ] );
		expect( names ).toEqual( expect.arrayContaining( [ 'Alice', 'Bob' ] ) );
	} );
} );
