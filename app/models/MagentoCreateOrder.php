<?php

use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Client;

class MagentoCreateOrder
{

    public function fire($job, $data)
    {
        // Create a client to work with the Twitter API
        $client = new Client('http://magentopos.infielddesign.com');

        $request = $client->post('api/rest/restnow/orders');
        $data = json_encode(array('store_id' => 1, 'quote_id' => $data['quoteId']));
        $request->setBody($data, 'application/json');
        $request->setHeader("Accept", "application/json");

        $response = $request->send();

        Log::info('This is some other useful information.' . $data['quoteId']);

        $job->delete();

    }

}