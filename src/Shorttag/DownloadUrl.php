<?php

namespace GFExcel\Shorttag;

use GFExcel\GFExcel;

/**
 * A short tag handler for [gfexcel_download_url].
 * Example usage: [gfexcel_download_url id=1 type=csv]
 * Id is required, type is optional.
 * @since 1.6.1
 */
class DownloadUrl
{
    /** @var string */
   public const SHORTTAG = 'gravityexport_download_url';

    public function __construct()
    {
        add_shortcode('gfexcel_download_url', [$this, 'handle']); // Backward compatible
        add_shortcode(self::SHORTTAG, [$this, 'handle']);
        add_filter('gform_replace_merge_tags', [$this, 'handleNotification'], 10, 2);
    }

    /**
     * Handles the [gfexcel_download_url] shorttag.
     * @since 1.6.1
     * @param array|string $arguments
     * @return string returns the replacing content, either a url or a message.
     */
    public function handle($arguments)
    {
        if (!is_array($arguments)) {
            $arguments = [];
        }

        if (!array_key_exists('id', $arguments)) {
            return $this->error(sprintf('Please add an `%s` argument to \'%s\' shorttag.', 'id', self::SHORTTAG));
        }

        if (!\GFAPI::form_id_exists($arguments['id'])) {
            return $this->error(sprintf('Form id not found for \'%s\' shorttag.', self::SHORTTAG));
        }

        return $this->getUrl($arguments['id'], $arguments['type'] ?? null);
    }

    /**
     * Handles the short-tag for gravity forms.
     * @since 1.6.1
     * @param string $text the text of the notification
     * @param array|false $form
     * @return string The url or an error message
     */
	public function handleNotification( $text, $form ) {
		if ( ! is_array( $form ) || ! isset( $form['id'] ) ) {
			return $text;
		}

		foreach ( [ self::SHORTTAG, 'gfexcel_download_url' ] as $short_tag ) {
			$custom_merge_tag = '{' . $short_tag . '}';

			if ( strpos( $text, $custom_merge_tag ) === false ) {
				continue;
			}

			$text = str_replace( $custom_merge_tag, $this->getUrl( $form['id'] ), $text );
		}

		return $text;
	}

    /**
     * Get the actual url by providing a array with an id, and a type.
     * @since 1.6.1
     * @param int $id
     * @param string|null $type either 'csv' or 'xlsx'.
     * @return string
     */
    private function getUrl($id, $type = null): string
    {
        $url = GFExcel::url($id);

        if ($type && in_array(strtolower($type), GFExcel::getPluginFileExtensions(), true)) {
            $url .= '.' . strtolower($type);
        }

        return $url;
    }

    /**
     * Returns the error message. Can be overwritten by filter hook.
     * @since 1.6.1
     * @param string $message The error message.
     * @return string The filtered error message.
     */
    private function error($message): string
    {
        return gf_apply_filters([
            'gfexcel_shorttag_error',
        ], $message);
    }
}
