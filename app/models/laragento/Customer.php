<?php

namespace Laragento;

class Customer extends MagentoResource {

    protected $table = 'customer_entity';
    protected $primaryKey = 'entity_id';

    protected $specialFields = array('id');
    protected $entityType = EavEntityType::TYPE_CUSTOMER;
    protected $resourceName = 'customer';
    protected $routeName = 'customers';
    protected $attributeTablePrefix = 'customer_entity_';

	public static function uri($apiVersion, $id)
	{
		return self::resourceUri('customers', $apiVersion, $id);
	}

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