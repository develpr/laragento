<?php

namespace Laragento;
use \Eloquent;

class OrderItem extends Eloquent {

	protected $table = 'sales_flat_order_item';
	protected $primaryKey = 'entity_id';

	//todo: need to complete this, including build json response object and relationship to product (?)

}