const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	patchExportFeedMeta,
	addNotification,
	submitEntryTriggeringNotifications,
	waitForCapturedEmail,
	readAttachmentBytes,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Attach single-entry export to a notification (XLSX)', () => {
	let data;

	test.beforeEach( async () => {
		data = await fixtures.createFromTemplate( {
			template: 'simple',
			skipView: true,
		} );
	} );

	test.afterEach( async () => {
		if ( data?.test_id ) {
			await cleanup( data.test_id );
		}
	} );

	test( 'a new entry triggers the configured notification with an XLSX attachment', async ( {
		page,
	} ) => {
		await enableDownloadUrl( page, data.form_id );

		// Unique subject so we can pick our message out of the shared
		// mail-capture inbox (parallel workers share the same uploads dir).
		const subject = `E2E XLSX ${ data.test_id }`;

		const notificationId = await addNotification( data.form_id, {
			name: 'E2E XLSX Attachment',
			to: 'xlsx-target@example.test',
			subject,
		} );

		patchExportFeedMeta( data.form_id, {
			attachment_notification: notificationId,
			file_extension: 'xlsx',
		} );

		const entryId = await submitEntryTriggeringNotifications( data.form_id, {
			'1': 'XlsxSpec',
			'2': 'xlsx-spec@gravitykit.test',
		} );
		expect( entryId ).toBeGreaterThan( 0 );

		// Poll the capture endpoint via Node fetch with retry — Playwright's
		// APIRequestContext reuses keep-alive connections and Apache's idle
		// timeout can drop them between specs (manifesting as "socket hang
		// up").
		const message = await waitForCapturedEmail(
			( m ) => m.subject === subject
		);
		expect( message.to ).toContain( 'xlsx-target@example.test' );
		expect( message.attachments ).toHaveLength( 1 );

		const attachment = message.attachments[ 0 ];
		expect( attachment.filename ).toMatch( /\.xlsx$/i );
		expect( attachment.size ).toBeGreaterThan( 512 );

		const bytes = readAttachmentBytes( attachment );
		// XLSX is a ZIP container — magic bytes are "PK".
		expect(
			bytes.slice( 0, 2 ).toString( 'binary' ),
			'Attachment body should be a valid ZIP/XLSX container'
		).toBe( 'PK' );
	} );
} );
