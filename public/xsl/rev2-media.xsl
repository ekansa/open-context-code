<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet  [
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
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:owl="http://www.w3.org/2002/07/owl#" xmlns:skos="http://www.w3.org/2008/05/skos#" xmlns:ocsem="http://opencontext.org/about/concepts#"
xmlns:conc="http://gawd.atlantides.org/terms/"
xmlns:bio="http://purl.org/NET/biol/ns#"
xmlns:gml="http://www.opengis.net/gml"
xmlns:atom="http://www.w3.org/2005/Atom"
xmlns:georss="http://www.georss.org/georss"
xmlns:oc="http://opencontext.org/schema/resource_schema_v1.xsd"
xmlns:arch="http://ochre.lib.uchicago.edu/schema/Resource/Resource.xsd"
xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/elements/1.1/">
<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML+RDFa 1.0//EN" doctype-system="http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"/>



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




		<div id="main">
		
				<xsl:comment>
				BEGIN Container for gDIV of general item information
				</xsl:comment>
		
				<div id="item_top">
						<xsl:comment>
						This is where the item name is displayed
						</xsl:comment>
						<div id="item_top_tab">
								<div id="item_top_row">
										<div id="item_top_icon_cell">
												<img>
														<xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link[1]/oc:item_class/oc:iconURI"/></xsl:attribute>
														<xsl:attribute name="alt"><xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link[1]/oc:item_class/oc:name"/></xsl:attribute>
												</img>
										</div>
										<div id="item_top_name_cell">
												<h1>Item: <xsl:value-of select="arch:resource/arch:name/arch:string"/></h1>
												<h2>Class: <xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link[1]/oc:item_class/oc:name"/></h2>
										</div>
										<div id="item_top_des_cell">Project: <a><xsl:attribute name="href">../projects/<xsl:if test="arch:resource/@ownedBy !=0"><xsl:value-of select="arch:resource/@ownedBy"/></xsl:if></xsl:attribute><xsl:value-of select="arch:resource/oc:metadata/oc:project_name"/></a>
										<br/>
										Number of Views: <strong><xsl:value-of select="arch:resource/oc:social_usage/oc:item_views/oc:count"/></strong>
										</div>
										<div id="citation-cell">
												<h5>Suggested Citation</h5>
												<div id="citation">
												<xsl:value-of select="$citationView"/>
												</div>
										</div>
										<!--
										<div id="item_top_view_cell">Number of Views: <strong><xsl:value-of select="arch:resource/oc:social_usage/oc:item_views/oc:count"/></strong>
										</div>
										-->
										
								</div>
						</div><!--end div for the top_tab -->
						<div id="item_context_tab">
								<div id="item_context_row" class="awld-scope">
										<div id="item_context_t_cell">
												<h5>Context (click to view):</h5>
										</div>
										<div id="item_context_cell">
												<xsl:for-each select="arch:resource/arch:links/oc:space_links/oc:link[1]/oc:context/oc:tree[@id='default']">
														<xsl:for-each select="oc:parent">
																<a><xsl:attribute name="class">awld-type-object</xsl:attribute>
																		<xsl:choose>
																				<xsl:when test="position() = last()">
																						<xsl:attribute name="rel">conc:findspot</xsl:attribute>
																				</xsl:when>
																		</xsl:choose>
																		<xsl:attribute name="href">../subjects/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a> / 
														</xsl:for-each>
												</xsl:for-each>
												<a><xsl:attribute name="href">../subjects/<xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link[1]/oc:id"/></xsl:attribute>
														<xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link[1]/oc:name"/></a> (linked item)
										</div>
								</div>
						</div>
				</div><!--end div for item_top -->
		
				<xsl:comment>
				Code for showing the main description content
				</xsl:comment>
				
				<div id="main_description">
						<div id="main_description_tab">
								<div id="main_description_row">
										<div id="left_des">
												
												<div style="width:100%;">
														
														<div id="preview">
																<h5>Media Preview</h5>
																<div id="image_area">
																		<xsl:choose>
																		<xsl:when test="arch:resource/@type !='image'">
																		<a><xsl:attribute name="href"><xsl:value-of select="arch:resource/arch:content/arch:externalFileInfo/arch:resourceURI"/></xsl:attribute><xsl:attribute name="title">Get Full File: <xsl:value-of select="arch:resource/arch:name/arch:string"/> (<xsl:value-of select="arch:resource/oc:metadata/oc:project_name"/>: <xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link/oc:item_class/oc:name"/>)</xsl:attribute><img> 
																									<xsl:attribute name="src"><xsl:value-of select="arch:resource/arch:content/arch:externalFileInfo/arch:previewURI"/></xsl:attribute>
																									<xsl:attribute name="alt"><xsl:value-of select="arch:resource/arch:name/arch:string"/></xsl:attribute>
																								</img></a>
																		</xsl:when>
																		<xsl:otherwise>
																		<a><xsl:attribute name="href"><xsl:value-of select="arch:resource/@UUID"/>/full</xsl:attribute><xsl:attribute name="title">Get Full Image: <xsl:value-of select="arch:resource/arch:name/arch:string"/> (<xsl:value-of select="arch:resource/oc:metadata/oc:project_name"/>: <xsl:value-of select="arch:resource/arch:links/oc:space_links/oc:link/oc:item_class/oc:name"/>)</xsl:attribute><img> 
																									<xsl:attribute name="src"><xsl:value-of select="arch:resource/arch:content/arch:externalFileInfo/arch:previewURI"/></xsl:attribute>
																									<xsl:attribute name="alt"><xsl:value-of select="arch:resource/arch:name/arch:string"/></xsl:attribute>
																								</img></a>
																		</xsl:otherwise>
																		</xsl:choose>
																</div>
														</div>
														
														
														<ul class="nav nav-tabs" id="obsTabs" style="width:100%; min-width:625px;">
																<li>
																		<xsl:attribute name="class">active</xsl:attribute>
																		<a><xsl:attribute name="href">#obs-1</xsl:attribute>Main Obs.</a>
																</li>	
														</ul>
														
														<div>
																<xsl:attribute name="class">tab-content</xsl:attribute>
																<div>
																		<xsl:attribute name="id">obs-1</xsl:attribute>
																		<xsl:attribute name="class">tab-pane fade in active</xsl:attribute>
																		<xsl:if test="count(descendant::arch:resource/arch:properties/arch:property[oc:show_val/text()]) !=0 or count(descendant::arch:resource/arch:notes/arch:note) !=0 ">
																				<div class="properties">
																						<xsl:if test="count(descendant::arch:resource/arch:properties/arch:property[oc:show_val/text()]) !=0">
																								<h5>Observation Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property[oc:show_val/text()])"/>)</h5>
																										<div class="list_tab"> 
																												<xsl:for-each select="arch:resource/arch:properties/arch:property[oc:show_val/text()]">
																														<div class="list_tab_row">
																																<div class="list_tab_cell">		
																																		<xsl:value-of select="oc:var_label"/>
																																</div>
																																<div class="list_tab_cell">
																																		<a><xsl:attribute name="href">../properties/<xsl:value-of select="oc:propid"/></xsl:attribute><xsl:value-of select="oc:show_val"/></a>
																																</div>
																													 </div>
																												 </xsl:for-each>
																										</div>
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
																								<div class="item-links">
																										<h5>Linked Items (<xsl:value-of select="count(descendant::arch:resource/arch:links/oc:space_links/oc:link)"/> items)</h5>
																										<div class="list_tab">
																												<xsl:for-each select="arch:resource/arch:links/oc:space_links/oc:link[position() mod 2 = 1]">
																														<div class="list_tab_row">
																																<div class="list_tab_cell_icon">	
																																		<a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
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
																																		  <a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
																																			  <xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
																																			  <xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
																																		  </img></a>
																																		</div>
																																		<div class="list_tab_cell"><a>
																																				<xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
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
																</div>
														</div>
												</div>
												<!--last div of observations related content -->
										</div><!-- end div for left des cell -->
										<div id="right_des">
												<div id="editorial">
													 <h5>Project Rreview Status</h5>
													 Peer-reviewed
												</div>
												<div id="media-links">
														<h5>Linked Media (<xsl:value-of select="count(descendant::arch:resource/arch:links/oc:media_links/oc:link)"/>)</h5>
														<div class="list_tab">
																<xsl:for-each select="arch:resource/arch:links/oc:media_links/oc:link">
																		<div class="list_tab_row">
																			<div class="list_tab_cell">
																				<a>
																					<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
																					<xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
																					<img>
																						<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
																						<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
																					</img>
																				</a>
																			</div>
																			<xsl:if test="oc:descriptor">
																				<div class="list_tab_cell">
																				<a>
																					<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
																					<xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
																						<xsl:value-of select="oc:descriptor"/>
																				</a>
																				</div>
																			</xsl:if>
																		</div>
																</xsl:for-each>
														</div>
														<p>
																<a>
																		<xsl:attribute name="href">../media/<xsl:value-of select="arch:resource/@UUID"/>.xml</xsl:attribute>
																		<xsl:attribute name="title">ArchaeoML (XML) Representation</xsl:attribute>
																		<xsl:attribute name="type">application/xml</xsl:attribute>ArchaeoML (XML) Version
																</a>
														</p>
														
														<p>
																<a>
																		<xsl:attribute name="href">https://github.com/ekansa/Open-Context-Data/tree/master/data/<xsl:value-of select="arch:resource/@ownedBy"/>/media/<xsl:value-of select="arch:resource/@UUID"/>.xml</xsl:attribute>
																		<xsl:attribute name="title">XML data in Github repository</xsl:attribute>
																		Version-control (Github, XML Data)
																</a>
														</p>
														
												</div>
										</div><!-- end div for right des cell -->
								</div><!-- end div for main des row -->
						</div><!-- end div for left des tab -->
				</div><!-- end div for main des -->
		
		
		</div><!-- End div for main body -->
</xsl:template>








</xsl:stylesheet>
