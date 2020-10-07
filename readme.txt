=== Gravity Forms Entries in Excel ===
Contributors: doekenorg
Donate link: https://www.paypal.me/doekenorg
Tags: Gravityforms, Excel, Export, Download, Entries
Requires at least: 4.0
Requires PHP: 7.1
Tested up to: 5.5
Stable tag: 1.8.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Export all Gravity Forms entries to Excel (.xlsx) via a download button OR via a secret (shareable) url.

== Description ==

Export all entries from your forms directly to Excel, using a unique and secure url. No need to login, or create a
user account for that one person who needs te results. Just copy the url, and give it to the guy who needs it.
It's that simple.

Using Gravity Forms you can export a CSV file, and import it to Excel. But an admin always needs to be involved
and using Excel to import a CSV is a pain in the butt.

The plugin has a lot of event-hooks to make your Excel output exactly how you want it.
Check out the FAQ to find out more.

== Documentation ==
I've added a documentation website. This docs will be updated from time to time with new features, fields and filters.
I'm planning to add `recipes` for quick updates, based on your questions. So if you have a specific need; Ask away!
If you are a developer; this site is probably for you.

Please visit: [gfexcel.com](https://gfexcel.com)

== Donate ==
Want to help out the development of the plugin, or just buy me a drink 🍺? You can make a donation via my paypal page:
[paypal.me/doekenorg](https://www.paypal.me/doekenorg). But as always; no pressure.

= Requirements =

* PHP 7.1. (No longer active support for 5.6)
* php-xml and php-zip libraries. The plugin will check for those.
* Gravity Forms 2.0.0 or higher

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `gf-entries-in-excel` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Forms > Select a form > Settings > Results in Excel to obtain your url
1. Download that Excel file!

== Frequently Asked Questions ==

= I don't want the the metadata like ID, date, and IP in my file =

No problem. You can use the `gfexcel_output_meta_info` or `gfexcel_output_meta_info_{form_id}` hooks to disable
this feature. Or (since version 1.4.0) you can select individual fields you want to exclude on the settings page.

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

Great question! Yes you can! You can set it on the setting spage, or make use of the following hooks to get that working:
`gfexcel_field_separated_{type}_{form_id}_{field_id}` where every variable is optional.

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

By now you really should know you can change almost every aspect of this plugin. Don't like the name? Change it using the settings page, or by using the `gfexcel_renderer_filename` or `gfexcel_renderer_filename_{form_id}` hooks.

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

= My numbers are formatted as a string, how can I change the celltype? =
A numberfield is formatted as a number, but most fields default to a string.
As of this moment, there are 3 field types. `Boolean`,`String` and `Numeric`. You can set these per field.
`
//add this to your functions.php
use GFExcel\Values\BaseValue;

add_filter('gfexcel_value_type',function($type, $field) {
    if($field->formId == 1 && $field->id == 2) {
        //Possible values are 'bool', 'string' or 'numeric',
        //or, use the constant, preffered:
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
        $value->setUrl('http://wordpress.org');
    }
}, 10, 2);
`

= I've added some notes, where are they? =
By default the notes are disabled for performance. If you'd like to add these to the row you can activate this like so:

`
//add this to your functions.php
add_filter('gfexcel_field_notes_enabled','__return_true');
//or
add_filter('gfexcel_field_notes_enabled_{formid}','__return_true'); // eg. gfexcel_field_notes_enabled_2
`

= It's all to boring in Excel. Can I use some colors? =
Definitely! You get to change: text color, background color, bold and italic. If that is not enough, you probably just need to add those Clip Arts yourself!

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

= I don't have enough... eh... Memory! =
Yes, this can happen. And to be frank (actually, I'm not, I'm Doeke), this isn't something that can be fixed.
As a default, Wordpress allocates 40 MB of memory. Because the plugin starts the rendering pretty early, it has most of it available.
But every cell to be rendered (even if it's empty) takes up about 1KB of memory. This means that you have (roughly)
`40 MB * 1024 KB = 40.960 Cells`. I say roughly, because we also use some memory for calculations and retrieving the data.
If you're around this cell-count, and the renderer fails; try to upgrade the `WP_MEMORY_LIMIT`. Checkout [Woocommerce's Docs](https://docs.woocommerce.com/document/increasing-the-wordpress-memory-limit/) for some tips.

= Can I hide a row, but not remove it? =
You got something to hide, eh? We got you "covered". You can hide a row by adding a hook. Why a hook and not a nice GUI? Because everyone has different reasons for hiding stuff. So I couldn't come up with a better solution for now.
Checkout this example:

`add_filter('gfexcel_renderer_hide_row', function ($hide, $row) {
     foreach ($row as $column) {
         if ($column->getFieldId() === 1 && empty($column->getValue())) {
             return true; // hide rows with an empty field 1
         }
     }
     return $hide; // don't forget me!
 }, 10, 2);`

== Screenshots ==

1. A 'Entries in Excel' link is added to the form settings
2. There is your url! Just copy and paste to the browser (or click the download button)
3. Or download it from the list via the bulk selector

== Changelog ==
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
* Bugfix: Product subfields we're all parsed as currency. Now its all the correct type.

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
* Enhancement: Updated PHPSpreadsheet to 1.12 (last to support PHP 7.1).
* Enhancement: Added quick-link to documentation on the plugins page.
* Enhancement: Added quick-link to settings on the plugins page.
* Enhancement: Replaced all translation calls with wordpress native calls to be polite to Poedit.
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
* Bugfix: Filters now only respond to the correct url.
* Bugfix: Forgot to update composer.json to reflect minimum PHP version of 7.1. (for Bedrock users).
* Changed: Updated composer.json to use phpspreadsheet ~1.9.0 to be consistent with the normal plugin version.

= 1.7.0 =
* Feature: Added field filtering to the url. Checkout [the documentation](https://gfexcel.com/docs/filtering/) for more info.
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
* Updated: PHPSpreadsheet updated to 1.9.0. Package to `^1.3`.

= 1.6.3 =
* Bugfix: Radio and checkboxes caused unforeseen error on short tag for GF.

= 1.6.2 =
* Bugfix: Referenced unavailable constant.
* Bugfix: short code had an breaking edge case.

= 1.6.1 =
* Security: Removed old style URL. If you were using it, please regenerate the URL.
* Enhancement: Added `[gfexcel_download_link id=2]` short tag for Wordpress and `{gfexcel_download_link}` for GF notification.
* Enhancement: Added reset of download counter (also refactored all counter code to SRP class).
* Enhancement: Added setting to format prices as numeric values.
* Enhancement: Added a download event so you can append logic to the download moment.
* Enhancement: Added `CreatedBy` field to easily change `user_id` to `nickname` or `display_name`. Use filter `gfexcel_meta_created_by_property`.
* Bugfix: Stripping title could cut multibyte character in half, making the xlsx useless.
* Bugfix: Removed `start_date` or `end_date` from date range filter when empty. Caused errors for some.
* Bugfix: `created_by` and `payment_date` were not converted to the wordpress timezone.

= 1.6.0 =
* Feature: The renderer now supports transposing. So every column is a row, and vica versa.
* Feature: Added a date range filter. Also included as `start_date` and `end_date` query_parameters.
* Feature: Added a "download" link per form on the Forms page. Less clicks for that file!
* Feature: Hide a row by hooking into `gfexcel_renderer_hide_row`. Checkout the FAQ for more info.
* Enhancement: All separable fields are handled as such, except for checkboxes. Made no sense.
* Enhancement: Product and calculation have some specific rendering on single field for clearity.
* Enhancement: Now supports *Gravity Forms Chained Selects*.
* Enhancement: Querying entries in smaller sets to avoid massive database queries that can choke a database server.
* Enhancement: Added a `gfexcel_output_search_criteria` filter to customize the `search_criteria` for requests.
* Bugfix: Downloading files didn't work on iOS.
* Info: PHP 5.6 is no longer actively supported. Will probably still work; but 7.1 is the new minimum.
* Info: Launched a (first version) documentation site! Checkout http://gfexcel.doeken.org

= 1.5.5 =
* Enhancement: Date fields now export the date according to it's field setting.
* Enhancement: Value Objects (BaseValue) can reference `getField()`, `getFieldType()` and `getFieldId()` to help with filtering.
* Enhancement: Name fields can now also be split up in to multiple fields. Made this a generic setting on the settings page. Please re-save your settings!
* Enhancement: Subfield labels can now also be overwritten with the `gfexcel_field_value`-hook.
* Bugfix: Found a memory leakage in retrieving fields for every row. Will now be retrieved only once per file.
* Bugfix: Custom Sub field labels were not exported.
* Bugfix: I spelled 'separate' wrong, and therefor the hooks were also wrong. **Please update your hooks If you use them!**

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
* Failed upload. I wish Wordpress would drop the ancient SVN approach!

= 1.4.0 =
* Celebration: 1000+ active installations! Whoop! That is so awesome! Thank you for the support and feedback!
As a celebration gift I've added some new settings, making the plugin more user-friendly, while maintaining developer-friendliness!
* Feature / Security: Regenerate url for a form, with fallback to old way. But please update all your urls!
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
* Enhancement: updated cell > url implementation. Each cell can be set individually now. See FAQ for more info.
* Upgraded to PHP 5.6 for minimal dependency. Last version with PHP 5.3 was 1.2.3
(sorry for the mix up, the new renderer forced my hand, and I forgot about this, otherwise the versioning had gone up sooner.)

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
* Last version to use PHP 5.3

= 1.2.2 =
* Enhancement: If a cell only contains a URL, that URL is set as a link on that cell, for easy access.

= 1.2.1 =
* Translation: Added `Dutch` translation + enabled possibility to translate via Wordpress.org. You can help me out!
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
* Bugfix: Changed the permalink registration so it works with multi site combined with the GF API (thanks for the assist @zitomerh). No need to reactivate the plugin now.
* Bugfix: In Standard URL permalink structure, the hash wasn't escaped properly

= 1.0.2 =
* Bugfix: Only 20 results were being returned by the GFAPI
* The title of a form could not be longer than 31 characters

= 1.0.1 =
* Updated readme
* Removed unnecessary assets

= 1.0 =
* First release

== Credits ==
- Logo by Karlo Norg | [SQUID Media](https://www.squidmedia.nl)
- Banner Photo by [Matt Benson](https://unsplash.com/@mattgyver) on [Unsplash](https://unsplash.com/photos/rHbob_bEsSs)