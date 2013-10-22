<?php

namespace Api\V1;

use Laragento;
use \Input;
use \Config;
use \Response;

class CoreConfigController extends \BaseController {

    protected $apiVersion = 'v1';
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
        $limit = Input::has('limit') ? Input::get('limit') : Config::get('app.laragento.defaults.pagination.limit');
        $offset = Input::has('offset') ? Input::get('offset') : Config::get('app.laragento.defaults.pagination.offset');

        //todo: generic
        $resourceQuery = Laragento\CoreConfig::take($limit)->skip($offset);

        $select = array('config_id as id', 'scope', 'path', 'value');

        $resourceQuery->select($select);

        $configs = $resourceQuery->get();

        $outputConfigs = array();

        foreach($configs as $config){
            $outputConfigs[] = $config->prepareOutput($this->apiVersion);
        }

        return Response::json($outputConfigs);

    }

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{

        $config = new Laragento\CoreConfig;

        $config->get('base_url_secure');
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}

}