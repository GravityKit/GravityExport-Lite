const { test } = require( '@playwright/test' );
const { expect, runSupportScript } = require( '../../helpers/test-helpers' );

// GEXPLIT-26 was fixed three times. The first gate opened on WP_DEBUG, the second on WP_DEBUG plus
// WP_DEBUG_DISPLAY, and both still handed an anonymous download the exception message, a stack
// trace, server paths and version details. Those constants describe how a site reports errors, not
// who may read them.
//
// The browser suite cannot turn those constants on for one request, so this runs against a WP-CLI
// process that defines both before WordPress loads. Without that, a run could not tell the gate
// that shipped from either of the two it replaced.
test( 'the debug constants do not open the failure details to a visitor', async () => {
	const output = runSupportScript( 'diagnostics-gate.php', {
		exec: 'define("WP_DEBUG", true); define("WP_DEBUG_DISPLAY", true);',
	} );

	expect( output ).toContain( 'passed' );
	expect( output ).not.toContain( 'FAIL' );
} );
