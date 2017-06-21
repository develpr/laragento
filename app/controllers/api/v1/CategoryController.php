<?php

namespace Api\V1;

use \Response;
use \Input;
use Laragento;

class CategoryController extends EavController {

    public function __construct(){
        $this->apiVersion = 'v1';
        $this->specialFields = array('id', 'path');
        $this->entityType = Laragento\EavEntityType::TYPE_CATEGORY;
        $this->resourceName = 'category';
        $this->routeName = 'categories';
        $this->primaryTable = 'catalog_category_entity'; //todo: can probably use the Model's table instead
        $this->attributeTablePrefix = 'catalog_category_entity_';
        $this->baseModel = new Laragento\Category();

        parent::__construct();

    }

}
