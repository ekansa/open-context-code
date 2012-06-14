<?php

if (!isset($_COOKIE["opencon_name"]))
{
$user ="Guest";
} 
else 
{
$user = $_COOKIE["opencon_name"];
}



?>



<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Open Context: New User Registration</title>



<script language="JavaScript" type="text/javascript">
<!--
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}


function hideDiv(pass) {
var divs = document.getElementsByTagName('div');
for(i=0;i<divs.length;i++){
if(divs[i].id.match(pass)){//if they are 'see' divs
if (document.getElementById) // DOM3 = IE5, NS6
divs[i].style.visibility="hidden";// show/hide
else
if (document.layers) // Netscape 4
document.layers[divs[i]].display = 'hidden';
else // IE 4
document.all.hideShow.divs[i].visibility = 'hidden';
}
}
}

function showDiv(pass) {
var divs = document.getElementsByTagName('div');
for(i=0;i<divs.length;i++){
if(divs[i].id.match(pass)){
if (document.getElementById)
divs[i].style.visibility="visible";
else
if (document.layers) // Netscape 4
document.layers[divs[i]].display = 'visible';
else // IE 4
document.all.hideShow.divs[i].visibility = 'visible';
}
}
}


//-->
</script>




<style type="text/css">
<!--

#Tabs {
	position:absolute;
	width:792px;
	height:29px;
	z-index:1;
	left: 2px;
	top: 94px;
}
#Banner {
	position:absolute;
	width:792px;
	height:94px;
	z-index:2;
	left: 2px;
	top: 0px;
}
#namelayer {
	position:absolute;
	width:516px;
	height:22px;
	z-index:3;
	left: 277px;
	top: 75px;
}
#footer {
	position:absolute;
	width:792px;
	height:50px;
	z-index:2;
	left: 0px;
	top: 528px;
}
-->
</style>

<link href="new_aai_style.css" rel="stylesheet" type="text/css" />

<style type="text/css">
<!--
#instructions {
	position:absolute;
	width:560px;
	height:40px;
	z-index:4;
	left: 135px;
	top: 152px;
}
#pagename {
	position:absolute;
	width:532px;
	height:29px;
	z-index:5;
	left: 135px;
	top: 128px;
}
#unparea {
	position:absolute;
	width:560px;
	height:139px;
	z-index:6;
	left: 135px;
	top: 211px;
}
#tool {
	position:absolute;
	width:94px;
	height:40px;
	z-index:7;
	left: 8px;
	top: 180px;
}
#outmes {
	position:absolute;
	width:560px;
	height:126px;
	z-index:8;
	left: 135px;
	top: 394px;
}
#errmes {
	position:absolute;
	width:560px;
	height:157px;
	z-index:8;
	left: 135px;
	top: 360px;
}
-->
</style>


<script type="text/javascript">
	var djConfig = {isDebug: false, debugAtAllCosts: false };
</script>
<script type="text/javascript" src="../../../dojo.js"></script>
<script type="text/javascript">
	dojo.require("dojo.lang.*");
	dojo.require("dojo.html");
	dojo.require("dojo.html.*");
	dojo.require("dojo.widget.*");
	dojo.require("dojo.io.*");
	dojo.require("dojo.event.topic");

	dojo.hostenv.writeIncludes();
	djConfig.isDebug=1;

	var tuser = "";
	var tpass = "";
	var rbutton = '<input name="submit" type="submit" id="submit" value="Register" />';
 	var rwait = "<table><tr id='prmes_1'><td width = '200'>";
	rwait = rwait + "Preparing Page...";
	rwait = rwait + "</td><td><img src='ui_images/loadingif.gif'></td></tr></table>";

	function comp_names(){
		var fname = dojo.byId('f_f_name').value;
		var minitial = dojo.byId('f_m_initial').value;
		var lname = dojo.byId('f_l_name').value;
		var all_initials = fname.substr(0,1) + minitial + lname.substr(0,1);
		dojo.byId('f_initals').value = all_initials;
		if (minitial.length >0){
			minitial = minitial +".";
		}
		var full_name = fname + " " + minitial + " " + lname;
		dojo.byId('f_full_name').value = full_name;
		
	}

	function ch_srole(){
		var frole = dojo.byId('f_slist').value;
		dojo.byId('f_srole').value = frole;
	}

	
	
	function makePane(){
	var mess=dojo.widget.byId("mesarea");
		mess.restoreWindow();
	}//end makePane funcito
	
	function hidemess(){
		var mess=dojo.widget.byId("mesarea");
		mess.minimizeWindow();
	}
	
	function a_form_init() {
	var goodout = dojo.byId("unparea");
	var badout = dojo.byId("errmes");
	var overbut = '<input name="submit" type="submit" id="submit" value="Try Again" />';
	var x = new dojo.io.FormBind({
		formNode: "f_unamepass",
		//formNode: document.forms[0],
		load: function(type, data, e) {
			
			var dout = data;
			if(dout.substring(0,5) == "<!--y" ){
				badout.innerHTML = " ";
				goodout.innerHTML = dout;
				dojo.byId('spass').value = tpass;
				dojo.byId('suser').value = tuser;
			}
			else{
				badout.innerHTML = dout;
				dojo.byId("regbut").innerHTML = overbut;
			}
		},
		mimetype: "text/plain"
		});
		
	x.onSubmit = function(form) {
		dojo.byId("errmes").innerHTML = "Validating username and password...";
		tuser = dojo.byId('user').value;
		tpass = dojo.byId('pass').value;
		return true; // need this, otherwise form won't get sent!
		}
	}


	function b_form_init() {
	var goodout = dojo.byId("unparea");
	var x = new dojo.io.FormBind({
		formNode: "p_details",
		//formNode: document.forms[0],
		load: function(type, data, e) {
			var dout = data;
			dojo.byId("errmes").innerHTML = " ";
			goodout.innerHTML = dout;
		},
		mimetype: "text/plain"
		});
		
	x.onSubmit = function(form) {
		hidemess();
		dojo.byId("errmes").innerHTML = "Updating Registration...";
		return true; // need this, otherwise form won't get sent!
		}
	}



	dojo.addOnLoad(function() {
	 //dojo.byId("regbut").innerHTML = rwait ;
	 a_form_init();
	 b_form_init();
	 dojo.byId("regbut").innerHTML = rbutton;
	});


