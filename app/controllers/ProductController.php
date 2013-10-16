<?php

class ProductController extends \BaseController {

    protected $apiVersion = 'v1';
    protected $attributeHelper = null;

    protected $path;

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{

        $limit = Input::has('limit') ? Input::get('limit') : Config::get('app.laragento.defaults.pagination.limit');
        $offset = Input::has('offset') ? Input::get('offset') : Config::get('app.laragento.defaults.pagination.offset');

        $products = Product::select('entity_id as id', 'sku')->skip($offset)->take($limit)->remember(1)->get();

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
        if(!$this->attributeHelper)
            $this->attributeHelper = new Attributes;

        $queryRequired = false;

        $product = Redis::hgetall('product:'.$id);

        if(!$product)
            $queryRequired = true;

        //We need to figure out if the fields that were requested exist in cache or not
        if(Input::has('fields')){

            $fields = array_map('trim',explode(',', Input::get('fields')));

            //If we don't yet have to make a query, make sure that all of the attributes are set that we need
            if(!$queryRequired)
            {
                foreach($fields as $attribute)
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
            $productQuery = Product::where('catalog_product_entity.entity_id', '=', $id);

            //note that we are normalizing all primary keys to 'id' - we might regret this later
            $select = array('catalog_product_entity.entity_id as id', 'sku');

            //Check if any ?fields= came in on the query string (or anywhere else) and process those if so
            if(Input::has('fields')){

                $fields = array_map('trim',explode(',', Input::get('fields')));

                if(($key = array_search('id', $fields)) !== false) {
                    unset($fields[$key]);
                }
                if(($key = array_search('sku', $fields)) !== false) {
                    unset($fields[$key]);
                }


                if(in_array('inventory', $fields)){
                    $productQuery->leftJoin('cataloginventory_stock_item as inventory', 'catalog_product_entity.entity_id', '=', 'inventory.product_id');
                    $select[] = 'inventory.qty as inventory';

                    if(($key = array_search('inventory', $fields)) !== false) {
                        unset($fields[$key]);
                    }
                }

                foreach($fields as $attributeCode)
                {
                    //make sure it's snake case
                    $attributeCode = $this->toSnakeCase($attributeCode);
                    $eavTypeId = $this->attributeHelper->getEavEntityTypeId(EavEntityType::TYPE_PRODUCT);
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

        if(Input::has('fields')){
            $fields = array_map('trim',explode(',', Input::get('fields')));
            foreach($fields as $attributeCode)
            {
                $attributeCode = $this->toCamelCase($attributeCode);
                $returnProduct[$this->toCamelCase($attributeCode)] = $product->$attributeCode;
            }
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

}