const path = require( 'path' );
const fs = require( 'fs' );
const { expect } = require( '@playwright/test' );
const { createHelpers, readPorts } = require( '@gravitykit/e2e-bootstrap' );

const setupDir = path.resolve( __dirname, '../setup' );

const base = createHelpers( {
	envPath: path.resolve( __dirname, '../../../.env' ),
	setupDir,
} );

// `createHelpers` calls `api.initFromEnv()` which requires both WP_ENV_URL
// and WP_ENV_PORT in process.env. In Playwright worker processes the .env
// is loaded but WP_ENV_URL is not set, so the fixtures baseUrl is left
// empty. Re-derive the tests baseURL from the ports sidecar (which is
// authoritative for this run) and push it into the api singleton.
const ports = readPorts( setupDir );
const testsBaseURL = `${ process.env.WP_ENV_URL || 'http://localhost' }:${ ports.wpTestsPort }`;
base.api.setConfig( { baseUrl: testsBaseURL } );

const FORM_SETTINGS_PATH = ( formId ) =>
	`/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=gravityexport-lite&id=${ formId }`;

const NOTIFICATIONS_LIST_PATH = ( formId ) =>
	`/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=notification&id=${ formId }`;

const NEW_NOTIFICATION_PATH = ( formId ) =>
	`/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=notification&id=${ formId }&nid=0`;

/**
 * Navigate to GravityExport Lite form settings and wait until the form
 * settings panel is actually present in the DOM (the title alone can
 * resolve while Gravity Forms is still rendering the body).
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} formId
 */
async function goToFormSettings( page, formId ) {
	await page.goto( FORM_SETTINGS_PATH( formId ) );
	// The form-settings form wrapper renders for both the activation and
	// the enabled states, so this is a stable anchor regardless of
	// whether the download URL has been enabled yet.
	await expect(
		page.locator( 'form#gform-settings' ),
		'Form settings panel should be rendered'
	).toBeVisible( { timeout: 15000 } );
}

/**
 * Enable the download URL on a form by clicking the activation button.
 * Idempotent: if URL is already enabled, returns without clicking.
 *
 * @param {import('@playwright/test').Page} page
 * @param {number} formId
 */
async function enableDownloadUrl( page, formId ) {
	await goToFormSettings( page, formId );

	const enable = page.locator(
		'button[name="gform-settings-save"][value="download_url_enable"]'
	);

	if ( await enable.isVisible() ) {
		await submitSettingsForm(
			page,
			'button[name="gform-settings-save"][value="download_url_enable"]'
		);
	}

	// Now the URL field is rendered as a readonly text input on the page.
	await expect(
		page.locator( 'input[name="_gform_setting_hash"]' )
	).toBeVisible();
}

/**
 * Read the currently-rendered download URL from the settings page.
 * Must already be on the GravityExport Lite settings page.
 *
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string>}
 */
async function readDownloadUrl( page ) {
	const value = await page
		.locator( 'input[name="_gform_setting_hash"]' )
		.inputValue();

	if ( ! value ) {
		throw new Error( 'Download URL input is empty — URL is not enabled.' );
	}

	return value;
}

/**
 * Persist the GravityExport Lite settings form via the "Save Settings →" button.
 *
 * @param {import('@playwright/test').Page} page
 */
async function saveSettings( page ) {
	await submitSettingsForm( page, '#gform-settings-save[value="save"]' );
}

/**
 * Submit the GravityExport Lite settings form, preserving the clicked
 * button's name/value so the GravityExport addon action handler receives
 * the right `gform-settings-save` value.
 *
 * The form has `data-js="page-loader"` which can intercept the synthesized
 * click and submit the form without recording the submitter. We bypass that
 * by calling `form.requestSubmit(submitter)` directly so the button's
 * name=value always reaches the server.
 *
 * @param {import('@playwright/test').Page} page
 * @param {string} buttonSelector - Selector for the submit button.
 */
