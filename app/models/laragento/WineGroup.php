<?php

namespace Laragento;
use \Eloquent;
use \URL;

class WineGroup extends MagentoResource {

    protected $table = 'wineclub_club_group';
    protected $primaryKey = 'entity_id';

    protected $resourceName = 'wineGroup';
    protected $routeName = 'wineGroups';


    public static function uri($apiVersion, $id)
    {
        return parent::resourceUri('wineGroups', $apiVersion, $id);
    }


    public function clubs()
    {
        return $this->hasMany('Laragento\WineClub', 'club_group');
    }


    public function prepareOutput($apiVersion)
    {

        $return = array(
            'id' => $this->entity_id,
            'href' => $this->path = self::uri($apiVersion, $this->entity_id)
        );

        return $return;
    }

}