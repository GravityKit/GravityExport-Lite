=== Gf Excel ===
Contributors: doekenorg
Donate link: https://www.squidmedia.nl/
Tags: Gravityforms, Excel, GF, GFExcel, Gravity, Forms, Output, Download, Entries
Requires at least: 4.0
Tested up to: 4.8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Download all entries for a specific form to an Excel-file (.xls) without being logged in via secret url.

== Description ==

Get realtime entries from your forms using a unique and secure url. No need to login, or create a user account for
that one person who needs te results. Just copy the url, and give it to the guy who needs it. It's that simple.

Using Gravity Forms you can always export a CSV file, and import it to Excel. But an admin always needs to be involved
and using Excel to import a CSV is a pain in the butt.

The plugin also has a few plugin hooks to make your excel output exactly how you want it. Check out the FAQ to find
out more.

= Requirements =

* PHP 5.3 or higher (tested on PHP7 too)
* Gravity Forms 2.0.0 or higher

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `gf-excel` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Make use you have a **unique** `NONCE_SALT` in your `wp-config.php` for security reasons
1. Go to Forms > Settings > Results in Excel to obtain your url
1. Download that excel file!

== Frequently Asked Questions ==

= I don't want the the metadata like ID, date, and IP in my file =

No problem. You can use the `gfexcel_output_meta_info` or `gfexcel_output_meta_info_{form_id}` hooks to disable
this feature.

Just add this to your `functions.php`:

```
add_filter("gfexcel_output_meta_info","__return_false");
```

= I want to rename the label only in Excel, how would I do this? =

Sure, makes sense. You can override the label hooking into
`gfexcel_field_label`, `gfexcel_field_label_{type}`,
`gfexcel_field_label_{type}_{form_id}` or `gfexcel_field_label_{type}_{form_id}_{field_id}`

The field object is provided as parameter, so you can check for type and stuff programatically.

 = I want to change the value of a field in Excel, can this be done? =

 Do you even need to ask? Of course this can be done!

 You can override the value by hooking into
 `gfexcel_field_value`, `gfexcel_field_value_{type}`,
 `gfexcel_field_value_{type}_{form_id}` or `gfexcel_field_value_{type}_{form_id}_{field_id}`

 The entry array is provided as a parameter, so you can combine fields if need be.

= Can I seperate the fields of an address into multiple columns? =

Great question! Yes you can! You can make use of the following hooks to get that working:
`gfexcel_field_address_seperated`, `gfexcel_field_address_seperated_{form_id}` or `gfexcel_field_address_seperated_{form_id}_{field_id}`

Just add this to your `functions.php`:

```
add_filter("gfexcel_field_address_seperated","__return_true");
```

= I have a custom field. Can your plugin handle this? =

Wow, it's almost as if you know the plugin. Spooky. But, yes you can. In multiple ways actually.
The default way the plugins renders the output, is by calling `get_value_export` on the field. All Gravity Forms fields
need that function, so make sure that is implemented. The result is one column with the output combined to one cell per row.

But you can also make your own field-renderer:
1. Make a class that extends `GFExcel\Field\BaseField` (recommended) or extends `GFExcel\Field\AbstractField` or implements `GFExcel\Field\FieldInterface`
1. Return your needed columns and cells by implementing `getColumns` and `getCells`. (See `AddressField` for some inspiration)
1. Add your class via the `gfexcel_transformer_fields` hook as: type => Fully Qualified Classname  (eg. $fields['awesome-type'] => 'MyTheme\Field\MyAwsomeField')

= I don't really like the downloaded file name! =

By now you really should know you can change almost every aspect of this plugin. Don't like the name? Change it
using the `gfexcel_renderer_filename` or `gfexcel_renderer_filename_{form_id}` hooks.

Also you can update title, subject and description metadata of the document by using `gfexcel_renderer_title(_{form_id})`,
 `gfexcel_renderer_subject(_{form_id})` and `gfexcel_renderer_description(_{form_id})`


== Screenshots ==

1. A 'Results in Excel' link is added to the form settings
2. There is your url! Just copy and paste to the browser (or click the download button)

== Changelog ==

= 1.0 =
* First release