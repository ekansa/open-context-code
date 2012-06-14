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
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ocsem="http://opencontext.org/about/concepts#" xmlns:gml="http://www.opengis.net/gml" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:oc="http://opencontext.org/schema/project_schema_v1.xsd" xmlns:arch="http://ochre.lib.uchicago.edu/schema/Project/Project.xsd" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/elements/1.1/">
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

<xsl:variable name="allCreators">
<xsl:for-each select="//arch:project/oc:metadata/dc:creator">&amp;rft.creator=<xsl:value-of select="."/>
</xsl:for-each>
&amp;rft_id=http%3A%2F%2Fopencontext.org%2Fprojects%2F<xsl:value-of select="//arch:project/@UUID"/>
</xsl:variable>






<xsl:variable name="badCOINS"><xsl:value-of select="atom:feed/atom:entry/arch:project/oc:metadata/oc:coins"/>
</xsl:variable>
<xsl:variable name="toReplace">Open%20Context</xsl:variable>
<xsl:variable name="replaceWith">Open%20Context&amp;rft.rights=</xsl:variable>
<xsl:variable name="toReplaceB">&amp;rft.type=dataset</xsl:variable>
<xsl:variable name="replaceWithB">&amp;rft.type=dataset<xsl:value-of select="$allCreators"/></xsl:variable>

<xsl:variable name="fixedCOINSpre">
	<xsl:choose>
			<xsl:when test="contains($badCOINS, $toReplace)">
				<xsl:variable name="prefix" select="substring-before($badCOINS, $toReplace)"/>
				<xsl:variable name="postfix" select="substring($badCOINS, string-length($prefix)+string-length($toReplace)+1)"/>
				<xsl:value-of select="concat($prefix, $replaceWith, $postfix)"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$badCOINS"/>
			</xsl:otherwise>
		</xsl:choose>
</xsl:variable>


<xsl:variable name="fixedCOINS">
	<xsl:choose>
			<xsl:when test="contains($fixedCOINSpre, $toReplaceB)">
				<xsl:variable name="prefix" select="substring-before($fixedCOINSpre, $toReplaceB)"/>
				<xsl:variable name="postfix" select="substring($fixedCOINSpre, string-length($prefix)+string-length($toReplaceB)+1)"/>
				<xsl:value-of select="concat($prefix, $replaceWithB, $postfix)"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$fixedCOINSpre"/>
			</xsl:otherwise>
		</xsl:choose>
</xsl:variable>



<xsl:variable name="num_editors">
	<xsl:value-of select="count(atom:feed/atom:entry/arch:project/oc:metadata/dc:creator)"/>
</xsl:variable>

<xsl:variable name="num_contribs">
	<xsl:value-of select="count(//arch:project/oc:metadata/dc:contributor)"/>
</xsl:variable>

<xsl:variable name="citation">
	<xsl:for-each select="//arch:project/oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="//arch:project/oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if>&quot;<xsl:value-of select="//arch:project/oc:metadata/dc:title"/>&quot; (Released <xsl:value-of select="//arch:project/oc:metadata/dc:date"/>). <xsl:for-each select="//arch:project/oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em> &lt;http://opencontext.org/projects/<xsl:value-of select="//arch:project/@UUID"/>&gt; 
</xsl:variable>






<html xmlns="http://www.w3.org/1999/xhtml" xmlns:dcterms="http://purl.org/dc/terms/">

<head>
<meta>
	<xsl:attribute  name="http-equiv">Content-Type</xsl:attribute>
	<xsl:attribute  name="content">text/html; charset=utf-8</xsl:attribute>
</meta>

<title>Open Context view of <xsl:value-of select="atom:feed/atom:entry/atom:title"/></title>

<link rel="shortcut icon" href="/images/general/oc_favicon.ico" type="image/x-icon"/> 
<link rel="alternate" type="application/atom+xml">
<xsl:attribute name="title">Atom feed: <xsl:value-of select="atom:feed/atom:entry/atom:title"/></xsl:attribute>
<xsl:attribute name="href"><xsl:value-of select="atom:feed/atom:entry/atom:id"/>.atom</xsl:attribute>
</link>

<link href="/css/default_banner.css" rel="stylesheet" type="text/css" />
<link href="/css/default_project.css" rel="stylesheet" type="text/css" />
<link href="/css/opencontext_style.css" rel="stylesheet" type="text/css" />

<link typeof="ocsem:projects" href="http://opencontext.org/about/concepts#" />
</head>

<body>

