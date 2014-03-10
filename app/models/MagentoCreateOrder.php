<?php

use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client;

class MagentoCreateOrder
{

    public function fire($job, $data)
    {
//        // Create a client to work with the Twitter API
//        $client = new Client('http://magentopos.com');
//
//        $request = $client->post('api/rest/restnow/quotes');
//        $data = json_encode(array('store_id' => 1));
//        $request->setBody($data, 'application/json');
//        $request->setHeader("Accept", "application/json");
//
//        $response = $request->send();
//        /** @var \Guzzle\Http\Message\Header $location */
//        $quoteLocation = $response->getHeader('Location');
//
//        $quoteLocation = str_replace('/api/rest/restnow/quotes/','',$quoteLocation);
//        $quoteId = str_replace('/store/1', '', $quoteLocation);
//
//        return array('quote_id' => $quoteId);


        Log::info('This is some useful information.');

        $job->delete();

    }

}