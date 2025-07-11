<?php

namespace GFExcel\Action;

use GFExcel\Routing\Request;
use GFExcel\Routing\Router;

/**
 * Class that filters the entries based on a filterset.
 * @since 1.7.0
 */
class FilterRequest {
	/**
	 * The query string part for filtering.
	 * @since 1.7.0
	 * @var string
	 */
	public const FILTER = 'filter';

	/**
	 * The query string part for the start date.
	 * @since 1.7.0
	 * @var string
	 */
	public const START_DATE = 'start_date';

	/**
	 * The query string part for the start date.
	 * @since 1.7.0
	 * @var string
	 */
	public const END_DATE = 'end_date';

	/**
	 * The query string part to retrieve one entry.
	 * @since 1.7.0
	 * @var string
	 */
	public const ENTRY = 'entry';

	/**
	 * The provided and parsed field filters.
	 * @since 1.7.0
	 * @var mixed
	 */
	private $field_filters = [];

	/**
	 * Filters used for the entry, not the fields.
	 * @since 1.7.0
	 * @var string[]
	 */
	private $general_filters = [];

	/**
	 * The router.
	 * @since 2.4.0
	 * @var Router
	 */
	private $router;

	/**
	 * Connect various endpoints for this filter.
	 * @since 1.7.0
	 */
	public function __construct( Router $router ) {
		$this->router = $router;

		add_filter( 'request', [ $this, 'request' ], 0 );
		add_filter( 'query_vars', [ $this, 'getQueryVars' ] );
		add_filter( 'gfexcel_output_search_criteria', [ $this, 'setSearchCriteria' ], 0 );
	}

	/**
	 * Adds needed parameters to the query vars for the request.
	 * @since 1.7.0
	 *
	 * @param mixed[] $vars The current query variables.
	 *
	 * @return mixed[] The new query variables.
	 */
	public function getQueryVars( $vars ) {
		return array_merge( $vars, [
			self::ENTRY,
			self::FILTER,
			self::START_DATE,
			self::END_DATE,
		] );
	}

	/**
	 * Intercepts the request and triggers the filter stages.
	 * @since 1.7.0
	 *
	 * @param array $query_vars the query vars.
	 *
	 * @return array the query vars.
	 */
	public function request( $query_vars ) {
		// only respond to a GFexcel URL.
		$request = Request::from_query_vars( $query_vars );
		if ( ! $this->router->matches( $request ) ) {
			return $query_vars;
		}

		$this->parseDates( $query_vars );
		$this->parseFilters( \rgar( $query_vars, self::FILTER, '' ) );
		$this->parseEntry( \rgar( $query_vars, self::ENTRY ) );

		return $query_vars;
	}

	/**
	 * Sets the search criteria on the hook for filtering.
	 * @since 1.7.0
	 *
	 * @param array $criteria The provided criteria to change.
	 *
	 * @return mixed[] The updated criteria.
	 */
	public function setSearchCriteria( $criteria ) {
		// remap the filters so it's following the rules.
		$field_filters = \rgar( $criteria, 'field_filters', [] );

		$criteria['field_filters'] = array_merge( $field_filters, $this->field_filters );
		$criteria                  = array_merge( $criteria, $this->general_filters );

		return $criteria;
	}

	/**
	 * Parses a filter-string and adds the filters to the internal array.
	 * @since 1.7.0
	 *
	 * @param string $filter_string the string that contains the filters.
	 *
	 * @return void
	 */
	private function parseFilters( string $filter_string ) {
		$sets = explode( ';', $filter_string );
		foreach ( $sets as $set_string ) {
			$filter = explode( ':', $set_string );
			$this->addFilter( $filter );
		}
	}

	/**
	 * Adds the filter to the internal array.
	 * @since 1.7.0
	 *
	 * @param string[] $filter
	 *
	 * @throws \InvalidArgumentException
	 */
	private function addFilter( array $filter ) {
		$parts = count( $filter );
		$key   = $filter[0];

		if ( ! $key ) {
			return;
		}

		$value = $operator = '';

		if ( in_array( $key, [ 'any', 'all' ] ) ) {
			$this->field_filters['mode'] = $key;

			return;
		}

		if ( $parts < 1 || $parts > 3 ) {
			throw new \InvalidArgumentException( 'Invalid filter provided.' );
		}

		if ( $parts === 1 ) {
			$operator = '!=';
		} elseif ( $parts === 2 ) {
			$value = (string) $filter[1];
		} elseif ( $parts === 3 ) {
			$operator = (string) $filter[1];
			$value    = (string) $filter[2];

			if ( in_array( trim( strtoupper( $operator ) ), [ 'IN', 'NOTIN', 'NOT IN' ], true ) ) {
				$value = preg_split( '/\s*,\s*/', $value );
			}
		}

		$this->field_filters[] = $operator ? compact( 'key', 'operator', 'value' ) : compact( 'key', 'value' );
	}

	/**
	 * Store the start and end date when provided.
	 * @since 1.7.0
	 *
	 * @param array $query_vars the query vars provided by the url.
	 */
	private function parseDates( array $query_vars ) {
		$dates = [
			self::START_DATE => \rgar( $query_vars, self::START_DATE, null ),
			self::END_DATE   => \rgar( $query_vars, self::END_DATE, null ),
		];

		$this->general_filters = array_merge( $this->general_filters, array_filter( $dates ) );
	}

	/**
	 * Add the filter part for a specific entry.
	 * @since 1.7.0
	 *
	 * @param int|null $entry the entry id to retrieve.
	 */
	private function parseEntry( $entry ) {
		if ( $entry ) {
			$this->parseFilters( sprintf( 'id:%d', $entry ) );
		}
	}
}
