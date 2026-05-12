const { createPlaywrightConfig, readPorts } = require( '@gravitykit/e2e-bootstrap' );

// wp-env:cli targets the `tests-cli` container (wpTestsPort, 8801), so plugins,
// licenses, and REST routes are only present on the tests instance. Point
// Playwright and the fixtures API at the same port; otherwise global setup,
// login, and tests hit the empty dev instance and REST returns the login page
// instead of JSON (see @debugging/14).
const ports = readPorts( __dirname );

// Build the tests base URL. If WP_ENV_URL already includes a port we honor it
// as-is; otherwise we append the resolved wpTestsPort. Naïvely concatenating
// `:${ports.wpTestsPort}` would produce invalid URLs like
// `http://localhost:8888:8801` when someone exports a fully-specified URL.
const testsBaseURL = ( () => {
	const raw = process.env.WP_ENV_URL || 'http://localhost';
	try {
		const u = new URL( raw );
		if ( u.port ) {
			return u.toString().replace( /\/$/, '' );
		}
		u.port = String( ports.wpTestsPort );
		return u.toString().replace( /\/$/, '' );
	} catch {
		return `${ raw }:${ ports.wpTestsPort }`;
	}
} )();

module.exports = createPlaywrightConfig( {
	setupDir: __dirname,
	testDir: '../tests',
	use: { baseURL: testsBaseURL },
	webServer: { url: testsBaseURL },
} );
