<?php

namespace Api\V1;

use Laragento;
use \Input;
use \Config;
use \Response;
use \Queue;

class OrderItemController extends \BaseController {

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
        Queue::push('MagentoAddItem', array('productId' => Input::get('productId'), 'quoteId' => Input::get('quoteId')));
        return array('success' => true);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{

        return array(true);
		//return($order->prepareOutput($this->apiVersion));
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