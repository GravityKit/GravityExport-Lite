const { test, expect } = require('@playwright/test');

test.describe('GravityExport Lite — Activation Smoke Test @smoke', () => {

	test('Plugin activates without fatal PHP errors', async ({ page } ) => {
		await page.goto('/wp-admin/plugins.php');

		await expect(
			page.getByText('The site is experiencing technical difficulties')
		).not.toBeVisible();

		await expect(
			page.locator('[data-plugin*="gfexcel.php"] .deactivate a')
		).toBeVisible();
	});

	test('WordPress admin dashboard loads cleanly after activation', async ({ page }) => {
		await page.goto('/wp-admin/');

		await expect(
			page.getByText('The site is experiencing technical difficulties')
		).not.toBeVisible();

		await expect(page.locator('#wpadminbar')).toBeVisible();
	});

	test('Gravity Forms menu loads without errors', async ({ page }) => {
		await page.goto('/wp-admin/admin.php?page=gf_edit_forms');

		await expect(
			page.getByText('The site is experiencing technical difficulties')
		).not.toBeVisible();

		await expect(page.locator('#wpbody')).toBeVisible();
	});

});
