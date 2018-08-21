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
`40 MB * 1024 KB = 40.960 Cells`. I say roughly, beceause we also use some memory for calculations and retrieving the data.
If you're around this cell-count, and the renderer fails; try to upgrade the `WP_MEMORY_LIMIT`. Checkout [Woocommerce's Docs](https://docs.woocommerce.com/document/increasing-the-wordpress-memory-limit/) for some tips.
