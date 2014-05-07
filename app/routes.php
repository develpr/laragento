<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function()
{
//
//    if(array_key_exists('frontend', $_COOKIE))
//        $session = $_COOKIE['frontend'];
//    else
//        return View::make('hello');
//
//    $session = Laragento\Session::find($session)->session_data;
//
//    if(array_key_exists('customer_id', $session['visitor_data']))
//        $quote = Laragento\Quote::customer($session['visitor_data']['customer_id'])->first();
//    else if(array_key_exists('quote_id', $session['visitor_data']))
//        $quote = Laragento\Quote::findOrFail($session['visitor_data']['quote_id']);
//    else
//        return View::make('hello');
//
//    $quoteItems = $quote->quoteItems;
//    $product = Laragento\Product::find(18);
//
//    $newQuoteItem = new Laragento\QuoteItem;
//    $price = '19.99';
//    $newQuoteItem->product_id = $product->entity_id;
//    $newQuoteItem->store_id = 1;
//    $newQuoteItem->sku = $product->sku;
//    $newQuoteItem->is_virtual = false;
//    $newQuoteItem->name = "Great Price";
//    $newQuoteItem->price = $price;
//    $newQuoteItem->base_price = $price;
//    $newQuoteItem->row_total = $price;
//    $newQuoteItem->base_row_total = $price;
//    $newQuoteItem->price_incl_tax = $price;
//    $newQuoteItem->row_total_incl_tax = $price;
//    $newQuoteItem->row_weight = 5;
//    $newQuoteItem->qty = 1;
//    $newQuoteItem->product_type = $product->type_id;
//
//    $quote->quoteItems()->save($newQuoteItem);
//
//
////
////    $newQuoteItem->
////
//
//    foreach($quoteItems as $quoteItem){
//        /** @var Laragento\QuoteItem $quoteItem */
//        echo $quoteItem->name;
//        echo "<br />";
//    }

    return View::make('hello');

});

Route::resource('api/v1/products', 'Api\V1\ProductController');
Route::resource('api/v1/categories', 'Api\V1\CategoryController');
Route::resource('api/v1/customers', 'Api\V1\CustomerController');
Route::resource('api/v1/customerAddresses', 'Api\V1\CustomerAddressController');
Route::resource('api/v1/configurations', 'Api\V1\CoreConfigController');
Route::resource('api/v1/orders', 'Api\V1\OrderController');
Route::resource('api/v1/wineClubs', 'Api\V1\WineClubController');
Route::resource('api/v1/quotes', 'Api\V1\QuoteController');


Route::get('test', function(){

    $client = new Guzzle\Http\Client(Config::get('app.laragento.storeUrl'));

    $quoteId = 1780;

    $request = $client->post('api/rest/restnow/orders');
    $data = json_encode(array(
        'store_id' => 1,
        'quote_id' => $quoteId,
        'payment_method' => 'authorizenet',
        'cc_number' => '4242424242424242',
        'cc_type' => "VI",
        'cc_expiration_month' => '12',
        'cc_expiration_year' => '18',
        'cc_cvv' => '923'
    ));
    $request->setBody($data, 'application/json');
    $request->setHeader("Accept", "application/json");

    /** @var Guzzle\Http\Message\Response $response */
    $response = $request->send();

    /** @var Guzzle\Http\Message\Header $location */
    $location = $response->getHeader('location');

    $orderId =  str_replace('/api/rest/restnow/orders/', '', $location);

    return Redirect::to('/api/v1/orders/'.$orderId);
});