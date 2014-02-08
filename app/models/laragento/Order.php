<?php

namespace Laragento;

class Order extends MagentoResource {

	protected $table = 'sales_flat_order';
	protected $primaryKey = 'entity_id';

	protected $resourceName = 'order';
	protected $routeName = 'orders';

	protected $apiVersion = 'v1';

	public static function uri($apiVersion, $id)
	{
		return parent::resourceUri('orders', $apiVersion, $id);
	}

	public function orderItems()
	{
		return $this->hasMany('Laragento\OrderItem', 'order_id');
	}

	public function prepareOutput($apiVersion)
	{
		$return = array(
			'id' => $this->entity_id,
			'href' => $this->path = self::uri($apiVersion, $this->entity_id),
			'status' => $this->status,
			'state' => $this->state,
			'customerFirstname' => $this->customer_firstname,
			'customerLastname' => $this->customer_lastname,
			'customerEmail' => $this->customer_email,
			'customerId' => $this->customer_id,
			'customerUri' => Customer::uri($apiVersion, $this->customer_id),
			'discountAmount' => $this->base_discount_amount,
			'grandTotal' => $this->base_grand_total,
			'shippingAmount' => $this->base_shipping_amount,
			'subtotal' => $this->base_subtotal,
			'subtotalInvoiced' => $this->base_subtotal_invoiced,
			'subtotalRefunded' => $this->base_subtotal_refunded,
			'taxAmount' => $this->base_tax_amount,
			'taxInvoiced' => $this->base_tax_invoiced,
			'totalInvoiced' => $this->base_total_invoiced,
			'totalPaid' => $this->base_total_paid,
			'grandTotal' => $this->grand_total,
			'billingAddressId' => $this->billing_address_id,
			'billingAddressUri' => CustomerAddress::uri($apiVersion, $this->billing_address_id),
			'shippingAddressId' => $this->shipping_address_id,
			'shippingAddressUri' => CustomerAddress::uri($apiVersion, $this->shipping_address_id),
			'quiteId' => $this->quote_id,
			'shippingDescription' => $this->shipping_description,
			'subtotalInclTax' => $this->base_subtotal_incl_tax,
			'weight' => $this->weight,
			'incrementId' => $this->increment_id,
			'currencyCode' => $this->base_currency_code,
			'totalItemCount' => $this->total_item_count,
			'isGift' => $this->is_gift
		);

		return $return;
	}

}