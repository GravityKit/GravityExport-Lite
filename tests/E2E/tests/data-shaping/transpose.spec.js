const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	readDownloadUrl,
	fetchDownload,
	parseCsv,
	patchExportFeedMeta,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Transpose mode', () => {
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

	test( 'enabling transpose makes fields the rows and entries the columns', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		// Baseline: rows = header + entries.
		const baselineRows = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		);
		const baselineColumnCount = baselineRows[ 0 ].length;
		const baselineRowCount = baselineRows.length;

		patchExportFeedMeta( data.form_id, { is_transposed: '1' } );

		const transposed = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		);

		// After transposing: rows ↔ columns. The new row count equals
		// the original column count, and the new column count equals
		// the original row count.
		expect(
			transposed.length,
			'Transposed row count should equal baseline column count'
		).toBe( baselineColumnCount );
		expect(
			transposed[ 0 ].length,
			'Transposed column count should equal baseline row count'
		).toBe( baselineRowCount );

		// Field labels now appear in the first column (one per row).
		const firstColumn = transposed.map( ( r ) => r[ 0 ] );
		expect(
			firstColumn,
			'First Name label appears as a row label in transpose mode'
		).toContain( 'First Name' );
		expect( firstColumn ).toContain( 'Email' );
	} );
} );
