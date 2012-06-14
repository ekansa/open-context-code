<?php

//Open Context: Copyright (2007) the Alexandria Archive Institute (AAI), All rights reserved.
//Version July 2007
//Authored by: Eric Kansa (on behalf of the AAI)
//
//The AAI releases Open Context software under the Free Software Foundation (FSF) GNU-General Public License (Version 3). 
//See full license text here: http://www.gnu.org/copyleft/gpl.html
//
//DISCLAIMER OF LIABILITY:  The authors of this software do not and cannot 
//exercise any control whatsoever over the content of the information 
//exchanged using this software.  The authors make no warranties of any 
//kind, whether expressed or implied, for the service this software is 
//providing or for the data exchanged with the assistance of this software. 
//The authors cannot be held responsible for any claims resulting from the 
//user's conduct and/or use of the software which is in any manner unlawful 
//or which damages such user or any other party.
//
//ADDITIONAL NOTE: This software is released in draft form, with little or no
//documentation. Database schema will be released shortly.
//
//SCRIPT NOTE:
//This script outputs results of the current selection to excel 
//Most of this script was original developed by another person (I'm not sure who)
//Here's where I found it: http://fundisom.com/phparadise/php/databases/mySQL_to_excel



//this function fixes variable ids to be nice as MySQL field values
function varid_short($varid){
	$output = "v_".(md5($varid));
	return $output;
}


for($i=0; $i<6; $i++){
	$random_string .= chr(rand(0,25)+65);
}

$nowsuffix = genid();
$tabname = "ex_o_".$random_string.$nowsuffix;



mysql_connect(localhost,$username,$password);
@mysql_select_db($database) or die( "Unable to select the stupid database");





//This query finds all the text variables used by items in the current selection

$varquery="SELECT DISTINCT var_tab.variable_uuid, var_tab.var_label, var_tab.var_type, projects.proj_name
FROM selection
JOIN observe ON selection.uuid = observe.subject_uuid
JOIN projects ON var_tab.project_id = projects.project_id
LEFT JOIN properties ON observe.property_uuid = properties.property_uuid
LEFT JOIN var_tab ON properties.variable_uuid = var_tab.variable_uuid
WHERE selection.session_id = '$sesid' 
AND var_tab.variable_uuid IS NOT NULL;
";

$varresults = mysql_query($varquery);
$numvar = mysql_numrows($varresults);



$varcnt =0;
$varquerycls = "";
$varcreate = "";
$varmod = "";
$numfields = $numvar + 4; //number of fields to be written in the Excel table
$wfield[0] = "Project";
$wfield[1] = "Item";
$wfield[2] = "Context";
$wfield[3] = "Authors";
$rfield[0] = "Project";
$rfield[1] = "Item";
$rfield[2] = "Context";
$rfield[3] = "Authors";





while ($varcnt <  $numvar) {

		$curvarid[$varcnt] = mysql_result($varresults,$varcnt,"variable_uuid");
		$curvartype[$varcnt] = mysql_result($varresults,$varcnt,"var_type");
		$curvarname[$varcnt] = mysql_result($varresults,$varcnt,"var_label");
		$curvarproj[$varcnt] = mysql_result($varresults,$varcnt,"proj_name");
		
		$act_var_id = varid_short($curvarid[$varcnt]);
		$wfield[4+$varcnt] = $curvarname[$varcnt]." (".$curvarproj[$varcnt].")";
		$rfield[4+$varcnt] =  $act_var_id;

		

		if ($varcnt < 1)

			{
			$colselect = $tabname.".Project, ".$tabname.".Item, ".$tabname.".Context, ".$tabname.".Authors, ".$tabname.".".$act_var_id;

			$nullvals = " NULL";
			}
		else
			{
			$colselect = $colselect.", ".$tabname.".".$act_var_id;
			$nullvals = $nullvals.",  NULL";
			}

		

		if ( ($curvartype[$varcnt] == "Calendric")||($curvartype[$varcnt] == "Calendar")||($curvartype[$varcnt] == "Nominal")||($curvartype[$varcnt] == "Ordinal")||($curvartype[$varcnt] == "Boolean") ||($curvartype[$varcnt] == "Alphanumeric") ||($curvartype[$varcnt] == "NOTES") )

			{

			$varcreate = $varcreate." ".$act_var_id."  TINYTEXT,";
			}

		if (  ($curvartype[$varcnt] == "Integer")||($curvartype[$varcnt] == "Decimal")||($curvartype[$varcnt] == "Serial"))

			{

			$varcreate = $varcreate." ".$act_var_id."  DOUBLE,";

			}

$varcnt++;

}//end loop through all cur project variables




