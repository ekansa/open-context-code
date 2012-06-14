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
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:gml="http://www.opengis.net/gml" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:oc="http://www.opencontext.org/database/schema/space_schema_v1.xsd" xmlns:arch="http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/elements/1.1/">
<xsl:output method="html" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>



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


<xsl:variable name="badCOINS"><xsl:value-of select="atom:feed/atom:entry/arch:spatialUnit/oc:metadata/oc:coins"/>
</xsl:variable>
<xsl:variable name="toReplace">Open%20Context</xsl:variable>
<xsl:variable name="replaceWith">Open%20Context&amp;rft.rights=</xsl:variable>

<xsl:variable name="fixedCOINS">
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


<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<title>Open Context view of <xsl:value-of select="atom:feed/atom:entry/atom:title"/></title>

<link rel="shortcut icon" href="http://www.opencontext.org/open c images/oc_favicon.ico" />
<link rel="alternate" type="application/atom+xml">
<xsl:attribute name="title">Atom feed: <xsl:value-of select="atom:feed/atom:entry/atom:title"/></xsl:attribute>
<xsl:attribute name="href"><xsl:value-of select="atom:feed/atom:entry/atom:id"/>.atom</xsl:attribute>
</link>


<link href="http://www.opencontext.org/database/new_aai_style.css" rel="stylesheet" type="text/css" />
<link href="/css/opencontext.css" rel="stylesheet" type="text/css" />

</head>

<body>







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
            <xsl:attribute name="src"><xsl:value-of select="atom:feed/atom:entry/arch:spatialUnit/oc:item_class/oc:iconURI"/></xsl:attribute>
            <xsl:attribute name="alt"><xsl:value-of select="atom:feed/atom:entry/arch:spatialUnit/oc:item_class/oc:name"/></xsl:attribute>
          </img>       </div>
       <div id="item_name" class="subHeader">Item: <xsl:value-of select="atom:feed/atom:entry/arch:spatialUnit/arch:name/arch:string"/></div>
       <div id="item_class" class="subHeader">Class: <xsl:value-of select="atom:feed/atom:entry/arch:spatialUnit/oc:item_class/oc:name"/></div>
    </div>

    <xsl:comment>
    This is where the item views are displayed
    </xsl:comment>
    <div id="viewtrack">
            <div id="item_views" class="bodyText">Number of Views: <strong><xsl:value-of select="atom:feed/atom:entry/arch:spatialUnit/oc:social_usage/oc:item_views/oc:count"/></strong></div>
            <div id="item_last_view" class="tinyText">Last View: <xsl:value-of select="atom:feed/atom:entry/arch:spatialUnit/oc:social_usage/oc:item_views/oc:view_time"/></div>
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
        <div class="subHeader" id="contexttitle" align="left">Context (click to view): </div>
        <div id="pcontext" class="bodyText" align="left">
        
                
        
                    <xsl:for-each select="atom:feed/atom:entry/arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent">
                       
                        <a>
                            <xsl:attribute name="href"><xsl:value-of select="oc:name"/></xsl:attribute><xsl:value-of select="oc:name"/>                        </a>
                    <xsl:if test="position() != last()"> / </xsl:if>
                    </xsl:for-each>
               
        </div>
    </div>
    <xsl:comment>
    END code for showing the containing context
    </xsl:comment>



<xsl:comment>
Code for showing the database-like content
</xsl:comment>

<div id="main_descriptions">



<xsl:comment>
Code for showing the item properties
</xsl:comment>

