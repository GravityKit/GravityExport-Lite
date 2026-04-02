const { createPlaywrightConfig } = require( '@gravitykit/e2e-bootstrap' );

module.exports = createPlaywrightConfig( {
	setupDir: __dirname,
	testDir: '../tests',
} );
