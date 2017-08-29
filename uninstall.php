<?php

defined( 'ABSPATH' ) or die( 'No direct access!' );

//update permalinks and clean cache
flush_rewrite_rules();
wp_cache_flush();
