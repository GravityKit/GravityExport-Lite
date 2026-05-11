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
	wpEval,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Entry notes column', () => {
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

	test( 'enabling the Notes setting renders a notes column with the entry note text', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		const noteText = 'E2E-NOTE-' + Date.now();

		// Attach a note to entry #1 (the first seeded entry).
		const firstEntryId = data.entries[ 0 ].id;
		wpEval(
			{ entryId: firstEntryId, note: noteText },
			`
			\\RGFormsModel::add_note(
				(int) $data['entryId'],
				1,
				'admin',
				$data['note']
			);
			echo 'ok';
			`
		);

		// Baseline (notes flag off): the CSV header must NOT carry a Notes
		// column. This catches accidental defaulting in the addon.
		const baselineHeader = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		)[ 0 ];
		expect(
			baselineHeader,
			'Notes column should be absent until the setting is enabled'
		).not.toContain( 'Notes' );

		patchExportFeedMeta( data.form_id, { enable_notes: '1' } );

		const rows = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		);
		const header = rows[ 0 ];
		const notesIdx = header.indexOf( 'Notes' );

		expect(
			notesIdx,
			'Notes column should appear once the setting is on'
		).toBeGreaterThanOrEqual( 0 );

		// One of the data rows now carries our note string.
		const notesValues = rows.slice( 1 ).map( ( r ) => r[ notesIdx ] );
		expect(
			notesValues.some( ( v ) => v && v.includes( noteText ) ),
			'At least one entry row should contain our seeded note text'
		).toBe( true );
	} );
} );
