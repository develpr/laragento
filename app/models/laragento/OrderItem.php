<?php

namespace Laragento;
use \Eloquent;

class OrderItem extends Eloquent {

	protected $table = 'sales_flat_order_item';
	protected $primaryKey = 'item_id';

	//todo: need to complete this, including build json response object and relationship to product (?)

	public function order()
	{
		return $this->hasOne('Laragento\Order', 'entity_id');
	}


	public function prepareOutput($apiVersion)
	{

		$return = array(
			'id' => $this->entity_id,
			'href' => $this->path = self::uri($apiVersion, $this->entity_id),
			'status' => $this->status,
			'state' => $this->state,
			'customerId' => $this->customer_id,
			'customerUri' => Customer::uri($apiVersion, $this->customer_id),
			'discountAmount' => $this->base_discount_amount,
		);

		return $return;
	}

}