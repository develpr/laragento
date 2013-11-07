<?php

namespace Laragento;
use \Eloquent;

class Quote extends Eloquent {

    protected $table = 'sales_flat_quote';
    protected $primaryKey = 'entity_id';

    public function scopeCustomer($query, $customerId)
    {
        return $query->whereCustomerId($customerId)->whereIsActive(true)->orderBy('created_at', 'desc')->get();
    }

    public function quoteItems()
    {
        return $this->hasMany('Laragento\QuoteItem');
    }

}