<div id="oc_logo">

	<a href="../" title="Open Context (Home)"><img alt="Open Context Logo" src="/images/general/oc_logo.jpg" style="border: none;" ></img></a>
    </div>
    <div id="oc_tagline">
	<img alt="Open Context Tagline" src="/images/general/oc_tagline.jpg" ></img>
    </div>
    <div id="oc_beta">
	<img alt="Beta Stamp" src="/images/general/oc_betastamp.jpg" ></img>
    </div>
    
    <div id="oc_top_search">

	<form method="get" action="../sets/" id="search-form">
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
<div id="item_name" class="subHeader">Project / Collection: <span class="bodyText"><xsl:value-of select="atom:feed/atom:entry/arch:project/arch:name/arch:string"/></span></div>       
       <div id="item_class" class="subHeader">Description: <span class="bodyText"><xsl:value-of select="atom:feed/atom:entry/arch:project/arch:notes/arch:note[@type='short_des']" disable-output-escaping="yes"/></span></div>
    </div>


    <xsl:comment>
    This is where the item views are displayed
    </xsl:comment>
    <div id="viewtrack">
            <div id="item_views" class="bodyText">Number of Views: <strong><xsl:value-of select="atom:feed/atom:entry/arch:project/oc:social_usage/oc:item_views[@type!='spatialCount']/oc:count"/></strong></div>
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



<xsl:comment>
Code for showing the database-like content
-
-
-
-
</xsl:comment>
<div id="main_descriptions">

<div id="left_des">


	<div id="allnotes" class="bodyText">
		<p class="subHeader">Project / Collection Overview</p>
		<xsl:if test="count(descendant::atom:feed/atom:entry/arch:project/arch:observations/arch:observation/arch:links/oc:diary_links/oc:link) != 0" >	
			<p class="bodyText">
				<xsl:for-each select="atom:feed/atom:entry/arch:project/arch:observations/arch:observation/arch:links/oc:diary_links/oc:link">
				   <a><xsl:attribute name="href">../narratives/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a><xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
		</xsl:if>
		<xsl:if test="count(descendant::atom:feed/atom:entry/arch:project/oc:social_usage/oc:external_references/oc:reference) != 0" >	
			<p class="bodyText"> 
				<xsl:for-each select="atom:feed/atom:entry/arch:project/oc:social_usage/oc:external_references/oc:reference">
				   <a><xsl:attribute name="href"><xsl:value-of select="oc:ref_URI"/></xsl:attribute><em><xsl:value-of select="oc:name" disable-output-escaping="yes"/></em></a>
				   <xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
        </xsl:if>
		
		
		<xsl:if test="count(descendant::atom:feed/atom:entry/arch:project/arch:notes/arch:note) = 0" >
		<p class="bodyText">(This item has no additional notes)</p>
		</xsl:if>
		<xsl:for-each select="atom:feed/atom:entry/arch:project/arch:notes/arch:note[@type!='short_des']">
			<div class="bodyText"><xsl:value-of select="arch:string" disable-output-escaping="yes" /></div><br/>
		</xsl:for-each>
		
		<br/>
		<p class="bodyText"><span style='text-decoration:underline;'>Suggested Citation for this Project Overview:</span><br/><xsl:value-of select="$citation"/></p>
		
	</div>

	<xsl:if test="count(descendant::atom:feed/atom:entry/arch:project/arch:observations/arch:observation/arch:links/oc:space_links/oc:link) != 0" >
		<div id="all_links">
			<p class="subHeader">Linked Items (<xsl:value-of select="count(descendant::atom:feed/atom:entry/arch:project/arch:observations/arch:observation/arch:links/oc:space_links/oc:link)"/> items)</p>
				<xsl:for-each select="atom:feed/atom:entry/arch:project/arch:observations/arch:observation/arch:links/oc:space_links/oc:link">
						<xsl:choose>
							<xsl:when test="position() mod 2 = 1">
								<div class="container_a">
								<div class="container">	
								<a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
									<xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
									<xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
								</img></a></div>
								<div class="container"><span class="bodyText"><a>
								<xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
								</a> ( <xsl:value-of select="oc:relation"/> )</span></div>
						</div> 
							</xsl:when>
							<xsl:otherwise>
								<div class="clear_container">
								<div class="container">	
								<a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
									<xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
									<xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
								</img></a></div>
								<div class="container"><span class="bodyText"><a>
								<xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
								</a> ( <xsl:value-of select="oc:relation"/> )</span></div>
						</div> 
							</xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
					<br/>
					<br/>
			</div>
	</xsl:if>
	
	
	
	
	
	
	
	
	
	<div id="preview">
		<p class="subHeader">Content Associated with this Project</p>
		<p class="bodyText">Items in these categories have been viewed: <strong><xsl:value-of select="//oc:social_usage/oc:item_views[@type='spatialCount']/oc:count"/></strong> times. (Ranked: <xsl:value-of select="//oc:social_usage/oc:item_views[@type='spatialCount']/oc:count/@rank"/> of  <xsl:value-of select="//oc:social_usage/oc:item_views[@type='spatialCount']/oc:count/@pop"/>)</p>
		<xsl:for-each select="atom:feed/atom:entry">
			<xsl:if test="./atom:category/@term ='category' ">
				<table>
					<tbody>
						<tr>
							<td><a><xsl:attribute name="href"><xsl:for-each select="./atom:link[@rel='alternate']"><xsl:value-of select=".//@href"/></xsl:for-each></xsl:attribute><img><xsl:attribute name="src"><xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute><xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute></img></a></td><td><strong><a><xsl:attribute name="href"><xsl:for-each select="./atom:link[@rel='alternate']"><xsl:value-of select=".//@href"/></xsl:for-each></xsl:attribute><xsl:value-of select="./atom:title"/></a></strong></td><td><span class="bodyText"><xsl:value-of select="./atom:content"/></span></td>
						</tr>
					</tbody>
				</table>
			</xsl:if>
		</xsl:for-each>
	
	</div>
	
	<div id="properties">
		<p class="subHeader">Description (<xsl:value-of select="count(descendant::atom:feed/atom:entry/arch:project/arch:properties/arch:property)"/> properties)</p>
		<table border="0" cellpadding="1">
			 <xsl:for-each select="atom:feed/atom:entry/arch:project/arch:properties/arch:property">
				  <tr>
					<td width='95'>
						<xsl:value-of select="oc:var_label"/>            </td>
					<td> </td>
					<td>
						<a>
							<xsl:attribute name="href">../properties/<xsl:value-of select="oc:propid"/></xsl:attribute>
							<xsl:value-of select="oc:show_val"/>                </a>            </td>
				  </tr>
			  </xsl:for-each>
			  <xsl:if test="count(descendant::atom:feed/atom:entry/arch:project/arch:properties/arch:property) = 0">
				<tr><td><xsl:value-of select="atom:feed/atom:entry/arch:project/oc:metadata/oc:no_props" disable-output-escaping="yes"/></td></tr>
			  </xsl:if>
		</table>
	</div>
	
	
	
	
	
	
	<div id="all_people" class="bodyText">
		<p class="subHeader">Associated People (<xsl:value-of select="count(descendant::atom:feed/atom:entry/arch:project/arch:links/oc:person_links/oc:link)"/> items)</p>
		<xsl:if test="count(descendant::atom:feed/atom:entry/arch:project/arch:links/oc:person_links/oc:link) != 0" >	
			<p class="bodyText">
				<xsl:for-each select="atom:feed/atom:entry/arch:project/arch:links/oc:person_links/oc:link">
				   <a><xsl:attribute name="href">../persons/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a><xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
		</xsl:if>
		<br/>
		<br/>	
	</div>
	
	
