# GravityExport Lite Technical Notes

## Architecture Overview
- **Single-feed addon**: `$_multiple_feeds = false` (line 49 in GravityExportAddon.php)
- **Slug**: `gravityexport-lite`
- **Main class**: `GFExcel\Addon\GravityExportAddon` extends `GFFeedAddOn`

## Feed Settings Structure
When Pro is active, Lite's tabbed structure renders as flat collapsible sections due to Pro having its own `GravityExportAddon.php` that extends `\GFAddOn` (not `GFFeedAddOn`).

### Tab Organization (defined in feed_settings_fields())
1. **Export Settings** - Download settings, file configuration
2. **Enabled Fields** - Field selection, sorting, header position
3. **Security** - Download permissions
4. **Instant Download ⚡** - Quick download with date range (last tab: action, not config)

## Key Filters & Hooks
- `gfexcel_general_settings` (line 359): Filters sections within Export Settings tab. Used by multi-row, PDF renderer addons
- `gform_gravityexport-lite_feed_settings_fields`: Applied by GF for feed settings customization

## Important Methods
- `feed_settings_fields()` (line 167): Returns tabbed structure
- `form_settings()` in AddonHelperTrait (line 267): Wraps with div, calls parent
- `feed_edit_page()` (line 915): Just calls parent + fires action

## Rendering Path
1. Single-feed addon: `form_settings()` → `feed_edit_page()` (no `fid` param)
2. Settings init: `feed_settings_init()` → `set_fields()`
3. GF checks for 'sections' key to enable tabs (class-settings.php:2352)
4. When Pro active: Renders as flat sections instead of tabs

## Documentation Links
- Lite docs: https://docs.gravitykit.com/category/942-gravityexport-lite
- Restricting file access: Article 1077
- Notification attachments: Article 888