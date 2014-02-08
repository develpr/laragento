<?php

namespace Laragento;
use \Eloquent;

class OrderPayment extends MagentoResource {

	protected $table = 'sales_flat_order_payment';
	protected $primaryKey = 'entity_id';

	//todo: need to complete this, including build json response object and relationship to product (?)


    public static function uri($apiVersion, $id)
    {
        return parent::resourceUri('orderPayments', $apiVersion, $id);
    }


    public function order()
	{
		return $this->hasOne('Laragento\Order', 'parent_id');
	}


	public function prepareOutput($apiVersion)
	{

		$return = array(
			'id' => $this->entity_id,
			'href' => $this->path = self::uri($apiVersion, $this->entity_id),
			'method' => $this->method,
            'shippingCaptured' => $this->base_shipping_captured,
            'amountPaid' => $this->base_amount_paid,
            'amountOrdered' => $this->base_amount_ordered,
		);

		return $return;
	}

}