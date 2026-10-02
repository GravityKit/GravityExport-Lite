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

// A Gravity Forms Repeater (field 10) whose first sub-field is a nested
// repeater. The row without phone numbers is the one that used to move every
// later value one column to the left.
test.describe( 'GravityExport Lite — Repeater field', () => {
	let data;

	test.beforeEach( async () => {
		data = await fixtures.createFromTemplate( {
			template: 'simple',
			skipView: true,
		} );

		wpEval(
			{ formId: data.form_id },
			`
			$form_id = (int) $data['formId'];
			foreach ( GFAPI::get_entries( $form_id ) as $entry ) {
				GFAPI::delete_entry( (int) $entry['id'] );
			}

			$form = GFAPI::get_form( $form_id );
			$form['fields'][] = GF_Fields::create( [
				'id'     => 10,
				'type'   => 'repeater',
				'label'  => 'People',
				'formId' => $form_id,
				'fields' => [
					[
						'id'     => 13,
						'type'   => 'repeater',
						'label'  => 'Phones',
						'fields' => [ [ 'id' => 14, 'type' => 'text', 'label' => 'Phone' ] ],
					],
					[ 'id' => 11, 'type' => 'text', 'label' => 'Role' ],
					[
						'id'      => 15,
						'type'    => 'checkbox',
						'label'   => 'Skills',
						'choices' => [
							[ 'text' => 'PHP', 'value' => 'php' ],
							[ 'text' => 'JS', 'value' => 'js' ],
						],
						'inputs'  => [
							[ 'id' => '15.1', 'label' => 'PHP' ],
							[ 'id' => '15.2', 'label' => 'JS' ],
						],
					],
					[
						'id'     => 12,
						'type'   => 'name',
						'label'  => 'Full Name',
						'inputs' => [
							[ 'id' => '12.3', 'label' => 'First' ],
							[ 'id' => '12.6', 'label' => 'Last' ],
						],
					],
				],
			] );
			$result = GFAPI::update_form( $form );
			if ( is_wp_error( $result ) ) { fwrite( STDERR, $result->get_error_message() ); exit( 1 ); }

			$entry_ids = [
				GFAPI::add_entry( [
					'form_id' => $form_id,
					'2'       => 'full@example.test',
					'10'      => [
						0 => [
							'13'   => [ [ '14' => '111' ], [ '14' => '222' ] ],
							'11'   => 'Lead',
							'15.1' => 'php',
							'12.3' => 'Ada',
							'12.6' => 'Lovelace',
						],
						// Row indexes can have gaps on entries added through the API.
						2 => [
							'11'   => 'Dev',
							'15.2' => 'js',
							'12.3' => 'Alan',
							'12.6' => 'Turing',
						],
					],
				] ),
				GFAPI::add_entry( [ 'form_id' => $form_id, '2' => 'empty@example.test' ] ),
			];
			foreach ( $entry_ids as $entry_id ) {
				if ( is_wp_error( $entry_id ) ) { fwrite( STDERR, $entry_id->get_error_message() ); exit( 1 ); }
			}
			echo 'ok';
			`
		);
	} );

	test.afterEach( async () => {
		if ( data?.test_id ) {
			await cleanup( data.test_id );
		}
	} );

	test( 'every repeater value lands under its own column, one row per entry', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		const rows = parseCsv(
			( await fetchDownload( request, `${ url }.csv` ) ).body
		);
		const [ header, ...entries ] = rows;

		for ( const column of [ 'Phone', 'Role', 'Skills', 'Full Name' ] ) {
			expect( header, `CSV header should have a "${ column }" column` ).toContain( column );
		}
		expect( entries, 'One CSV row per entry' ).toHaveLength( 2 );

		const byEmail = ( email ) => entries.find( ( row ) => row.includes( email ) );
		const cell = ( row, column ) => row[ header.indexOf( column ) ];

		const full = byEmail( 'full@example.test' );
		expect( cell( full, 'Phone' ) ).toBe( '111, 222\n---\n' );
		expect( cell( full, 'Role' ) ).toBe( 'Lead\n---\nDev' );
		expect( cell( full, 'Skills' ) ).toBe( 'php\n---\njs' );
		expect( cell( full, 'Full Name' ) ).toBe( 'Ada\nLovelace\n---\nAlan\nTuring' );

		const empty = byEmail( 'empty@example.test' );
		for ( const column of [ 'Phone', 'Role', 'Skills', 'Full Name' ] ) {
			expect( cell( empty, column ), `Empty repeater leaves "${ column }" empty` ).toBe( '' );
		}
	} );

	test( 'the repeater is not offered as a sort field', async ( { page } ) => {
		await enableDownloadUrl( page, data.form_id );

		const options = await page
			.locator( 'select[name$="sort_field"] option' )
			.allTextContents();

		expect( options, 'Sort field list should be rendered' ).toContain( 'Entry Date' );
		expect( options ).not.toContain( 'People' );
	} );
} );
