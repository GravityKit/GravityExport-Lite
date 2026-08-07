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

test.describe( 'GravityExport Lite — Disabled fields are excluded from the export', () => {
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

	test( 'disabling the Email field removes its column from the CSV download', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		// Baseline: Email column is present.
		const baseline = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		);
		expect(
			baseline[ 0 ],
			'Baseline header should include Email'
		).toContain( 'Email' );
		const baselineRowCount = baseline.length;

		// Disable field id=2 (Email) via the persisted feed meta. The
		// addon reads from `meta/export-fields/disabled` as a CSV string
		// (see FieldsRepository::getDisabledFields). Writing it directly
		// is what the sortable UI would do, minus the JS resync timing.
		patchExportFeedMeta( data.form_id, {
			'export-fields': { disabled: '2', enabled: '' },
		} );

		const rows = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		);

		expect(
			rows[ 0 ],
			'Disabled Email field must not appear in the header'
		).not.toContain( 'Email' );
		expect(
			rows[ 0 ],
			'Other fields are still present'
		).toContain( 'First Name' );
		expect(
			rows.length,
			'Row count is unchanged — only column is removed'
		).toBe( baselineRowCount );
	} );
} );