</div>

<div id="right_des">

	<div id="all_root" class="bodyText">
		
		<xsl:if test="//oc:space_links/oc:link">
				<p class="subHeader">Explore this Project</p>
						
						<xsl:for-each select="//oc:space_links/oc:link">
								
								<xsl:if test="position() = 1">
								
										<div style="display:table-row;">
												<div style="display:table-cell;">
														<a><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute>
														<img><xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute><xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute></img>
														</a>
												</div>
												<div style="display:table-cell; padding:4px; vertical-align:middle;">
														<a><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute>
																<xsl:value-of select="oc:name"/>
														</a>
												</div>
										</div>
							
								</xsl:if>
						</xsl:for-each>
						
				<br/>
		</xsl:if>
		<p class="subHeader">Search Data within Project</p>
			<xsl:for-each select="atom:feed/atom:entry">
				<xsl:if test="./atom:category/@term ='context' ">
				   <table>
						<tbody>
							<tr>
								<td><a><xsl:attribute name="href"><xsl:for-each select="./atom:link[@type='application/xhtml+xml']"><xsl:value-of select=".//@href"/></xsl:for-each></xsl:attribute><img><xsl:attribute name="src"><xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute><xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute></img></a></td><td><strong><a><xsl:attribute name="href"><xsl:for-each select="./atom:link[@type='application/xhtml+xml']"><xsl:value-of select=".//@href"/></xsl:for-each></xsl:attribute><xsl:value-of select="./atom:title"/></a></strong></td><td><span class="bodyText"><xsl:value-of select="./atom:content"/></span></td>
							</tr>
						</tbody>
					</table>
				</xsl:if>
			</xsl:for-each>
		<br/>	
	</div>


	<div id="all_keywords" class="bodyText">
		<p class="subHeader">Keywords for this Project</p>
			<em><xsl:for-each select="//arch:project/oc:metadata/dc:subject">
				<xsl:value-of select="." /><xsl:if test="position() != last()">, </xsl:if>
				</xsl:for-each>
			</em>
		<br/>	
	</div>


	<div id="all_tags" class="bodyText">
		<p class="subHeader">Tags Used in this Project  (<xsl:value-of select="count(descendant::atom:feed/atom:entry/atom:category[@term='user tag'])"/>)</p>
		<p class="bodyText">Items from this project/collection have been tagged by: <strong><xsl:value-of select="	count(descendant::atom:feed/atom:entry/atom:category[@term='tag creator'])"/></strong> people.</p>
			<xsl:for-each select="atom:feed/atom:entry">
				<xsl:if test="./atom:category/@term ='user tag' ">
				   <p class="bodyText"><a><xsl:attribute name="href"><xsl:for-each select="./atom:link[@rel='related']"><xsl:value-of select=".//@href"/></xsl:for-each></xsl:attribute><xsl:value-of select="./atom:title"/></a></p>
				</xsl:if>
			</xsl:for-each>
		<br/>	
	</div>

	
	<div id="all_media" class="bodyText" >
		<p class="subHeader">Linked Media  (<xsl:value-of select="count(descendant::atom:feed/atom:entry/arch:project/arch:links/oc:media_links/oc:link)"/> files)</p>
		<xsl:if test="count(descendant::atom:feed/atom:entry/arch:project/arch:links/oc:media_links/oc:link) != 0" >
			<table border="0" cellpadding="1">
				<xsl:for-each select="atom:feed/atom:entry/arch:project/arch:links/oc:media_links/oc:link">
						<tr>
							<xsl:choose>
							<xsl:when test="oc:type = 'csv'">	
							<td >
								<a>
									<xsl:attribute name="href">../tables/<xsl:value-of select="oc:id"/></xsl:attribute>
									<xsl:attribute name="title">Downloadable table: <xsl:value-of select="oc:name"/></xsl:attribute>
									<img>
										<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
										<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
									</img>
								</a>
							</td>
							<td >
								<a>
									<xsl:attribute name="href">../tables/<xsl:value-of select="oc:id"/></xsl:attribute>
									<xsl:attribute name="title">Downloadable table: <xsl:value-of select="oc:name"/></xsl:attribute>
									<xsl:value-of select="oc:name"/></a>
							</td>
							</xsl:when>
							<xsl:when test="oc:type = 'acrobat pdf'">	
							<td >
								<a>
									<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
									<xsl:attribute name="title">Acrobat Document: <xsl:value-of select="oc:name"/></xsl:attribute>
									<img>
										<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
										<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
									</img>
								</a>
							</td>
							<td >
								<a>
									<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
									<xsl:attribute name="title">Acrobat Document: <xsl:value-of select="oc:name"/></xsl:attribute>
									<xsl:value-of select="oc:name"/></a>
							</td>
							</xsl:when>
							<xsl:otherwise>	
							<td colspan='2'>
								<a>
									<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
									<xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
									<img>
										<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
										<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
									</img>
								</a>
							</td>
							</xsl:otherwise>
							</xsl:choose>
						</tr>
				</xsl:for-each>
			</table>
			<br/>
			<br/>
		</xsl:if>
	</div>

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
        src="http://www.w3.org/Icons/valid-xhtml-rdfa"
        alt="Valid XHTML + RDFa" height="31" width="88" /></a>