//$varcreate = str_replace("-", "", $varcreate);
//$varcreate = str_replace("TINYTEXT", " TINYTEXT", $varcreate);
//$varcreate = str_replace("DOUBLE", " DOUBLE", $varcreate);
//deletes an existing session's query table if it exists
$cleanslate = "DROP TABLE IF EXISTS $tabname;";
mysql_query($cleanslate);




	//create a new querying table for this project and session
	$createquery = "CREATE TEMPORARY TABLE $tabname  (
					uuid varchar(50),
					Project varchar(250),
					Item varchar(250),
					Context varchar(250),
					Authors varchar(250),
					$varcreate
					PRIMARY KEY (uuid)
					) type=HEAP;
					";

					

			$createquery = "CREATE TABLE $tabname  (
					uuid varchar(50),
					Project varchar(250),
					Item varchar(250),
					Context varchar(250),
					Authors varchar(250),
					$varcreate
					PRIMARY KEY (uuid)
					) type=MyISAM;
					";	

mysql_query($createquery);
//print $createquery ;





	//add record of the temporary table so it can be dropped later
	$user_agent = getenv("HTTP_USER_AGENT");
	$q_agent = addslashes($user_agent);

	$add_rec = "INSERT INTO temp_tabs (user_type, tab_name)
	VALUES ('$q_agent', '$tabname')";
	
	mysql_query($add_rec);




$colls_alter = "selection.uuid, projects.proj_name, space.space_label, space_context.context_view, NULL,".$nullvals;

$acolls_alter = explode(",",$colls_alter);



$create_cols = "uuid varchar(50), Project varchar(250), Item varchar(250), Context varchar(250), Authors varchar(250),".$varcreate;

$acreate = explode(",",$create_cols);





//print "<br>Number of values to insert".(count($acolls_alter))."<br>";

//print "<br>Number of collumns".(count($acreate))."<br>";



//populates the excel output table with space uuid's for the current selection

$addspacequery = "INSERT IGNORE INTO $tabname (uuid, Project, Item, Context)
				SELECT DISTINCT selection.uuid, projects.proj_name,
				space.space_label, space_context.context_view
				FROM selection
				JOIN space ON selection.uuid = space.uuid
				LEFT JOIN space_context ON selection.uuid = space_context.uuid
				JOIN projects ON space.project_id = projects.project_id
				WHERE selection.session_id = '$sesid';
				";

mysql_query($addspacequery);



//***********************************************************
//***********************************************************
//this next part finds all the authors for each item of content

$itemlistquery = "SELECT $tabname.uuid
FROM $tabname
";



$itemlist = mysql_query($itemlistquery);
$numitems = mysql_numrows($itemlist);
$itemcnt = 0;

while ($itemcnt <  $numitems) {

	$id =  mysql_result($itemlist,$itemcnt,"uuid");

	//this finds people linked to a given item

	$persquery = "SELECT persons.person_uuid, persons.combined_name, links.link_type
	FROM space
	INNER JOIN links ON space.uuid = links.origin_uuid
	INNER JOIN persons ON links.targ_uuid = persons.person_uuid
	WHERE space.uuid = '$id' AND links.targ_type ='person'
	ORDER BY links.link_type;
	";

	$persresult = mysql_query($persquery);
	$numpers = mysql_numrows($persresult);
	$authors= "";

	$i=0;
	while ($i < $numpers) {

		$persid = mysql_result($persresult,$i,"person_uuid");
		$persname = mysql_result($persresult,$i,"combined_name");
		$act_role =mysql_result($persresult,$i,"link_type");
		$author_ok = is_ok_author($act_role);
		
		if (($i<1)&&($author_ok)){
			$authors = $persname;
		}
		
		if (($i>=1)&&($author_ok)){
			$authors .= ", ".$persname;
		}
		
	$i++;
	}//end loop that gets people linked to an item

	$authors = addslashes($authors);

	$updatequery = "UPDATE $tabname 
		SET  $tabname.Authors = '$authors'
		WHERE $tabname.uuid = '$id'
		"; 

	mysql_query($updatequery);

$itemcnt++;
}// end itemcnt loop









//populates the excel output table with properties and values for desciptive properties.		
$varcnt =0;
$valjoins = "";
while ($varcnt <  $numvar) {
	$updatevar = varid_short($curvarid[$varcnt]);
	$act_varid = $curvarid[$varcnt];
	$updatetype = $curvartype[$varcnt];

		$updatequery = "UPDATE $tabname 
		INNER JOIN observe ON $tabname.uuid = observe.subject_uuid
		INNER JOIN properties ON observe.property_uuid = properties.property_uuid
		LEFT JOIN val_tab ON properties.value_uuid = val_tab.value_uuid
		SET  $tabname.$updatevar = (
		IF (val_tab.val_text IS NULL , ( IF (
		properties.val_date =0, properties.val_num, properties.val_date)), val_tab.val_text)
		)
		WHERE properties.variable_uuid = '$act_varid'
		"; 

	mysql_query($updatequery);

$varcnt++;
}//end loop adding values to the output table





//DEFINE SQL QUERY:
//you can use just about ANY kind of select statement you want -
//edit this to suit your needs!

$sql = "SELECT $colselect FROM $tabname";

//print $sql;



$result = mysql_query($sql);
$cnt_rows = mysql_numrows($result);



//deletes an existing session's query table if it exists

$cleanslate = "DROP TABLE IF EXISTS $tabname;";

mysql_query($cleanslate);





mysql_close();



