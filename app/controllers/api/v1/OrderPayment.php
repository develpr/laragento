<?php

namespace Api\V1;

use Laragento;
use \Input;
use \Config;
use \Response;

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
		$resourceQuery = Laragento\OrderPayment::take($limit)->skip($offset);

		$select = array('*');

		$resourceQuery->select($select);

		$orderPayments = $resourceQuery->get();

		$outputOrderPayments = array();

		foreach($orderPayments as $orderPayment){
			$outputOrderPayments[] = $orderPayment->prepareOutput($this->apiVersion);
		}

		return Response::json($outputOrderPayments);

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

		//todo: this does nothing at this point with the orderItems
		$order = Laragento\Order::findOrFail($id);

		if(Input::has('with') && Input::get('with') == 'orderItems'){
			$orderItems = $order->orderItems;
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