<?php

/**
 * Class EavEntityType
 * @property-read string $entity_type_id
 * @property-read string $entity_type_code
 */
class EavEntityType extends Eloquent {

    protected $table = 'eav_entity_type';
    protected $primaryKey = 'entity_type_id';

    const TYPE_PRODUCT = "catalog_product";
    const TYPE_CUSTOMER = "customer";
    const TYPE_CUSTOMER_ADDRESS = "customer_address";
    const TYPE_CATEGORY = "catalog_category";
    const TYPE_ORDER = "order";

}