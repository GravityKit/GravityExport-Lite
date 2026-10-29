const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	readDownloadUrl,
	fetchDownload,
	parseCsv,
	wpEval,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — CSV cells that start like a formula', () => {
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

	test( 'formula-like entry values are prefixed with a quote; plain numbers are not', async ( {
		page,
		request,
	} ) => {
		const values = [ '=1+1', '@SUM(1+1)', '+1+1', '-5' ];

		wpEval(
			{ formId: data.form_id, entryIds: data.entries.map( ( e ) => e.id ), values },
			`
			$form     = GFAPI::get_form( (int) $data['formId'] );
			$field_id = 0;
			foreach ( $form['fields'] as $field ) {
				if ( $field->label === 'First Name' ) {
					$field_id = $field->id;
				}
			}
			if ( ! $field_id ) { fwrite( STDERR, 'no First Name field' ); exit( 1 ); }
			foreach ( $data['values'] as $i => $value ) {
				GFAPI::update_entry_field( (int) $data['entryIds'][ $i ], $field_id, $value );
			}
			echo 'ok';
			`
		);

		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		const response = await fetchDownload( request, `${ url }.csv` );
		expect( response.status ).toBe( 200 );

		const rows = parseCsv( response.body );
		const col = rows[ 0 ].indexOf( 'First Name' );
		const cells = rows.slice( 1 ).map( ( r ) => r[ col ] );

		expect( cells, 'Formula-like values must not reach the CSV as formulas' ).toEqual(
			expect.arrayContaining( [ "'=1+1", "'@SUM(1+1)", "'+1+1" ] )
		);
		expect( cells, 'A plain negative number must stay a number' ).toContain( '-5' );
		expect( cells ).not.toContain( '=1+1' );
	} );
} );
