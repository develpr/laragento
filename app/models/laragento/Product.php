<?php

namespace Laragento;
use \Eloquent;

class Product extends Eloquent {

    protected $table = 'catalog_product_entity';
    protected $primaryKey = 'entity_id';

}