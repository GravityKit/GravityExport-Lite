const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	readDownloadUrl,
	fetchDownload,
	parseCsv,
	parseXlsx,
	wpEval,
} = require( '../../helpers/test-helpers' );

// Two Repeaters on one form. "People" (10) holds a Number, a Product and a Name with hidden inputs,
// for cell types and "Multiple Columns". "Things" (40) is three levels deep, for the bracketed groups.
const FORM_PHP = `
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
			[ 'id' => 11, 'type' => 'text', 'label' => 'Role' ],
			[ 'id' => 16, 'type' => 'number', 'label' => 'Score' ],
			[
				'id'        => 19,
				'type'      => 'product',
				'inputType' => 'singleproduct',
				'label'     => 'Ticket',
				'basePrice' => '$10.00',
				'inputs'    => [
					[ 'id' => '19.1', 'label' => 'Product' ],
					[ 'id' => '19.2', 'label' => 'Price' ],
					[ 'id' => '19.3', 'label' => 'Quantity' ],
				],
			],
			[
				'id'         => 21,
				'type'       => 'name',
				'label'      => 'Full Name',
				'nameFormat' => 'advanced',
				'inputs'     => [
					[ 'id' => '21.2', 'label' => 'Prefix', 'isHidden' => true ],
					[ 'id' => '21.3', 'label' => 'First' ],
					[ 'id' => '21.4', 'label' => 'Middle', 'isHidden' => true ],
					[ 'id' => '21.6', 'label' => 'Last' ],
					[ 'id' => '21.8', 'label' => 'Suffix', 'isHidden' => true ],
				],
			],
		],
	] );
	$form['fields'][] = GF_Fields::create( [
		'id'     => 40,
		'type'   => 'repeater',
		'label'  => 'Things',
		'formId' => $form_id,
		'fields' => [
			[
				'id'     => 41,
				'type'   => 'repeater',
				'label'  => 'Level 2',
				'fields' => [
					[ 'id' => 42, 'type' => 'text', 'label' => 'L2 Text' ],
					[
						'id'     => 43,
						'type'   => 'repeater',
						'label'  => 'Level 3',
						'fields' => [ [ 'id' => 44, 'type' => 'text', 'label' => 'L3 Text' ] ],
					],
				],
			],
			[ 'id' => 45, 'type' => 'text', 'label' => 'After' ],
		],
	] );
	$result = GFAPI::update_form( $form );
	if ( is_wp_error( $result ) ) { fwrite( STDERR, $result->get_error_message() ); exit( 1 ); }

	$ada  = [ '11' => 'Lead', '16' => '7.5', '19.1' => 'Ticket', '19.2' => '$10.00', '19.3' => '2', '21.3' => 'Ada', '21.6' => 'Lovelace' ];
	$alan = [ '11' => 'Dev', '16' => '3', '19.1' => 'Ticket', '19.2' => '$5.00', '19.3' => '1', '21.3' => 'Alan', '21.6' => 'Turing' ];

	$entry_ids = [
		GFAPI::add_entry( [ 'form_id' => $form_id, '2' => 'one@example.test', '10' => [ $ada ] ] ),
		GFAPI::add_entry( [
			'form_id' => $form_id,
			'2'       => 'two@example.test',
			'10'      => [ $ada, $alan ],
			'40'      => [
				[
					'41' => [
						[ '42' => 'p1a', '43' => [ [ '44' => 'deep1' ], [ '44' => 'deep2' ] ] ],
						[ '42' => 'p1b' ],
					],
					'45' => 'after1',
				],
				[ '41' => [ [ '43' => [ [ '44' => 'deep3' ] ] ] ], '45' => 'after2' ],
				// No third-level values in this row.
				[ '41' => [ [ '42' => 'p3a' ], [ '42' => 'p3b' ] ], '45' => 'after3' ],
			],
		] ),
	];
	foreach ( $entry_ids as $entry_id ) {
		if ( is_wp_error( $entry_id ) ) { fwrite( STDERR, $entry_id->get_error_message() ); exit( 1 ); }
	}
	echo 'ok';
`;

/**
 * Sets GravityExport Lite's global settings and returns the previous ones.
 *
 * @param {object} settings
 * @returns {object} The settings before the change.
 */
function setPluginSettings( settings ) {
	return JSON.parse(
		wpEval(
			{ settings },
			`
			$addon    = \\GFExcel\\Addon\\GravityExportAddon::get_instance();
			$previous = $addon->get_plugin_settings();
			$previous = is_array( $previous ) ? $previous : [];
			$addon->update_plugin_settings( array_merge( $previous, $data['settings'] ) );
			echo wp_json_encode( (object) $previous );
			`
		)
	);
}

/**
 * Restores GravityExport Lite's global settings exactly.
 *
 * @param {object} settings
 */
function restorePluginSettings( settings ) {
	wpEval(
		{ settings },
		`
		\\GFExcel\\Addon\\GravityExportAddon::get_instance()->update_plugin_settings( (array) $data['settings'] );
		echo 'ok';
		`
	);
}

