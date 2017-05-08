<?php

namespace Ncmb;

/**
 * Handles querying data
 */
class Query
{
    /**
     * API path to search
     *
     * @var string
     */
    private $apiPath;

    /**
     * Class name for data stored on NCMB.
     *
     * @var string
     */
    private $className;

    /**
     * Where constraints.
     *
     * @var array
     */
    private $where = [];

    /**
     * Order By keys.
     *
     * @var array
     */
    private $orderBy = [];

    /**
     * Include nested objects.
     *
     * @var array
     */
    private $includes = [];

    /**
     * Skip from the beginning of the search results.
     *
     * @var int
     */
    private $skip = 0;

    /**
     * Determines if the query is a count query or a results query.
     *
     * @var int
     */
    private $count;

    /**
     * Limit of results, defaults to 100 when not explicitly set.
     *
     * @var int
     */
    private $limit = -1;

    /**
     * Create a Query for a given NCMB Class.
     *
     * @param mixed $className Class Name of data on NCMB.
     */
    public function __construct($className = null)
    {
        $this->apiPath = null;
        $this->className = $className;
    }

    /**
     * Set API Path
     * @param string $apiPath
     */
    public function setApiPath($apiPath)
    {
        $this->apiPath = $apiPath;
    }

    /**
     * Get API Path
     * @return string API path string
     */
    public function getApiPath()
    {
        if ($this->apiPath !== null) {
            return $this->apiPath;
        }
        if ($this->className !== null) {
            return 'classes/' . $this->className;
        }
        throw new Exception('Both apiPath and classNmae are not set');
    }

    /**
     * Execute a query to get only the first result.
     *
     * @return array|\Ncmb\Object Returns the first object or an empty array
     */
    public function first()
    {
        $this->limit = 1;
        $result = $this->find();
        if (count($result)) {
            return $result[0];
        } else {
            return [];
        }
    }

    /**
     * Execute a find query and return the results.
     *
     * @return Ncmb\Object[]
     */
    public function find()
    {
        $options = [
            'query' => $this->getQueryOptions()
        ];

        $result = ApiClient::request(
            'GET',
            $this->getApiPath(),
            $options
        );
        $output = [];
        foreach ($result['results'] as $row) {
            $obj = Object::create($this->className, $row['objectId']);
            $obj->mergeAfterFetch($row);
            $output[] = $obj;
        }

        return $output;
    }

