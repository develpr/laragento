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

class CustomerController extends EavConroller{

    public function __construct(){
        $this->apiVersion = 'v1';
        $this->specialFields = array('id', 'email');
        $this->entityType = Laragento\EavEntityType::TYPE_CUSTOMER;
        $this->resourceName = 'customer';
        $this->routeName = 'customers';
        $this->primaryTable = 'customer_entity'; //todo: can probably use the Model's table instead
        $this->attributeTablePrefix = 'customer_entity_';
        $this->baseModel = new Laragento\Customer;

        parent::__construct();
    }
}

//
//class CustomerController extends \BaseController {
//
//    protected $apiVersion = 'v1';
//    protected $attributeHelper = null;
//    protected $fields = false;
//    protected $queries = false;
//
//    protected $path;
//
//    /**
//     * Instantiate a new ProductController instance.
//     */
//    public function __construct()
//    {
//        if(!$this->attributeHelper)
//            $this->attributeHelper = new Laragento\Attributes;
//
//        if(Input::has('fields'))
//            $this->fields = array_map('trim',explode(',', Input::get('fields')));
//
//        if($this->fields !== false){
//            if(($key = array_search('id', $this->fields)) !== false) {
//                unset($this->fields[$key]);
//            }
//            if(($key = array_search('email', $this->fields)) !== false) {
//                unset($this->fields[$key]);
//            }
//        }
//
//
//    }
//
//
//    /**
//	 * Display a listing of the resource.
//	 *
//	 * @return Response
//	 */
//	public function index()
//	{
//        $limit = Input::has('limit') ? Input::get('limit') : Config::get('app.laragento.defaults.pagination.limit');
//        $offset = Input::has('offset') ? Input::get('offset') : Config::get('app.laragento.defaults.pagination.offset');
//
//
//        //Now we need to check for queries
//        $queries = $this->getQueries();
//
//
//        $customerQuery = Laragento\Customer::take($limit)->skip($offset);
//
//        $select = array('customer_entity.entity_id as id', 'email');
//
//        //There are two possibly useful queries parameters that are not actual fields that we need
//        //to treat on their own, those being the SKU and ID (or entity_id) which are on the customer_entity
//        //table itself, not in an eav table
//        if($queries !== false)
//        {
//            if(isset($queries['email']))
//                $customerQuery->where('customer_entity.email', '=', $queries['email']);
//            if(isset($queries['id']))
//                $customerQuery->where('customer_entity.entity_id', '=', $queries['id']);
//
//            unset($queries['email']);
//            unset($queries['id']);
//            if(($key = array_search('email', $this->fields)) !== false)
//            {
//                unset($this->fields[$key]);
//            }
//            if(($key = array_search('id', $this->fields)) !== false)
//            {
//                unset($this->fields[$key]);
//            }
//
//        }
//
//        //Check if any ?fields= came in on the query string (or anywhere else) and process those if so
//        if($this->fields !== false)
//        {
//
//            //Load the attributes from the appropriate table
//            foreach($this->fields as $attributeCode)
//            {
//                //make sure it's snake case
//                $attributeCode = $this->toSnakeCase($attributeCode);
//                $eavTypeId = $this->attributeHelper->getEavEntityTypeId(Laragento\EavEntityType::TYPE_CUSTOMER);
//                $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);
//
//                if(!$attribute)
//                    throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this customer');
//
//                $customerQuery->leftJoin('customer_entity_' . $attribute['backend_type'] . ' as ' . $attributeCode, 'customer_entity.entity_id', '=', $attributeCode . '.entity_id');
//                $customerQuery->where($attributeCode . '.attribute_id', '=', $attribute['id']);
//                $select[] = $attributeCode . '.value as ' . $this->toCamelCase($attributeCode);
//
//            }
//
//            //Add any additional where filters for any parameters that are being searched for
//            foreach($queries as $attributeCode => $attributeValue)
//            {
//                //make sure it's snake case
//                $attributeCode = $this->toSnakeCase($attributeCode);
//                $eavTypeId = $this->attributeHelper->getEavEntityTypeId(Laragento\EavEntityType::TYPE_CUSTOMER);
//                $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);
//
//                if(!$attribute)
//                    throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this produt');
//
//                if(strpos($attributeValue, '*') !== false)
//                    $customerQuery->where($attributeCode . '.value', 'like', str_replace('*', '%', $attributeValue));
//                else
//                    $customerQuery->where($attributeCode . '.value', '=', $attributeValue);
//
//            }
//
//        }
//
//        $customerQuery->select($select);
//
//        $customers = $customerQuery->remember(1)->get();
//
//        $customersResult = array();
//
//        //Build a more formal output array
//        foreach($customers as $customer){
//            $customersResult[] = $this->buildCustomertJson($customer, false, true);
//        }
//
//        return Response::json($customersResult);
//	}
//
//	/**
//	 * Show the form for creating a new resource.
//	 *
//	 * @return Response
//	 */
//	public function create()
//	{
//		//
//	}
//
//	/**
//	 * Store a newly created resource in storage.
//	 *
//	 * @return Response
//	 */
//	public function store()
//	{
//		//
//	}
//
//	/**
//	 * Display the specified resource.
//	 *
//	 * @param  int  $id
//	 * @return Response
//	 */
//	public function show($id)
//	{
//        $customer = $this->getCustomer($id);
//
//        return Response::json($customer);
//	}
//
//
//    /**
//     * Get a customer either from redis or from the database
//     *
//     * @param $id
//     * @return array
//     * @throws Symfony\Component\Translation\Exception\InvalidResourceException
//     */
//    protected function getCustomer($id)
//    {
//        $queryRequired = false;
//
//        $customer = Redis::hgetall('customer:'.$id);
//
//        if(!$customer)
//            $queryRequired = true;
//
//        //We need to figure out if the fields that were requested exist in cache or not
//        if($this->fields !== false){
//
//            //If we don't yet have to make a query, make sure that all of the attributes are set that we need
//            if(!$queryRequired)
//            {
//                foreach($this->fields as $attribute)
//                {
//                    $attribute = $this->toCamelCase($attribute);
//                    if(!array_key_exists($attribute, $customer)){
//                        $queryRequired = true;
//                        break;
//                    }
//                }
//            }
//        }
//
//        //If we have determined that we actually need to make a query to the mysql database..
//        if($queryRequired)
//        {
//            $customerQuery = Laragento\Customer::where('customer_entity.entity_id', '=', $id);
//
//            //note that we are normalizing all primary keys to 'id' - we might regret this later
//            $select = array('customer_entity.entity_id as id', 'email');
//
//            //Check if any ?fields= came in on the query string (or anywhere else) and process those if so
//            if($this->fields !== false){
//
//                foreach($this->fields as $attributeCode)
//                {
//                    //make sure it's snake case
//                    $attributeCode = $this->toSnakeCase($attributeCode);
//                    $eavTypeId = $this->attributeHelper->getEavEntityTypeId(Laragento\EavEntityType::TYPE_CUSTOMER);
//                    $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);
//
//                    if(!$attribute)
//                        throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this produt');
//
//                    $customerQuery->leftJoin('customer_entity_' . $attribute['backend_type'] . ' as ' . $attributeCode, 'customer_entity.entity_id', '=', $attributeCode . '.entity_id');
//                    $customerQuery->where($attributeCode . '.attribute_id', '=', $attribute['id']);
//                    $select[] = $attributeCode . '.value as ' . $this->toCamelCase($attributeCode);
//
//                }
//
//            }
//
//            $customerQuery->select($select);
//
//            $customer = $customerQuery->firstOrFail();
//
//        }
//
//        return $this->buildCustomertJson($customer, $queryRequired, true);
//    }
//
//	/**
//	 * Show the form for editing the specified resource.
//	 *
//	 * @param  int  $id
//	 * @return Response
//	 */
//	public function edit($id)
//	{
//		//
//	}
//
//	/**
//	 * Update the specified resource in storage.
//	 *
//	 * @param  int  $id
//	 * @return Response
//	 */
//	public function update($id)
//	{
//		//
//	}
//
//	/**
//	 * Remove the specified resource from storage.
//	 *
//	 * @param  int  $id
//	 * @return Response
//	 */
//	public function destroy($id)
//	{
//		//
//	}
//
//    /**
//     * Builds a formal array of customer data, and if specified caches the customer data with redis
//     *
//     * @param $customer the customer information
//     * @param bool $cache should these values be cached
//     * @param bool $includeHref should the href attribute be included
//     * @return array
//     */
//    protected function buildCustomertJson($customer, $cache = false, $includeHref = false)
//    {
//        if(is_array($customer))
//            $customer = json_decode(json_encode($customer), FALSE);
//
//        $this->path = URL::to('/api/' . $this->apiVersion . '/customers');
//
//
//        $cacheData = array();
//
//        $returnCustomer = array(
//            'id' => $customer->id,
//            'email' => $customer->email
//        );
//
//        if($this->fields !== false){
//            foreach($this->fields as $attributeCode)
//            {
//                $attributeCode = $this->toCamelCase($attributeCode);
//                $returnCustomer[$this->toCamelCase($attributeCode)] = $customer->$attributeCode;
//            }
//        }
//
//        if($cache)
//        {
//            foreach($returnCustomer as $key => $value)
//            {
//                $cacheData[] = $this->toCamelCase($key);
//                $cacheData[] = $value;
//            }
//
//            Redis::hmset('customer:' . $returnCustomer['id'], $returnCustomer);
//            Redis::expire('customer:' . $returnCustomer['id'], Config::get('app.laragento.cache.customer.seconds'));
//        }
//
//        //Shoudl we include the href to the resource?
//        if($includeHref)
//            $returnCustomer['href'] = $this->path . '/' . $customer->id;
//
//        return $returnCustomer;
//    }
//
//    private function toSnakeCase($input) {
//        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
//        $ret = $matches[0];
//        foreach ($ret as &$match) {
//            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
//        }
//        return implode('_', $ret);
//    }
//
//    private function toCamelCase($val) {
//        $val = str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));
//        $val = strtolower(substr($val,0,1)).substr($val,1);
//        return $val;
//    }
//
//    private function getQueries(){
//
//        $queries = Input::all();
//
//        //Unset any "keywords" for the query
//        unset($queries['fields']);
//        unset($queries['limit']);
//        unset($queries['offset']);
//
//        $queryParameters = array_keys($queries);
//
//        $this->fields = $this->fields ? array_merge($this->fields,$queryParameters) : $queryParameters;
//
//        $this->fields = array_unique($this->fields);
//
//        return $queries;
//
//    }
//
//}