test.describe( 'GravityExport Lite — Repeater field shapes', () => {
	let data;
	let previousSettings;

	test.beforeEach( async () => {
		data = await fixtures.createFromTemplate( {
			template: 'simple',
			skipView: true,
		} );

		wpEval( { formId: data.form_id }, FORM_PHP );
		previousSettings = null;
	} );

	test.afterEach( async () => {
		if ( previousSettings ) {
			restorePluginSettings( previousSettings );
		}

		if ( data?.test_id ) {
			await cleanup( data.test_id );
		}
	} );

	/**
	 * Downloads the form's export and returns its header and rows, keyed by entry email.
	 *
	 * @param {import('@playwright/test').Page} page
	 * @param {import('@playwright/test').APIRequestContext} request
	 * @param {'csv'|'xlsx'} type
	 */
	async function download( page, request, type ) {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );
		const response = await fetchDownload( request, `${ url }.${ type }` );
		expect( response.status, `The ${ type } download should succeed` ).toBe( 200 );

		const rows =
			'xlsx' === type
				? parseXlsx( response.body )
				: parseCsv( response.body ).map( ( row ) => row.map( ( value ) => ( { value } ) ) );
		const [ header, ...entries ] = rows;
		const columns = header.map( ( cell ) => cell.value );
		const byEmail = ( email ) => {
			const row = entries.find( ( cells ) => cells.some( ( cell ) => cell?.value === email ) );
			expect( row, `An export row for ${ email }` ).toBeTruthy();

			return ( column ) => {
				expect( columns, `The export should have a "${ column }" column` ).toContain( column );

				return row[ columns.indexOf( column ) ] || { value: '', type: 'empty' };
			};
		};

		return { columns, entries, byEmail };
	}

	test( 'XLSX: a one-row Repeater keeps Number and numeric Product cells as numbers', async ( {
		page,
		request,
	} ) => {
		previousSettings = setPluginSettings( { field_separation_enabled: 1, numeric_price_enabled: 1 } );

		const { byEmail } = await download( page, request, 'xlsx' );

		const one = byEmail( 'one@example.test' );
		expect( one( 'Score' ) ).toEqual( { value: '7.5', type: 'number' } );
		expect( one( 'Price' ) ).toEqual( { value: '10', type: 'number' } );
		expect( one( 'Quantity' ) ).toEqual( { value: '2', type: 'number' } );
		expect( one( 'Role' ) ).toEqual( { value: 'Lead', type: 'string' } );

		// Two rows share one cell, so it is text.
		const two = byEmail( 'two@example.test' );
		expect( two( 'Score' ) ).toEqual( { value: '7.5\n---\n3', type: 'string' } );
	} );

	test( 'three levels of Repeaters: third-level groups are bracketed by their second-level item', async ( {
		page,
		request,
	} ) => {
		const { byEmail } = await download( page, request, 'csv' );
		const two = byEmail( 'two@example.test' );

		expect( two( 'L2 Text' ).value ).toBe( 'p1a, p1b\n---\n\n---\np3a, p3b' );
		expect( two( 'L3 Text' ).value ).toBe( '[deep1, deep2], []\n---\n[deep3]\n---\n' );
		expect( two( 'After' ).value ).toBe( 'after1\n---\nafter2\n---\nafter3' );

		// No third-level values anywhere: the column stays empty instead of showing "[]".
		expect( byEmail( 'one@example.test' )( 'L3 Text' ).value ).toBe( '' );
	} );

	test( 'Multiple Columns: Name and Product inside a Repeater split into one column per input', async ( {
		page,
		request,
	} ) => {
		// Numeric prices on: with them off, a separated Price column exports 0.00 (GEXPLIT-41).
		previousSettings = setPluginSettings( { field_separation_enabled: 1, numeric_price_enabled: 1 } );

		const { columns, entries, byEmail } = await download( page, request, 'csv' );

		// Hidden Name inputs get no column.
		for ( const hidden of [ 'Prefix', 'Middle', 'Suffix' ] ) {
			expect( columns, `Hidden input "${ hidden }" should have no column` ).not.toContain( hidden );
		}
		for ( const row of entries ) {
			expect( row.length, 'Every row has one cell per header' ).toBeLessThanOrEqual( columns.length );
		}

		const two = byEmail( 'two@example.test' );
		expect( two( 'Role' ).value ).toBe( 'Lead\n---\nDev' );
		expect( two( 'Score' ).value ).toBe( '7.5\n---\n3' );
		expect( two( 'Product' ).value ).toBe( 'Ticket\n---\nTicket' );
		expect( two( 'Price' ).value ).toBe( '10\n---\n5' );
		expect( two( 'Quantity' ).value ).toBe( '2\n---\n1' );
		expect( two( 'First' ).value ).toBe( 'Ada\n---\nAlan' );
		expect( two( 'Last' ).value ).toBe( 'Lovelace\n---\nTuring' );
	} );

	test( 'without Multiple Columns: Number and Product rows stay in their own cells', async ( {
		page,
		request,
	} ) => {
		previousSettings = setPluginSettings( { field_separation_enabled: 0, numeric_price_enabled: 0 } );

		const { byEmail } = await download( page, request, 'csv' );
		const two = byEmail( 'two@example.test' );

		expect( two( 'Score' ).value ).toBe( '7.5\n---\n3' );
		expect( two( 'Ticket' ).value ).toBe( 'Ticket\nPrice: $10.00\nQty: 2\n---\nTicket\nPrice: $5.00\nQty: 1' );
		expect( two( 'Full Name' ).value ).toBe( 'Ada\nLovelace\n---\nAlan\nTuring' );
	} );
} );
