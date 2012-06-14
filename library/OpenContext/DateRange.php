<?php

class OpenContext_DateRange {

const maxFacetNumber = 10;

public static function make_date_range_facets($solr_start_array, $solr_end_array){ 
    //old depricated function, only here just in case there's some reference to it    
   return null;
}//end function



//this makes a space seperated time span and preps them for facets
public static function alt_timeSpanFacetOutput($solr_tspan_array){
    
    // $solr_tspan_array is an array of solr time span facet values
    
    if(count($solr_tspan_array)<1){
        return false;
    }
    
    
    $lowFreqDates = array(); //array of dates with less frequency
    $highFreqDates = array();
    $dateStartOrder = array();
    $i=0;
    foreach($solr_tspan_array as $spanKey => $count){
        $actSpanArray = explode(" ", $spanKey);
        $actBegin = $actSpanArray[0];
        $actEnd = $actSpanArray[1];
        $sortValue = $actBegin + ($actEnd / 1000000); //use the start value to sort by, use the end as a less important part of sorting
        $dateStartOrder[$spanKey] =  $sortValue;
        if($i >= self::maxFacetNumber){
            $lowFreqDates[$spanKey] = $sortValue;
        }
        else{
            $highFreqDates[$spanKey] = $sortValue;
        }
    $i++;
    }
    asort($dateStartOrder);
    
    $keyBreaks = array();
    $prevLatest = -14000000000; //hopefully nothing older than the known universe...
    foreach($dateStartOrder as $spanKey =>$val){
        $actSpanArray = explode(" ", $spanKey);
        $actBegin = $actSpanArray[0];
        $actEnd = $actSpanArray[1];
        if($actBegin >= $prevLatest){
            $actKey = $spanKey;
            $keyBreaks[$actKey] = array();
        }
        
        $keyBreaks[$actKey][] = $spanKey;
        
        if($actEnd > $prevLatest){
            $prevLatest = $actEnd;
        }
    }
    
    if(count($keyBreaks) <= self::maxFacetNumber){
        $sortedKeys = $keyBreaks; //below max number of facets
        unset($keyBreaks);
    }
    else{
        $sortedKeys = array();
        $highFreqKeys = array_keys($highFreqDates);
        $jj = 0;
        foreach($keyBreaks as $ignoreKey => $groupedKeys){
            if($jj < self::maxFacetNumber){
                $actKey = $ignoreKey;    
            }
            
            foreach($groupedKeys as $actGkey){
                $sortedKeys[$actKey][] = $actGkey;
            }
            $jj++;
        }//end loop
    
    }
    
    $sortedSpans = array();
    foreach($sortedKeys as $ignoreKey => $groupedKeys){
        $groupTotalFacetCount = 0;
        foreach($groupedKeys as $spanKey){
            $groupTotalFacetCount += $solr_tspan_array[$spanKey];
        }
        
        if(count($groupedKeys)>1){
            $totalSpanString = implode(" ", $groupedKeys); //combine all the span together
            $totalSpanArray = explode(" ", $totalSpanString); //break into array
            $finalSpan = (min($totalSpanArray))." ".(max($totalSpanArray));
        }
        else{
            $finalSpan = $groupedKeys[0];
        }
        
        $sortedSpans[$finalSpan] = $groupTotalFacetCount;
    }//end loop
    unset($solr_tspan_array);
    
    
    //echo print_r($sortedSpans);
    
    
    //$sortedSpans is now the array of time spans and facet counts, sorted by early to late dates
    $output = array();
    foreach($sortedSpans as $spanKey => $count){
        $datesArray = explode(" ", $spanKey);    
        $start = $datesArray[0]; //use the start value to sort by
        $end = $datesArray[1];
        $uriParams = "t-start=".$start."&t-end=".$end;
        $display = OpenContext_DateRange::bce_ce_note($start);
        $display .= " to ".OpenContext_DateRange::bce_ce_note($end);
        $output[] = array("uri_param" => $uriParams,
                          "display"=> $display,
                          "t-start" => $start,
                          "t-end" => $end,
                          "count"=> $count);
    }
    
    //echo print_r($output);
    
    return $output;
}//end function



//this makes a space seperated time span and preps them for facets
public static function timeSpanFacetOutput($solr_tspan_array){
    
    // $solr_tspan_array is an array of solr time span facet values
    
    if(count($solr_tspan_array)<1){
        return false;
    }
    
    
    $lowFreqDates = array(); //array of dates with less frequency
    $highFreqDates = array();
    $dateStartOrder = array();
    $i = 0;
    foreach($solr_tspan_array as $spanKey => $count){
        $datesArray = explode(" ", $spanKey);    
        $dateStartOrder[$spanKey] = $datesArray[0] + ($datesArray[1] / 1000000); //use the start value to sort by, use the end as a less important part of sorting
        
        if($i >= self::maxFacetNumber){
            $lowFreqDates[$spanKey] = $datesArray[0] + ($datesArray[1] / 1000000);
        }
        else{
             $highFreqDates[$spanKey] = $datesArray[0] + ($datesArray[1] / 1000000);
        }
        
    $i++;
    }
    
    asort($dateStartOrder);
    asort($highFreqDates);
    
    /*
    This makes sure that the high frequency keys are always have start dates later than the previous key's end-date
    */
    $checkedRangeHF = array();
    $ll = 0;
    $prevEnd = -14000000000; //hopefully nothing older than the known universe...
    foreach($highFreqDates as $spanKey => $val){
        $actSpanArray = explode(" ", $spanKey);
        $actBegin = $actSpanArray[0];
        $actEnd = $actSpanArray[1];
        if($actBegin >= $prevEnd){
            $checkedRangeHF[$spanKey] = $val;
        }
        else{
            $lowFreqDatesp[$spanKey] = $val;
        }
        $prevEnd = $actEnd;
    }
    
    unset($highFreqDates);
    $highFreqDates = $checkedRangeHF;
    unset($checkedRangeHF);

    $highFreqKeys = array_keys($highFreqDates);
        
    //this next part groups spankeys together, so that low frequency keys are bundled with high frequency keys
    $sortedKeys = array();
    $actKey = $highFreqKeys[0];
    foreach($dateStartOrder as $spanKey => $sortVal){
        
        if(array_key_exists($spanKey, $highFreqDates)){
            $actKey = $spanKey;
        }
        if(array_key_exists($actKey, $sortedKeys)){
            $sortedKeys[$actKey][] = $spanKey;
        }
        else{
            $sortedKeys[$actKey] = array();
            $sortedKeys[$actKey][] = $spanKey;
        }
    }    
    
    
    $sortedSpans = array();
    foreach($sortedKeys as $ignoreKey => $groupedKeys){
        $groupTotalFacetCount = 0;
        foreach($groupedKeys as $spanKey){
            $groupTotalFacetCount += $solr_tspan_array[$spanKey];
        }
        
        if(count($groupedKeys)>1){
            $totalSpanString = implode(" ", $groupedKeys); //combine all the span together
            $totalSpanArray = explode(" ", $totalSpanString); //break into array
            $finalSpan = (min($totalSpanArray))." ".(max($totalSpanArray));
        }
        else{
            $finalSpan = $groupedKeys[0];
        }
        
        $sortedSpans[$finalSpan] = $groupTotalFacetCount;
    }//end loop
    unset($solr_tspan_array);
    
    
    //echo print_r($sortedSpans);
    
    
    //$sortedSpans is now the array of time spans and facet counts, sorted by early to late dates
    $output = array();
    foreach($sortedSpans as $spanKey => $count){
        $datesArray = explode(" ", $spanKey);    
        $start = $datesArray[0]; //use the start value to sort by
        $end = $datesArray[1];
        $uriParams = "t-start=".$start."&t-end=".$end;
        $display = OpenContext_DateRange::bce_ce_note($start);
        $display .= " to ".OpenContext_DateRange::bce_ce_note($end);
        $output[] = array("uri_param" => $uriParams,
                          "display"=> $display,
                          "t-start" => $start,
                          "t-end" => $end,
                          "count"=> $count);
    }
    
    //echo print_r($output);
    
    return $output;
}//end function


public static function bce_ce_note($dec_time){
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


}