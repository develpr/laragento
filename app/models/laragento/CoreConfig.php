<?php

namespace Laragento;
use \Eloquent;
use \URL;
use \Redis;

/**
 * Class EavEntityType
 * @property-read string $entity_type_id
 * @property-read string $entity_type_code
 */
class CoreConfig extends Eloquent implements MagentoResource{

    protected $table = 'core_config_data';
    protected $primaryKey = 'config_id';

    protected $resourceName = 'customer';
    protected $routeName = 'configurations';

    public function prepareOutput($apiVersion)
    {
        $return = array(
            'id' => $this->id,
            'path' => $this->path,
            'scope' => $this->scope,
            'value' => $this->value,
            'href' => $this->path = URL::to('/api/' . $apiVersion . '/' . $this->routeName . '/' . $this->id)
        );

        return $return;
    }

}