<?php

namespace Api\V1;

use Laragento;

use \Input;
use \Response;
use \Request;
use \Redis;
use \URL;
use \Config;
use \Symfony\Component\Translation\Exception\InvalidResourceException;

class EavConroller extends \BaseController {

    protected $apiVersion;
    protected $attributeHelper = null;
    protected $fields = false;
    protected $queries = false;
    protected $specialFields;
    protected $defaultFields = false;
    protected $entityType;
    protected $resourceName;
    protected $routeName;
    protected $baseModel;

    //todo: can probably use the Model's table instead
    protected $primaryTable;
    protected $attributeTablePrefix;

    protected $path;

    /**
     * Instantiate a new EavController instance.
     */
    public function __construct()
    {
        if(!$this->attributeHelper)
            $this->attributeHelper = new Laragento\Attributes;

        if(Input::has('fields'))
            $this->fields = array_map('trim',explode(',', Input::get('fields')));

        if($this->fields === false && $this->defaultFields !== false)
            $this->fields = $this->defaultFields;

        if($this->fields !== false){
            foreach($this->specialFields as $specialField){
                if(($key = array_search($specialField, $this->fields)) !== false) {
                    unset($this->fields[$key]);
                }
            }
        }

    }


    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $limit = Input::has('limit') ? Input::get('limit') : Config::get('app.laragento.defaults.pagination.limit');
        $offset = Input::has('offset') ? Input::get('offset') : Config::get('app.laragento.defaults.pagination.offset');


        //Now we need to check for queries
        $queries = $this->getQueries();

        //todo: generic
        $resourceQuery = $this->baseModel->take($limit)->skip($offset);

        $select = array($this->getPrimaryTable() . '.entity_id as id');

        foreach($this->specialFields as $specialField){
            //Id is being used in all cases as primary id
            if($specialField != 'id')
                $select[] = $specialField;
        }

        //There are two possibly useful queries parameters that are not actual fields that we need
        //to treat on their own, those being the SKU and ID (or entity_id) which are on the resources primary table
        //table itself, not in an eav table
        if($queries !== false)
        {
            foreach($this->specialFields as $specialField){
                if(isset($queries[$specialField]))
                    $resourceQuery->where($this->getPrimaryTable() . '.' . $specialField, '=', $queries[$specialField]);
                unset($queries[$specialField]);

                if(($key = array_search($specialField, $this->fields)) !== false)
                {
                    unset($this->fields[$key]);
                }
            }

        }

        //Check if any ?fields= came in on the query string (or anywhere else) and process those if so
        if($this->fields !== false)
        {
            //Load the attributes from the appropriate table
            foreach($this->fields as $attributeCode)
            {
                //make sure it's snake case
                $attributeCode = $this->toSnakeCase($attributeCode);
                $eavTypeId = $this->attributeHelper->getEavEntityTypeId($this->entityType);
                $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);

                if(!$attribute)
                    throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this ' . $this->getResourceName());

                $resourceQuery->leftJoin($this->attributeTablePrefix . $attribute['backend_type'] . ' as ' . $attributeCode, $this->getPrimaryTable() . '.entity_id', '=', $attributeCode . '.entity_id');
                $resourceQuery->where($attributeCode . '.attribute_id', '=', $attribute['id']);
                $select[] = $attributeCode . '.value as ' . $this->toCamelCase($attributeCode);

            }

            //Add any additional where filters for any parameters that are being searched for
            foreach($queries as $attributeCode => $attributeValue)
            {
                //make sure it's snake case
                $attributeCode = $this->toSnakeCase($attributeCode);
                $eavTypeId = $this->attributeHelper->getEavEntityTypeId($this->entityType);
                $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);

                //If the attribute wasn't found
                if(!$attribute)
                    throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this ' . $this->getResourceName());

