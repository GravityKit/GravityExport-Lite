=== Gravity Forms Entries in Excel ===
Contributors: doekenorg
Donate link: https://www.paypal.me/doekenorg
Tags: Gravityforms, Excel, GF, GFExcel, Gravity, Forms, Output, Download, Entries, Export, CSV, Office, xlsx, xls
Requires at least: 4.0
Requires PHP: 5.3
Tested up to: 4.9.4
Stable tag: 1.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Export all Gravity Forms entries to Excel (.xls) via a download button OR via a secret (shareable) url.

== Description ==

Export all entries from your forms directly to Excel, using a unique and secure url. No need to login, or create a
user account for that one person who needs te results. Just copy the url, and give it to the guy who needs it.
It's that simple.

Using Gravity Forms you can export a CSV file, and import it to Excel. But an admin always needs to be involved
and using Excel to import a CSV is a pain in the butt.

The plugin has a lot of event-hooks to make your Excel output exactly how you want it.
Check out the FAQ to find out more.

= Requirements =

* PHP 5.3 or higher (for now; will be dropping 5.3 in near future)
* Gravity Forms 2.0.0 or higher

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `gf-entries-in-excel` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Make sure you have a **unique** `NONCE_SALT` in your `wp-config.php` for security reasons!
1. Go to Forms > Select a form > Settings > Results in Excel to obtain your url
1. Download that Excel file!

== Frequently Asked Questions ==

= I don't want the the metadata like ID, date, and IP in my file =

No problem. You can use the `gfexcel_output_meta_info` or `gfexcel_output_meta_info_{form_id}` hooks to disable
this feature.

Just add this to your `functions.php`:

`
add_filter("gfexcel_output_meta_info","__return_false");
`

= I want to rename the labels, but only in Excel, how can I do this? =

Sure, makes sense. You can override the label hooking into
`gfexcel_field_label`, `gfexcel_field_label_{type}`, `gfexcel_field_label_{type}_{form_id}` or
`gfexcel_field_label_{type}_{form_id}_{field_id}`

The field object is provided as parameter, so you can check for type and stuff programatically.

= How can I change the value of a field in Excel? =

You can override the value by hooking into `gfexcel_field_value`, `gfexcel_field_value_{type}`,
`gfexcel_field_value_{type}_{form_id}` or `gfexcel_field_value_{type}_{form_id}_{field_id}`

The entry array is provided as a parameter, so you can combine fields if you want.

= Can I seperate the fields of an address into multiple columns? =

Great question! Yes you can! You can make use of the following hooks to get that working:
`gfexcel_field_address_seperated`, `gfexcel_field_address_seperated_{form_id}` or
`gfexcel_field_address_seperated_{form_id}_{field_id}`

Just add this to your `functions.php`:

`
add_filter("gfexcel_field_address_seperated","__return_true");
`

= I have a custom field. Can your plugin handle this? =

You should ask yourself, if your field can handle this plugin! But, yes it can. In multiple ways actually.

The default way the plugins renders the output, is by calling `get_value_export` on the field.
All Gravity Forms fields need that function, so make sure that is implemented.
The result is one column with the output combined to one cell per row.

But you can also make your own field-renderer, like this:

1. Make a class that extends `GFExcel\Field\BaseField` (recommended) or extends `GFExcel\Field\AbstractField` or implements `GFExcel\Field\FieldInterface`
1. Return your needed columns and cells by implementing `getColumns` and `getCells`. (See `AddressField` for some inspiration)
1. Add your class via the `gfexcel_transformer_fields` hook as: type => Fully Qualified Classname  (eg. $fields['awesome-type'] => 'MyTheme\Field\MyAwsomeField')

= I don't really like the downloaded file name! =

By now you really should know you can change almost every aspect of this plugin. Don't like the name? Change it using
the `gfexcel_renderer_filename` or `gfexcel_renderer_filename_{form_id}` hooks.

