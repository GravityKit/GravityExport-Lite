const { test } = require( '@playwright/test' );
const {
	expect,
	fixtures,
	cleanup,
	enableDownloadUrl,
	patchExportFeedMeta,
	addNotification,
	submitEntryTriggeringNotifications,
	getCapturedEmails,
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
		request,
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

		const allMessages = await getCapturedEmails( request );
		const messages = allMessages.filter( ( m ) => m.subject === subject );
		expect(
			messages,
			'Exactly one notification with our spec-scoped subject'
		).toHaveLength( 1 );

		const message = messages[ 0 ];
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
