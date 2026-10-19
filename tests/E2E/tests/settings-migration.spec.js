const { test } = require( '@playwright/test' );
const { expect, runSupportScript } = require( '../helpers/test-helpers' );

// The unit tests cannot load the Gravity Forms add-on this migration saves through.
test( 'the 2.0 settings migration moves the old settings and leaves every other site as it was', async () => {
	const output = runSupportScript( 'settings-migration.php' );

	expect( output ).toContain( '4 of 4 passed' );
	expect( output ).not.toContain( 'FAIL' );
} );
