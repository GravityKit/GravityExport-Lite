<?php

namespace GFExcel;

use GFExcel\Addon\GravityExportAddon;

trigger_error( sprintf(
	'Class "%s" is deprecated in favor of "%s".',
	'GFExcel\GFExcelAdmin',
	GravityExportAddon::class
), E_USER_DEPRECATED );

/**
 * Making sure old version of plugins still work.
 * @deprecated Will be removed in the next major.
 */
class_alias( GravityExportAddon::class, '\GFExcel\GFExcelAdmin' );
