<?php

namespace GFExcel\Action;

/**
 * An immutable, typed notice describing the outcome of an action.
 *
 * @since TBD
 */
final class ActionNotice {
	public const SUCCESS = 'success';
	public const ERROR   = 'error';
	public const INFO    = 'info';

	/**
	 * The notice type; one of the class constants.
	 * @since TBD
	 * @var string
	 */
	private $type;

	/**
	 * The translated, display-ready message.
	 * @since TBD
	 * @var string
	 */
	private $message;

	private function __construct( string $type, string $message ) {
		$this->type    = $type;
		$this->message = $message;
	}

	/**
	 * Creates a success notice.
	 * @since TBD
	 * @param string $message The translated message.
	 * @return self
	 */
	public static function success( string $message ): self {
		return new self( self::SUCCESS, $message );
	}

	/**
	 * Creates an error notice.
	 * @since TBD
	 * @param string $message The translated message.
	 * @return self
	 */
	public static function error( string $message ): self {
		return new self( self::ERROR, $message );
	}

	/**
	 * Creates an informational notice.
	 * @since TBD
	 * @param string $message The translated message.
	 * @return self
	 */
	public static function info( string $message ): self {
		return new self( self::INFO, $message );
	}

	/**
	 * The notice type.
	 * @since TBD
	 * @return string One of self::SUCCESS, self::ERROR, self::INFO.
	 */
	public function type(): string {
		return $this->type;
	}

	/**
	 * The translated message.
	 * @since TBD
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}
}
