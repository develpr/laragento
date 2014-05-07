<?php

namespace Api\V1;

use Laragento;
use \Input;
use \Config;
use \Response;
use Guzzle\Http\Client;

class OrderController extends \BaseController {

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
		$resourceQuery = Laragento\Order::take($limit)->skip($offset);

		$select = array('*');

		$resourceQuery->select($select);

		$orders = $resourceQuery->get();

		$outputOrders = array();

		foreach($orders as $order){
			$outputOrders[] = $order->prepareOutput($this->apiVersion);
		}

		return Response::json($outputOrders);

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
        // Create a client to work with the Twitter API
        $client = new Client(Config::get('app.laragento.storeUrl'));

        $quoteId = Input::get('quote_id');

        $request = $client->post('api/rest/restnow/orders');
        $data = json_encode(array(
            'store_id' => 1,
            'quote_id' => $quoteId,
            'payment_method' => 'authorizenet',
            'cc_number' => '4242424242424242',
            'cc_type' => "VI",
            'cc_expiration_month' => '12',
            'cc_expiration_year' => '18',
            'cc_cvv' => '923'
        ));
        $request->setBody($data, 'application/json');
        $request->setHeader("Accept", "application/json");

        /** @var Guzzle\Http\Message\Response $response */
        $response = $request->send();

        /** @var Guzzle\Http\Message\Header $location */
        $location = $response->getHeader('location');

        $orderId =  str_replace('/api/rest/restnow/orders/', '', $location);

        $order = Laragento\Order::find($orderId);

        //todo: should actually redirect to the quote, but we're not sending the CORS header then
        return Response::json($order, 201);

    }

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{

		//todo: this does nothing at this point with the orderItems
		$order = Laragento\Order::findOrFail($id);

		if(Input::has('with') && Input::get('with') == 'orderItems'){
			$orderItems = $order->orderItems;
            $orderPayments = $order->orderPayments;
            $customer = $order->customer;
		}

		$test = "HI";

		return($order->prepareOutput($this->apiVersion));
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

}