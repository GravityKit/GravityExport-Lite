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
	parseCsv,
} = require( '../../helpers/test-helpers' );

test.describe( 'GravityExport Lite — Attach single-entry export to a notification (CSV)', () => {
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

	test( 'a new entry triggers the configured notification with a CSV attachment of just that entry', async ( {
		page,
		request,
	} ) => {
		await enableDownloadUrl( page, data.form_id );

		// Subject is unique per spec/run so we can pick our own message out
		// of the shared mail-capture inbox without racing parallel workers.
		const subject = `E2E CSV ${ data.test_id }`;

		const notificationId = await addNotification( data.form_id, {
			name: 'E2E CSV Attachment',
			to: 'csv-target@example.test',
			subject,
		} );

		// Point the addon at this notification and request CSV format.
		patchExportFeedMeta( data.form_id, {
			attachment_notification: notificationId,
			file_extension: 'csv',
		} );

		const entryId = await submitEntryTriggeringNotifications( data.form_id, {
			'1': 'Spec',
			'2': 'spec@gravitykit.test',
		} );
		expect( entryId ).toBeGreaterThan( 0 );

		const allMessages = await getCapturedEmails( request );
		const messages = allMessages.filter( ( m ) => m.subject === subject );
		expect(
			messages,
			'Exactly one notification with our spec-scoped subject should have been sent'
		).toHaveLength( 1 );

		const message = messages[ 0 ];
		expect( message.to ).toContain( 'csv-target@example.test' );
		expect(
			message.attachments,
			'Notification carries exactly one attachment'
		).toHaveLength( 1 );

		const attachment = message.attachments[ 0 ];
		expect( attachment.filename ).toMatch( /\.csv$/i );
		expect( attachment.size ).toBeGreaterThan( 0 );

		const bytes = readAttachmentBytes( attachment );
		const rows = parseCsv( bytes );

		// Header + 1 row for the just-submitted entry only.
		expect(
			rows.length,
			'CSV must contain exactly the one entry that triggered the notification (header + 1 row)'
		).toBe( 2 );

		const header = rows[ 0 ];
		const firstNameIdx = header.indexOf( 'First Name' );
		expect( rows[ 1 ][ firstNameIdx ] ).toBe( 'Spec' );
	} );
} );
