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
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:gml="http://www.opengis.net/gml" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:oc="http://opencontext.org/schema/resource_schema_v1.xsd" xmlns:arch="http://ochre.lib.uchicago.edu/schema/Resource/Resource.xsd" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:ocsem="http://opencontext.org/about/concepts#" >
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


<xsl:variable name="badCOINS"><xsl:value-of select="arch:resource/oc:metadata/oc:coins"/>
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


<xsl:variable name="num_contribs">
	<xsl:value-of select="count(arch:resource/oc:metadata/dc:contributor)"/>
</xsl:variable>

<xsl:variable name="num_editors">
	<xsl:value-of select="count(arch:resource/oc:metadata/dc:creator)"/>
</xsl:variable>

<xsl:variable name="citation">
	<xsl:for-each select="arch:resource/oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="arch:resource/oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if> &quot;<span xmlns:dc="http://purl.org/dc/elements/1.1/" property="dc:title"><xsl:value-of select="arch:resource/oc:metadata/dc:title"/></span>&quot; (Released <xsl:value-of select="arch:resource/oc:metadata/dc:date"/>). <xsl:for-each select="arch:resource/oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em>  
</xsl:variable>



<xsl:variable name="citationView">
	<xsl:for-each select="//arch:resource/oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="//arch:resource/oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if>&quot;<xsl:value-of select="//arch:resource/oc:metadata/dc:title"/>&quot; (Released <xsl:value-of select="//arch:resource/oc:metadata/dc:date"/>). <xsl:for-each select="//arch:resource/oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em> &lt;http://opencontext.org/media/<xsl:value-of select="//arch:resource/@UUID"/>&gt; 
</xsl:variable>











<xsl:variable name="firstOrigin">
	<xsl:value-of select="arch:resource/oc:social_usage/oc:user_tags/oc:tag/@origin_id"/>
</xsl:variable>


<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta>
	<xsl:attribute  name="http-equiv">Content-Type</xsl:attribute>
	<xsl:attribute  name="content">text/html; charset=utf-8</xsl:attribute>
</meta>

<title>Open Context view of <xsl:value-of select="//dc:title"/></title>

<link rel="shortcut icon" href="http://www.opencontext.org/open c images/oc_favicon.ico" />
<link rel="alternate" type="application/atom+xml">
<xsl:attribute name="title">Atom feed: <xsl:value-of select="//dc:title"/></xsl:attribute>
<xsl:attribute name="href">http://opencontext.org/media/<xsl:value-of select="arch:resource/@UUID"/>.atom</xsl:attribute>
</link>

<link href="/css/default_banner.css" rel="stylesheet" type="text/css" />
<link href="/css/default_media.css" rel="stylesheet" type="text/css" />
<link href="/css/opencontext_style.css" rel="stylesheet" type="text/css" />

<link typeof="ocsem:media">
		<xsl:attribute name="href">http://opencontext.org/media/<xsl:value-of select="arch:resource/@UUID"/>/full</xsl:attribute>
</link>


</head>

