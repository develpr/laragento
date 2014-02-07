<?php


namespace Laragento;

class CustomerAddress extends MagentoResource {
    
    protected $table = 'customer_address_entity';
    protected $primaryKey = 'entity_id';

    protected $specialFields = array('id');
    protected $entityType = EavEntityType::TYPE_CUSTOMER_ADDRESS;
    protected $resourceName = 'customer_address';
    protected $routeName = 'customerAddresses';
    protected $attributeTablePrefix = 'customer_address_entity_';

	public static function uri($apiVersion, $id)
	{
		return parent::resourceUri('customerAddresses', $apiVersion, $id);
	}


	public function customer()
    {
        return $this->belongsTo('Laragento\Customer', 'parent_id');
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