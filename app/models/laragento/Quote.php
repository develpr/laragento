<?php

namespace Laragento;
use \Eloquent;

class Quote extends Eloquent {

    protected $table = 'sales_flat_quote';
    protected $primaryKey = 'entity_id';
    protected $routeName = 'quotes';

    public function scopeCustomer($query, $customerId)
    {
        return $query->whereCustomerId($customerId)->whereIsActive(true)->orderBy('created_at', 'desc')->get();
    }

    public function quoteItems()
    {
        return $this->hasMany('Laragento\QuoteItem');
    }

    public function prepareOutput($apiVersion)
    {
        $return = array(
            'id'    => $this->entity_id,
            'total'    => $this->grand_total,
            'subtotal' => $this->subtotal,
            'href'  => URL::to('/api/' . $apiVersion . '/' . $this->routeName . '/' . $this->entity_id)
        );

        return $return;
    }

}