<body>

    <h1 style='color:#2E2E2E;'>Open Context: <xsl:value-of select="arch:resource/arch:name/arch:string"/> (<a><xsl:attribute name="href">../projects/<xsl:if test="arch:resource/@ownedBy !=0"><xsl:value-of select="arch:resource/@ownedBy"/></xsl:if></xsl:attribute><xsl:value-of select="arch:resource/oc:metadata/oc:project_name"/></a> - <xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link/oc:item_class/oc:name"/>) [<a><xsl:attribute name="href">http://opencontext.org/media/<xsl:value-of select="//arch:resource/@UUID"/></xsl:attribute>Go Back</a>]</h1>
    <div id="fullImage" class="bodyText" style="padding-left:2px;">
		<a><xsl:attribute name="href"><xsl:value-of select="arch:resource/arch:content/arch:externalFileInfo/arch:resourceURI"/></xsl:attribute>
		   <xsl:attribute name="title">Download File</xsl:attribute>
		   <img> 
				<xsl:attribute name="src"><xsl:value-of select="arch:resource/arch:content/arch:externalFileInfo/arch:resourceURI"/></xsl:attribute>
				<xsl:attribute name="alt"><xsl:value-of select="arch:resource/arch:name/arch:string"/></xsl:attribute>
		   </img>
		</a>
		<br/>
		<xsl:for-each select="arch:resource/arch:links/oc:space_links/oc:link/oc:context/oc:tree[@id='default']">
                       <xsl:if test="position() = 1">
				Context: 
						   <xsl:for-each select="oc:parent">
							<a><xsl:attribute name="href">../subjects/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a> /
							</xsl:for-each> 
						</xsl:if>
                    </xsl:for-each>
                    
                    <a><xsl:attribute name="href">../subjects/<xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link/oc:id"/></xsl:attribute><xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link/oc:name"/></a> (Linked item)
		
		<span style="margin-left:50px;">
		Project Keywords:
		<em> 
		<xsl:choose>
			<xsl:when test="//arch:DublinCoreMetadata/arch:subject">
				<xsl:for-each select="//arch:DublinCoreMetadata/arch:subject">
						<xsl:if test="position() = 1">
								<xsl:value-of select="."/> 
						</xsl:if>
						<xsl:if test="position() != 1">
						, <xsl:value-of select="."/>
						</xsl:if>
				</xsl:for-each> 
			</xsl:when>
			<xsl:otherwise>
				<xsl:for-each select="//oc:metadata/dc:subject">
						<xsl:if test="position() = 1">
								<xsl:value-of select="."/> 
						</xsl:if>
						<xsl:if test="position() != 1">
						, <xsl:value-of select="."/>
						</xsl:if>
				</xsl:for-each>
			</xsl:otherwise>
		</xsl:choose>
		</em>
		</span>
		<br/>
		
		<p class="subHeader">Description (<xsl:value-of select="count(descendant::arch:resource/arch:properties/arch:property)"/> properties)</p>
		<table style="border:none; padding:1px;">
			 <xsl:for-each select="arch:resource/arch:properties/arch:property">
				  <tr>
					<td width='95'>
						<xsl:value-of select="oc:var_label"/>            </td>
					<td> </td>
					<td>
						<a>
							<xsl:attribute name="href">../properties/<xsl:value-of select="oc:propid"/></xsl:attribute>
							<xsl:choose>
							<xsl:when test="contains(oc:show_val, 'http://')">
							(Outside Link)
							</xsl:when>
							<xsl:otherwise>
							<xsl:value-of select="oc:show_val"/>
							</xsl:otherwise>
							</xsl:choose></a></td>
				  </tr>
			  </xsl:for-each>
			  <xsl:if test="count(descendant::arch:resource/arch:properties/arch:property) = 0">
				<tr><td><xsl:value-of select="arch:resource/oc:metadata/oc:no_props"/></td></tr>
			  </xsl:if>
		</table>
		<br/>
		<p class="bodyText"><span style='text-decoration:underline;'>Suggested Citation:</span><br/><xsl:value-of select="$citationView"/></p>
     </div>
 
 <div class="tinyText" id="licarea"> 
To the extent to which copyright applies, this content is licensed with:<a>
		<xsl:attribute name="rel">license</xsl:attribute>
		<xsl:attribute name="href"><xsl:value-of select="arch:resource/oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute><xsl:value-of select="arch:resource/oc:metadata/oc:copyright_lic/oc:lic_name"/>
            <xsl:value-of select="arch:resource/oc:metadata/oc:copyright_lic/oc:lic_vers"/>&#32;License
	</a> Attribution Required: <a href='javascript:showCite()'>Citation</a>, and hyperlinks for online uses.
	<div style="width:0px; overflow:hidden;">
		<a xmlns:cc="http://creativecommons.org/ns#">
			<xsl:attribute name="href"><xsl:value-of select="arch:resource/oc:metadata/dc:identifier"/></xsl:attribute>
			<xsl:attribute name="property">cc:attributionName</xsl:attribute>
			<xsl:attribute name="rel">cc:attributionURL</xsl:attribute>
			<xsl:value-of select="$citation"/>
		</a>
	</div>
</div>
 
 
 
 <div style="display:none;">
		<div property="dc:title"><xsl:value-of select="//oc:metadata/dc:title"/></div>
		<div property="dc:date"><xsl:value-of select="//oc:metadata/dc:date"/></div>
		
		<xsl:for-each select="//oc:metadata/dc:creator">
		<div rel="dc:creator"><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute></div>
		<div property="rdfs:label"><xsl:attribute name="about"><xsl:value-of select="@href"/></xsl:attribute><xsl:value-of select="."/></div>
		</xsl:for-each>
		
		<xsl:for-each select="//oc:metadata/dc:contributor">
		<div rel="dc:contributor"><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute></div>
		<div property="rdfs:label"><xsl:attribute name="about"><xsl:value-of select="@href"/></xsl:attribute>
		<xsl:value-of select="."/></div>
		</xsl:for-each>
		
		<div rel="dc:publisher"><xsl:attribute name="href">http://opencontext.org</xsl:attribute></div>
		<div property="rdfs:label"><xsl:attribute name="about">http://opencontext.org</xsl:attribute>Open Context</div>
		
</div>
 
 
 
</body>
</html>

</xsl:template>
</xsl:stylesheet>
