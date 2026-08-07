const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	goToFormSettings,
	enableDownloadUrl,
	readDownloadUrl,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Enable Download URL', () => {
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

	test( 'enabling the download URL renders a copyable URL that persists across reloads', async ( {
		page,
	} ) => {
		await goToFormSettings( page, data.form_id );

		// Pristine state: only the activation button is present.
		const activate = page.locator(
			'button[name="gform-settings-save"][value="download_url_enable"]'
		);
		await expect( activate ).toBeVisible();
		await expect(
			page.locator( 'input[name="_gform_setting_hash"]' )
		).toHaveCount( 0 );

		await enableDownloadUrl( page, data.form_id );

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
