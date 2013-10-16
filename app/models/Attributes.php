<?php

class Attributes{

    /**
     * Load all of the attribute types from Magento's tables and store in redis cache
     *
     * @return bool did it work?
     */
    public function loadAttributeTypes()
    {
        $eavEntityTypes = EavEntityType::all();

        Redis::pipeline(function($pipe) use ($eavEntityTypes)
        {
            foreach($eavEntityTypes as $eavType)
            {
                /** @var EavEntityType $eavType */
                $pipe->set("eavEntityType:".$eavType->entity_type_code, $eavType->entity_type_id);
            }
        });

        return true;
    }

    /**
     * Given a type code (i.e. "catalog_product") returns the associated primary key
     *
     * @param $typeCode
     * @return mixed
     */
    public function getEavEntityTypeId($typeCode)
    {
        $redis = Redis::connection();

        $typeId = $redis->get('eavEntityType:'.$typeCode);

        if(!$typeId)
        {
            $this->loadAttributeTypes();
            $typeId = $redis->get('eavEntityType:'.$typeCode);
        }

        return $typeId;

    }


    /**
     * Load all EAV attributes into redis cache
     *
     * @param $typeCode
     * @return bool
     */
    public function loadEavAttributes($typeCode)
    {

        $eavAttributes = EavAttribute::where('entity_type_id', '=', $typeCode)->get();

        Redis::pipeline(function($pipe) use ($eavAttributes, $typeCode)
        {
            foreach($eavAttributes as $eavAttribute)
            {
                $pipe->hmset('eavAttribute:' . $typeCode . ':' . $eavAttribute->attribute_code,
                    'id',
                    $eavAttribute->attribute_id,
                    'backend_type',
                    $eavAttribute->backend_type,
                    'entity_type_id',
                    $eavAttribute->entity_type_id,
                    'frontend_label',
                    $eavAttribute->frontend_label
                );
            }
        });

        return true;
    }

    /**
     * Load a specific attribute code
     *
     * @param $attributeCode
     * @param $attributeTypeCode
     * @return mixed
     */
    public function getEavAttribute($attributeCode, $attributeTypeCode)
    {
        $attribute = Redis::hgetall('eavAttribute:'.$attributeCode);

        //If the attribute isn't in redis' cache then we need to try to load it
        if(!$attribute)
        {
            $this->loadEavAttributes($attributeTypeCode);
            $attribute = Redis::hgetall('eavAttribute:' . $attributeTypeCode .':'.$attributeCode);
        }

        return $attribute;

    }

}