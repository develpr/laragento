<?php

namespace Laragento;
use \Eloquent;

class Customer extends Eloquent {

    protected $table = 'customer_entity';
    protected $primaryKey = 'entity_id';

}