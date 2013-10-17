<?php

namespace Api\V1;

use Laragento;

use \Input;
use \Response;
use \Request;
use \Redis;
use \URL;
use \Config;

use \Symfony\Component\Translation\Exception\InvalidResourceException;

class CustomerAddressController extends EavConroller{

    public function __construct(){

        $this->apiVersion = 'v1';
        $this->specialFields = array('id');
        $this->entityType = Laragento\EavEntityType::TYPE_CUSTOMER_ADDRESS;
        $this->resourceName = 'customer_address';
        $this->routeName = 'customerAddresses';
        $this->primaryTable = 'customer_address_entity'; //todo: can probably use the Model's table instead
        $this->attributeTablePrefix = 'customer_address_entity_';
        $this->baseModel = new Laragento\CustomerAddress;

        parent::__construct();

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {

        //Get the standard EAV results
        $result = parent::prepareShow($id);

        if(Input::has('with') && Input::get('with') == "customer"){
            $address = Laragento\CustomerAddress::find($id);
            $customer = $address->customer;

            $customerOutput = $customer->prepareOutput($this->apiVersion);

            $result['customer'] = $customerOutput;
        }

        return $result;

    }

}