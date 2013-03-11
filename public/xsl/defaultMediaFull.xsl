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

<link href="/css/oc-layout-rev2.css" rel="stylesheet" type="text/css" />
<link href="/css/bootstrap.css" rel="stylesheet" type="text/css" />
<link href="/css/bootstrap-responsive.css" rel="stylesheet" type="text/css" />
<link href="/css/subject-rev2.css" rel="stylesheet" type="text/css" />
<link href="/css/general-item-rev2.css" rel="stylesheet" type="text/css" />

<link typeof="ocsem:media">
		<xsl:attribute name="href">http://opencontext.org/media/<xsl:value-of select="arch:resource/@UUID"/>/full</xsl:attribute>
</link>


</head>

<body>

    <h1>Open Context: <xsl:value-of select="arch:resource/arch:name/arch:string"/> (<a><xsl:attribute name="href">http://opencontext.org/projects/<xsl:if test="arch:resource/@ownedBy !=0"><xsl:value-of select="arch:resource/@ownedBy"/></xsl:if></xsl:attribute><xsl:value-of select="arch:resource/oc:metadata/oc:project_name"/></a> - <xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link/oc:item_class/oc:name"/>) [<a><xsl:attribute name="href">http://opencontext.org/media/<xsl:value-of select="//arch:resource/@UUID"/></xsl:attribute>Go Back</a>]</h1>
    <div id="fullImage" style="padding-left:2px; text-align:left;">
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
						<strong>Context:</strong> 
									<xsl:for-each select="oc:parent">
									<a><xsl:attribute name="href">../subjects/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a> /
									</xsl:for-each> 
								</xsl:if>
								  </xsl:for-each>
								  
								  <a><xsl:attribute name="href">../subjects/<xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link/oc:id"/></xsl:attribute><xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link/oc:name"/></a> (Linked item)
				
				<br/>
				<br/>
				<h4>Project Keywords</h4>
				<span style="text-align:left; max-width: 400px;">
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
				<br/>
				
				<xsl:if test="count(descendant::arch:resource/arch:properties/arch:property[oc:show_val/text()]) !=0 or count(descendant::arch:resource/arch:notes/arch:note) !=0 ">
						<div class="properties" style="text-align:left; max-width:600px;">
								<xsl:if test="count(descendant::arch:resource/arch:properties/arch:property[oc:show_val/text()]) !=0">
										<h5>Media Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property[oc:show_val/text()])"/>)</h5>
										<table class="table table-striped table-condensed table-hover table-bordered prop-tab">
												<thead>
														<tr>
																<th>Variable</th>
																<th>Value</th>
														</tr>
												</thead>
												<tbody> 
														<xsl:for-each select="arch:resource/arch:properties/arch:property[oc:show_val/text()]">
																<tr>
																		<td>
																				<xsl:value-of select="oc:var_label"/>
																		</td>
																		<td>
																				<a><xsl:attribute name="href">../properties/<xsl:value-of select="oc:propid"/></xsl:attribute><xsl:value-of select="oc:show_val"/></a>
																		</td>
																</tr>
														 </xsl:for-each>
												</tbody>
										</table>
								</xsl:if>
								<xsl:if test="count(descendant::arch:resource/arch:notes/arch:note) !=0 ">
										<div class="item-notes">
												<h5>Item Notes</h5>
												<xsl:for-each select="arch:resource/arch:notes/arch:note">
														<div class="item-note">
																<xsl:value-of select="arch:string" disable-output-escaping="yes" />
														</div>
												</xsl:for-each>
										</div>
								</xsl:if>
								<xsl:if test="count(descendant::arch:resource/arch:links/oc:space_links/oc:link) != 0" >
										<div class="item-links" style="text-align:left; max-width:600px;">
												<h5>Linked Items (<xsl:value-of select="count(descendant::arch:resource/arch:links/oc:space_links/oc:link)"/> items)</h5>
												<div class="list_tab">
														<xsl:for-each select="arch:resource/arch:links/oc:space_links/oc:link[position() mod 2 = 1]">
																<div class="list_tab_row">
																		<div class="list_tab_cell_icon">	
																				<a><xsl:attribute name="href">../subjects/<xsl:value-of select="oc:id"/></xsl:attribute><img> 
																					<xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
																					<xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
																				</img></a>
																		</div>
																		<div class="list_tab_cell"><a>
																				<xsl:attribute name="href">../subjects/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																				</a> ( <xsl:value-of select="oc:relation"/> )
																		</div>
																	 
																		<xsl:for-each select="following-sibling::oc:link[1]">
																				<div class="list_tab_cell_icon">	
																				  <a><xsl:attribute name="href">../subjects/<xsl:value-of select="oc:id"/></xsl:attribute><img> 
																					  <xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
																					  <xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
																				  </img></a>
																				</div>
																				<div class="list_tab_cell"><a>
																						<xsl:attribute name="href">../subjects/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																						</a> ( <xsl:value-of select="oc:relation"/> )
																				</div>
																		</xsl:for-each>
																</div>
														</xsl:for-each>
												</div>
										</div>
								</xsl:if>
								</div>
				</xsl:if>
				<br/>
				<br/>
				<h5>Suggested Citation</h5>
				<div id="citation" style="width: 400px;">
						<xsl:value-of select="$citationView"/>
				</div>
				<br/>
				<br/>
     </div>
 
 <div id="licarea" style="width: 600px; text-align: left;"> 
		<h5>Copyright Licensing</h5>
		<div class="list_tab" >
			 <div class="list_tab_row">
				  <div id="license-icon">
						<xsl:choose>
							 <xsl:when test="//oc:metadata/oc:copyright_lic/oc:lic_URI">
								  <a>
										<xsl:attribute name="href"><xsl:value-of select="//oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute>
										<img> 
										  <xsl:attribute name="src"><xsl:value-of select="//oc:metadata/oc:copyright_lic/oc:lic_icon_URI"/></xsl:attribute>
										  <xsl:attribute name="alt"><xsl:value-of select="//oc:metadata/oc:copyright_lic/oc:lic_name"/></xsl:attribute>
										</img>
								  </a>
								  <div style="display:none;">
										<a>
										<xsl:attribute name="about"><xsl:value-of select="arch:resource/arch:content/arch:externalFileInfo/arch:resourceURI"/></xsl:attribute>
										<xsl:attribute name="rel">license</xsl:attribute>
										<xsl:attribute name="href"><xsl:value-of select="//oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute>
										</a>
								  </div>
							 </xsl:when>
							 <xsl:otherwise>
								  <a href="http://creativecommons.org/licenses/by/3.0/">
										<img src="http://i.creativecommons.org/l/by/3.0/88x31.png" alt="Creative Commons Attribution 3.0 License" />
								  </a>
								  <div style="display:none;">
										<a>
										<xsl:attribute name="about"><xsl:value-of select="arch:resource/arch:content/arch:externalFileInfo/arch:resourceURI"/></xsl:attribute>
										<xsl:attribute name="rel">license</xsl:attribute>
										<xsl:attribute name="href">http://creativecommons.org/licenses/by/3.0/</xsl:attribute>
										</a>
								  </div>
							 </xsl:otherwise>
						</xsl:choose>
				  </div>
				  <div id="license-text">
						To the extent to which copyright applies, this content is licensed with:
						<a>
							 <xsl:attribute name="rel">license</xsl:attribute>
							 <xsl:choose>
								  <xsl:when test="//oc:metadata/oc:copyright_lic/oc:lic_URI">
										<xsl:attribute name="href"><xsl:value-of select="//oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute>
								  </xsl:when>
								  <xsl:otherwise>
										  <xsl:attribute name="href">http://creativecommons.org/licenses/by/3.0/</xsl:attribute>
								  </xsl:otherwise>
							 </xsl:choose>
							 <xsl:choose>
								  <xsl:when test="//oc:metadata/oc:copyright_lic/oc:lic_URI">
										Creative Commons <xsl:value-of select="//oc:metadata/oc:copyright_lic/oc:lic_name"/>&#32;<xsl:value-of select="//oc:metadata/oc:copyright_lic/oc:lic_vers"/>&#32;License
								  </xsl:when>
								  <xsl:otherwise>
										  Creative Commons Attribution 3.0&#32;License
								  </xsl:otherwise>
							 </xsl:choose>
						</a>
						Attribution Required: Citation, and hyperlinks for online uses.
						<div style="display:none; width:0px; overflow:hidden;">
							<abbr class="unapi-id"><xsl:attribute name="title"><xsl:value-of select="//oc:metadata/dc:identifier"/></xsl:attribute><xsl:value-of select="//oc:metadata/dc:identifier"/></abbr>
							<a xmlns:cc="http://creativecommons.org/ns#">
								<xsl:attribute name="href"><xsl:value-of select="//oc:metadata/dc:identifier"/></xsl:attribute>
								<xsl:attribute name="property">cc:attributionName</xsl:attribute>
								<xsl:attribute name="rel">cc:attributionURL</xsl:attribute>
								<xsl:value-of select="$citation"/>
							</a>
						</div>
				  </div>
			 </div>
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
