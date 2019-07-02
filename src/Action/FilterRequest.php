<?php

namespace GFExcel\Action;

/**
 * Class that filters the entries based on a filterset.
 * @since $ver$
 */
class FilterRequest
{
    /**
     * The query string part for filtering.
     * @since $ver$
     * @var string
     */
    const FILTER = 'filter';

    /**
     * The query string part for the start date.
     * @since $ver$
     * @var string
     */
    const START_DATE = 'start_date';

    /**
     * The query string part for the start date.
     * @since $ver$
     * @var string
     */
    const END_DATE = 'end_date';

    /**
     * The query string part to retrieve one entry.
     * @since $ver$
     * @var string
     */
    const ENTRY = 'entry';

    /**
     * The provided and parsed field filters.
     * @since $ver$
     * @var string[]
     */
    private $field_filters = [];

    /**
     * Filters used for the entry, not the fields.
     * @since $ver$
     * @var string[]
     */
    private $general_filters = [];

    /**
     * Connect various endpoints for this filter.
     * @since $ver$
     */
    public function __construct()
    {
        add_action('request', [$this, 'request'], 0);
        add_filter('query_vars', [$this, 'getQueryVars']);
        add_filter('gfexcel_output_search_criteria', [$this, 'setSearchCriteria'], 0);
    }

    /**
     * Adds needed parameters to the query vars for the request.
     * @since $ver$
     * @param $vars
     * @return array the query variables.
     */
    public function getQueryVars($vars)
    {
        return array_merge($vars, [
            self::ENTRY,
            self::FILTER,
            self::START_DATE,
            self::END_DATE,
        ]);
    }

    /**
     * Intercepts the request and triggers the filter stages.
     * @since $ver$
     * @param array $query_vars the query vars.
     * @return array the query vars.
     */
    public function request($query_vars)
    {
        $this->parseDates($query_vars);
        $this->parseFilters(rgar($query_vars, self::FILTER, ''));
        $this->parseEntry(rgar($query_vars, self::ENTRY));

        return $query_vars;
    }

    /**
     * Sets the search criteria on the hook for filtering.
     * @since $ver$
     * @param array $criteria The provided criteria to change.
     * @return mixed[] The updated criteria.
     */
    public function setSearchCriteria($criteria)
    {
        // remap the filters so it's following the rules.
        $field_filters = rgar($criteria, 'field_filters', []);

        $criteria['field_filters'] = array_merge($field_filters, $this->field_filters);
        $criteria = array_merge($criteria, $this->general_filters);

        return $criteria;
    }

    /**
     * Parses a filter-string and adds the filters to the internal array.
     * @since $ver$
     * @param string $filter_string the string that contains the filters.
     *
     */
    private function parseFilters(string $filter_string)
    {
        $sets = explode(';', $filter_string);
        if (count($sets) > 0) {
            foreach ($sets as $set_string) {
                $filter = explode(':', $set_string);
                $this->addFilter($filter);
            }
        }
    }

    /**
     * Adds the filter to the internal array.
     * @since $ver$
     * @param string[] $filter
     * @throws \InvalidArgumentException
     */
    private function addFilter(array $filter)
    {
        $parts = count($filter);
        $key = $filter[0];

        if (!$key) {
            return;
        }

        $value = $operator = '';

        if (in_array($key, ['any', 'all'])) {
            $this->field_filters['mode'] = $key;
            return;
        }

        if ($parts < 1 || $parts > 3) {
            throw new \InvalidArgumentException('Invalid filter provided.');
        }

        if ($parts === 1) {
            $operator = '!=';
        } elseif ($parts === 2) {
            $value = (string) $filter[1];
        } elseif ($parts === 3) {
            $operator = (string) $filter[1];
            $value = (string) $filter[2];
        }

        $this->field_filters[] = $operator ? compact('key', 'operator', 'value') : compact('key', 'value');
    }

    /**
     * Store the start and end date when provided.
     * @since $ver$
     * @param array $query_vars
     */
    private function parseDates(array $query_vars)
    {
        $dates = [
            self::START_DATE => rgar($query_vars, self::START_DATE, null),
            self::END_DATE => rgar($query_vars, self::END_DATE, null),
        ];

        $this->general_filters = array_merge($this->general_filters, array_filter($dates));
    }

    /**
     * Add the filter part for a specific entry.
     * @since $ver$
     * @param string|null $entry
     */
    private function parseEntry($entry)
    {
        if ($entry) {
            $this->parseFilters(sprintf('id:%d', $entry));
        }
    }
}
