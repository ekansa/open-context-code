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
xmlns:oc="http://opencontext.org/schema/space_schema_v1.xsd"
xmlns:arch="http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd"
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


<xsl:variable name="badCOINS"><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:coins"/>
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

<xsl:variable name="item_id">
	<xsl:value-of select="arch:spatialUnit/@UUID"/>
</xsl:variable>

<xsl:variable name="num_contribs">
	<xsl:value-of select="count(arch:spatialUnit/oc:metadata/dc:contributor)"/>
</xsl:variable>

<xsl:variable name="num_editors">
	<xsl:value-of select="count(arch:spatialUnit/oc:metadata/dc:creator)"/>
</xsl:variable>

<xsl:variable name="num_externalRefs">
	<xsl:value-of select="count(//oc:external_references/oc:reference)"/>
</xsl:variable>

<xsl:variable name="num_Obs">
	<xsl:value-of select="count(//arch:observations/arch:observation)"/>
</xsl:variable>

<xsl:variable name="num_Children">
	<xsl:value-of select="count(//arch:spatialUnit/oc:children/oc:tree/oc:child)"/>
</xsl:variable>

<xsl:variable name="num_linkedData">
	<xsl:value-of select="count(//oc:linkedData/oc:relationLink)"/>
</xsl:variable>

<xsl:variable name="qPrefix">http://opencontext.org/sets/</xsl:variable>
<xsl:variable name="ChildQValue">
		<xsl:choose>
				<xsl:when test="//arch:spatialUnit/oc:children/@qPath">
						<xsl:value-of select="$qPrefix"/><xsl:value-of select="//arch:spatialUnit/oc:children/@qPath"/>
				</xsl:when>
				<xsl:otherwise>0</xsl:otherwise>
		</xsl:choose>
</xsl:variable>


<xsl:variable name="max_Tabs">7</xsl:variable>

<xsl:variable name="citation">
	<xsl:for-each select="arch:spatialUnit/oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="arch:spatialUnit/oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if> &quot;<span xmlns:dc="http://purl.org/dc/elements/1.1/" property="dc:title"><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/></span>&quot; (Released <xsl:value-of select="arch:spatialUnit/oc:metadata/dc:date"/>). <xsl:for-each select="arch:spatialUnit/oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em>  
</xsl:variable>

