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

test.describe( 'GravityExport Lite — Field sort order drives column order', () => {
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

	test( 'putting Email ahead of First Name swaps their column positions', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		// Baseline: First Name precedes Email.
		const baseline = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		)[ 0 ];
		expect( baseline.indexOf( 'First Name' ) ).toBeLessThan(
			baseline.indexOf( 'Email' )
		);

		// Promote Email (field id 2) ahead of First Name (id 1) by writing
		// the ordered enabled list (CSV) into the feed meta. The plugin
		// renders columns in this order.
		patchExportFeedMeta( data.form_id, {
			'export-fields': { enabled: '2,1', disabled: '' },
		} );

		const header = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		)[ 0 ];

		expect( header ).toContain( 'Email' );
		expect( header ).toContain( 'First Name' );
		expect(
			header.indexOf( 'Email' ),
			'Email must precede First Name after the reorder'
		).toBeLessThan( header.indexOf( 'First Name' ) );
	} );
} );