</script>
</head>

<body  onload="MM_preloadImages('ui_images/oc_banner_rev.jpg','ui_images/closeicon.jpg','ui_images/oc_tab1_login.jpg','ui_images/oc_tab1_login_over.jpg','ui_images/oc_tab2_search_over.jpg','ui_images/oc_tab2_search.jpg','ui_images/oc_tab3_browse.jpg','ui_images/oc_tab3_browse_over.jpg','ui_images/oc_page_tab4_selections.jpg','ui_images/oc_tab4_selections_over.jpg','ui_images/oc_tab5_details.jpg','ui_images/oc_tab6_mytags.jpg','ui_images/oc_tab6_mytags_over.jpg','../ui_images/oc_bottom_bar_plain.jpg','ui_images/whitetab_selections_over.jpg','ui_images/selection_table/columnbutton_class_over.jpg','ui_images/selection_table/columnbutton_item_over.jpg','ui_images/selection_table/columnbutton_projectover.jpg','ui_images/selection_table/columnbutton_context_over.jpg','ui_images/selection_table/columnbutton_tags_over.jpg','../ui_images/oc_tab2_search_over.jpg','ui_images/whitetab_mytags_over.jpg')" 
>



<div id="Banner" align="left"><img src="ui_images/oc_banner_rev.jpg" alt="banner" width="792" height="94" /></div>

<div class="bodyText" id="namelayer">You are begining the registration process </div>

<div id="Tabs">
<table width="792" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="132"><a href="accounts.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('login','','ui_images/oc_tab1_login_over.jpg',1)"><img src="ui_images/oc_page_tab1_login.jpg" alt="login" name="login" width="132" height="29" border="0" id="login" /></a></td>
        <td width="132"><a href="main_search.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('search','','ui_images/oc_tab2_search_over.jpg',1)"><img src="ui_images/oc_tab2_search.jpg" alt="new search" name="search" width="132" height="29" border="0" id="search" /></a></td>
        <td width="132"><a href="browse.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('browse','','ui_images/oc_tab3_browse_over.jpg',1)"><img src="ui_images/oc_tab3_browse.jpg" alt="browse" name="browse" width="132" height="29" border="0" id="browse" /></a></td>
        <td width="132"><a href="selections.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('selections','','ui_images/oc_tab4_selections_over.jpg',1)"><img src="ui_images/oc_tab4_selections.jpg" alt="selections" name="selections" width="132" height="29" border="0" id="selections" /></a></td>
        <td width="132"> <td width="132"><img src="ui_images/oc_tab5_details.jpg" alt="detailstab" width="132" height="29" /></td>
        <td width="132"><a href="mytags.php" onmouseout="MM_swapImgRestore()" onmouseover="MM_swapImage('mytags','','ui_images/oc_tab6_mytags_over.jpg',1)"><img src="ui_images/oc_tab6_mytags.jpg" alt="my tags" name="mytags" width="132" height="29" border="0" id="mytags" /></a></td>
      </tr>
  </table>	
</div>

<div id="footer"><img src="ui_images/oc_bottom_bar_plain.jpg" alt="footer" width="792" height="50" border="0" usemap="#Map" /> </div>
<map name="Map" id="Map"><area shape="rect" coords="555,6,756,44" href="http://www.alexandriaarchive.org/" alt="selections"/></map>

<div id="tool"></div>

<div class="pageName" id="pagename"><strong>Open Context Account Registration </strong></div>

<div class="bodyText" id="instructions">
  <p>Becoming a registered user of Open Context allows you to publish data and use its tagging features. Tags enable you to save and revist your search results, and they help add to the richness of Open Context content.<em> Note: in order to use this service, your browser must accept &quot;cookies&quot; for this site (www.opencontext.org)</em>. </p>