<xsl:variable name="citationView">
	<xsl:for-each select="arch:spatialUnit/oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="arch:spatialUnit/oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if>&quot;<xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/>&quot; (Released <xsl:value-of select="arch:spatialUnit/oc:metadata/dc:date"/>). <xsl:for-each select="arch:spatialUnit/oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em> &lt;http://opencontext.org/subjects/<xsl:value-of select="arch:spatialUnit/@UUID"/>&gt; 
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
														<xsl:attribute name="src"><xsl:value-of select="arch:spatialUnit/oc:item_class/oc:iconURI"/></xsl:attribute>
														<xsl:attribute name="alt"><xsl:value-of select="arch:spatialUnit/oc:item_class/oc:name"/></xsl:attribute>
												</img>
										</div>
										<div id="item_top_name_cell">
												<h1>Item: <xsl:value-of select="arch:spatialUnit/arch:name/arch:string"/></h1>
												<h2>Class: <xsl:value-of select="//arch:spatialUnit/oc:item_class/oc:name"/></h2>
										</div>
										<div id="item_top_des_cell">
												<h2 class="top_detail">Project: <a><xsl:attribute name="href">../projects/<xsl:if test="arch:spatialUnit/@ownedBy !=0"><xsl:value-of select="arch:spatialUnit/@ownedBy"/></xsl:if></xsl:attribute><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:project_name"/></a></h2>
												<h2 class="views">Number of Views: <xsl:value-of select="arch:spatialUnit/oc:social_usage/oc:item_views/oc:count"/></h2>
										</div>
										<!--
										<div id="item_top_view_cell">Number of Views: <strong><xsl:value-of select="arch:spatialUnit/oc:social_usage/oc:item_views/oc:count"/></strong>
										</div>
										-->
										<div id="citation-cell">
												<h2 class="top_detail">Suggested Citation</h2>
												<div id="citation">
												<xsl:value-of select="$citationView"/>
												</div>
										</div>
								</div>
						</div><!--end div for the top_tab -->
						<div id="item_context_tab">
								<div id="item_context_row" class="awld-scope">
										<div id="item_context_t_cell">
												<h5>Context (click to view):</h5>
										</div>
										<div id="item_context_cell">
												<xsl:for-each select="arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent">
														<a>
																<xsl:attribute name="class">awld-type-object</xsl:attribute>
																<xsl:choose>
																		<xsl:when test="position() = last()">
																				<xsl:attribute name="rel">conc:findspot</xsl:attribute>
																		</xsl:when>
																</xsl:choose>
																<xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a>
														<xsl:if test="position() != last()"> / </xsl:if>
												</xsl:for-each>
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
														<xsl:choose>
																<xsl:when test="$num_Obs != 0">
																		<ul class="nav nav-tabs" id="obsTabs">
																				<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation">
																						<xsl:choose>
																								<xsl:when test="(@obsNumber = '100')">
																										<!-- do nothing -->
																								</xsl:when>
																								<xsl:when test="(@obsNumber != '100') and ((@obsNumber &lt; '0') or (oc:obs_metadata/oc:type = 'Preliminary')) ">
																										<xsl:call-template name="obsLinks">
																												<xsl:with-param name="totalObs" select="$num_Obs"/>
																												<xsl:with-param name="obsPos" select="position()"/>
																												<xsl:with-param name="notCurrent" select="1"/>
																												<xsl:with-param name="max_Tabs" select="$max_Tabs"/>
																										</xsl:call-template>
																								</xsl:when>
																								<xsl:when test="(position() &gt;= $max_Tabs) and ($num_Obs &gt; $max_Tabs)">
																										<!-- do nothing -->
																								</xsl:when>
																								<xsl:otherwise>
																										<xsl:call-template name="obsLinks">
																												<xsl:with-param name="totalObs" select="$num_Obs"/>
																												<xsl:with-param name="obsPos" select="position()"/>
																												<xsl:with-param name="notCurrent" select="0"/>
																												<xsl:with-param name="max_Tabs" select="$max_Tabs"/>
																										</xsl:call-template>
																								</xsl:otherwise>
																						</xsl:choose>
																				</xsl:for-each>
																				<xsl:if test="$num_Obs &gt; $max_Tabs">
																						
																						<li class="dropdown" id="more-obs-menu">
																								<a href="#more-obs-menu" class="dropdown-toggle" data-toggle="dropdown">More <b class="caret">.</b></a>
																								<ul class="dropdown-menu">
																										<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation">
																												<xsl:if test="(position() &gt;= $max_Tabs) and (@obsNumber != '100')">
																														<li>																					
																																<a data-toggle="tab"><xsl:attribute name="href">#obs-<xsl:value-of select="position()"/></xsl:attribute>Obs. <xsl:value-of select="position()"/></a>
																														</li>
																												</xsl:if>
																										</xsl:for-each>
																								</ul>
																						</li>
																						
																				</xsl:if>
																		</ul>
																</xsl:when>
																<xsl:otherwise>
																</xsl:otherwise>
														</xsl:choose>
														
														<div>
																<xsl:if test="$num_Obs != 0">
																		<xsl:attribute name="class">tab-content</xsl:attribute>
																		<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation">
																				<div>
																						<xsl:attribute name="id">obs-<xsl:value-of select="position()"/></xsl:attribute>
																						<xsl:if test="position() = 1">
																								<xsl:attribute name="class">tab-pane fade in active</xsl:attribute>
																						</xsl:if>
																						<xsl:if test="position() != 1">
																								<xsl:attribute name="class">tab-pane fade</xsl:attribute>
																						</xsl:if>
																						<xsl:if test="count(descendant::arch:properties/arch:property[oc:show_val/text()]) !=0 or count(descendant::arch:notes/arch:note) !=0 ">
																								<div class="properties">
																										  <xsl:if test="count(descendant::arch:properties/arch:property[oc:show_val/text()]) !=0">
																												<xsl:choose>
																														<xsl:when test="oc:obs_metadata/oc:name">
																																<h5><xsl:value-of select="oc:obs_metadata/oc:name"/>: Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property[oc:show_val/text()])"/>)<span style="margin-left:25px;">[Observation <xsl:value-of select="position()"/>]</span></h5>
																														</xsl:when>
																														<xsl:otherwise>
																																<h5>Observation <xsl:value-of select="position()"/>: Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property[oc:show_val/text()])"/>)</h5>
																														</xsl:otherwise>
																												</xsl:choose>
																												
																												<table class="table table-striped table-condensed table-hover table-bordered" style="margin:2%; width:95%;">
																														<thead>
																																<tr>
																																		<th>Variable</th>
																																		<th>Value</th>
																																</tr>
																														</thead>
																														<tbody>
																														<xsl:for-each select="arch:properties/arch:property[oc:show_val/text()]">
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
																										  <xsl:if test="count(descendant::arch:notes/arch:note) !=0 ">
																												<div class="item-notes">
																													 <h5>Item Notes</h5>
																													 <xsl:for-each select="arch:notes/arch:note">
																														  <div class="item-note">
																																<xsl:value-of select="arch:string" disable-output-escaping="yes" />
																														  </div>
																													 </xsl:for-each>
																												</div>
																										  </xsl:if>
																										 
																										  <xsl:if test="count(descendant::arch:links/oc:space_links/oc:link) != 0" >
																												<div class="item-links">
																													 <h5>Linked Items (<xsl:value-of select="count(descendant::arch:links/oc:space_links/oc:link)"/> items)</h5>
																													 <div class="list_tab">
																														  <xsl:for-each select="arch:links/oc:space_links/oc:link[position() mod 2 = 1]">
																																<div class="list_tab_row">
																																	 <div class="list_tab_cell_icon_duoCol">	
																																			 <a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
																																				 <xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
																																				 <xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
																																			 </img></a>
																																	 </div>
																																	 <div class="list_tab_cell"><a>
																																			 <xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																																			 </a> ( <xsl:value-of select="oc:relation"/> )
																																	 </div>
																																	 
																																	 <xsl:for-each select="following-sibling::oc:link[1]">
																																		  <div class="list_tab_cell_icon_duoCol">	
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
																										  
																										  
																										  <!-- linked documents -->
																										  <xsl:if test="count(descendant::arch:links/oc:diary_links/oc:link) != 0" >
																												<div class="item-links">
																													 <h5>Linked Documents / Logs (<xsl:value-of select="count(descendant::arch:links/oc:diary_links/oc:link)"/> items)</h5>
																													 <div class="list_tab">
																														  <xsl:for-each select="arch:links/oc:diary_links/oc:link[position() mod 2 = 1]">
																																<div class="list_tab_row">
																																	 
																																	 <div class="list_tab_cell"><a>
																																			 <xsl:attribute name="href">../documents/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																																			 </a>
																																	 </div>
																																	 
																																	 <xsl:for-each select="following-sibling::oc:link[1]">
																																		  <div class="list_tab_cell"><a>
																																			 <xsl:attribute name="href">../documents/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																																			 </a>
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
																		</xsl:for-each>
																</xsl:if>
														</div>
												
												</div>
												<!--last div of observations related content -->
												
												<div class="item-links" id="item-children-diaries" >
													 <xsl:if test="($num_Children &gt; 50) and ($ChildQValue != 0)" >
																<h5>Contents (<xsl:value-of select="count(descendant::arch:spatialUnit/oc:children/oc:tree/oc:child)"/> items)</h5>
																<p>Too many items are contained in this context to display.
																To browse and search through items contained in <strong><xsl:value-of select="//arch:spatialUnit/arch:name/arch:string"/></strong>,
																please <a><xsl:attribute name="href"><xsl:value-of select="$ChildQValue"/></xsl:attribute>(click here)</a>.
																</p>
													 </xsl:if>
													 <xsl:if test="($num_Children != 0) and (($num_Children &lt; 51) or ($ChildQValue = 0))" >
														  <h5>Contents (<xsl:value-of select="count(descendant::arch:spatialUnit/oc:children/oc:tree/oc:child)"/> items)</h5>
														  <div class="list_tab">
																<xsl:for-each select="arch:spatialUnit/oc:children/oc:tree/oc:child[position() mod 2 = 1]">
																	 <div class="list_tab_row">
																		  <div class="list_tab_cell_icon_duoCol">	
																				<a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
																					<xsl:choose>
																						  <xsl:when test="contains(oc:item_class/oc:iconURI, 'http://')">
																								  <xsl:attribute name="src"><xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
																						  </xsl:when>
																						  <xsl:otherwise>
																								  <xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
																						  </xsl:otherwise>
																					  </xsl:choose>
																					  <xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
																				  </img></a>
																		  </div>
																		  <div class="list_tab_cell"><a>
																				<xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																				</a> (<xsl:choose><xsl:when test="oc:descriptor"><xsl:value-of select="oc:descriptor"/></xsl:when>
												<xsl:otherwise><xsl:value-of select="oc:item_class/oc:name"/></xsl:otherwise>
												</xsl:choose>)
																		  </div>
																		  
																		  <xsl:for-each select="following-sibling::oc:child[1]">
																				<div class="list_tab_cell_icon_duoCol">	
																					 <a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
																						 <xsl:choose>
																								<xsl:when test="contains(oc:item_class/oc:iconURI, 'http://')">
																										<xsl:attribute name="src"><xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
																								</xsl:when>
																								<xsl:otherwise>
																										<xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
																								</xsl:otherwise>
																							</xsl:choose>
																							<xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
																						</img></a>
																				</div>
																				<div class="list_tab_cell"><a>
																					 <xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																					 </a> (<xsl:choose><xsl:when test="oc:descriptor"><xsl:value-of select="oc:descriptor"/></xsl:when>
													 <xsl:otherwise><xsl:value-of select="oc:item_class/oc:name"/></xsl:otherwise>
													 </xsl:choose>)
																				</div>
																		  </xsl:for-each>
																	 </div>
																</xsl:for-each>
														  </div>
												  </xsl:if>
													 <br/>
												</div>
												
												
										</div><!-- end div for left des cell -->
										<div id="right_des">
												
												
												<div id="editorial" >
													 <h5>Editorial Status</h5>
													 Peer-reviewed
													 
													 <xsl:if test="count(descendant::arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@status='public']) != 0">
														<br/>
														<br/>
														<h5>Editorial Description (<xsl:value-of select="count(descendant::arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@status='public'])"/>)</h5>
														<xsl:for-each select="arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag">
														  <a>
															  <xsl:if test="@type != 'chronological'"><xsl:attribute name="href">../sets/?tag[]=<xsl:value-of select="oc:name"/></xsl:attribute></xsl:if>
															  <xsl:if test="@type = 'chronological'"><xsl:attribute name="href">../sets/?t-start=<xsl:value-of select="//oc:time_start"/>&amp;t-end=<xsl:value-of select="//oc:time_finish"/></xsl:attribute><xsl:attribute name="title">Rough dates provided by editors to facilitate searching</xsl:attribute></xsl:if><xsl:value-of select="oc:name"/></a><xsl:if test="position() != last()"> , </xsl:if>
													  </xsl:for-each>
														
														<xsl:if test="//oc:user_tags/oc:tag[@type = 'chronological']">
																  <p class="tinyText"><strong>Editor's Note:</strong> Date ranges are approximate and do not necessarily reflect the opinion of data contributors. These dates are provided only to facilitate searches.</p>
														  </xsl:if>
													 </xsl:if>
													 
														<xsl:if test="$num_linkedData != 0">
																<br/>
																<div class="awld-scope">
																		<h5>Linked Data:</h5>
																		<xsl:for-each select="//oc:linkedData/oc:relationLink">
																		<p class="tinyText">
																		<xsl:value-of select="oc:vocabulary"/>-<xsl:value-of select="oc:label"/> :: <xsl:value-of select="oc:targetLink/oc:vocabulary"/>-
																		<a>
																		<xsl:if test="@href = 'http://purl.org/NET/biol/ns#term_hasTaxonomy'">
																				<xsl:attribute name="rel">bio:term_hasTaxonomy</xsl:attribute>
																		</xsl:if>
																		<xsl:attribute name="property"><xsl:value-of select="oc:targetLink/oc:label"/></xsl:attribute>
																		<xsl:attribute name="href"><xsl:value-of select="oc:targetLink/@href"/></xsl:attribute>
																		<xsl:attribute name="title">Target concept</xsl:attribute><xsl:value-of select="oc:targetLink/oc:label"/></a>
																		</p>
																</xsl:for-each>
																</div>
														</xsl:if>
												</div><!-- end div for editorial content -->
												
												<div id="media-links">
														<h5>Linked Media (<xsl:value-of select="count(descendant::arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link)"/>)</h5>
														<div class="list_tab">
																	 <xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link[position() mod 2 = 1]">
																		  <div class="list_tab_row">
																				<div class="list_tab_thumb_cell">	
																					 <a>
																					 <xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
																					 <xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
																					 <img>
																						 <xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
																						 <xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
																					 </img>
																					 </a>
																					 <xsl:if test="oc:descriptor">
																						  <br/>
																						  <a>
																								<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
																								<xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
																								<xsl:value-of select="oc:descriptor"/>
																							</a>
																					 </xsl:if>
																				</div>
																				<xsl:for-each select="following-sibling::oc:link[1]">
																					 <div class="list_tab_thumb_cell">	
																						  <a>
																						  <xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
																						  <xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
																						  <img>
																							  <xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
																							  <xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
																						  </img>
																						  </a>
																						  <xsl:if test="oc:descriptor">
																								<br/>
																								<a>
																									 <xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
																									 <xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
																									 <xsl:value-of select="oc:descriptor"/>
																								 </a>
																						  </xsl:if>
																					 </div>
																				</xsl:for-each>
																				<xsl:if test="count(following-sibling::oc:link[1]) = 0">
																						<div class="list_tab_thumb_cell">
																						<br/>
																						</div>
																				</xsl:if>
																		  </div>
																	 </xsl:for-each>
																</div>
														<p>
																<a>
																		<xsl:attribute name="href">../subjects/<xsl:value-of select="arch:spatialUnit/@UUID"/>.xml</xsl:attribute>
																		<xsl:attribute name="title">ArchaeoML (XML) Representation</xsl:attribute>
																		<xsl:attribute name="type">application/xml</xsl:attribute>ArchaeoML (XML) Version
																</a>
														</p>
														<p>
																<a>
																		<xsl:attribute name="href">https://github.com/ekansa/Open-Context-Data/tree/master/data/<xsl:value-of select="arch:spatialUnit/@ownedBy"/>/subjects/<xsl:value-of select="$item_id"/>.xml</xsl:attribute>
																		<xsl:attribute name="title">XML data in Github repository</xsl:attribute>
																		Version-control (Github, XML Data)
																</a>
														</p>
												</div>
												
										</div><!-- end div for right des cell -->
								</div><!-- end div for main des row -->
						</div><!-- end div for left des tab -->
				</div><!-- end div for main des -->
		
				<xsl:for-each select="//oc:linkedData/oc:relationLink">
				<!--RDFa metadata, hidden from view  -->
				<div style="display:none;">
						<xsl:if test="contains(@href, 'http://gawd.atlantides.org/terms/origin')">
								<div xmlns:oac="http://www.openannotation.org/ns/" id="concordiaOrigin" typeof="oac:Annotation">
								<xsl:attribute name="about">http://opencontext.org/subjects/<xsl:value-of select="$item_id"/>#concordiaOrigin</xsl:attribute>
								
								<!-- for each record change this to the Pleiades URI of the Origin place (like a mint) -->
								<a rel="oac:Body">
								<xsl:attribute name="href"><xsl:value-of select="oc:targetLink/@href"/></xsl:attribute>
								Origin place, source of the object (like a mint)
								</a>
								
								<!-- for each record change this to the Pleiades URI of the Origin place (like a mint) -->
								<a rel="oac:Target">
								<xsl:attribute name="href">http://opencontext.org/subjects/<xsl:value-of select="$item_id"/></xsl:attribute>
								URI of the Open Context object
								</a>
								<span property="dc:title">Annoation linking this Open Context object to an origin location</span>
								<span property="dc:publisher">OpenContext.org</span>
								</div>
						</xsl:if>
						
						<xsl:if test="contains(@href, 'http://purl.org/dc/terms/references')">
								<div xmlns:oac="http://www.openannotation.org/ns/" id="concordiaRelated" typeof="oac:Annotation">
								<xsl:attribute name="about">http://opencontext.org/subjects/<xsl:value-of select="$item_id"/>#concordiaRelated</xsl:attribute>
								
								<!-- for each record change this to the Pleiades URI of the Origin place (like a mint) -->
								<a rel="oac:Body">
								<xsl:attribute name="href"><xsl:value-of select="oc:targetLink/@href"/></xsl:attribute>
								Origin place, source of the object (like a mint)
								</a>
								
								<!-- for each record change this to the Pleiades URI of the Origin place (like a mint) -->
								<a rel="oac:Target">
								<xsl:attribute name="href">http://opencontext.org/subjects/<xsl:value-of select="$item_id"/></xsl:attribute>
								URI of the Open Context object
								</a>
								<span property="dc:title">Annoation linking this Open Context object to an origin location</span>
								<span property="dc:publisher">OpenContext.org</span>
								</div>
						</xsl:if>
				</div>
				</xsl:for-each>
		
		</div><!-- End div for main body -->
