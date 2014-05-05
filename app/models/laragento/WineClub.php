<?php

namespace Laragento;
use \Eloquent;
use \URL;

class WineClub extends MagentoResource {

    protected $table = 'wineclub_club';
    protected $primaryKey = 'entity_id';

    protected $resourceName = 'wineClub';
    protected $routeName = 'wineClubs';

    //todo: need to complete this, including build json response object and relationship to product (?)


    public static function uri($apiVersion, $id)
    {
        return parent::resourceUri('wineClubs', $apiVersion, $id);
    }


    public function group()
    {
        return $this->belongsTo('Laragento\WineGroup', 'club_group');
    }

    public function customers()
    {
        return $this->belongsToMany('Laragento\Customer', 'wineclub_member', 'customer_id', 'club_id');
    }


    public function scopeOfCustomer($query, Customer $customer)
    {
        $blah = "hi";
        return $query;
    }

    public function prepareOutput($apiVersion)
    {

        $return = array(
            'id'    => $this->entity_id,
            'name'  => $this->name,
            'group' => $this->group->name,
            'href'  => $this->path = self::uri($apiVersion, $this->entity_id)
        );

        return $return;
    }

}