Also you can update title, subject and description metadata of the document by using
`gfexcel_renderer_title(_{form_id})`, `gfexcel_renderer_subject(_{form_id})` and
`gfexcel_renderer_description(_{form_id})`.

= Can I change the sort order of the rows? =

Sure, why not. By default we sort on date of entry in acending order. You can change this, per form,
on the Form settings page (Results in Excel) under "Settings".

= I want to download directly from the forms table without the url! =

Allright! No need to yell! For those situation we've added a bulk option on the forms table.
As a bonus, you can select multiple forms, and it will download all results in one file,
on multiple worksheets (oohhh yeah!)

= How can I disable the hyperlinks on URL-only cells? =
You can disable the hyperlinks by using the `gfexcel_renderer_disable_hyperlinks`-hook.

`
//add this to your functions.php
add_filter('gfexcel_renderer_disable_hyperlinks','__return_true');
`

== Screenshots ==

1. A 'Results in Excel' link is added to the form settings
2. There is your url! Just copy and paste to the browser (or click the download button)
3. Or download it from the list via the bulk selector

== Changelog ==

= 1.2.4 =
* Enhancement: moved away from deprecated PhpExcel to PhpSpreadsheet (Thanks @ravloony).
* Enhancement: composer.json update to wordpress-plugin for easier installation with bedrock.
* Enhancement: Metadata now uses GFExport to get all metadata; so a row now has all metadata. Can still be disabled.
* Feature: New ListField transformer. Splits list fields into it's own excel columns, with newline-seperated values per column.
* Feature: New meta fields transformer. Special filter hooks for meta fields with `gfexcel_meta_value`.
* Feature: New meta subfield transformer for `date_created`. Use `gfexcel_meta_date_created_seperated` to split date and time in 2 columns.
* Bugfix: Plugin hooks later, so filters also work on bulk-download files.

= 1.2.3 =
* Bugfix: Worksheets could contain invalid characters, and break download.

= 1.2.2 =
* Enhancement: If a cell only contains a URL, that URL is set as a link on that cell, for easy access.

= 1.2.1 =
* Translation: Added `Dutch` translation + enabled posibility to translate via Wordpress.org. You can help me out!
* Enhancement: Worksheets now have a title, and of course a `gfexcel_renderer_worksheet_title` hook.

= 1.2.0 =
* (Very cool) Feature: Download Excel output directly from forms table, and (drumroll), download multiple forms in one file!
* Feature: Added `gfexcel_field_disable` filter to disable all fields you want. Fields will be filtered out before handling.
* Feature: Added `gfexcel_output_rows` and `gfexcel_output_columns` filters to have more control over output. Thanks @mircobabini.
* Feature: Added a setting for sort order per form. Also contains some hooks to override that work!

= 1.1.0 =
* Feature: Download counter (starts counting as of this version)
* Feature: SectionField added to disable empty section columns. Disabled by default. Enable with `gfexcel_field_section_enabled` hook (return true).
* Feature: FileUploadField added to disable file upload columns. Enabled by default. Dnable with `gfexcel_field_fileuploads_enabled` hook (return false).
* Update: Wait until plugins are loaded. Need to be sure Gravity Forms is active. This caused a problem in some multisite implementations.
* Bugfix: Changed the permalink registration so it works with multisite combined with the GF API (thanks for the assist @zitomerh). No need to reactivate the plugin now.
* Bugfix: In Standard URL permalink structure, the hash wasn't escaped properly

= 1.0.2 =
* Bugfix: Only 20 results were beging returned by the GFAPI
* The title of a form could not be longer than 31 characters

= 1.0.1 =
* Updated readme
* Removed unnecessary assets

= 1.0 =
* First release

== Credits ==
- Logo by Karlo Norg | [SQUID Media](https://www.squidmedia.nl)
- Banner Photo by [Matt Benson](https://unsplash.com/@mattgyver) on [Unsplash](https://unsplash.com/photos/rHbob_bEsSs)