</xsl:template>


<!-- Template for navigating observation tabs -->
<xsl:template name="obsLinks">
  
		<xsl:param name="totalObs" select="1"/>
		<xsl:param name="obsPos" select="1"/>
		<xsl:param name="notCurrent" select="0"/>
		<xsl:param name="max_Tabs" select="10"/>
		
				<xsl:choose>
						<xsl:when test="(($notCurrent = 1) and ($totalObs &lt; $max_Tabs)) or (($notCurrent = 1) and ($max_Tabs = $obsPos))">
								<li>
								<a><xsl:attribute name="href">#obs-<xsl:value-of select="$obsPos"/></xsl:attribute>(Prelim. Version)</a>
								</li>
						</xsl:when>
						<xsl:when test="($obsPos &gt;= $max_Tabs) and ($totalObs &gt; $max_Tabs)">
								<!-- do nothing! -->
						</xsl:when>
						<xsl:when test="$obsPos = 1">
								<li>
										<xsl:attribute name="class">active</xsl:attribute>
										<a><xsl:attribute name="href">#obs-<xsl:value-of select="$obsPos"/></xsl:attribute>Main Obs.</a>
								</li>
						</xsl:when>
						<xsl:otherwise>
								<li>
								<a><xsl:attribute name="href">#obs-<xsl:value-of select="$obsPos"/></xsl:attribute>Obs. <xsl:value-of select="$obsPos"/></a>
								</li>
						</xsl:otherwise>.
				</xsl:choose>
		
</xsl:template>







</xsl:stylesheet>