</div>


<xsl:comment>
Code for licensing information
</xsl:comment>

<div id="all_lic">
<div id="lic_pict">
<a>
	<xsl:attribute name="href"><xsl:value-of select="atom:feed/atom:entry/arch:project/oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute>
	<img width='88' height='31' style='border:none;'> 
	  <xsl:attribute name="src"><xsl:value-of select="atom:feed/atom:entry/arch:project/oc:metadata/oc:copyright_lic/oc:lic_icon_URI"/></xsl:attribute>
	  <xsl:attribute name="alt"><xsl:value-of select="atom:feed/atom:entry/arch:project/oc:metadata/oc:copyright_lic/oc:lic_name"/></xsl:attribute>
	</img>
</a>
</div>

<div class="tinyText" id="licarea"> 
To the extent to which copyright applies, this content is licensed with:<a>
		<xsl:attribute name="rel">license</xsl:attribute>
		<xsl:attribute name="href"><xsl:value-of select="atom:feed/atom:entry/arch:project/oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute><xsl:value-of select="atom:feed/atom:entry/arch:project/oc:metadata/oc:copyright_lic/oc:lic_name"/>
            <xsl:value-of select="atom:feed/atom:entry/arch:project/oc:metadata/oc:copyright_lic/oc:lic_vers"/>&#32;License
	</a> Attribution Required: <a href='javascript:showCite()'>Citation</a>, and hyperlinks for online uses.
	<div style="width:0px; overflow:hidden;">
		<a xmlns:cc="http://creativecommons.org/ns#">
			<xsl:attribute name="href"><xsl:value-of select="atom:feed/atom:entry/arch:projectt/oc:metadata/dc:identifier"/></xsl:attribute>
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