<div id="allprops">
    <div class="subHeader" id="proptitle" align="left">
            <table width="250" border="0" cellpadding="0">
              <tr>
                <td width="106" valign="middle" class="subHeader" align="left">Description</td>
                <td width="138" valign="middle" class="tinyText" align="left">
                (<xsl:value-of select="count(descendant::atom:feed/atom:entry/arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property)"/> properties)                </td>
              </tr>
            </table>
    </div>
    
    <div id="props" class="bodyText" align="left">
    <table border="0" cellpadding="1">
     <xsl:for-each select="atom:feed/atom:entry/arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property">
          <tr>
            <td width='95'>
                <xsl:value-of select="oc:var_label"/>            </td>
            <td>&nbsp;</td>
            <td>
                <a>
                    <xsl:attribute name="href">../properties/<xsl:value-of select="oc:propid"/></xsl:attribute>
                    <xsl:value-of select="oc:show_val"/>                </a>            </td>
          </tr>
      </xsl:for-each>
      <xsl:if test="count(descendant::atom:feed/atom:entry/arch:spatialUnit/arch:observations/arch:observation/arch:properties/arch:property) = 0">
        <tr>
        <td>
        <xsl:value-of select="atom:feed/atom:entry/arch:spatialUnit/oc:metadata/oc:no_props"/>        </td>
        </tr>
      </xsl:if>
    </table>
    </div>
</div>
<xsl:comment>
END for showing the item properties
</xsl:comment>







<xsl:comment>
CODE for showing child items
</xsl:comment>


<div id="allchildren">
    <div class="subHeader" id="childtitle" align="left">        
            <table width="160" border="0" cellpadding="0">
              <tr>
                <td width="78" valign="middle" class="subHeader" align="left">Contents</td>
                <td  valign="middle" class="tinyText" align="center">
                (<xsl:value-of select="count(descendant::atom:feed/atom:entry/arch:spatialUnit/oc:children/oc:tree/oc:child)"/> items)                </td>
              </tr>
            </table>
    </div>
    
    <div id="children" class="bodyText" align="left">
        <table border="0" cellpadding="1">
            <xsl:for-each select="atom:feed/atom:entry/arch:spatialUnit/oc:children/oc:tree/oc:child">
                <tr>
                    <td>
                        <a>
                        <xsl:attribute name="href">
                           <!-- <xsl:value-of select="/atom:feed/atom:entry/arch:spatialUnit/   /> --><xsl:value-of select="oc:name"/>                        </xsl:attribute>
                        <xsl:value-of select="oc:name"/>                        </a>                    </td>
                    <td>
                        <xsl:value-of select="oc:item_class/oc:name"/>                    </td>
                </tr>
            </xsl:for-each>
        </table>
    </div>
</div>

<xsl:comment>
END for showing child items
</xsl:comment>





<xsl:comment>
CODE for showing linked spatial items
</xsl:comment>
<div id="alllinks">
    <div class="subHeader" id="linkstitle" align="left">
            <table border="0" cellpadding="0">
              <tr>
                <td width="98" valign="middle" class="subHeader" align="left">Linked Items</td>
                <td valign="middle" class="tinyText" align="center">
                (<xsl:value-of select="count(descendant::atom:feed/atom:entry/arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:space_links/oc:link)"/> items)
                </td>
              </tr>
            </table> 
    </div>
    
    <div id="links" class="bodyText" align="left">
    <xsl:if test="count(descendant::atom:feed/atom:entry/arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:space_links/oc:link) != 0" >
        <table border="0" cellpadding="1">
            <xsl:for-each select="atom:feed/atom:entry/arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:space_links/oc:link">
                    <tr>
                        <td>
                            <xsl:value-of select="oc:relation"/>
                        </td>
                        <td>
                            <a>
                                <xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
                            </a> (<xsl:value-of select="oc:item_class/oc:name"/>)
                        </td>
                    </tr>
            </xsl:for-each>
        </table>
    </xsl:if>
    </div>
</div>
<xsl:comment>
END for showing linked spatial items
</xsl:comment>














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
<img src="http://www.opencontext.org/database/ui_images/oc_bottom_bar_plain.jpg" alt="footer" width="792" height="50" border="0" usemap="#AAI_Map" /> 
<div id="w3c_val_logo">
<a href="http://validator.w3.org/check?uri=referer"><img
        src="http://www.w3.org/Icons/valid-xhtml10-blue"
        alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
</div>
</div>
<map name="AAI_Map" id="AAI_Map">
<area shape="rect" coords="555,6,756,44" href="http://www.alexandriaarchive.org/" alt="selections"/>
</map>


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
