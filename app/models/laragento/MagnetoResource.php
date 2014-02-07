<?php

namespace Laragento;
use \Eloquent;
use \URL;

class MagentoResource extends Eloquent {

	protected static function resourceUri($routeName, $apiVersion = "v1", $id = "")
	{
		$base = URL::to('/api/' . $apiVersion . '/' . $routeName);

		return ($id) ? $base . '/' . $id : $base;
	}

}