async function submitSettingsForm( page, buttonSelector ) {
	// `waitForLoadState('load')` can resolve against the *current* page if
	// it's already loaded, which races with the form-submit navigation
	// that's about to start. Instead, register a navigation expectation
	// that only resolves when the document leaves and a new one finishes
	// loading. The Settings save POSTs to the same URL and reloads, so
	// any navigation matching the gravityexport-lite subview is good.
	const navigationStarted = page.waitForRequest(
		( req ) =>
			req.url().includes( 'subview=gravityexport-lite' ) &&
			req.method() === 'POST',
		{ timeout: 30000 }
	);
	await page.evaluate( ( selector ) => {
		const btn = document.querySelector( selector );
		if ( ! btn ) {
			throw new Error( `submit button not found: ${ selector }` );
		}
		const form = document.getElementById( 'gform-settings' );
		if ( ! form ) {
			throw new Error( 'gform-settings form missing' );
		}
		form.requestSubmit( btn );
	}, buttonSelector );
	await navigationStarted;
	await page.waitForLoadState( 'domcontentloaded' );
}

/**
 * Issue a download request against the public URL. Uses Playwright's request
 * fixture so the call is independent of the logged-in browser context.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {string} url
 * @returns {Promise<{status: number, headers: object, body: Buffer}>}
 */
async function fetchDownload( request, url ) {
	const response = await request.get( url );
	const body = await response.body();

	return {
		status: response.status(),
		headers: response.headers(),
		body,
	};
}

/**
 * Parse a CSV body into rows. Handles double-quoted fields with embedded
 * commas/newlines. Sufficient for the simple shapes GravityExport produces.
 *
 * @param {Buffer|string} body
 * @returns {string[][]}
 */
function parseCsv( body ) {
	const text = Buffer.isBuffer( body ) ? body.toString( 'utf8' ) : body;
	const rows = [];
	let row = [];
	let field = '';
	let inQuotes = false;

	for ( let i = 0; i < text.length; i++ ) {
		const ch = text[ i ];

		if ( inQuotes ) {
			if ( ch === '"' && text[ i + 1 ] === '"' ) {
				field += '"';
				i++;
			} else if ( ch === '"' ) {
				inQuotes = false;
			} else {
				field += ch;
			}

			continue;
		}

		if ( ch === '"' ) {
			inQuotes = true;
		} else if ( ch === ',' ) {
			row.push( field );
			field = '';
		} else if ( ch === '\n' || ch === '\r' ) {
			if ( ch === '\r' && text[ i + 1 ] === '\n' ) {
				i++;
			}

			row.push( field );
			rows.push( row );
			row = [];
			field = '';
		} else {
			field += ch;
		}
	}

	if ( field.length > 0 || row.length > 0 ) {
		row.push( field );
		rows.push( row );
	}

	return rows.filter( ( r ) => r.some( ( c ) => c.length > 0 ) );
}

const MAIL_URL = () => `${ testsBaseURL }/wp-json/gk-e2e/v1/mail`;
const MAIL_HEADERS = { 'X-E2E-TEST-TOKEN': 'gravitykit-e2e-test' };

/**
 * Whether an error from `fetch` looks like a transient connection-level
 * failure that's worth retrying.
 *
 * Apache in the wp-env container has a 5s KeepAlive timeout. Long-running
 * suites reuse connections from a previous test's request, and the server
 * may have already closed the socket — surfacing as ECONNRESET / EPIPE /
 * "socket hang up". A single retry on a fresh socket reliably succeeds.
 *
 * @param {Error} err
 * @returns {boolean}
 */
function isTransientNetworkError( err ) {
	if ( ! err ) {
		return false;
	}
	const msg = String( err.message || err );
	const code = err.code || err.cause?.code || '';
	return (
		/socket hang up/i.test( msg ) ||
		/ECONNRESET/.test( msg ) ||
		/ECONNREFUSED/.test( msg ) ||
		/EPIPE/.test( msg ) ||
		/fetch failed/i.test( msg ) ||
		code === 'ECONNRESET' ||
		code === 'EPIPE' ||
		code === 'UND_ERR_SOCKET'
	);
}

