<?php

namespace Api\V1;

use Laragento;

use \Input;
use \Response;
use \Request;
use \Redis;
use \URL;
use \Config;

class ProductController extends \BaseController {

    protected $apiVersion = 'v1';
    protected $attributeHelper = null;
    protected $fields = false;
    protected $queries = false;

    protected $path;

    /**
     * Instantiate a new ProductController instance.
     */
    public function __construct()
    {
        if(!$this->attributeHelper)
            $this->attributeHelper = new Laragento\Attributes;

        if(Input::has('fields'))
            $this->fields = array_map('trim',explode(',', Input::get('fields')));

        if($this->fields !== false){
            if(($key = array_search('id', $this->fields)) !== false) {
                unset($this->fields[$key]);
            }
            if(($key = array_search('sku', $this->fields)) !== false) {
                unset($this->fields[$key]);
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


        $productQuery = Laragento\Product::take($limit)->skip($offset);

        $select = array('catalog_product_entity.entity_id as id', 'sku');

        //There are two possibly useful queries parameters that are not actual fields that we need
        //to treat on their own, those being the SKU and ID (or entity_id) which are on the catalog_product_entity
        //table itself, not in an eav table
        if($queries !== false)
        {
            if(isset($queries['sku']))
                $productQuery->where('catalog_product_entity.sku', '=', $queries['sku']);
            if(isset($queries['id']))
                $productQuery->where('catalog_product_entity.entity_id', '=', $queries['id']);

            unset($queries['sku']);
            unset($queries['id']);
            if(($key = array_search('sku', $this->fields)) !== false)
            {
                unset($this->fields[$key]);
            }
            if(($key = array_search('id', $this->fields)) !== false)
            {
                unset($this->fields[$key]);
            }

        }

        //Check if any ?fields= came in on the query string (or anywhere else) and process those if so
        if($this->fields !== false)
        {

            if(in_array('inventory', $this->fields)){
                $productQuery->leftJoin('cataloginventory_stock_item as inventory', 'catalog_product_entity.entity_id', '=', 'inventory.product_id');
                $select[] = 'inventory.qty as inventory';

                if(($key = array_search('inventory', $this->fields)) !== false)
                {
                    unset($this->fields[$key]);
                }
            }

            //Load the attributes from the appropriate table
            foreach($this->fields as $attributeCode)
            {
                //make sure it's snake case
                $attributeCode = $this->toSnakeCase($attributeCode);
                $eavTypeId = $this->attributeHelper->getEavEntityTypeId(Laragento\EavEntityType::TYPE_PRODUCT);
                $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);

                if(!$attribute)
                    throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this product');

                $productQuery->leftJoin('catalog_product_entity_' . $attribute['backend_type'] . ' as ' . $attributeCode, 'catalog_product_entity.entity_id', '=', $attributeCode . '.entity_id');
                $productQuery->where($attributeCode . '.attribute_id', '=', $attribute['id']);
                $select[] = $attributeCode . '.value as ' . $this->toCamelCase($attributeCode);

            }

            //Add any additional where filters for any parameters that are being searched for
            foreach($queries as $attributeCode => $attributeValue)
            {
                //make sure it's snake case
                $attributeCode = $this->toSnakeCase($attributeCode);
                $eavTypeId = $this->attributeHelper->getEavEntityTypeId(Laragento\EavEntityType::TYPE_PRODUCT);
                $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);

                if(!$attribute)
                    throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this produt');

                if(strpos($attributeValue, '*') !== false)
                    $productQuery->where($attributeCode . '.value', 'like', str_replace('*', '%', $attributeValue));
                else
                    $productQuery->where($attributeCode . '.value', '=', $attributeValue);

            }

        }

        $productQuery->select($select);

        $products = $productQuery->remember(1)->get();

        $productsResult = array();

        //Build a more formal output array
        foreach($products as $product){
            $productsResult[] = $this->buildProductJson($product, false, true);
        }

        return Response::json($productsResult);
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

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
        $product = $this->getProduct($id);

        return Response::json($product);
	}


