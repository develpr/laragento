<?php

namespace Laragento;
use \Eloquent;

class QuoteItem extends Eloquent {

    protected $table = 'sales_flat_quote_item';
    protected $primaryKey = 'entity_id';

    public function quote()
    {
        return $this->belongsTo('Quote', 'quote_id');
    }
}