                if(strpos($attributeValue, '*') !== false)
                    $resourceQuery->where($attributeCode . '.value', 'like', str_replace('*', '%', $attributeValue));
                else
                    $resourceQuery->where($attributeCode . '.value', '=', $attributeValue);

            }

        }

        $resourceQuery->select($select);

        $resources = $resourceQuery->remember(1)->get();

        $resourceResult = array();

        //Build a more formal output array
        foreach($resources as $resource){
            $resourceResult[] = $this->buildResourceJson($resource, false, true);
        }

        return Response::json($resourceResult);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        //
    }

    protected function prepareShow($id)
    {
        $resource = $this->getResource($id);

        return $resource;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $resource = $this->prepareShow($id);

        return Response::json($resource);
    }


    /**
     * Get a resource either from redis or from the database
     *
     * @param $id
     * @return array
     * @throws Symfony\Component\Translation\Exception\InvalidResourceException
     */
    protected function getResource($id)
    {
        $queryRequired = false;

        $resource = Redis::hgetall($this->getResourceName() . ':'.$id);

        if(!$resource)
            $queryRequired = true;

        //We need to figure out if the fields that were requested exist in cache or not
        if($this->fields !== false){

            //If we don't yet have to make a query, make sure that all of the attributes are set that we need
            if(!$queryRequired)
            {
                foreach($this->fields as $attribute)
                {
                    $attribute = $this->toCamelCase($attribute);
                    if(!array_key_exists($attribute, $resource)){
                        $queryRequired = true;
                        break;
                    }
                }
            }
        }

        //If we have determined that we actually need to make a query to the mysql database..
        if($queryRequired)
        {
            //todo: make generic
            $resourceQuery = $this->baseModel->where($this->getPrimaryTable() . '.entity_id', '=', $id);

            //note that we are normalizing all primary keys to 'id' - we might regret this later
            $select = array($this->getPrimaryTable() . '.entity_id as id');

            foreach($this->specialFields as $specialField){
                //Id is being used in all cases as primary id
                if($specialField != 'id')
                    $select[] = $specialField;
            }

            //Check if any ?fields= came in on the query string (or anywhere else) and process those if so
            if($this->fields !== false){

                foreach($this->fields as $attributeCode)
                {
                    //make sure it's snake case
                    $attributeCode = $this->toSnakeCase($attributeCode);
                    $eavTypeId = $this->attributeHelper->getEavEntityTypeId($this->entityType);
                    $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);

                    if(!$attribute)
                        throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this ' . $this->getResourceName());

                    $resourceQuery->leftJoin($this->attributeTablePrefix . $attribute['backend_type'] . ' as ' . $attributeCode, $this->getPrimaryTable() . '.entity_id', '=', $attributeCode . '.entity_id');
                    $resourceQuery->where($attributeCode . '.attribute_id', '=', $attribute['id']);
                    $select[] = $attributeCode . '.value as ' . $this->toCamelCase($attributeCode);

                }

            }

            $resourceQuery->select($select);

            $resource = $resourceQuery->firstOrFail();

        }

        return $this->buildResourceJson($resource, $queryRequired, true);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Builds a formal array of resource data, and if specified caches the resource data with redis
     *
     * @param $resource the resource information
     * @param bool $cache should these values be cached
     * @param bool $includeHref should the href attribute be included
     * @return array
     */
    protected function buildResourceJson($resource, $cache = false, $includeHref = false)
    {
        if(is_array($resource))
            $resource = json_decode(json_encode($resource), FALSE);

        $this->path = URL::to('/api/' . $this->apiVersion . '/' . $this->routeName);

        $cacheData = array();

        $returnResource = array(
            'id' => $resource->id
        );

        foreach($this->specialFields as $specialField){
            $returnResource[$specialField] = $resource->$specialField;
        }

        if($this->fields !== false){
            foreach($this->fields as $attributeCode)
            {
                $attributeCode = $this->toCamelCase($attributeCode);
                $returnResource[$this->toCamelCase($attributeCode)] = $resource->$attributeCode;
            }
        }

        if($cache)
        {
            foreach($returnResource as $key => $value)
            {
                $cacheData[] = $this->toCamelCase($key);
                $cacheData[] = $value;
            }

            Redis::hmset($this->getResourceName() . ':' . $returnResource['id'], $returnResource);
            Redis::expire($this->getResourceName() . ':' . $returnResource['id'], Config::get('app.laragento.cache.' . $this->getResourceName() . '.seconds'));
        }

        //Shoudl we include the href to the resource?
        if($includeHref)
            $returnResource['href'] = $this->path . '/' . $resource->id;

        return $returnResource;
    }

    private function toSnakeCase($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    private function toCamelCase($val) {
        $val = str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));
        $val = strtolower(substr($val,0,1)).substr($val,1);
        return $val;
    }

    private function getQueries(){

        $queries = Input::all();

        //Unset any "keywords" for the query
        unset($queries['fields']);
        unset($queries['limit']);
        unset($queries['offset']);
        unset($queries['with']);

        $queryParameters = array_keys($queries);

        $this->fields = $this->fields ? array_merge($this->fields,$queryParameters) : $queryParameters;

        $this->fields = array_unique($this->fields);

        return $queries;

    }

    private function getPrimaryTable()
    {
        return $this->primaryTable;
    }

    private function getResourceName()
    {
        return $this->resourceName;
    }

}