    /**
     * Get a product either from redis or from the database
     *
     * @param $id
     * @return array
     * @throws Symfony\Component\Translation\Exception\InvalidResourceException
     */
    protected function getProduct($id)
    {
        $queryRequired = false;

        $product = Redis::hgetall('product:'.$id);

        if(!$product)
            $queryRequired = true;

        //We need to figure out if the fields that were requested exist in cache or not
        if($this->fields !== false){

            //If we don't yet have to make a query, make sure that all of the attributes are set that we need
            if(!$queryRequired)
            {
                foreach($this->fields as $attribute)
                {
                    $attribute = $this->toCamelCase($attribute);
                    if(!array_key_exists($attribute, $product)){
                        $queryRequired = true;
                        break;
                    }
                }
            }
        }

        //If we have determined that we actually need to make a query to the mysql database..
        if($queryRequired)
        {
            $productQuery = Laragento\Product::where('catalog_product_entity.entity_id', '=', $id);

            //note that we are normalizing all primary keys to 'id' - we might regret this later
            $select = array('catalog_product_entity.entity_id as id', 'sku');

            //Check if any ?fields= came in on the query string (or anywhere else) and process those if so
            if($this->fields !== false){

                if(in_array('inventory', $this->fields)){
                    $productQuery->leftJoin('cataloginventory_stock_item as inventory', 'catalog_product_entity.entity_id', '=', 'inventory.product_id');
                    $select[] = 'inventory.qty as inventory';

                    if(($key = array_search('inventory', $this->fields)) !== false) {
                        unset($this->fields[$key]);
                    }
                }

                foreach($this->fields as $attributeCode)
                {
                    //make sure it's snake case
                    $attributeCode = $this->toSnakeCase($attributeCode);
                    $eavTypeId = $this->attributeHelper->getEavEntityTypeId(Laragento\EavEntityType::TYPE_PRODUCT);
                    $attribute = $this->attributeHelper->getEavAttribute($attributeCode, $eavTypeId);

                    if(!$attribute)
                        throw new \Symfony\Component\Translation\Exception\InvalidResourceException($attributeCode . ' does not exist for this produt');

                    $productQuery->leftJoin('catalog_product_entity_' . $attribute['backend_type'] . ' as ' . $attributeCode, 'catalog_product_entity.entity_id', '=', $attributeCode . '.entity_id');
                    $productQuery->where($attributeCode . '.attribute_id', '=', $attribute['id']);
                    $select[] = $attributeCode . '.value as ' . $this->toCamelCase($attributeCode);

                }

            }

            $productQuery->select($select);

            $product = $productQuery->firstOrFail();

        }

        return $this->buildProductJson($product, $queryRequired, true);
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
     * Builds a formal array of product data, and if specified caches the product data with redis
     *
     * @param $product the product information
     * @param bool $cache should these values be cached
     * @param bool $includeHref should the href attribute be included
     * @return array
     */
    protected function buildProductJson($product, $cache = false, $includeHref = false)
    {
        if(is_array($product))
            $product = json_decode(json_encode($product), FALSE);

        $this->path = URL::to('/api/' . $this->apiVersion . '/products');


        $cacheData = array();

        $returnProduct = array(
            'id' => $product->id,
            'sku' => $product->sku
        );

        if($this->fields !== false){
            foreach($this->fields as $attributeCode)
            {
                $attributeCode = $this->toCamelCase($attributeCode);
                $returnProduct[$this->toCamelCase($attributeCode)] = $product->$attributeCode;
            }
        }

        if(isset($product->inventory)){
            $returnProduct['inventory'] = $product->inventory;
        }


        if($cache)
        {
            foreach($returnProduct as $key => $value)
            {
                $cacheData[] = $this->toCamelCase($key);
                $cacheData[] = $value;
            }

            Redis::hmset('product:' . $returnProduct['id'], $returnProduct);
            Redis::expire('product:' . $returnProduct['id'], Config::get('app.laragento.cache.product.seconds'));
        }

        //Shoudl we include the href to the resource?
        if($includeHref)
            $returnProduct['href'] = $this->path . '/' . $product->id;

        return $returnProduct;
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

        $queryParameters = array_keys($queries);

        $this->fields = $this->fields ? array_merge($this->fields,$queryParameters) : $queryParameters;

        $this->fields = array_unique($this->fields);

        return $queries;

    }

}