<?php

class MeasurementUnits{
    
    public $units = array(
                    
                    "http://www.freebase.com/view/en/counting_measure" => array("sType" => "Count",
                                                                      "name" => "counting measure",
                                                                      "abrv" => "count"
                                                                      ),
                    
                    "http://www.freebase.com/view/m/01x32f_" => array("sType" => "Mass",
                                                                      "name" => "milligram",
                                                                      "abrv" => "mg"
                                                                      ),
                    
                    "http://www.freebase.com/view/en/gram" => array("sType" => "Mass",
                                                                      "name" => "gram",
                                                                      "abrv" => "g"
                                                                      ),
                    
                    "http://www.freebase.com/view/en/kilogram" => array("sType" => "Mass",
                                                                      "name" => "kilogram",
                                                                      "abrv" => "kg"
                                                                      ),
                    
                    "http://www.freebase.com/view/en/micrometer" => array("sType" => "Length",
                                                                      "name" => "micrometer / micron",
                                                                      "abrv" => "µm"
                                                                      ),
                    
                    "http://www.freebase.com/view/en/millimeter" => array("sType" => "Length",
                                                                      "name" => "millimeter",
                                                                      "abrv" => "mm"
                                                                      ),
                    
                    "http://www.freebase.com/view/en/centimeter" => array("sType" => "Length",
                                                                      "name" => "centimeter",
                                                                      "abrv" => "cm"
                                                                      ),
                    
                    "http://www.freebase.com/view/en/meter" => array("sType" => "Length",
                                                                      "name" => "meter",
                                                                      "abrv" => "m"
                                                                      ),
                    
                    "http://www.freebase.com/view/en/kilometer" => array("sType" => "Length",
                                                                      "name" => "kilometer",
                                                                      "abrv" => "m"
                                                                      ),
                    
                    "http://www.freebase.com/view/en/liter" => array("sType" => "Volume",
                                                                      "name" => "liter",
                                                                      "abrv" => "L"
                                                                      ),
                    
                    "http://sw.opencyc.org/2008/06/10/concept/Mx4rHs7hMuxiQdaeRI29oZztbw" => array("sType" => "Density",
                                                                      "name" => "grams per liter",
                                                                      "abrv" => "grams per L"
                                                                      ),
                    
                    "http://en.wikipedia.org/wiki/Count_per_Liter" => array("sType" => "Density",
                                                                      "name" => "number density (count per liter)",
                                                                      "abrv" => "count per L"
                                                                      ),
                    
                    "http://www.w3.org/2003/01/geo/wgs84_pos#alt" => array("sType" => "Geospatial (WGS84)",
                                                                      "name" => "altitude (meters)",
                                                                      "abrv" => "alt (m)"
                                                                      ),
                    
                    "http://www.w3.org/2003/01/geo/wgs84_pos#long" => array("sType" => "Geospatial (WGS84)",
                                                                      "name" => "longitude (WGS84)",
                                                                      "abrv" => "lon"
                                                                      ),
                    
                    "http://www.w3.org/2003/01/geo/wgs84_pos#lat" => array("sType" => "Geospatial (WGS84)",
                                                                      "name" => "latitude (WGS84)",
                                                                      "abrv" => "lat"
                                                                      )
                    
                         );
    
    
    function URI_toUnit($uri){
        $units = $this->units;
        if(array_key_exists($uri, $units)){
            return $units[$uri];
        }
        else{
            return false;
        }
    }
    
    
}//end class


?>