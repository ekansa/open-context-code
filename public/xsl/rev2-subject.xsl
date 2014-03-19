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
<xsl:stylesheet version="1.0"
		xmlns:oc="http://opencontext.org/schema/space_schema_v1.xsd"
		xmlns:arch="http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd"
		
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:dc="http://purl.org/dc/elements/1.1/"
		xmlns:gml="http://www.opengis.net/gml"
		xmlns:atom="http://www.w3.org/2005/Atom"
		xmlns:georss="http://www.georss.org/georss"
		xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
		xmlns:xhtml="http://www.w3.org/1999/xhtml"
		xmlns:cc="http://creativecommons.org/ns#"
		
		xmlns:ocsem="http://opencontext.org/about/concepts#"
		xmlns:conc="http://gawd.atlantides.org/terms/"
		xmlns:bio="http://purl.org/NET/biol/ns#"
		xmlns:bibo="http://purl.org/ontology/bibo/"
		xmlns:dcmitype="http://purl.org/dc/dcmitype/"
		xmlns:dcterms="http://purl.org/dc/terms/"
		xmlns:foaf="http://xmlns.com/foaf/0.1/"
		xmlns:owl="http://www.w3.org/2002/07/owl#"
		xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
		xmlns:rdfa="http://www.w3.org/ns/rdfa#"
		xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
		xmlns:skos="http://www.w3.org/2004/02/skos/core#">
<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML+RDFa 1.0//EN" doctype-system="http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"/>


<!-- include other XSL files for generally used functions -->
<xsl:include href="rev2-string-replace.xsl"/>


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
	<xsl:for-each select="//oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="//oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if> &quot;<span xmlns:dc="http://purl.org/dc/elements/1.1/" property="dc:title"><xsl:value-of select="//oc:metadata/dc:title"/></span>&quot; (Released <xsl:value-of select="//oc:metadata/dc:date"/>). <xsl:for-each select="//oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em>  
</xsl:variable>

