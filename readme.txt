=== GravityExport Lite for Gravity Forms ===
Contributors: gravityview, doekenorg
Donate link: https://www.gravitykit.com/extensions/gravityexport/?utm_source=plugin&utm_campaign=gravityexport-lite&utm_content=readme-donate
Tags: Gravity Forms, GravityForms, Excel, Export, Entries
Requires at least: 4.0
Requires PHP: 7.2
Tested up to: 6.7
Stable tag: 2.3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Export all Gravity Forms entries to Excel (.xlsx) or CSV via a download button or a secret shareable URL.

== Description ==

> ### GravityExport (Gravity Form Entries in Excel) is the ultimate no-hassle solution for exporting data from Gravity Forms.
> Powerful new functionality is available with GravityExport! Save exports to FTP & Dropbox, export as PDF, and format exports for data analysis.
>
> [Learn more about GravityExport](https://www.gravitykit.com/extensions/gravityexport/?utm_source=plugin&utm_campaign=gravityexport-lite&utm_content=readme-learn-more)

### Export entries using a secure URL

When you configure a new export, the plugin will generate a secure download URL that you can share with anyone who needs the data (No need to log in!). Reports will automatically update as new entries are added.

#### GravityExport Lite includes many features:

- Limit access to downloads—either make a URL public or require users to be logged-in with correct permissions
- Download reports from multiple forms at once
- Export entry notes along with entries
- Transpose data (instead of one entry per-row, it would be one entry per column)
- Attach entry exports to notifications

[youtube https://youtu.be/diqNgFCguM4]

### Export Gravity Forms entries directly to Excel (.xlsx)

Export your entries directly to .xlsx format. No more wasting time importing your CSV files into Excel and re-configuring columns.

### Export Gravity Forms submissions as CSV

If you'd prefer to have your reports generated as CSV, GravityExport Lite makes it easy.

### Add search filters to the URL

Once you have your download URL, you can easily [filter by date range and field value](https://gfexcel.com/docs/filtering/).

### Configure export fields

Save time generating exports in Gravity Forms: Configure the fields that are included in your CSV or Excel export. No need to set up every time!

### Documentation & support

If you have any questions regarding GravityExport Lite, [check out our documentation](https://docs.gravitykit.com/category/791-gravityexport?utm_source=plugin&utm_campaign=gravityexport-lite&utm_content=readme-checkout-docs).

If you need further assistance, [read this first](https://wordpress.org/support/topic/read-me-first-9/) and our support team will gladly give you a helping hand!

#### Requirements

* PHP 7.2
* `php-xml` and `php-zip` libraries. The plugin will check for those.
* Gravity Forms 2.5 or higher

<hr>

### Gain additional powerful functionality

The [full version of GravityExport](https://www.gravitykit.com/extensions/gravityexport/?utm_source=plugin&utm_campaign=gravityexport-lite&utm_content=readme-full-version) unlocks these game-changing features:

* 📄 **PDF Export**
GravityExport supports exporting entries as PDF! You can choose to have a PDF generated for each entry or one PDF that includes all entries. You can also customize the PDF output by adjusting the size, orientation, and more.
* 📦 **Dropbox integration**
Save your form data directly to Dropbox.
* 👩🏽‍💻 **Send reports to FTP**
Store reports on your own FTP server! GravityExport supports the SFTP, FTP + SSL, and FTP protocols.
* ⬇️ **Multiple download URLs**
Create multiple export URLs that output to different formats and include different fields.
* 📊 **Export data ready for analysis**
Make it easier to process your Gravity Forms data by splitting fields with multiple values into different rows.

We've written an article that contains all you need to know about [exporting data from Gravity Forms](https://www.gravitykit.com/exporting-gravity-forms-to-excel/?utm_source=plugin&utm_campaign=gravityexport-lite&utm_content=readme-all-need-to-know).

#### Credits

- The GravityExport Lite plugin was created by [Doeke Norg](https://doeken.org)

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `gravityexport-lite` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Forms > Select a form > Settings > Results in Excel to obtain your URL
1. Download that Excel file!

== Frequently Asked Questions ==

= I don't want the metadata like ID, date, and IP in my file =

No problem. You can use the `gfexcel_output_meta_info` or `gfexcel_output_meta_info_{form_id}` hooks to disable
this feature. Or (since version 1.4.0) you can select individual fields you want to exclude on the settings page.

Just add this to your `functions.php`:

`
add_filter( 'gfexcel_output_meta_info', '__return_false' );
`

= I want to rename the labels, but only in Excel, how can I do this? =

Sure, makes sense. You can override the label hooking into
`gfexcel_field_label`, `gfexcel_field_label_{type}`, `gfexcel_field_label_{type}_{form_id}` or
`gfexcel_field_label_{type}_{form_id}_{field_id}`

The field object is provided as parameter, so you can check for type and stuff programmatically.

= How can I change the value of a field in Excel? =

You can override the value by hooking into `gfexcel_field_value`, `gfexcel_field_value_{type}`,
`gfexcel_field_value_{type}_{form_id}` or `gfexcel_field_value_{type}_{form_id}_{field_id}`

The entry array is provided as a parameter, so you can combine fields if you want.

= Can I separate the fields of an address into multiple columns? =

Great question! Yes you can! You can set it on the settings page, or make use of the following hooks to get that working:
`gfexcel_field_separated_{type}_{form_id}_{field_id}` where every variable is optional.

= I have a custom field. Can your plugin handle this? =

Yes it can, in multiple ways:

The default way the plugins renders output is by calling `get_value_export` on the field.
All Gravity Forms fields need that function, so make sure that is implemented.
The result is one column with the output combined to one cell per row.

But you can also make your own field renderer, like this:

1. Make a class that extends `GFExcel\Field\BaseField` (recommended) or extends `GFExcel\Field\AbstractField` or implements `GFExcel\Field\FieldInterface`
1. Return your needed columns and cells by implementing `getColumns` and `getCells`. (See `AddressField` for some inspiration)
1. Add your class via the `gfexcel_transformer_fields` hook as: `type => Fully Qualified Classname`  (e.g., `$fields['awesome-type'] => 'MyTheme\Field\MyAwesomeField'`)

= How can I change the downloaded file name? =

By now you really should know you can change almost every aspect of this plugin. Don't like the name?
Change it using the settings page, or by using the `gfexcel_renderer_filename` or `gfexcel_renderer_filename_{form_id}` hooks.

Also you can update title, subject and description metadata of the document by using
`gfexcel_renderer_title(_{form_id})`, `gfexcel_renderer_subject(_{form_id})` and
`gfexcel_renderer_description(_{form_id})`.

= Can I change the sort order of the rows? =

Sure, why not. By default, we sort on date of entry in ascending order. You can change this, per form,
on the Form settings page (GravityExport Lite) under "General settings".

= I want to download directly from the forms table without the URL! =

You're in luck: for those situations we've added a bulk option on the forms table.
As a bonus, you can select multiple forms, and it will download all results in one file,
on multiple worksheets (!!!)

= How can I disable the hyperlinks on URL-only cells? =
You can disable the hyperlinks by using the `gfexcel_renderer_disable_hyperlinks`-hook.

`
//add this to your functions.php
add_filter('gfexcel_renderer_disable_hyperlinks','__return_true');
`

= My numbers are formatted as a string, how can I change the cell type? =

A number field is formatted as a number, but most fields default to a string.
As of this moment, there are 3 field types. `Boolean`,`String` and `Numeric`. You can set these per field.
`
//add this to your functions.php
use GFExcel\Values\BaseValue;

add_filter('gfexcel_value_type',function($type, $field) {
    if($field->formId == 1 && $field->id == 2) {
        //Possible values are 'bool', 'string' or 'numeric',
        //or, use the constant, preferred:
        return BaseValue::TYPE_NUMERIC; //TYPE_STRING, TYPE_BOOL
    }
}, 10, 2);
`

= I'd like to add a hyperlink to a specific field =
Since most values are Value Objects, we can interact with them, and trigger a `setUrl` function on a value.
`
//add this to your functions.php
add_filter('gfexcel_value_object',function($value, $field) {
    if($field->formId == 1 && $field->id == 2) {
        $value->setUrl('https://wordpress.org');
    }
}, 10, 2);
`

= I've added some notes, where are they? =
By default the notes are disabled for performance. If you'd like to add these to the row you can activate this like so:

`
//add this to your functions.php
add_filter('gfexcel_field_notes_enabled','__return_true');
//or
add_filter('gfexcel_field_notes_enabled_{formid}','__return_true'); // e.g., gfexcel_field_notes_enabled_2
`

= How do I add colors? It's all too boring in Excel. =

Definitely! You get to change: text color, background color, bold and italic. If that is not enough, maybe you should add clip art!

`
//add this to your functions.php
add_filter('gfexcel_value_object', function (BaseValue $value, $field, $is_label) {
    // Need to know if this field is a label?
    if (!$is_label) {
        return $value;
    }

    $value->setColor('#ffffff'); //font color, needs a six character color hexcode. #fff won't cut it here.
    $value->setBold(true); // Bold text
    $value->setItalic(true); // Italic text (to be combined with bold)
    $value->setBackgroundColor('#0085BA'); // background color

    // $field is the GF_Field object, so you can use that too for some checks.
    return $value;
}, 10, 3);
`

= I don't have enough memory! =

Yes, this can happen. Unfortunately, this isn't something that can be fixed without modifying your server configuration.
As a default, WordPress allocates 40 MB of memory. Because the plugin starts the rendering pretty early, it has most of it available.
But every cell to be rendered (even if it's empty) takes up about 1KB of memory. This means that you have (roughly)
`40 MB * 1024 KB = 40,960 Cells`. I say roughly, because we also use some memory for calculations and retrieving the data.
If you're around this cell-count, and the renderer fails; try to upgrade the `WP_MEMORY_LIMIT`. Checkout [WooCommerce's Docs](https://docs.woocommerce.com/document/increasing-the-wordpress-memory-limit/) for some tips.

= Can I hide a row, but not remove it? =

You can hide a row by adding a hook. Checkout this example:

`add_filter('gfexcel_renderer_hide_row', function ($hide, $row) {
     foreach ($row as $column) {
         if ($column->getFieldId() === 1 && empty($column->getValue())) {
             return true; // hide rows with an empty field 1
         }
     }
     return $hide; // don't forget me!
 }, 10, 2);`

== Screenshots ==

1. A 'GravityExport' link is added to the form settings
2. There is your URL! Just copy and paste to the browser (or click the download button)
3. Or download it from the list via the bulk selector

== Changelog ==

= 2.3.6 on December 18, 2024 =

* Fixed: Type errors on fields no longer cause exports to fail. Values become empty, and errors are logged.
* Enhancement: Added a `gfexcel_field_checkbox_empty` filter to control checkbox output, when no option was selected.

= 2.3.5 on November 25, 2024 =

* Fixed: PHP notice in WordPress 6.7 caused by initializing product translations too early.

= 2.3.4 on November 6, 2024 =

* Fixed: Critical error in combination with GravityExport and PHP 8.

= 2.3.3 on October 29, 2024 =

* Enhancement: Added a `gfexcel_field_fileuploads_force_download` filter to control whether file upload links open in the browser or download directly.

= 2.3.2 on October 14, 2024 =

* Fixed: Added compatibility for servers that miss the `iconv` or `mbstring` PHP extension.
* Fixed: Critical error when downloading an export file.

= 2.3.1 on July 4, 2024 =

* Updated: Release to wordpress.org was missing files.

= 2.3.0 on July 4, 2024 =

* Updated: PhpSpreadsheet to [1.19.0](https://github.com/PHPOffice/PhpSpreadsheet/releases/tag/1.19.0).
* Enhancement: Added a `gfexcel_renderer_csv_output_encoding` hook to set the character encoding on the CSV.

= 2.2.0 on March 8, 2024 =

* Added: Option to secure the download link embed shortcode with a secret key.
* Added: New button to easily copy the download link embed shortcode.
* Bugfix: Nested forms with missing entries could trigger a critical error.

= 2.1.0 on September 25, 2023 =

* Bugfix: Data from the old nested form could be exported if the nested form was changed.
* Bugfix: Nested form fields could end up in different columns.
* Enhancement: Added global `Use admin label` setting.
* Enhancement: Moved dependencies to a custom namespace to avoid collision with other plugins.
* Added: German translation

__Developer Updates:__

* Added: `gk/gravityexport/settings/use-admin-labels` hook.
* Added: `gk/gravityexport/field/use-admin-labels` hook.
* Added: `gk/gravityexport/field/nested-form/export-field` hook to dynamically add other nested form fields to the export.

__Developers__: This might be a breaking change to some plugins, if they directly reference any of the dependencies.

= 2.0.6 on July 29, 2023 =

* Bugfix: Checkbox lists without values in the entry could cause problems on transposed exports.

= 2.0.5 on July 13, 2023 =

* Bugfix: Single option survey fields were no longer exported.

= 2.0.4 on July 7, 2023 =

* Bugfix: Prevents throwing errors on a malformed notification.

= 2.0.3 on June 7, 2023 =

* Bugfix: Filtering on URL's didn't work on the old download URL structure anymore.

= 2.0.2 on June 5, 2023  =

* Bugfix: Attachments could have the wrong fields.

= 2.0.1 on June 2, 2023 =

* Bugfix: The notification for the migration could throw an exception in some instances.

= 2.0 on May 29, 2023 =

* **This is a major update!** Enjoy the much nicer feed configuration interface!
* Changed: The minimum version of Gravity Forms is now 2.5
* Enhancement: Fields can now be enabled or disabled all at once on the form settings page.
* Enhancement: Meta fields can now be (de)selected all at once on the plugin settings page.
* Enhancement: Meta fields with array values are now properly deconstructed on export.
* Enhancement: Added support for GravityPerks Media Library ID fields.
* Enhancement: Upload fields can now split into multiple rows (GravityExport).
* Security: Upload fields now show the private download URL to avoid enumeration on public files.

__Developer Updates:__

* This is a major release behind the scenes; we have rewritten much of the plugin to better integrate with Gravity Forms feed add-on framework.
* Modified: Changed the textdomain of the plugin to `gk-gravityexport-lite`.

= 1.11.4 on November 30, 2022 =

* Enhancement: Added `Entry ID` as a sorting option. Useful when duplicate entry dates exist.
* Bugfix: on PHP 8 an array value threw an error instead of silently failing.

= 1.11.3 on September 29, 2022 =

* Enhancement: List fields column labels can now be manipulated by the `gfexcel_field_label` filter.

= 1.11.2 on July 19, 2022 =

* Enhancement: Added `date_updated` as a default exported meta field.
* Bugfix: Filtering using `in` and `not in` operators in the URL query string did not work.
* Bugfix: Exported multiselect fields (e.g., checkboxes and nested forms) could be missing a separator between values if one value is a `0`.

= 1.11.1 on June 20, 2022 =

* Enhancement: Nested form field values are formatted properly through transformers.
* Bugfix: Sort order of nested form fields could be swapped on some entries.
* Bugfix: PDFs displaying field names as a vertical column could contain extra empty columns.
* Bugfix: Download of nested forms could fail on PHP 8.

= 1.11 on January 31, 2022 =

* Feature: Survey Likert fields can return the score instead of the value by applying the `gfexcel_field_likert_use_score` hook.
* Enhancement: Updated all `gfexcel_renderer_csv_*` hooks to include the form id.
* Bugfix: Possible errors when using `gform_export_separator` callbacks with 2 expected parameters.

= 1.10.1 on December 10, 2021 =

* Bugfix: Fatal error when using the plugin with PHP <7.4.

= 1.10 on December 10, 2021 =

* Enhancement: Support for Gravity Perks Nested Forms.
* Enhancement: `gfexcel_field_value` filters will also be applied on subfields of separable fields.
* Enhancement: `gform_export_separator` filter will also be used to determine the delimiter.
* Enhancement: `gform_include_bom_export_entries` filter will also be used to determine BOM character use.
* Bugfix: Default value of file upload was not available without saving the general settings once.
* Bugfix: Gravity Perks: Live Preview (show hidden) would not work properly.

= 1.9.3 on September 30, 2021 =

* Bugfix: Feed settings page would break when using Gravity Forms ≥2.5.10.1.

= 1.9.2 on September 13, 2021 =

* Bugfix: Fatal error when using Gravity Forms ≤2.4.23.
	- We highly recommend upgrading to Gravity Forms >2.5.

= 1.9.1 on September 8, 2021 =

* **GravityExport Lite** requires PHP 7.2
* Bugfix: Fatal error when trying to download an export file.

= 1.9.0 on September 7, 2021 =

* **Gravity Forms Entries in Excel is now known as GravityExport Lite**
	- Same plugin and functionality you love!
	- Internal code restructuring ensures better extensibility and facilitates new feature development (coming in future versions!)
	- Ready to be used with [GravityExport](https://www.gravitykit.com/extensions/gravityexport/) that brings additional functionality to an already full-featured plugin.
* Enhancement: Improved Gravity Forms 2.5 compatibility.

__Developer Updates:__

**Please note that `gfexcel_*` hooks will be gradually renamed while retaining backward compatibility.**

* Enhancement: Removed all `displayOnly` fields from the export list like the normal export function.
* Enhancement: Added a `gfexcel_output_sorting_options` filter to set sorting options.
* Enhancement: Added a `gfexcel_hash_form_id` filter to get form ID from the unique URL hash value.
* Enhancement: Added a `gfexcel_hash_feed_data` filter to get feed object from the unique URL hash value.
* Enhancement: Added a `gfexcel_get_entries_<form_id>_<feed_id>` filter to override default logic responsible for querying DB entries.

= 1.8.14 on July 20, 2021 =
* Enhancement: Improved usability on small screens and enhanced accessibility.
* Bugfix: Incorrect or incomplete export of certain form field values (e.g., Gravity Forms Survey fields).

= 1.8.13 on June 10, 2021 =
* Bugfix: Plugin would not activate on hosts running PHP 7.1.

= 1.8.12 on May 14, 2021 =
* Enhancement: Updated PhpSpreadsheet to 1.17.
* Enhancement: Added `gfexcel_file_extension` webhook to overwrite the extension (by default, `.xlsx`).
* Enhancement: composer.json constraints updated for Bedrock users.
* Bugfix: Removed deprecation warning on Gravity Forms 2.5.
* Bugfix: Sort order is now saved again on Gravity Forms 2.5.
* Bugfix: Improve button appearance on Gravity Forms 2.5.
* Bugfix: Sanitized URLs with `esc_url()` in the dashboard.
* Bugfix: Resolved some silent PHP warnings.

= 1.8.11 =
Not released on WordPress due to linter issues.

= 1.8.10 on April 13, 2021 =
* Bugfix: Default combiner glue for List Fields was accidentally changed.
* Bugfix: Mark baker dependencies no longer clash with other plugins using the same dependencies.

= 1.8.9 =
* Conflict: Updated dependencies to resolve conflict with Visualizer.

= 1.8.8 =
* Enhancement: Better support for checkbox fields.

= 1.8.7 =
* Bugfix: Product quantity was set to 1 by default if the value was empty.

= 1.8.6 =
* Bugfix: Resetting the form count could result in an error you would receive per email.

= 1.8.5 =
* Enhancement: You can now sort by subfields like "last name" in a name field.
* Enhancement: Hover state for sorting items.
* Enhancement: Added form id to `gfexcel_renderer_cell_properties` hook.

= 1.8.4 =
* Bugfix: Product field was unhappy without splitting fields.

= 1.8.3 =
* Bugfix: Notification update sometimes produced error. No more happy emoticons.

= 1.8.2 =
* Bugfix: Empty numeric values are not allowed by PhpSpreadsheet anymore. So much for SemVer :-)
* Bugfix: Product subfields were all parsed as currency. Now they are the correct type.

= 1.8.1 =
* Bugfix: Numeric values were presented as currency by default.

= 1.8.0 =
* Last version to support PHP 7.1. Next minor release will only support 7.2+.
* Feature: Added `setFontSize(?float $font_size)` on value objects, so every cell can have a different font size.
* Feature: Added `setBorder($color, $position)` on value objects to set a border on a cell.
* Feature: Added CurrencyValue type and formatting on numeric cells. So you can have a currency symbol and a numeric value.
* Feature: Added new CombinerInterface to streamline the process of combining values into columns.
* Feature: Added notifications base to bomb you with info. Kidding, only useful messages of course.
* Bugfix: 'gfexcel_renderer_csv_include_separator_line' had a typo.
* Bugfix: List field threw notice when you changed the column names.
* Bugfix: Disabled warning when `set_time_limit` is not allowed to prevent failing download.
* Enhancement: Updated PhpSpreadsheet to 1.12 (last to support PHP 7.1).
* Enhancement: Added quick-link to documentation on the plugins page.
* Enhancement: Added quick-link to settings on the plugins page.
* Enhancement: Replaced all translation calls with WordPress native calls to be polite to Poedit.
* Enhancement: Added some unit tests.

= 1.7.5 =
* Enhancement: Added some renderer hooks for CSV manipulation.
	* `gfexcel_renderer_csv_delimiter` -> default: `,`
	* `gfexcel_renderer_csv_enclosure` -> default: `"`
	* `gfexcel_renderer_csv_line_ending` -> default: `PHP_EOL`
	* `gfexcel_renderer_csv_use_bom` -> default: `false`
	* `gfexcel_renderer_csv_include_separator_line` -> default: `false`

= 1.7.4 =
* Bugfix: Setting a cell to bold no longer makes it italic too.

= 1.7.3 =
* Bugfix: Download rights were not checked properly. No security risk, but it should work. :)

= 1.7.2 =
* Bugfix: Custom filenames were not being showed by the field anymore.

= 1.7.1 =
* Bugfix: Column-names now match the filters in the sortable lists.
* Bugfix: Filters now only respond to the correct URL.
* Bugfix: Forgot to update composer.json to reflect minimum PHP version of 7.1. (for Bedrock users).
* Changed: Updated composer.json to use PhpSpreadsheet ~1.9.0 to be consistent with the normal plugin version.

= 1.7.0 =
* Feature: Added field filtering to the URL. Checkout [the documentation](https://gfexcel.com/docs/filtering/) for more info.
* Feature: Added support for [Repeater fields](https://docs.gravityforms.com/repeater-fields/).
* Feature: Added download links for a single entry on the entry detail page.
* Feature: Added download link to admin bar for recent forms.
* Enhancement: Added a maximum column width via `gfexcel_renderer_columns_max_width`.
* Enhancement: Added a `gfexcel_renderer_wrap_text` hook to disable wrapping text.
* Enhancement: Added `$form_id` as an argument to `gfexcel_output_search_criteria` for convenience.
* Enhancement: Added `noindex, nofollow` to the headers of the export, and added a `Disallow` to the `robots.txt`.
* Enhancement: Added a `gfexcel_download_renderer` hook to inject a custom renderer.
* Bugfix: Prevent notice at render-time for `ob_end_clean`.
* Bugfix: Reset download hash and counter on duplicated form.
* Updated: PhpSpreadsheet updated to 1.9.0. Package to `^1.3`.

= 1.6.3 =
* Bugfix: Radio and checkboxes caused unforeseen error on short tag for GF.

= 1.6.2 =
* Bugfix: Referenced unavailable constant.
* Bugfix: short code had an breaking edge case.

= 1.6.1 =
* Security: Removed old style URL. If you were using it, please regenerate the URL.
* Enhancement: Added `[gfexcel_download_link id=2]` short tag for WordPress and `{gfexcel_download_link}` for GF notification.
* Enhancement: Added reset of download counter (also refactored all counter code to SRP class).
* Enhancement: Added setting to format prices as numeric values.
* Enhancement: Added a download event so you can append logic to the download moment.
* Enhancement: Added `CreatedBy` field to easily change `user_id` to `nickname` or `display_name`. Use filter `gfexcel_meta_created_by_property`.
* Bugfix: Stripping title could cut multibyte character in half, making the xlsx useless.
* Bugfix: Removed `start_date` or `end_date` from date range filter when empty. Caused errors for some.
* Bugfix: `created_by` and `payment_date` were not converted to the WordPress timezone.

= 1.6.0 =
* Feature: The renderer now supports transposing, so that every column is a row and vice versa.
* Feature: Added a date range filter. Also included as `start_date` and `end_date` query_parameters.
* Feature: Added a "download" link per form on the Forms page. Fewer clicks for that file!
* Feature: Hide a row by hooking into `gfexcel_renderer_hide_row`. Checkout the FAQ for more info.
* Enhancement: All separable fields are handled as such, except for checkboxes. Made no sense.
* Enhancement: Product and calculation have some specific rendering on single field for clarity.
* Enhancement: Now supports *Gravity Forms Chained Selects*.
* Enhancement: Querying entries in smaller sets to avoid massive database queries that can choke a database server.
* Enhancement: Added a `gfexcel_output_search_criteria` filter to customize the `search_criteria` for requests.
* Bugfix: Downloading files didn't work on iOS.
* Info: PHP 5.6 is no longer actively supported. Will probably still work; but 7.1 is the new minimum.
* Info: Launched a (first version) documentation site! [Check out gfexcel.com](https://gfexcel.com)

= 1.5.5 =
* Enhancement: Date fields now export the date according to its field setting.
* Enhancement: Value Objects (BaseValue) can reference `getField()`, `getFieldType()` and `getFieldId()` to help with filtering.
* Enhancement: Name fields can now also be split up in to multiple fields. Made this a generic setting on the settings page. Please re-save your settings!
* Enhancement: Subfield labels can now also be overwritten with the `gfexcel_field_value`-hook.
* Bugfix: Found a memory leakage in retrieving fields for every row. Will now be retrieved only once per file.
* Bugfix: Custom Sub field labels were not exported.
* Bugfix: I spelled 'separate' wrong, and therefore the hooks were also wrong. **Please update your hooks If you use them!**

= 1.5.4 =
* Language: Finnish language files added thanks to @Nomafin!
* Enhancement: Better inclusion of script and styles.
* Enhancement: Renamed `Results in Excel` to `Entries in Excel` to be more consistent.
* Enhancement: Added a quick link to settings from the plugins page.
* Bugfix: Wrong minimum version of Gravity Forms set, should be 2.0.
* Help: Added some help text to the global settings page. I need your input!

= 1.5.3 =
* Enhancement: Added plugin settings page with plugin wide default settings
* Enhancement: Added dependency checks to plugin, so without them, the plugin won't work.
* Bugfix: Prices were shown in html characters. Not really a bug, but it was bugging someone :)
* Bugfix: Address field needed wrapping of value objects on separate fields.
* Bugfix: Some fields were missing wrapping of value object.

= 1.5.2 =
* Bugfix: Posting a form gave a 500 error, because of missing form info in front-end.

= 1.5.1 =
* (Awesome) Feature: You can now set the order of the fields by sorting them, using drag and drop!
* Feature: Add colors and font styles to cells by using the `gfexcel_value_object`-hook (See docs).
* Feature: Attach a single entry file to a notification email.
* Feature: We now support exports in CSV. Why? Because we can! (and also Harry asked me too).
* Enhancement: You can now add .xlsx or .csv to the end of the URL, to force that output.
* Enhancement: Added support for the Woocommerce add-on.
* Enhancement: Added support for the Members plugin. You need 'gravityforms_export_entries' role for this plugin.
* Bugfix: The extension did not match the renderer, which sometimes caused Excel to give a warning.
* Bugfix: Lists with a single column could not be exported.

= 1.5.0 =
* Failed upload. I wish WordPress would drop the ancient SVN approach!

= 1.4.0 =
* Celebration: 1000+ active installations! Whoop! That is so awesome! Thank you for the support and feedback!
As a celebration gift I've added some new settings, making the plugin more user-friendly, while maintaining developer-friendliness!
* Feature / Security: Regenerate URL for a form, with fallback to old way. But please update all your URLs!
This update also makes the slug more secure and unique by not using the (possibly default) NONCE_SALT.
* Feature: Disable fields and metadata with checkboxes on the settings page. Can still be overwritten with the hooks.
* Feature: Enable notes on the settings page. Can still be overwritten with the hook.
* Feature: Added setting to set the custom filename. Can also still be overwritten with the hook.
* Feature: Added error handling to provide better feedback and support.

= 1.3.1 =
* Enhancement: Added notes per entry. Activate with `gfexcel_field_notes_enabled`.
* Enhancement: Removed unnecessary files from the plugin to make it smaller.

= 1.3.0 =
* Feature: Wrapped values in value objects, so we can be more specific in Excel for cell-type-hinting
* Feature: NumberField added that uses the NumberValue type for Excel
* Feature: Added filters to typehint cell values. See FAQ for more info.
* Enhancement: updated cell > URL implementation. Each cell can be set individually now. See FAQ for more info.
* Upgraded to PHP 5.6 for minimal dependency. Last version with PHP 5.3 was 1.2.3
(sorry for the mix-up, the new renderer forced my hand, and I forgot about this, otherwise the versioning had gone up sooner.)

= 1.2.4 =
* Enhancement: moved away from deprecated PhpExcel to PhpSpreadsheet (Thanks @ravloony).
* Enhancement: `composer.json` update to `wordpress-plugin` for easier installation with bedrock.
* Enhancement: Metadata now uses GFExport to get all metadata; so a row now has all metadata. Can still be disabled.
* Feature: New ListField transformer. Splits list fields into its own Excel columns, with newline-separate values per column.
* Feature: New meta fields transformer. Special filter hooks for meta fields with `gfexcel_meta_value`.
* Feature: New meta subfield transformer for `date_created`. Use `gfexcel_meta_date_created_separated` to split date and time in 2 columns.
* Bugfix: Plugin hooks later, so filters also work on bulk-download files.

= 1.2.3 =
* Bugfix: Worksheets could contain invalid characters, and break download.
* Last version to use PHP 5.3

= 1.2.2 =
* Enhancement: If a cell only contains a URL, that URL is set as a link on that cell, for easy access.

= 1.2.1 =
* Translation: Added `Dutch` translation + enabled possibility to translate via WordPress.org. You can help me out!
* Enhancement: Worksheets now have a title, and of course a `gfexcel_renderer_worksheet_title` hook.

= 1.2.0 =
* (Very cool) Feature: Download Excel output directly from forms table, and (drumroll), download multiple forms in one file!
* Feature: Added `gfexcel_field_disable` filter to disable all fields you want. Fields will be filtered out before handling.
* Feature: Added `gfexcel_output_rows` and `gfexcel_output_columns` filters to have more control over output. Thanks @mircobabini.
* Feature: Added a setting for sort order per form. Also contains some hooks to override that work!

= 1.1.0 =
* Feature: Download counter (starts counting as of this version)
* Feature: SectionField added to disable empty section columns. Disabled by default. Enable with `gfexcel_field_section_enabled` hook (return true).
* Feature: FileUploadField added to disable file upload columns. Enabled by default. Disable with `gfexcel_field_fileuploads_enabled` hook (return false).
* Update: Wait until plugins are loaded. Need to be sure Gravity Forms is active. This caused a problem in some multisite implementations.
* Bugfix: Changed the permalink registration so that it works with multisite combined with the GF API (thanks for the assist @zitomerh). No need to reactivate the plugin now.
* Bugfix: In Standard URL permalink structure, the hash wasn't escaped properly

= 1.0.2 =
* Bugfix: Only 20 results were being returned by the GFAPI
* The title of a form could not be longer than 31 characters

= 1.0.1 =
* Updated readme
* Removed unnecessary assets

= 1.0 =
* First release
