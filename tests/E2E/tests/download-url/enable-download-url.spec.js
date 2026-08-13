const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	goToFormSettings,
	readDownloadUrl,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Download URL', () => {
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

	test( 'opening the settings renders a copyable URL that persists across reloads', async ( {
		page,
	} ) => {
		await goToFormSettings( page, data.form_id );

		// The first visit mints the link, so there is nothing to activate and the
		// download form is usable immediately.
		await expect(
			page.locator(
				'button[name="gform-settings-save"][value="download_url_enable"]'
			)
		).toHaveCount( 0 );
		await expect(
			page.locator( 'input[name="_gform_setting_hash"]' )
		).toBeVisible();
		await expect(
			page.locator( '.gk-gravityexport-download-file button[type="submit"]' )
		).toBeVisible();

		const url = await readDownloadUrl( page );

		expect(
			url,
			'URL should be on the gravityexport-lite endpoint with a hex hash suffix'
		).toMatch( /\/gravityexport-lite\/[a-f0-9]+$/ );

		// Regenerate, Disable, Copy controls are visible once enabled.
		await expect( page.locator( '#download-url-reset' ) ).toBeVisible();
		await expect( page.locator( '#download-url-disable' ) ).toBeVisible();
		await expect(
			page.locator( 'button.copy-attachment-url' )
		).toBeVisible();

		// Reload — URL must survive.
		await page.reload();
		await expect(
			page.locator( 'input[name="_gform_setting_hash"]' )
		).toHaveValue( url );
	} );
} );