<xsl:variable name="citationView">
	<xsl:for-each select="//oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="//oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if>&quot;<xsl:value-of select="//oc:metadata/dc:title"/>&quot; (Released <xsl:value-of select="//oc:metadata/dc:date"/>). <xsl:for-each select="//oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em> &lt;http://opencontext.org/subjects/<xsl:value-of select="arch:spatialUnit/@UUID"/>&gt; 
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
												<h2 class="top_detail"><xsl:if test="arch:spatialUnit/@ownedBy !=0">Project: <a><xsl:attribute name="href">../projects/<xsl:value-of select="arch:spatialUnit/@ownedBy"/></xsl:attribute><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:project_name"/></a></xsl:if><xsl:if test="arch:spatialUnit/@ownedBy =0">Open Context</xsl:if></h2>
												<h2 class="views">Number of Views: <xsl:value-of select="arch:spatialUnit/oc:social_usage/oc:item_views/oc:count"/></h2>
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
												
												<div>
														<xsl:choose>
																<xsl:when test="$num_Obs !=0">
																		
																		<xsl:if test="$num_Obs &gt; 1">
																				<xsl:attribute name="class">item-multi-obs</xsl:attribute>
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
																		</xsl:if>
																		<xsl:if test="$num_Obs = 1">
																				<xsl:attribute name="class">item-multi-obs</xsl:attribute>
																		</xsl:if>
																</xsl:when>
																<xsl:otherwise>
																		
																</xsl:otherwise>
														</xsl:choose>
														
														<div>
																<xsl:if test="$num_Obs != 0">
																		<xsl:if test="$num_Obs != 1">
																				<xsl:attribute name="class">tab-content</xsl:attribute>
																		</xsl:if>
																		<xsl:if test="$num_Obs = 1">
																				<xsl:attribute name="class">item-single-obs</xsl:attribute>
																		</xsl:if>
																		<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation">
																				<div>
																						<xsl:attribute name="id">obs-<xsl:value-of select="position()"/></xsl:attribute>
																						<xsl:if test="position() = 1 and $num_Obs != 1">
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
																																<h5><xsl:value-of select="oc:obs_metadata/oc:name"/>: Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property[oc:show_val/text()])"/>)<!--<span style="margin-left:25px;">[Observation <xsl:value-of select="position()"/>]</span> --></h5>
																														</xsl:when>
																														<xsl:when test="$num_Obs = 1">
																																<h5>Descriptive Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property[oc:show_val/text()])"/>)</h5>
																														</xsl:when>
																														<xsl:otherwise>
																																<h5>Observation <xsl:value-of select="position()"/>: Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property[oc:show_val/text()])"/>)</h5>
																														</xsl:otherwise>
																												</xsl:choose>
																												
																												<table class="table table-striped table-condensed table-hover table-bordered prop-tab">
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
																																		  <xsl:choose>
																																				
																																				
																																				<xsl:when test="oc:var_label[@type = 'multivalue']">
																																					<ul>
																																						<xsl:for-each select="oc:show_values[@showLink = '0']/oc:show_val">
																																							<li><xsl:value-of select="."/></li>
																																						</xsl:for-each>
																																						<xsl:for-each select="oc:show_values[@showLink = '1']/oc:show_val">
																																							<li><a><xsl:attribute name="href">../properties/<xsl:value-of select="@propUUID"/></xsl:attribute><xsl:value-of select="."/></a>																													</li>
																																						</xsl:for-each>
																																					</ul>
																																				</xsl:when>
																											
																																		
																																				<xsl:when test="(oc:var_label[@type = 'boolean']) and (oc:show_val = 'true')">
																																					 <a><xsl:attribute name="href">../properties/<xsl:value-of select="oc:propid"/></xsl:attribute>True</a>
																																				</xsl:when>
																																				<xsl:when test="(oc:var_label[@type = 'boolean']) and (oc:show_val = 'false')">
																																					 <a><xsl:attribute name="href">../properties/<xsl:value-of select="oc:propid"/></xsl:attribute>False</a>
																																				</xsl:when>
																																				<xsl:when test="oc:var_label[@type = 'alphanumeric'] and oc:show_val[@type = 'xhtml']">
																																					
																																					 <xsl:for-each select="oc:show_val/*">
																																						  <xsl:call-template  name="node-output" >
																																								<xsl:with-param name="root" select="."/>
																																						  </xsl:call-template>
																																					 </xsl:for-each>
																																				</xsl:when>
																																				<xsl:otherwise>
																																					 <xsl:value-of select="oc:show_val"/>
																																				</xsl:otherwise>
																																		  </xsl:choose>
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
																														  <xsl:choose>
																																<xsl:when test="arch:string/@type = 'xhtml'">
																																	 <!-- <xsl:value-of select="arch:string"/> -->
																																	 <xsl:for-each select="arch:string/*">
																																		  <xsl:call-template  name="node-output" >
																																				<xsl:with-param name="root" select="."/>
																																		  </xsl:call-template>
																																	 </xsl:for-each>
																																</xsl:when>
																																<xsl:otherwise>
																																	 <xsl:value-of select="arch:string" disable-output-escaping="yes" />
																																</xsl:otherwise>
																														  </xsl:choose>
																														  </div>
																													 </xsl:for-each>
																												</div>
																										  </xsl:if>
																										 
																										  <xsl:if test="count(descendant::arch:links/oc:space_links/oc:link) != 0" >
																												<div class="item-links">
																													 <xsl:attribute name="id">l-subjects-<xsl:value-of select="position()"/></xsl:attribute>
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
																																			 </a> <em><xsl:value-of select="oc:relation"/></em>
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
																																				  </a> <em><xsl:value-of select="oc:relation"/></em>
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
																													 <xsl:attribute name="id">l-docs-<xsl:value-of select="position()"/></xsl:attribute>
																													 <h5>Linked Documents / Logs (<xsl:value-of select="count(descendant::arch:links/oc:diary_links/oc:link)"/>)</h5>
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
																										  
																										  
																										  <!-- linked persons -->
																										  <xsl:if test="count(descendant::arch:links/oc:person_links/oc:link) != 0" >
																												<div class="person-links">
																													 <xsl:attribute name="id">l-persons-<xsl:value-of select="position()"/></xsl:attribute>
																													 <h5>Linked Persons / Organizations (<xsl:value-of select="count(descendant::arch:links/oc:person_links/oc:link)"/>)</h5>
																													 <div class="list_tab">
																														  <xsl:for-each select="arch:links/oc:person_links/oc:link[position() mod 2 = 1]">
																																<div class="list_tab_row">
																																	 
																																	 <div class="list_tab_cell"><a>
																																			 <xsl:attribute name="href">../persons/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																																			 </a>, <em><xsl:value-of select="oc:relation"/></em>
																																	 </div>
																																	 
																																	 <xsl:for-each select="following-sibling::oc:link[1]">
																																		  <div class="list_tab_cell"><a>
																																			 <xsl:attribute name="href">../persons/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
																																			 </a>, <em><xsl:value-of select="oc:relation"/></em>
																																	 </div>
																																	 </xsl:for-each>
																																	 
																																</div>
																														  </xsl:for-each>
																													 </div>
																												</div>
																										  </xsl:if>
																								</div>
																						</xsl:if>
																				
																						<xsl:if test="count(descendant::arch:properties/arch:property[oc:show_val/text()]) =0 and count(descendant::arch:notes/arch:note) =0 ">
																								Data creators have not provided any descriptive properties for this item.
																						</xsl:if>
																				</div>
																		</xsl:for-each>
																</xsl:if>
														
																<xsl:if test="$num_Obs = 0">
																		<xsl:attribute name="class">item-single-obs</xsl:attribute>
																		Data creators have not provided any descriptive properties for this item.
																</xsl:if>
														</div>
												</div>
												<!--last div of observations related content -->
												
												
												<xsl:if test="($num_Children &gt; 50) and ($ChildQValue != 0)" >
													 <div class="item-links" id="item-children" >
														  <h5>Contents (<xsl:value-of select="count(descendant::arch:spatialUnit/oc:children/oc:tree/oc:child)"/> items)</h5>
														  <p>Too many items are contained in this context to display.
														  To browse and search through items contained in <strong><xsl:value-of select="//arch:spatialUnit/arch:name/arch:string"/></strong>,
														  please <a><xsl:attribute name="href"><xsl:value-of select="$ChildQValue"/></xsl:attribute>(click here)</a>.
														  </p>
													 </div>
												</xsl:if>
													 
												<xsl:if test="($num_Children != 0) and (($num_Children &lt; 51) or ($ChildQValue = 0))" >
													 <div class="item-links" id="item-children" >
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
													 </div>
												</xsl:if>
												
										</div><!-- end div for left des cell -->
										<div id="right_des">
												
												
												<div id="editorial" >
														<h5>Project Editorial Status</h5>
														<div id="project-edit-status">
																<span id="project-edit-stars">
																		<xsl:attribute name="title"><xsl:value-of select="//oc:metadata/oc:project_name/@statusDes"/> (Click for more)</xsl:attribute>
																		<a href="../about/publishing#editorial-status">
																				<xsl:choose>
																						<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 1">
																								&#9679;&#9675;&#9675;&#9675;&#9675;
																						</xsl:when>
																						<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 2">
																								&#9679;&#9679;&#9675;&#9675;&#9675;
																						</xsl:when>
																						<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 3">
																								&#9679;&#9679;&#9679;&#9675;&#9675;
																						</xsl:when>
																						<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 4">
																								&#9679;&#9679;&#9679;&#9679;&#9675;
																						</xsl:when>
																						<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 5">
																								&#9679;&#9679;&#9679;&#9679;&#9679;
																						</xsl:when>
																						<xsl:otherwise>
																								Forthcoming/DRAFT
																						</xsl:otherwise>
																				</xsl:choose>
																		  </a>
																</span>
																<xsl:value-of select="//oc:metadata/oc:project_name/@statusLabel"/>
														</div>
														<br/>
														<br/>
														<h5>Suggested Citation</h5>
														<div id="citation">
															 <xsl:value-of select="$citationView"/><xsl:if test="//oc:metadata/dc:identifier[@type ='doi']">DOI:<a><xsl:attribute name="href"><xsl:value-of select="//oc:metadata/dc:identifier[@type ='doi']/@href"/></xsl:attribute><xsl:value-of select="//oc:metadata/dc:identifier[@type ='doi']"/></a></xsl:if>
														</div>
														
														<!--
														<xsl:if test="//dc:subject = 'DINAA'">
															<br/>
															<br/>
															<h5>Mapping Data</h5>
															<div id="map" style="height:180px;">
																
															</div>
														</xsl:if>
														-->
														
														<br/>
														<br/>
														<h5>Mapping Data</h5>
														<div id="map" style="height:180px;">
															
														</div>
														
													 
														<xsl:if test="count(descendant::arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@status='public']) != 0">
														
															<br/>
															<br/>
															<h5>Editorial Description (<xsl:value-of select="count(descendant::arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@status='public'])"/>)</h5>
															<xsl:for-each select="arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag">
																<a>
																  <xsl:if test="@type != 'chronological'"><xsl:attribute name="href">../sets/?tag[]=<xsl:value-of select="oc:name"/></xsl:attribute></xsl:if>
																  <xsl:if test="@type = 'chronological'"><xsl:attribute name="href">../sets/?t-start=<xsl:value-of select="//oc:time_start"/>&amp;t-end=<xsl:value-of select="//oc:time_finish"/></xsl:attribute><xsl:attribute name="id">time-span</xsl:attribute><xsl:attribute name="title">Rough dates provided by editors to facilitate searching</xsl:attribute></xsl:if><xsl:value-of select="oc:name"/></a><xsl:if test="position() != last()"> , </xsl:if>
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
																			<xsl:if test="oc:targetLink/@href">
																				<p class="tinyText">
																				  <xsl:value-of select="oc:vocabulary"/>-<xsl:value-of select="oc:label"/> :: <xsl:value-of select="oc:targetLink/oc:vocabulary"/>-
																				  <a>
																				  <xsl:if test="@href = 'http://purl.org/NET/biol/ns#term_hasTaxonomy'">
																						  <xsl:attribute name="rel">bio:term_hasTaxonomy</xsl:attribute>
																				  </xsl:if>
																				  <!-- <xsl:attribute name="property"><xsl:value-of select="oc:targetLink/oc:label"/></xsl:attribute> -->
																				  <xsl:attribute name="href"><xsl:value-of select="oc:targetLink/@href"/></xsl:attribute>
																				  <xsl:attribute name="title">Target concept</xsl:attribute><xsl:value-of select="oc:targetLink/oc:label"/></a>
																				</p>
																			</xsl:if>
																</xsl:for-each>
																</div>
														</xsl:if>
												</div><!-- end div for editorial content -->
												
												<xsl:if test="//oc:metadata/oc:tableRefs">
													<div id="table-links">
														<h5>Downloadable Tables with this Item</h5>
														<xsl:for-each select="//oc:metadata/oc:tableRefs/oc:link">
															<p>
																	<a>
																			<xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute>
																			<xsl:attribute name="title">This downloadable table has a record of this item</xsl:attribute>
																			<xsl:value-of select="."/>
																	</a>
															</p>
														</xsl:for-each>
													</div>
												</xsl:if>
												
												
												<div id="media-links">
														<h5>Linked Media (<xsl:value-of select="count(descendant::arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link)"/>)</h5>
														  
														  <xsl:if test="arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link">
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
														  </xsl:if>
														  <p>
																  <a>
																		  <xsl:attribute name="href">../subjects/<xsl:value-of select="arch:spatialUnit/@UUID"/>.xml</xsl:attribute>
																		  <xsl:attribute name="title">ArchaeoML (XML) Representation</xsl:attribute>
																		  <xsl:attribute name="type">application/xml</xsl:attribute>ArchaeoML (XML) Version
																  </a>
														  </p>
														  <xsl:if test="arch:spatialUnit/@ownedBy !=0">
																<p>
																		<a>
																				<xsl:attribute name="href">https://github.com/ekansa/opencontext-<xsl:value-of select="arch:spatialUnit/@ownedBy"/>/tree/master/subjects/<xsl:value-of select="$item_id"/>.xml</xsl:attribute>
																				<xsl:attribute name="title">XML data in Github repository</xsl:attribute>
																				Version-control (Github, XML Data)
																		</a>
																</p>
														  </xsl:if>
												</div>
												<!-- end div for media-links -->
												
												<div id="item-license" >
													 <h5>Copyright Licensing</h5>
													 <div class="list_tab">
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
																		  </xsl:when>
																		  <xsl:otherwise>
																				<a href="http://creativecommons.org/licenses/by/3.0/">
																					 <img src="http://i.creativecommons.org/l/by/3.0/88x31.png" alt="Creative Commons Attribution 3.0 License" />
																				</a>
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
												<!-- end div for license div -->
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
						
						<xsl:if test="contains(@href, 'http://gawd.atlantides.org/terms/findspot')">
								<div xmlns:oac="http://www.openannotation.org/ns/" id="concordiaFindspot" typeof="oac:Annotation">
								<xsl:attribute name="about">http://opencontext.org/subjects/<xsl:value-of select="$item_id"/>#concordiaFindspot</xsl:attribute>
								
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
		
		
		
		
		<!--invisible RDFa metadata -->
		<div style="display:none;">
				
				<div>
						<xsl:attribute name="base"><xsl:value-of select="//oc:metadata/dc:identifier"/></xsl:attribute>
						<div property="dcterms:title"><xsl:value-of select="//oc:metadata/dc:title"/></div>
						<div property="dcterms:date"><xsl:value-of select="//oc:metadata/dc:date"/></div>
						<div about="" rel="dcterms:isPartOf"><xsl:attribute name="href"><xsl:value-of select="//oc:metadata/oc:project_name/@href"/></xsl:attribute></div>
						<div about="" rel="dcterms:publisher"><xsl:attribute name="href">http://opencontext.org</xsl:attribute></div>
						
						<div about="" rel="bibo:status"><xsl:attribute name="href"><xsl:value-of select="//oc:metadata/oc:project_name/@statusURI"/></xsl:attribute></div>
						<div about="" rel="bibo:status"><xsl:attribute name="href">http://opencontext.org/about/publishing/#edit-level-<xsl:value-of select="//oc:metadata/oc:project_name/@editStatus"/></xsl:attribute></div>
				
				
						<xsl:for-each select="//oc:metadata/dc:creator">
						<div about="" rel="dc:creator"><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute></div>
						<div property="rdfs:label"><xsl:attribute name="about"><xsl:value-of select="@href"/></xsl:attribute><xsl:value-of select="."/></div>
						</xsl:for-each>
						
						<xsl:for-each select="//oc:metadata/dc:contributor">
						<div about="" rel="dc:contributor"><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute></div>
						<div property="rdfs:label"><xsl:attribute name="about"><xsl:value-of select="@href"/></xsl:attribute>
						<xsl:value-of select="."/></div>
						</xsl:for-each>
				
						<div id="geo-lat" property="geo:lat"><xsl:value-of select="//oc:metadata/oc:geo_reference/oc:geo_lat"/></div>
						<div id="geo-lon" property="geo:lon"><xsl:value-of select="//oc:metadata/oc:geo_reference/oc:geo_long"/></div>
						
				</div>
				
				<div about="http://opencontext.org">
					<div property="rdfs:label">Open Context</div>
				</div>
				
				<div id="#geo-data">
					<div id="geo-note" about="" property="dcterms:description"><xsl:value-of select="//oc:metadata/oc:geo_reference/oc:metasource/oc:note"/></div>
					<xsl:choose>
						<xsl:when test="//oc:metadata/oc:geo_reference/oc:metasource/@ref_type = 'self'">
							<div id="geo-source">Spatial reference specified for this item.</div>
						</xsl:when>
						<xsl:otherwise>
							<div id="geo-source">Spatial reference inferred from containment in <a><xsl:attribute name="href"><xsl:value-of select="//oc:metadata/oc:geo_reference/oc:metasource/@href"/></xsl:attribute><xsl:value-of select="//oc:metadata/oc:geo_reference/oc:metasource/oc:source_name"/></a>.</div>
						</xsl:otherwise>
					</xsl:choose>
				</div>
				
				
		</div>
		
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
