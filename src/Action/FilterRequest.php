<?php

namespace GFExcel\Action;

/**
 * Class that filters the entries based on a filterset.
 * @since $ver$
 * @todo maybe move the date hooks here too.
 */
class FilterRequest
{
    /**
     * The querystring part for filtering.
     * @since $ver$
     * @var string
     */
    const FILTER = 'filter';

    /**
     * The provided and parsed filters.
     * @since $ver$
     * @var string[]
     */
    private $filters = [];

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
            self::FILTER,
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
        $this->parseFilters(rgar($query_vars, self::FILTER, ''));
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

        $criteria['field_filters'] = array_merge($field_filters, $this->filters);

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

        $value = $operator = '';

        if (in_array($key, ['any', 'all'])) {
            $this->filters['mode'] = $key;
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
            $operator = $filter[1]; //todo normalize this.
            $value = (string) $filter[2];
        }

        $this->filters[] = $operator ? compact('key', 'operator', 'value') : compact('key', 'value');
    }
}
