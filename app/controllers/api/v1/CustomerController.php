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

class CustomerController extends EavController{

    public function __construct(){
        $this->apiVersion = 'v1';
        $this->specialFields = array('id', 'email');
        $this->defaultFields = array('firstname', 'lastname');
        $this->entityType = Laragento\EavEntityType::TYPE_CUSTOMER;
        $this->resourceName = 'customer';
        $this->routeName = 'customers';
        $this->primaryTable = 'customer_entity'; //todo: can probably use the Model's table instead
        $this->attributeTablePrefix = 'customer_entity_';
        $this->baseModel = new Laragento\Customer;

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

        if(Input::has('with') && Input::get('with') == "addresses"){
            $customer = Laragento\Customer::find($id);
            $addresses = $customer->addresses;

            $addressOutput = array();

            foreach($addresses as $address){
                $addressOutput[] = $address->prepareOutput($this->apiVersion);
            }

            $result['addresses'] = $addressOutput;
        }

        return $result;
	}

}
