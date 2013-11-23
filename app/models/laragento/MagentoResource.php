<?php namespace Laragento;

interface MagentoResource{

    /**
     * Prepares the API output for a given resource
     *
     * @param $apiVersion
     * @return mixed
     */
    public function prepareOutput($apiVersion);

}