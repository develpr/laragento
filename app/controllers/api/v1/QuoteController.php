<?php

namespace Api\V1;

use Laragento;
use \Input;
use \Config;
use \Response;
use Guzzle\Http\Client;

class QuoteController extends \BaseController {

    protected $apiVersion = 'v1';
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $limit = Input::has('limit') ? Input::get('limit') : Config::get('app.laragento.defaults.pagination.limit');
        $offset = Input::has('offset') ? Input::get('offset') : Config::get('app.laragento.defaults.pagination.offset');

        //todo: generic
        $resourceQuery = Laragento\Quote::take($limit)->skip($offset);

        $select = array('*');

        $resourceQuery->select($select);

        $quotes = $resourceQuery->get();

        $outputQuotes = array();

        foreach($quotes as $quote){

            $outputQuotes[] = $quote->prepareOutput($this->apiVersion);
        }

        return Response::json($outputQuotes);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        // Create a client to work with the Twitter API
        $client = new Client(Config::get('app.laragento.storeUrl'));

        $request = $client->post('api/rest/restnow/quotes');
        $data = json_encode(array('store_id' => 1));
        $request->setBody($data, 'application/json');
        $request->setHeader("Accept", "application/json");

        $response = $request->send();
        /** @var \Guzzle\Http\Message\Header $location */
        $quoteLocation = $response->getHeader('Location');

        $quoteLocation = str_replace('/api/rest/restnow/quotes/','',$quoteLocation);
        $quoteId = str_replace('/store/1', '', $quoteLocation);

        return array('quote_id' => $quoteId);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {

        $quote = new Laragento\Quote($id);

        return($quote->prepareOutput($this->apiVersion));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {

        $client = new Client(Config::get('app.laragento.storeUrl'));

        $data = array(
            'store_id' => 1,
            'quote_id' => Input::get('quote_id'),
            'product_id' => Input::get('product_id'),
        );

        if(Input::has('quantity'))
            $data['qty'] = Input::get('quantity');

        $request = $client->post('api/rest/restnow/quoteItems');
        $data = json_encode($data);
        $request->setBody($data, 'application/json');
        $request->setHeader("Accept", "application/json");

        $response = $request->send();


    }

}