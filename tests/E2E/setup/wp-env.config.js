const path = require( 'path' );
const { generateWpEnvConfig, loadEnv } = require( '@gravitykit/e2e-bootstrap' );

loadEnv( path.resolve( __dirname, '../../../.env' ) );

generateWpEnvConfig( {
	outputDir: __dirname,
	pluginPath: '../../..',
	additionalLifecycleCommands: [],
	additionalMappings: {
		// wp_mail short-circuit + REST inspection endpoint used by notification specs.
		'wp-content/mu-plugins/e2e-mail-capture.php': './mu-plugins/e2e-mail-capture.php',
	},
} ).catch( ( err ) => {
	console.error( err );
	process.exit( 1 );
} );
