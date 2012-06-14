<?php

function date_check($date_array, $check_date_early, $check_date_late, $last_end_index, $keep_end=true){
//this function adds facet counts between specified date ranges
//it returns an array where:
//the 1st value is the end-date of a range
//the 2nd value is the total number of items within that range
//the 3rd value is the last index in the date array that met the date-range criteria
//the last index value returned so the whole array does not need to be searched each time
    
    $count_dates = count($date_array);
    $date_keys = array_keys($date_array);
    $item_count = 0;
    $jj = $last_end_index;
    while($jj<$count_dates){
        $act_date = $date_keys[$jj];
        
        if(($act_date>=$check_date_early)&&($act_date<$check_date_late)){
            
            $item_count = $item_count + $date_array[$act_date];
            
            if($check_date_late>2000){
                $check_date_late = $act_date;
            }
            
            if($keep_end){
                $end_date = $check_date_late;
            }
            else{
                $end_date = $act_date;
            }
            
            $last_end_index = $jj;
            
        }
        
        $jj++;
    }
    
    $end_count = array($end_date,$item_count,$last_end_index);
    
    return $end_count;
}//end function


function bce_ce_note($dec_time){
//this function creates human readible dates, with a CE, BCE notation
//large values have a K for thousands or an M for millions appended

	$abs_time = abs($dec_time);
	
	if($dec_time<0){
			$suffix = " BCE";
		}
		else{
			$suffix = " CE";
		}
	
	if($abs_time<10000){
	    if($dec_time<0){
                $output = (number_format($abs_time)).$suffix;
	    }
            else{
                $output = round($abs_time,0).$suffix;
            }
	}//end case with less than 10,000
	else{
		
                if($abs_time<1000000){
                    $rnd_time = round($abs_time/1000,1);
                    $output = (number_format($rnd_time))."K".$suffix;
                }
                else{
                    $rnd_time = round($abs_time/1000000,2);
                    $output = (number_format($rnd_time))."M".$suffix;
                }
	}
	
	return $output;

}//end function


function make_date_range_facets($solr_start_array, $solr_end_array){

    $standard_date_range = array(
    -2000000,
    -1500000,
    -1000000,
    -750000,
    -500000, 
    -250000, 
    -100000, 
    -40000, 
    -20000, 
    -10000,
    -7500,
    -5000, 
    -4000,
    -3000,
    -2000,
    -1500,
    -1000,
    -750,
    -500, 
    -250, 
    0, 
    250, 
    500, 
    1000, 
    1500, 
    1750, 
    1900, 
    1950   
    );

    //make sure the end dates are sorted by their key. this helps
    //the code loop through this array of dates only once
    $date_end_array = $solr_end_array;
    ksort($date_end_array);
    
    
    $date_start = array_keys($solr_start_array);
    $count_start = count($date_start); //number of different start dates
    $early_date = min($date_start); //eariest date for this set
    
    $date_end = array_keys($solr_end_array); 
    $late_date = max($date_end); //latest date for this set
    
    //get the date range for this set
    $drange= abs($late_date - $early_date);
    
    
    
    $i=0;
    $incrementer = 1;
    //this section tests to see if the standard date range should be applied
    //a standard date range should be used if the set's date range is large
    //and has many different start values
    $do_standard = false;
    if(($count_start>10)&&($drange>100)&&($date_end>1500)){
        $do_standard = true;
    }
    if(($count_start>10)&&($drange>250)&&($date_end>-500)){
        $do_standard = true;
    }
    if(($count_start>10)&&($drange>250)&&($date_end>-1000)){
        $do_standard = true;
    }
    if(($count_start>10)&&($drange>500)&&($date_end>-1500)){
        $do_standard = true;
    }
    if(($count_start>10)&&($drange>1000)&&($date_end>-5000)){
        $do_standard = true;
    }
    if(($count_start>10)&&($drange>2500)&&($date_end>-10000)){
        $do_standard = true;
    }
    
    //$do_standard = true;
     
    if($do_standard){
        //instead of using the solr returned array of different start dates
        //use the standard list of start dates defined at the begining of this function
        $date_start = $standard_date_range;
        $count_start = count($date_start);
    }
    else{
        sort($date_start);
        if($count_start>15){
            //this makes sure that there aren't more than 15 facets returned to the user
            $incrementer = $count_start/15;
            $incrementer = floor($incrementer);
        }
    }
    
    $last_found = true;
    $last_end_index = 0;
    $index_facets = 0;
    $result_facets = array();
    while($i<$count_start){
        
        if($i<($count_start - $incrementer)){
           $next_start = $date_start[$i + $incrementer]; 
        }
        else{
            $next_start = 30000; // a date far in the future, to make sure we cover all date ranges
        }
        
        if($last_found){
            $act_start = $date_start[$i];
        }
        
        if($next_start>$early_date){
            $end_count = date_check($date_end_array, $act_start, $next_start, $last_end_index, $do_standard);
            
            if($end_count[1]>0){
                $last_found = true;
                $last_end_index = $end_count[2];
                $human_read = bce_ce_note($act_start)." to ".bce_ce_note($end_count[0]);
                $facet_count = $end_count[1];
                $uri_parameters = "t_start=".$act_start."&t_end=".$end_count[0];
                //$solr_parameters = "time_start:[".$act_start." TO ".$end_count[0]."]&&time_end:[".$act_start." TO ".$end_count[0]."]";
                //echo "<br/>".$human_read." count: ".$facet_count."<br/> <em>uri parameters: ".$uri_parameters." <br/>";
                //echo "solr parameters: ".$solr_parameters."</em><br/><br/>";
                
                $result_facets[$index_facets]=array("display"=>$human_read, "count"=>$facet_count, "uri_param"=>$uri_parameters);
                
                $index_facets++;
            }
            else{
                $last_found = false;
            }
        }
        
        $i = $i + $incrementer;
    }//end while loop 

    return $result_facets;

    //Results are as Follows:
    //for each $result_facet
    //the key "display" is the human readible date range
    //the key "count" is the facet count
    //the key "uri_param" is the uri parameter that will add this date-facet filter
}//end FUNCTION make_date_range_facets



//$date_array["start"] = array("-200"=>119769,"-6500"=>22871,"-1100"=>5914,"-200000"=>529,"1990"=>522,"-2900"=>70,"-700"=>8);
//$date_array["end"] = array("360"=>119769,"-5500"=>22871,"-650"=>5914,"-40000"=>529,"1995"=>522,"-2400"=>55,"-550"=>23);

$result_facets = make_date_range_facets($facet_fields["time_start"],$facet_fields["time_end"]);
echo var_dump($result_facets);

?>