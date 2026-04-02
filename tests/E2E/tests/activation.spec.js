const { test, expect } = require( '@playwright/test' );

test.describe( 'Plugin Activation', () => {
	test( 'plugin is active', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );
		const plugin = page.locator( '[data-plugin*="gfexcel.php"]' );
		await expect( plugin ).toBeVisible();
		await expect( plugin.locator( '.deactivate' ) ).toBeVisible();
	} );

	test( 'admin bar is visible', async ( { page } ) => {
		await page.goto( '/' );
		await expect( page.locator( '#wpadminbar' ) ).toBeVisible();
	} );

	test( 'no PHP fatal errors on plugin load', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );
		await expect( page.locator( '.error, .notice-error' ).first() ).not.toBeVisible();
	} );
} );
