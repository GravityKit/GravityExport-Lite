const path = require( 'path' );
const { generateWpEnvConfig, loadEnv } = require( '@gravitykit/e2e-bootstrap' );

loadEnv( path.resolve( __dirname, '../../../.env' ) );

generateWpEnvConfig( {
	outputDir: __dirname,
	pluginPath: '../../..',
	additionalLifecycleCommands: [],
} ).catch( ( err ) => {
	console.error( err );
	process.exit( 1 );
} );
