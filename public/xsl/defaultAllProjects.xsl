<?xml version="1.0" encoding="utf-8"?><!-- DWXMLSource="../../../Documents and Settings/Eric Kansa/Desktop/atomSample.xml" --><!DOCTYPE xsl:stylesheet  [
	<!ENTITY nbsp   "&#160;">
	<!ENTITY copy   "&#169;">
	<!ENTITY reg    "&#174;">
	<!ENTITY trade  "&#8482;">
	<!ENTITY mdash  "&#8212;">
	<!ENTITY ldquo  "&#8220;">
	<!ENTITY rdquo  "&#8221;"> 
	<!ENTITY pound  "&#163;">
	<!ENTITY yen    "&#165;">
	<!ENTITY euro   "&#8364;">
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:gml="http://www.opengis.net/gml" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:oc="http://opencontext.org/schema/project_schema_v1.xsd" xmlns:arch="http://ochre.lib.uchicago.edu/schema/Project/Project.xsd" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/elements/1.1/">
<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>



<xsl:template name="string-replace">
		<xsl:param name="arg"/>
		<xsl:param name="toReplace"/>
		<xsl:param name="replaceWith"/>
		<xsl:choose>
			<xsl:when test="contains($arg, $toReplace)">
				<xsl:variable name="prefix" select="substring-before($arg, $toReplace)"/>
				<xsl:variable name="postfix" select="substring($arg, string-length($prefix)+string-length($toReplace)+1)"/>
				<xsl:value-of select="concat($prefix, $replaceWith)"/>
				<xsl:call-template name="string-replace">
					<xsl:with-param name="arg" select="$postfix"/>
					<xsl:with-param name="toReplace" select="$toReplace"/>
					<xsl:with-param name="replaceWith" select="$replaceWith"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$arg"/>
			</xsl:otherwise>
		</xsl:choose>
</xsl:template>



<xsl:template match="/">


<xsl:variable name="lastDate"><xsl:value-of select="substring(atom:feed/atom:updated,1,10)"/></xsl:variable>

<xsl:variable name="fixedCOINS">ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc&amp;rft.type=dataset&amp;rft.title=Open%20Context%20Projects%20and%20Collections&amp;rft.date=<xsl:value-of select="$lastDate"/>&amp;rft.creator=Sarah%20Whitcher%20Kansa&amp;rft.subject=archaeology&amp;rft.subject=dataset&amp;rft.subject=excavation&amp;rft.subject=survey&amp;rft.subject=data&amp;rft.subject=field%20research&amp;rft.subject=collections&amp;rft.format=XML&amp;rft.format=Text%2FHTML&amp;rft.format=.jpg&amp;rft.format=.gif&amp;rft.coverage=Near%20East&amp;rft.language=eng&amp;rft.publisher=Open%20Context&amp;rft.rights=&amp;rft.source=Open%20Context&amp;rft.rights=Creative%20Commons%20Attribution%203&amp;rft_id=http%3A%2F%2Fwww.opencontext.org%2Fprojects%2F
</xsl:variable>


<xsl:variable name="citation">&quot;Open Context Projects and Collections&quot; (Updated <xsl:value-of select="$lastDate"/>). Edited by Sarah Whicher Kansa</xsl:variable>




<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta>
	<xsl:attribute  name="http-equiv">Content-Type</xsl:attribute>
	<xsl:attribute  name="content">text/html; charset=utf-8</xsl:attribute>
</meta>

<title>Open Context view of all Projects</title>

<link rel="shortcut icon" href="/images/general/oc_favicon.ico" type="image/x-icon"/>
<link rel="alternate" type="application/atom+xml">
<xsl:attribute name="title">Atom feed: <xsl:value-of select="atom:feed/atom:entry/atom:title"/></xsl:attribute>
<xsl:attribute name="href">../projects/.atom</xsl:attribute>
</link>

<link href="/css/default_banner.css" rel="stylesheet" type="text/css" />
<link href="/css/default_project.css" rel="stylesheet" type="text/css" />
<link href="/css/opencontext_style.css" rel="stylesheet" type="text/css" />


</head>

<body>

  <div id="oc_logo">

	<a href="../" title="Open Context (Home)"><img alt="Open Context Logo" src="/images/general/oc_logo.jpg" border="0" ></img></a>
    </div>
    <div id="oc_tagline">
	<img alt="Open Context Tagline" src="/images/general/oc_tagline.jpg" ></img>
    </div>
    <div id="oc_beta">
	<img alt="Beta Stamp" src="/images/general/oc_betastamp.jpg" ></img>
    </div>
    
    <div id="oc_top_search">

	<form method="get" action="../search/" id="search-form">
	<div id="search_box">
	<input type='text' name='q' class='tinyText' value='Search' size='30' onfocus="if(this.value=='Search')this.value='';" onblur="if(this.value=='')this.value='Search';" />
	</div>
	<div id="search_cntrl">
	    <input class="oc_top_sbutton" type="submit" value="" />
	</div>
	</form>
    </div>

   
   
   <!-- 
    Navigation tabs
    -->    
    
<div id="oc_main_nav">
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="../" title="Open Context Home Page, map and timeline interface">Home</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">

    <a href="../about/" title="Background, uses, guide for contributors, web services overview">About</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="act_nav_l"></div>
  <div class="act_nav">
    <a href="../projects/" title="Summary of datasets in Open Context">Projects</a>
  </div>
  <div class="act_nav_r"></div>

  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="../sets/" title="Search and browse through locations, contexts, finds, etc.">Browse</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="../lightbox/" title="Search and browse through images linked to Open Context records">Lightbox</a>

  </div>
    <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="http://opencontext.org/tables/" title="Tabular data formated for easy download">Tables</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>

  <div class="n_act_nav">
    <span title="Use the Browse or Lightbox feature and select an item for detailed view">Details</span>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <span title="Manage your password and notification settings">My Account</span>
  </div>

  <div class="n_act_nav_r"></div>
</div>



<xsl:comment>
BEGIN Container for main page content
</xsl:comment>
<div id="main_page">








<xsl:comment>
BEGIN Container for gDIV of general item information
-
-
-
-
</xsl:comment>

<div id="item_general"> 

    <xsl:comment>
    This is where the item name is displayed
    </xsl:comment>
    <div id="item_name_class"> 
      <div id="item_class_icon"> 
          <img width='40' height='40'> 
            <xsl:attribute name="src">/images/item_view/project_icon.jpg</xsl:attribute>
            <xsl:attribute name="alt">Project or Organization</xsl:attribute>
          </img></div>
<div id="item_name" class="subHeader">List of all Projects and Collections</div>       
       <div id="item_class" class="bodyText">Projects represent a specific study or research effort to collect a body of documentation.</div>
    </div>

    <xsl:comment>
    This is where the item views are displayed
    </xsl:comment>
    <div id="viewtrack">
            <div id="item_views" class="bodyText">Number of Projects: <strong><xsl:value-of select="count(//atom:entry)"/></strong></div>
            <div id="item_last_view" class="tinyText"></div>
    </div>
</div>
<xsl:comment>
END code for General Item info DIV
-
-
-
-
</xsl:comment>
    
    
    <xsl:comment>
    Code for showing the containing context
    </xsl:comment>
    <div id="parent_contexts">
        
    </div>
    <xsl:comment>
    END code for showing the containing context
    </xsl:comment>

<div class="bodyText" style="margin-top:5px; margin-bottom:5px; margin-left:12px; padding:4px; width:700px"><strong>Note:</strong> All projects and collections in Open Context were developed by professional researchers. While all contributions are evaluated by a credentialed editorial staff prior to publication, most content represents "field notes" and "raw data", and will likely contain some typographic and other errors. So please use with discretion.</div>


<xsl:comment>
Code for showing the database-like content
-
-
-
-
</xsl:comment>
<div id="main_descriptions">
	<p class="subHeader" style="margin-left:10px;">Projects in Open Context</p>
	<div id="allnotes" class="bodyText">
		<table>
			<tbody>
				<tr>
					<th>Project</th>
					<th>Description</th>
					<th>Primary People</th>
					<th>Keywords</th>
				</tr>
				<xsl:for-each select="atom:feed/atom:entry">
					<tr>
						<xsl:choose>
						<xsl:when test="position() mod 2 = 1">
							<xsl:attribute name="style">background-color: #F4F4F4;</xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
						 
						</xsl:otherwise>
						</xsl:choose>
						<td>
							<a><xsl:attribute name="href">../projects/<xsl:value-of select="arch:project/@UUID"/></xsl:attribute><xsl:value-of select="arch:project/arch:name/arch:string" /></a>
						</td>
						<td>
								<xsl:if test="arch:project/oc:metadata/oc:project_name/@pub_status = 'forthcoming'"><span style="color:#191970; font-weight:bold;">[<em>Forthcoming Project</em>] </span> 
								</xsl:if>
								<xsl:value-of select="arch:project/arch:notes/arch:note[@type='short_des']/arch:string" disable-output-escaping="yes" /></td>
						<td>
						<xsl:for-each select="atom:author">
							<xsl:value-of select="." /><xsl:if test="position() != last()">, 
							</xsl:if>
						</xsl:for-each>
						</td>
						<td class="tinyText"><em>
						<xsl:for-each select="arch:project/oc:metadata/dc:subject">
							<xsl:value-of select="." /><xsl:if test="position() != last()">, 
							</xsl:if>
						</xsl:for-each>
						
						</em></td>
					</tr>
				</xsl:for-each>
			</tbody>
		</table>
	
	
</div>


<div id="bottom_des">
<br/>
<br/>



<xsl:comment>
END Code for showing the database-like content
</xsl:comment>
</div>















</div>
<xsl:comment>
END Container for main page content
</xsl:comment>

















<xsl:comment>
BEGIN COINS metadata (for Zotero)
</xsl:comment>

<span class="Z3988">
	<xsl:attribute name="title"><xsl:value-of select="$fixedCOINS"/></xsl:attribute>
</span>

<xsl:comment>
END COINS metadata (for Zotero)
</xsl:comment>



<div id="footer">

<div id="w3c_val_logo">
<a href="http://validator.w3.org/check?uri=referer"><img
        src="http://www.w3.org/Icons/valid-xhtml10-blue"
        alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
</div>


<xsl:comment>
Code for licensing information
</xsl:comment>

<div id="all_lic">
<div id="lic_pict">
<a>
	<xsl:attribute name="href">http://creativecommons.org/licenses/by/3.0/</xsl:attribute>
	<img width='88' height='31' border='0'> 
	  <xsl:attribute name="src">http://i.creativecommons.org/l/by/3.0/88x31.png</xsl:attribute>
	  <xsl:attribute name="alt">Creative Commons Attribution 3.0 License</xsl:attribute>
	</img>
</a>
</div>

<div class="tinyText" id="licarea"> 
To the extent to which copyright applies, this content is licensed with:<a>
		<xsl:attribute name="rel">license</xsl:attribute>
		<xsl:attribute name="href">http://creativecommons.org/licenses/by/3.0/</xsl:attribute>Creative Commons Attribution 3.0&#32;License
	</a> Attribution Required: <a href='javascript:showCite()'>Citation</a>, and hyperlinks for online uses.
	<div style="width:0px; overflow:hidden;">
		<a xmlns:cc="http://creativecommons.org/ns#">
			<xsl:attribute name="href">../projects/</xsl:attribute>
			<xsl:attribute name="property">cc:attributionName</xsl:attribute>
			<xsl:attribute name="rel">cc:attributionURL</xsl:attribute>
			<xsl:value-of select="$citation"/>
		</a>
	</div>
</div>

</div>
<xsl:comment>
END Code for licensing information
</xsl:comment>



</div>

</div>

<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-4019411-1";
urchinTracker();
</script>
</body>
</html>

</xsl:template>
</xsl:stylesheet>