/**
 * fetch() wrapper that retries on transient connection-level failures.
 *
 * We use Node's global fetch rather than the Playwright APIRequestContext
 * for the mail endpoints because:
 *  1) The APIRequestContext aggressively reuses HTTP connections, and
 *     Apache's keep-alive idle timeout (5s) causes "socket hang up" when
 *     the next test reuses a stale connection.
 *  2) The mail endpoint doesn't need browser cookies — auth is the
 *     X-E2E-TEST-TOKEN header.
 *
 * @param {string} url
 * @param {object} options
 * @returns {Promise<Response>}
 */
async function fetchWithRetry( url, options = {}, { tries = 4, baseDelayMs = 100 } = {} ) {
	let lastError;
	for ( let attempt = 0; attempt < tries; attempt++ ) {
		try {
			return await fetch( url, options );
		} catch ( err ) {
			lastError = err;
			if ( ! isTransientNetworkError( err ) ) {
				throw err;
			}
			// Exponential backoff: 100 → 200 → 400 → 800 ms.
			await new Promise( ( r ) =>
				setTimeout( r, baseDelayMs * 2 ** attempt )
			);
		}
	}
	throw lastError;
}

/**
 * Fetch captured emails from the mail-capture mu-plugin.
 *
 * `request` is accepted for backwards compatibility but ignored — the
 * implementation uses Node fetch with retries (see fetchWithRetry).
 *
 * @returns {Promise<Array>}
 */
async function getCapturedEmails() {
	const response = await fetchWithRetry( MAIL_URL(), { headers: MAIL_HEADERS } );

	if ( ! response.ok ) {
		throw new Error(
			`Mail capture GET failed: ${ response.status } ${ response.statusText }`
		);
	}

	const data = await response.json();
	return data.messages || [];
}

/**
 * Poll the mail-capture endpoint until a message matching the predicate
 * appears, or the timeout expires. The mu-plugin writes the JSON record
 * synchronously inside `pre_wp_mail`, so as soon as the wp-cli call that
 * triggered the notification returns, the message is on disk — but we
 * still poll defensively because PHP opcache + bind-mounts have produced
 * sub-second race windows.
 *
 * @param {(message: object) => boolean} predicate
 * @param {object} [opts]
 * @param {number} [opts.timeoutMs=10000]
 * @param {number} [opts.intervalMs=100]
 * @returns {Promise<object>} The matched message.
 */
async function waitForCapturedEmail( predicate, opts = {} ) {
	const { timeoutMs = 10000, intervalMs = 100 } = opts;
	const deadline = Date.now() + timeoutMs;
	let lastSeenSubjects = [];

	while ( Date.now() < deadline ) {
		const messages = await getCapturedEmails();
		lastSeenSubjects = messages.map( ( m ) => m.subject );
		const hit = messages.find( predicate );
		if ( hit ) {
			return hit;
		}
		await new Promise( ( r ) => setTimeout( r, intervalMs ) );
	}

	throw new Error(
		`waitForCapturedEmail: no message matched predicate within ${ timeoutMs }ms.\n` +
			`Last seen subjects: ${ JSON.stringify( lastSeenSubjects ) }`
	);
}

/**
 * Clear all captured emails. Call in beforeEach to avoid cross-test bleed.
 * Accepts `request` for backwards compatibility but ignores it.
 */
async function clearCapturedEmails() {
	const response = await fetchWithRetry( MAIL_URL(), {
		method: 'DELETE',
		headers: MAIL_HEADERS,
	} );

	if ( ! response.ok ) {
		throw new Error( `Mail capture DELETE failed: ${ response.status }` );
	}
}

/**
 * Read a captured attachment's full bytes from disk. The capture record stores
 * the absolute path inside the WordPress container — `wp-env` shares the
 * uploads dir with the host through the docker volume, so the path resolves
 * directly. Read what we need; cap at a sensible size.
 *
 * @param {object} attachment
 * @returns {Buffer}
 */
