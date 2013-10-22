<?php

namespace Laragento;
use \Eloquent;
use \URL;

class Customer extends Eloquent {

    protected $table = 'customer_entity';
    protected $primaryKey = 'entity_id';

    protected $specialFields = array('id');
    protected $entityType = EavEntityType::TYPE_CUSTOMER;
    protected $resourceName = 'customer';
    protected $routeName = 'customers';
    protected $attributeTablePrefix = 'customer_entity_';


    public function addresses()
    {
        return $this->hasMany('Laragento\CustomerAddress', 'parent_id');
    }


    public function prepareOutput($apiVersion)
    {
        $return = array(
            'id' => $this->entity_id,
            'href' => $this->path = URL::to('/api/' . $apiVersion . '/' . $this->routeName . '/' . $this->entity_id)
        );

        return $return;
    }

}