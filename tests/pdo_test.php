<?php

use BastMain\utils\IdCardTool;

require_once '../vendor/autoload.php';

class pdo_test
{


    public function __construct()
    {
        $IdCardTool = new IdCardTool();
        $a = $IdCardTool->getAddress(441623199609153711);
        var_dump($a);
        /*try {
            $areas = PDOTool::fetchall('select areaCode,detail from s_area_code where 1');

            $arr = [];
            foreach($areas as $area)
            {
                $arr[$area['areaCode']] = $area['detail'];
            }

            var_dump($arr);
            file_put_contents('./area.json',json_encode($arr));

        }catch (\Exception $exception)
        {
            var_dump($exception->getMessage());
        }*/

    }
}

$pdo_test = new  pdo_test();
