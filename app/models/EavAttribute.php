<?php

class EavAttribute extends Eloquent {

    protected $table = 'eav_attribute';
    protected $primaryKey = 'attribute_id';

    const TYPE_PRODUCT = "catalog_product";
    const TYPE_CUSTOMER = "customer";
    const TYPE_CUSTOMER_ADDRESS = "customer_address";
    const TYPE_CATEGORY = "catalog_category";
    const TYPE_ORDER = "order";

}