$xcol = 0;
while($xcol < $numfields){

	$actfield = $rfield[$xcol];
	
	$yrow = 0;
	while($yrow < $cnt_rows){
		$wr_cell[$xcol][$yrow]=mysql_result($result,$yrow, $actfield);
	$yrow++;
	}//end adding row data

$xcol++;
}// end adding collumn data





//if this parameter is included ($w=1), file returned will be in word format ('.doc')
//if parameter is not included, file returned will be in excel format ('.xls')

if (isset($w) && ($w==1)){

     $file_type = "msword";
     $file_ending = "doc";

}else {
     $file_type = "vnd.ms-excel";
     $file_ending = "xls";
}

//header info for browser: determines file type ('.doc' or '.xls')

header("Content-Type: application/$file_type");
header("Content-Disposition: attachment; filename=Your_cur_selection.$file_ending");
header("Cache-Control: public");
//header("Pragma: no-cache");
header("Expires: 0");



/*    Start of Formatting for Word or Excel    */

if (isset($w) && ($w==1)) //check for $w again
{
     /*    FORMATTING FOR WORD DOCUMENTS ('.doc')   */
     //create title with timestamp:

     if ($Use_Title == 1)
     {
         echo("$title\n\n");
     }

     //define separator (defines columns in excel & tabs in word)
     $sep = "\n"; //new line character
	 $row = 0;
     while($row < $cnt_rows)
     {
         //set_time_limit(60); // HaRa

         $schema_insert = ""; 
         for($j=0; $j < $numfields; $j++)
         {

         	//define field names
         	$field_name = $wfield[$j];
         	//will show name of fields

         	$schema_insert .= "$field_name:\t";

             if(!isset($wr_cell[$j])) {
                 $schema_insert .= "NULL".$sep;
                 }

             elseif ($wr_cell[$j] != "") {
                 $schema_insert .= $wr_cell[$j][$row].$sep;
                 }
             else {
                 $schema_insert .= "".$sep;
             }
         }//end for loop

         $schema_insert = str_replace($sep."$", "", $schema_insert);
         $schema_insert .= "\t";

         print(trim($schema_insert));

         //end of each mysql row
         //creates line to separate data from each MySQL table row
         print "\n----------------------------------------------------\n";

     $row++;
     }//end of row counting

}else{

     /*    FORMATTING FOR EXCEL DOCUMENTS ('.xls')   */
     //create title with timestamp:

     if ($Use_Title == 1)
     {
         echo("$title\n");
     }

     //define separator (defines columns in excel & tabs in word)
     $sep = "\t"; //tabbed character

     //start of printing column names as names of MySQL fields
     for ($i = 0; $i < $numfields; $i++){
	     $wfield[$i] = str_replace($sep."$", "", $wfield[$i]);
		 $wfield[$i] = str_replace("&#305;", "i", $wfield[$i]);
		 $wfield[$i] = str_replace("&#350", "S", $wfield[$i]);
		 $wfield[$i] = str_replace("&#351;", "s", $wfield[$i]);
		 $wfield[$i] = str_replace("&#199;", "C", $wfield[$i]);
		 $wfield[$i] = str_replace("&#231;", "c", $wfield[$i]);
		 $wfield[$i] = str_replace("&#214;", "O", $wfield[$i]);
		 $wfield[$i] = str_replace("&#246;", "o", $wfield[$i]);
		 $wfield[$i] = str_replace("&#334;", "O", $wfield[$i]);
		 $wfield[$i] = str_replace("&#335;", "o", $wfield[$i]);
       	echo $wfield[$i]."\t";
     }//end for loop

     print("\n");
     //end of printing column names

     //start while loop to get data

	 $row = 0;
     while($row < $cnt_rows)
     {
         //set_time_limit(60); // HaRa

         $schema_insert = "";
         for($j=0; $j<$numfields; $j++)
         {

             if(!isset($wr_cell[$j]))
                $schema_insert .= "".$sep;
             elseif ($wr_cell[$j] != "")
                 $schema_insert .= $wr_cell[$j][$row].$sep;
             else
                 $schema_insert .= "".$sep;
         }//end for loop

         $schema_insert = str_replace($sep."$", "", $schema_insert);
		 $schema_insert = str_replace("&#305;", "i", $schema_insert);
		 $schema_insert = str_replace("&#350", "S", $schema_insert);
		 $schema_insert = str_replace("&#351;", "s", $schema_insert);
		 $schema_insert = str_replace("&#199;", "C", $schema_insert);
		 $schema_insert = str_replace("&#231;", "c", $schema_insert);
		 $schema_insert = str_replace("&#214;", "O", $schema_insert);
		 $schema_insert = str_replace("&#246;", "o", $schema_insert);
		 $schema_insert = str_replace("&#334;", "O", $schema_insert);
		 $schema_insert = str_replace("&#335;", "o", $schema_insert);

         //following fix suggested by Josue (thanks, Josue!)
         //this corrects output in excel when table fields contain \n or \r
         //these two characters are now replaced with a space

         $schema_insert = preg_replace("/\r\n|\n\r|\n|\r/", " ", $schema_insert);
	     $schema_insert .= "\t";

         print(trim($schema_insert));
         print "\n";

	 $row++;

     }//end of row counting

}

?>