</div>

<div class="bodyText" id="unparea">
<p>Please choose a user-name and password and enter them in the form below. </p>
 <form id="f_unamepass" method="post" action="reg_simple_new.php">
        <label><span class="subHeader">Enter a username </span>
          <input name="user" type="text" id="user" onchange="MM_validateForm('search','','R');return document.MM_returnValue" size="32" maxlength="32" />
        </label>
        <label><br />
		  <br />
		  <span class="subHeader">Enter a password</span>
		  <input name="pass" type="password" id="pass" size="32" maxlength="32" />
		  <br />
		  <br />
        </label>
		<div id="regbut">
		  <table>
		  	<tr>
		  	<td width = '200'>Preparing page...</td>
			<td><img src='/ui_images/loadingif.gif'></td>
			</tr>
		</table>
		</div>
  </form>
</div>



<div class="bodyText" id="errmes"></div>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>

<div id='mesarea' dojoType='FloatingPane' style='width: 570px; height: 375px; left: 130px; top: 50px;' executeScripts='true' hasShadow='true' resizable='true' displayCloseAction='false' displayMinimizeAction='false' displayMaximizeAction='false'
title='Please Describe Yourself'
windowState='minimized' >

<div id="dformarea" style="
	width:540px;
	height:273px;
	border-width: thin;
	border-style: solid;
	border-color: #D7D7D7;">
	
<form action="reg_user_sup_add.php" method="post" id="p_details">
<table border="0" cellspacing="0" cellpadding="2">
  <tr>
    <td width="149">Given Name </td>
    <td colspan="2"><input id = "f_f_name" name="f_name" type="text" value="First" size="50" maxlength="98" onchange="comp_names()"/></td>
	<td width="70" rowspan="8" align="center"><input id='suser' name='suser' type='hidden' value='none' >
	<input id='spass' name='spass' type='hidden' value='none' >
	</td>
  </tr>
  <tr>
    <td>Middle Initial </td>
    <td colspan="2"><input id = "f_m_initial" name="m_inital" type="text" value="" size="2" maxlength="2" onchange="comp_names()"/></td>
  </tr>
  <tr>
    <td>Family Name (surname): </td>
    <td colspan="2"><input id = "f_l_name" name="l_name" type="text" value="Surname" size="50" maxlength="100" onchange="comp_names()"/></td>
  </tr>
  <tr>
    <td>Initials</td>
    <td colspan="2"><input id = "f_initals" name="initals" type="text" value="" size="5" maxlength="5" /></td>
  </tr>
  <tr>
    <td>Full Name </td>
    <td colspan="2"><input id = "f_full_name" name="full_name" type="text" value="First MI. Surname" size="50" maxlength="100" /></td>
  </tr>
  <tr>
    <td>Email: </td>
    <td colspan="2"><input id = "f_email" name="email" type="text" value="you@anywhere.edu" size="50" maxlength="200" /></td>
  </tr>
  <tr>
    <td>Affiliation:</td>
    <td colspan="2"><input id = "f_affil" name="affil" type="text" value="Your organization" size="50" maxlength="200" /></td>
  </tr>
  <tr>
    <td>Role within your Organization:</td>
    <td colspan="2"><input id = "f_srole" name="srole" type="text" value="Professor (tenured)" size="31" maxlength="200" readonly /></td>
  </tr>
  <tr>
    <td><div align="right"><i>(Select from list)</i></div></td>
    <td width="221">
	<select id = "f_slist" name="role" size="3" onchange="ch_srole()">
	<OPTION>No role given</OPTION>
	<OPTION>Dept. Head (commercial)</OPTION>
	<OPTION>Proj. Head (commercial)</OPTION>
	<OPTION>IT specialist (commercial)</OPTION>
	<OPTION>Other staff (commercial)</OPTION>
	<OPTION>Consultant</OPTION>
	<OPTION>Librarian / Library Science</OPTION>
	<OPTION>Museum Curator</OPTION>
	<OPTION>Museum Staff</OPTION>
	<OPTION SELECTED>Professor (tenured)</OPTION>
	<OPTION>Professor (not tenured)</OPTION>
	<OPTION>Adjunct Faculty</OPTION>
	<OPTION>Graduate Student</OPTION>
	<OPTION>Undergraduate Student</OPTION>
	<OPTION>Dept. Head (nonprofit)</OPTION>
	<OPTION>Proj. Head (nonprofit)</OPTION>
	<OPTION>IT specialist (nonprofit)</OPTION>
	<OPTION>Other staff (nonprofit)</OPTION>
	</select></td>
	<td width="75" align="center"><div id="add_button"><input name="submit" type="submit" value="Continue"></div>
	</td>
	<td align="center"><div id = "close_button"><input name="cancel" type="button" value="Cancel" onclick ="javascript:hidemess()"/></div>
	</td>
  </tr>
</table>

</form>
</div>
</div>


</body>
</html >