    /**
     * Set the skip parameter as a query constraint.
     *
     * @param int $n Number of objects to skip from start of results.
     *
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function skip($n)
    {
        $this->skip = $n;
        return $this;
    }

    /**
     * Set the limit parameter as a query constraint.
     *
     * @param int $n Number of objects to return from the query.
     *
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function limit($n)
    {
        $this->limit = $n;
        return $this;
    }

    /**
     * Set a constraint for a field matching a given value.
     *
     * @param string $key   Key to set up an equals constraint.
     * @param mixed  $value Value the key must equal.
     *
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function equalTo($key, $value)
    {
        if ($value === null) {
            $this->doesNotExist($key);
        } else {
            $this->where[$key] = $value;
        }

        return $this;
    }

    /**
     * Helper for condition queries.
     *
     * @param string $key       The key to where constraints
     * @param string $condition The condition name
     * @param mixed  $value     The condition value, can be a string or an array of strings
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function addCondition($key, $condition, $value)
    {
        if (!isset($this->where[$key])) {
            $this->where[$key] = [];
        }
        $this->where[$key][$condition] = Encoder::encode($value);
        return $this;
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be not equal to the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that must not be equalled.
     *
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function notEqualTo($key, $value)
    {
        return $this->addCondition($key, '$ne', $value);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be less than the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that provides an Upper bound.
     *
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function lessThan($key, $value)
    {
        return $this->addCondition($key, '$lt', $value);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be greater than the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that provides an Lower bound.
     *
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function greaterThan($key, $value)
    {
        return $this->addCondition($key, '$gt', $value);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be less than or equal to the provided value.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that provides an Upper bound.
     *
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function lessThanOrEqualTo($key, $value)
    {
        return $this->addCondition($key, '$lte', $value);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * not be contained in the provided list of values.
     *
     * @param string $key   The key to check.
     * @param mixed  $value The value that provides an Lower bound.
     *
     * @return \Ncmb\Query Returns this query, so you can chain this call.
     */
    public function greaterThanOrEqualTo($key, $value)
    {
        return $this->addCondition($key, '$gte', $value);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * be contained in the provided list of values.
     *
     * @param string $key   The key to check.
     * @param array  $values The values that will match.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function containedIn($key, $values)
    {
        return $this->addCondition($key, '$in', $values);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * not be contained in the provided list of values.
     *
     * @param string $key    The key to check.
     * @param array  $values The values that will not match.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function notContainedIn($key, $values)
    {
        return $this->addCondition($key, '$nin', $values);
    }

    /**
     * Add a constraint for finding objects that contain the given key.
     *
     * @param string $key The key that should not exist.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function exists($key)
    {
        return $this->addCondition($key, '$exists', true);
    }

    /**
     * Add a constraint for finding objects that not contain the given key.
     *
     * @param string $key The key that should not exist.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function doesNotExist($key)
    {
        return $this->addCondition($key, '$exists', false);
    }

    /**
     * Add a constraint for finding objects that match with the gevin regexp.
     *
     * @param string $key    The key to check.
     * @param string $values The RegEx that will match.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function regex($key, $value)
    {
        return $this->addCondition($key, '$regex', $value);
    }

    /**
     * Add a constraint for finding objects that contains some item with the
     * given items.
     *
     * @param string $key    The key to check.
     * @param array  $values The values that will match.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function inArray($key, $values)
    {
        return $this->addCondition($key, '$inArray', $values);
    }

    /**
     * Add a constraint for finding objects that not contains any item with the
     * given items.
     *
     * @param string $key    The key to check.
     * @param array  $values The values that will not match.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function notInArray($key, $values)
    {
        return $this->addCondition($key, '$ninArray', $values);
    }

    /**
     * Add a constraint to the query that requires a particular key's value to
     * contain each one of the provided list of values.
     *
     * @param string $key  The key to check. This key's value must be an array.
     * @param array  $values The values that will match.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function containsAll($key, $values)
    {
        return $this->addCondition($key, '$all', $values);
    }

    /**
     * Add a constraint that requires that a key's value matches a Query
     * constraint.
     *
     * @param string     $key   The key that the contains the object to match
     *                          the query.
     * @param \Ncmb\Query $query The query that should match.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function matchesQuery($key, $query)
    {
        $queryParam = $query->getOptions();
        $queryParam['className'] = $query->className;
        $this->addCondition($key, '$inQuery', $queryParam);

        return $this;
    }

    /**
     * Add a constraint that requires that a key's value not matches a Query
     * constraint.
     *
     * @param string     $key   The key that the contains the object not to
     *                          match the query.
     * @param \Ncmb\Query $query The query that should not match.
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function doesNotMatchQuery($key, $query)
    {
        $queryParam = $query->getOptions();
        $queryParam['className'] = $query->className;
        $this->addCondition($key, '$notInQuery', $queryParam);

        return $this;
    }

    /**
     * Add a constraint that requires that a key's value matches a value in an
     * object returned by the given query.
     *
     * @param string     $key      The key that contains teh value that is
     *                             being matched.
     * @param string     $queryKey The key in objects returned by the query to
     *                             match against.
     * @param \Ncmb\Query $query   The query to run.
     *
     * @return \Ncmb\Query Returns the query, so you can chain this call.
     */
    public function matchesKeyInQuery($key, $queryKey, $query)
    {
        $queryParam = $query->getOptions();
        $queryParam['className'] = $query->className;
        $this->addCondition(
            $key,
            '$select',
            ['key' => $queryKey, 'query' => $queryParam]
        );
        return $this;
    }

    /**
     * Constructs a ParseQuery object that is the OR of the passed in
     * queries objects.
     * All queries must have same class name.
     *
     * @param array $queryObjects Array of \Ncmb\Query objects to OR.
     *
     * @throws \Ncmb\Exception If all queries don't have same class.
     *
     * @return \Ncmb\Query The query that is the OR of the passed in queries.
     */
    public static function orQueries($queryObjects)
    {
        $className = null;
        foreach ($queryObjects as $queryObject) {
            if (is_null($className)) {
                $className = $queryObject->className;
            }
            if ($className != $queryObject->className) {
                throw new Exception('All queries must be for the same class');
            }
        }
        $query = new self($className);
        $query->orCondition($queryObjects);

        return $query;
    }

    /**
     * Add constraint that at least one of the passed in queries matches.
     *
     * @param array $queries The list of queries to OR.
     *
     * @return ParseQuery Returns the query, so you can chain this call.
     */
    private function orCondition($queries)
    {
        $this->where['$or'] = [];
        foreach ($queries as $query) {
            $this->where['$or'][] = $query->where;
        }
        return $this;
    }

    /**
     * Add constraint for relation.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return \Ncmb\Query
     */
    public function relatedTo($key, $value)
    {
        $this->addCondition('$relatedTo', $key, $value);
        return $this;
    }

    /**
     * Returns an associative array of the query constraints.
     *
     * @return array
     */
    public function getOptions()
    {
        $opts = [];
        if (!empty($this->where)) {
            $opts['where'] = $this->where;
        }
        if (count($this->includes)) {
            $opts['include'] = implode(',', $this->includes);
        }
        if ($this->limit >= 0) {
            $opts['limit'] = $this->limit;
        }
        if ($this->skip > 0) {
            $opts['skip'] = $this->skip;
        }
        if ($this->orderBy) {
            $opts['order'] = implode(',', $this->orderBy);
        }
        if ($this->count) {
            $opts['count'] = $this->count;
        }

        return $opts;
    }

    /**
     * Build query options from query constraints.
     *
     * @param array $queryOptions Associative array of the query constraints.
     *
     * @return array query options as assoc array
     */
    public static function buildQueryOptions($queryOptions)
    {
        if (isset($queryOptions['where'])) {
            // JSONise where conditions
            $whereConds = Encoder::encode($queryOptions['where']);
            $queryOptions['where'] = json_encode($whereConds);
        }
        return $queryOptions;
    }

    /**
     * Build query options of this Query object.
     *
     * @reutrn array query options as assoc array
     */
    public function getQueryOptions()
    {
        return self::buildQueryOptions($this->getOptions());
    }
}
