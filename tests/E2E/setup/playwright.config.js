const { createPlaywrightConfig, readPorts } = require( '@gravitykit/e2e-bootstrap' );

// wp-env:cli targets the `tests-cli` container (wpTestsPort, 8801), so plugins,
// licenses, and REST routes are only present on the tests instance. Point
// Playwright and the fixtures API at the same port; otherwise global setup,
// login, and tests hit the empty dev instance and REST returns the login page
// instead of JSON (see @debugging/14).
const ports = readPorts( __dirname );
const testsBaseURL = `${ process.env.WP_ENV_URL || 'http://localhost' }:${ ports.wpTestsPort }`;

module.exports = createPlaywrightConfig( {
	setupDir: __dirname,
	testDir: '../tests',
	use: { baseURL: testsBaseURL },
	webServer: { url: testsBaseURL },
} );
