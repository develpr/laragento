<?php namespace Laragento;

use Response;
use Request;
use Input;
use Redis;

class Cart
{
    private $quote = null;
    private $quoteItems = null;
    private $session = null;

    public function __construct()
    {
        if(array_key_exists('frontend', $_COOKIE))
            $this->session = $_COOKIE['frontend'];
        else
            return View::make('hello');

        $this->session = Session::find($this->session)->session_data;

        if(array_key_exists('customer_id', $this->session['visitor_data']))
            $this->quote = Quote::customer($this->session['visitor_data']['customer_id'])->first();
        else if(array_key_exists('quote_id', $this->session['visitor_data']))
            $this->quote = Quote::findOrFail($this->session['visitor_data']['quote_id']);
        //todo: else we should make a new visitor cart!

        if($this->quote)
            $this->quoteItems = $this->quote->quoteItems;

    }

    public function removeProducts(array $products)
    {
        foreach($products as $product)
            $this->removeProduct($product);
    }

    public function removeProduct(Product $product)
    {
        $redisProductPrice = Redis::hget('product:' . $product->entity_id . ':0:price', 'default');

        $quoteItem = $this->quote->quoteItems()->where('product_id', '=', $product->entity_id)->first();
        if($quoteItem)
        {
            $quoteItem->delete();
        }
    }

    public function addProducts(array $products)
    {
        foreach($products as $product)
            $this->addProduct($product);
    }

    public function addProduct(Product $product)
    {
        $redisProduct = Redis::hgetall('product:' . $product->entity_id . ':0:data');

        //todo: need to check customer group to get proper price
        $redisProductPrice = Redis::hget('product:' . $product->entity_id . ':0:price', 'default');

        $quoteItem = $this->quote->quoteItems()->where('product_id', '=', $product->entity_id)->first();
        if($quoteItem)
        {
            $quoteItem->qty = $quoteItem->qty+1;
            $quoteItem->sku = $product->sku;
            $quoteItem->is_virtual = false;
            $quoteItem->name = $product->name;
            $quoteItem->row_total = $quoteItem->qty * $redisProductPrice;
            $quoteItem->base_row_total = $quoteItem->qty * $redisProductPrice;
            $quoteItem->row_total_incl_tax = $quoteItem->qty * $redisProductPrice;
            $quoteItem->row_weight = $redisProduct['weight'];
            $quoteItem->save();
        }
        else
        {
            $newQuoteItem = new QuoteItem;
            $newQuoteItem->product_id = $product->entity_id;
            $newQuoteItem->store_id = 1;
            $newQuoteItem->sku = $product->sku;
            $newQuoteItem->is_virtual = false;
            $newQuoteItem->name = $product->name;
            $newQuoteItem->price = $redisProductPrice;
            $newQuoteItem->base_price = $redisProductPrice;
            $newQuoteItem->row_total = $redisProductPrice;
            $newQuoteItem->base_row_total = $redisProductPrice;
            $newQuoteItem->price_incl_tax = $redisProductPrice;
            $newQuoteItem->row_total_incl_tax = $redisProductPrice;
            $newQuoteItem->row_weight = $redisProduct['weight'];
            $newQuoteItem->qty = 1;
            $newQuoteItem->product_type = $product->type_id;

            $this->quote->quoteItems()->save($newQuoteItem);
        }

    }

}