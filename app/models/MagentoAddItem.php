<?php

use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client;

class MagentoAddItem
{

    public function fire($job, $data)
    {
//        // Create a client to work with the Twitter API
//        $client = new Client('http://magentopos.com');
//
//        $request = $client->post('api/rest/restnow/quoteItems');
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
//

        Log::info('This is some other useful information.');

        $job->delete();

    }

}