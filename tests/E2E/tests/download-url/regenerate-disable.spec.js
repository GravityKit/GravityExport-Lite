const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	readDownloadUrl,
	fetchDownload,
	submitSettingsForm,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Regenerate and Disable Download URL', () => {
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

	test( 'regenerating swaps the hash and kills the previous URL; disabling removes access entirely', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );
		const originalUrl = await readDownloadUrl( page );

		// Confirm the original URL works first so we have a clean baseline.
		const initialResponse = await fetchDownload( request, originalUrl );
		expect( initialResponse.status, 'baseline URL should be live' ).toBe( 200 );

		// Regenerate.
		await submitSettingsForm( page, '#download-url-reset' );
		await expect(
			page.locator( 'input[name="_gform_setting_hash"]' )
		).not.toHaveValue( originalUrl );

		const newUrl = await readDownloadUrl( page );
		expect(
			newUrl,
			'Regenerate must produce a different URL'
		).not.toBe( originalUrl );
		expect( newUrl ).toMatch( /\/gravityexport-lite\/[a-f0-9]+$/ );

		// Old URL must be dead.
		const oldResponse = await fetchDownload( request, originalUrl );
		expect(
			oldResponse.status,
			'After regenerate the previous URL must not return a file'
		).not.toBe( 200 );

		// New URL still works.
		const liveResponse = await fetchDownload( request, newUrl );
		expect( liveResponse.status, 'New URL serves the export' ).toBe( 200 );

		// Disable. Activation button is back; URL input is gone.
		await submitSettingsForm( page, '#download-url-disable' );
		await expect(
			page.locator(
				'button[name="gform-settings-save"][value="download_url_enable"]'
			)
		).toBeVisible();
		await expect(
			page.locator( 'input[name="_gform_setting_hash"]' )
		).toHaveCount( 0 );

		// And the URL is no longer fulfillable.
		const disabledResponse = await fetchDownload( request, newUrl );
		expect(
			disabledResponse.status,
			'Disabled URL must not serve the file'
		).not.toBe( 200 );
	} );
} );
