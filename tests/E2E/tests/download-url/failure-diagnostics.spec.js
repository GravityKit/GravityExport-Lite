const { test, request: playwrightRequest } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	readDownloadUrl,
	fetchDownload,
	readServerErrorLog,
} = require( '../../helpers/test-helpers' );

// Raised by tests/E2E/setup/mu-plugins/e2e-export-failure.php.
const FAILURE_MARKER = 'E2E forced export failure marker 8f2c1d';

/**
 * Query string that makes this one request fail, tagged so its trace can be told apart from one
 * another test left in the shared container log.
 */
function failureArg( token ) {
	return `gk_e2e_force_export_failure=${ token }`;
}

function uniqueToken() {
	return `t${ Date.now().toString( 36 ) }${ Math.random().toString( 36 ).slice( 2, 8 ) }`;
}

/**
 * Everything a failure used to reveal to whoever asked for the download.
 *
 * Each is a separate assertion so a partial regression names what leaked rather than just failing.
 */
const DIAGNOSTICS = [
	[ 'the exception message', FAILURE_MARKER ],
	[ 'a stack trace', 'Error stack trace' ],
	[ 'the plugin version', 'Plugin Version' ],
	[ 'the Gravity Forms version', 'Gravity Forms Version' ],
	[ 'the PHP version', 'PHP Version' ],
	[ 'the WordPress version', 'WordPress Version' ],
	[ 'absolute server paths', '/var/www/html' ],
];

// GEXPLIT-26. A failed export printed the exception message, a stack trace, absolute paths and the
// plugin, Gravity Forms, PHP and WordPress versions to whoever requested the download, ungated. The
// download URL is meant to be handed to people who are not administrators, so "whoever requested it"
// is the whole point of the feature and the reason this matters.
test.describe( 'GravityExport Lite — What a failed export reveals', () => {
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

	test( 'an anonymous visitor sees that it broke, and nothing else', async ( { page } ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		// The `request` fixture inherits the admin storageState, so a genuinely signed-out visitor
		// needs a context of its own. Without this the test would assert the administrator's view.
		const anonymous = await playwrightRequest.newContext( { storageState: undefined } );

		try {
			const response = await fetchDownload(
				anonymous,
				`${ url }.csv?${ failureArg( uniqueToken() ) }`
			);

			const body = response.body.toString();

			expect(
				body,
				'the forced failure did not reach the error page'
			).toContain( 'Something is broken' );

			expect(
				body,
				'the visitor is not told where the reason went'
			).toContain( 'error log' );

			for ( const [ label, needle ] of DIAGNOSTICS ) {
				expect( body, `a signed-out visitor was shown ${ label }` ).not.toContain(
					needle
				);
			}
		} finally {
			await anonymous.dispose();
		}
	} );

	test( 'a user who can export entries still sees the details', async ( { page, request } ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		// `request` carries the signed-in administrator, who holds gravityforms_export_entries.
		const response = await fetchDownload(
			request,
			`${ url }.csv?${ failureArg( uniqueToken() ) }`
		);

		const body = response.body.toString();

		expect( body ).toContain( 'Something is broken' );

		for ( const [ label, needle ] of DIAGNOSTICS ) {
			// Paths are incidental to the report; the rest is what makes it actionable.
			if ( needle === '/var/www/html' ) {
				continue;
			}

			expect( body, `someone who can export entries was denied ${ label }` ).toContain(
				needle
			);
		}
	} );

	test( 'the reason reaches the error log even when the visitor is not shown it', async ( {
		page,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const url = await readDownloadUrl( page );

		const anonymous = await playwrightRequest.newContext( { storageState: undefined } );

		// Tagging this request is what ties the log entry to it. Matching the shared marker alone
		// would also match an entry another test wrote, so the assertion would hold even if this
		// request logged nothing.
		const token = uniqueToken();

		try {
			await fetchDownload( anonymous, `${ url }.csv?${ failureArg( token ) }` );
		} finally {
			await anonymous.dispose();
		}

		// Hiding the reason from the visitor is only acceptable because it is kept somewhere the
		// site's maintainer can read it.
		const log = readServerErrorLog();

		expect(
			log,
			'this request was hidden from the visitor and not written anywhere'
		).toContain( `${ FAILURE_MARKER } ${ token }` );
	} );
} );
