const path = require( 'path' );
const fs = require( 'fs' );
const { generateWpEnvConfig, loadEnv } = require( '@gravitykit/e2e-bootstrap' );

loadEnv( path.resolve( __dirname, '../../../.env' ) );

// Ensure the host-side capture directory exists before wp-env starts so the
// bind-mount resolves cleanly. Mode 0777 because both the host user (cli
// writes) and Apache's www-data (REST reads) need access, and the simplest
// way to guarantee that across container UIDs is fully permissive bits.
const mailCaptureHostDir = path.resolve( __dirname, 'mail-capture' );
if ( ! fs.existsSync( mailCaptureHostDir ) ) {
	fs.mkdirSync( mailCaptureHostDir, { recursive: true, mode: 0o777 } );
}
fs.chmodSync( mailCaptureHostDir, 0o777 );

generateWpEnvConfig( {
	outputDir: __dirname,
	pluginPath: '../../..',
	additionalLifecycleCommands: [],
	additionalMappings: {
		// wp_mail short-circuit + REST inspection endpoint used by notification specs.
		'wp-content/mu-plugins/e2e-mail-capture.php': './mu-plugins/e2e-mail-capture.php',
		// Bind-mounted capture directory shared between tests-wordpress and
		// tests-cli. Avoids the cross-container UID mismatch that prevented
		// the cli (host UID) from writing into a www-data-owned uploads dir.
		'wp-content/e2e-mail-capture': './mail-capture',
	},
} ).catch( ( err ) => {
	console.error( err );
	process.exit( 1 );
} );