function readAttachmentBytes( attachment ) {
	const containerPath = attachment.path;

	if ( ! containerPath ) {
		throw new Error( 'Attachment record has no path' );
	}

	// Map the container path /var/www/html/wp-content/uploads/... to the
	// host-side wp-env volume. wp-env publishes the volume under
	// ~/wp-env/<hash>/tests-WordPress/ but we don't need the host path:
	// reading via shell exec into the container is more reliable than
	// guessing the published location.
	const { execFileSync } = require( 'child_process' );
	const container =
		process.env.GK_E2E_TESTS_CONTAINER || findTestsContainer();

	return execFileSync( 'docker', [ 'exec', container, 'cat', containerPath ], {
		maxBuffer: 50 * 1024 * 1024,
	} );
}

let cachedWpContainer = null;
let cachedCliContainer = null;

function findContainer( filterName, cache ) {
	if ( cache.value ) {
		return cache.value;
	}

	const { execFileSync } = require( 'child_process' );
	const out = execFileSync(
		'docker',
		[ 'ps', '--format', '{{.Names}}', '--filter', `name=${ filterName }` ],
		{ encoding: 'utf8' }
	);

	const name = out.split( '\n' ).find( Boolean );

	if ( ! name ) {
		throw new Error(
			`No container matching '${ filterName }' found. Is wp-env running?`
		);
	}

	cache.value = name.trim();
	return cache.value;
}

/** Container hosting the WordPress webserver for tests (port = wpTestsPort). */
function findTestsContainer() {
	return findContainer( 'tests-wordpress', { value: cachedWpContainer } );
}

/** Container with wp-cli installed (read/write WP state from the shell). */
function findCliContainer() {
	if ( cachedCliContainer ) {
		return cachedCliContainer;
	}
	cachedCliContainer = findContainer( 'tests-cli', {
		get value() {
			return cachedCliContainer;
		},
		set value( v ) {
			cachedCliContainer = v;
		},
	} );
	return cachedCliContainer;
}

/**
 * Run an arbitrary PHP snippet inside the tests-cli container via `wp eval`.
 * Provides a JSON payload on stdin and returns stdout. Use this for surgical
 * state mutations (form/feed meta) that are awkward through the admin UI.
 *
 * @param {object} payload
 * @param {string} php - The PHP body. `$data` is the decoded payload.
 * @returns {string} stdout
 */
function wpEval( payload, php ) {
	const { execFileSync } = require( 'child_process' );
	const container = findCliContainer();
	const wrapped = `$data = json_decode( file_get_contents( 'php://stdin' ), true );\n${ php }`;

	return execFileSync(
		'docker',
		[
			'exec',
			'-i',
			container,
			'wp',
			'--allow-root',
			'eval',
			wrapped,
		],
		{
			input: JSON.stringify( payload ),
			encoding: 'utf8',
		}
	);
}

/**
 * Add a notification to a form via REST. Uses the e2e-fixtures REST namespace
 * for consistency with form/entry seeding, but the actual notification
 * mutation is done via GFAPI through a small in-line PHP exec call.
 *
 * @param {number} formId
 * @param {object} notification - { name, to, subject, message, gravityexport_attachment, gravityexport_attachment_type }
 * @returns {Promise<string>} The created notification id.
 */
async function addNotification( formId, notification ) {
	const out = wpEval(
		{
			formId,
			notification: {
				id: notification.id || `e2e_${ Date.now() }`,
				name: notification.name || 'E2E Notification',
				isActive: true,
				to: notification.to || 'admin@example.test',
				toType: 'email',
				subject: notification.subject || 'E2E Test Notification',
				message: notification.message || 'Body',
				from: '{admin_email}',
				event: 'form_submission',
				gravityexport_attachment: notification.gravityexport_attachment ? '1' : '0',
				gravityexport_attachment_type: notification.gravityexport_attachment_type || 'xlsx',
			},
		},
		`
		$form = GFAPI::get_form( (int) $data['formId'] );
		if ( ! $form ) { fwrite( STDERR, 'no form' ); exit( 1 ); }
		$notifications = isset( $form['notifications'] ) && is_array( $form['notifications'] ) ? $form['notifications'] : [];
		$n = $data['notification'];
		$notifications[ $n['id'] ] = $n;
		$form['notifications'] = $notifications;
		$res = GFAPI::update_form( $form );
		if ( is_wp_error( $res ) ) { fwrite( STDERR, $res->get_error_message() ); exit( 1 ); }
		echo $n['id'];
		`
	);

	return out.trim();
}

