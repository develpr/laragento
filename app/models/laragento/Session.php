<?php

namespace Laragento;
use \Eloquent;

class Session extends Eloquent {

    protected $table = 'core_session';
    protected $primaryKey = 'session_id';
    protected $appends = array('is_customer', 'customer_id', 'visitor_id');

    public function getSessionDataAttribute($data)
    {
        $data = substr($data, 5);
        return unserialize($data);
    }



}