/**
 * Submit an entry to a form via fixtures REST (the simplest reliable trigger
 * for `form_submission` notifications without driving the public frontend).
 *
 * @param {number} formId
 * @param {object} values - { fieldId: value }
 * @returns {Promise<number>} entry id
 */
async function submitEntryTriggeringNotifications( formId, values ) {
	const out = wpEval(
		{ formId, values },
		`
		$form = GFAPI::get_form( (int) $data['formId'] );
		if ( ! $form ) { fwrite( STDERR, 'no form' ); exit( 1 ); }
		$entry = $data['values'];
		$entry['form_id'] = $form['id'];
		$entry_id = GFAPI::add_entry( $entry );
		if ( is_wp_error( $entry_id ) ) { fwrite( STDERR, $entry_id->get_error_message() ); exit( 1 ); }
		$entry = GFAPI::get_entry( $entry_id );

		// GFAPI::send_notifications routes through the GF_Notifications
		// background processor when async sending is enabled, which is the
		// default on recent GF versions. The processor queues the work for
		// dispatch on PHP shutdown — but in a one-shot wp-cli context the
		// dispatch never reaches a worker, so wp_mail is never called and
		// our pre_wp_mail hook never sees the notification. Bypass the
		// async path by calling GFCommon::send_notifications directly with
		// the explicit list of notification ids that match the event.
		$ids_to_send = [];
		foreach ( ( $form['notifications'] ?? [] ) as $n ) {
			if ( rgar( $n, 'isActive' ) && rgar( $n, 'event' ) === 'form_submission' ) {
				$ids_to_send[] = $n['id'];
			}
		}
		GFCommon::send_notifications( $ids_to_send, $form, $entry, true, 'form_submission' );

		echo $entry_id;
		`
	);

	return parseInt( out.trim(), 10 );
}

/**
 * Mutate the GravityExport feed meta for a form. Used by tests that need
 * deterministic configuration (disabled fields, custom order, notes flag,
 * transpose, attached notification) without driving the sortable UI.
 *
 * @param {number} formId
 * @param {object} patch - Keys to merge into the feed's `meta` JSON.
 */
function patchExportFeedMeta( formId, patch ) {
	wpEval(
		{ formId, patch },
		`
		$addon = \\GFExcel\\Addon\\GravityExportAddon::get_instance();
		$feed  = $addon->get_feed_by_form_id( (int) $data['formId'] );
		if ( ! $feed ) { fwrite( STDERR, 'no feed' ); exit( 1 ); }
		$settings = isset( $feed['meta'] ) && is_array( $feed['meta'] ) ? $feed['meta'] : [];
		foreach ( $data['patch'] as $key => $value ) {
			$settings[ $key ] = $value;
		}
		$addon->save_feed_settings( (int) $feed['id'], (int) $feed['form_id'], $settings );
		echo 'ok';
		`
	);
}

module.exports = {
	...base,
	expect,
	goToFormSettings,
	enableDownloadUrl,
	readDownloadUrl,
	saveSettings,
	submitSettingsForm,
	fetchDownload,
	parseCsv,
	getCapturedEmails,
	waitForCapturedEmail,
	clearCapturedEmails,
	readAttachmentBytes,
	addNotification,
	submitEntryTriggeringNotifications,
	patchExportFeedMeta,
	wpEval,
	FORM_SETTINGS_PATH,
	NOTIFICATIONS_LIST_PATH,
	NEW_NOTIFICATION_PATH,
};
