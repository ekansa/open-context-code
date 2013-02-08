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
					 
				xmlns:oc="http://opencontext.org/schema/property_schema_v1.xsd"
				xmlns:arch="http://ochre.lib.uchicago.edu/schema/Project/Variable.xsd"
					 
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:dc="http://purl.org/dc/elements/1.1/"
				xmlns:gml="http://www.opengis.net/gml"
				xmlns:atom="http://www.w3.org/2005/Atom"
				xmlns:georss="http://www.georss.org/georss"
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
<xsl:for-each select="//arch:property/oc:metadata/dc:creator">&amp;rft.creator=<xsl:value-of select="."/>
</xsl:for-each>
&amp;rft_id=http%3A%2F%2Fopencontext.org%2Fprojects%2F<xsl:value-of select="//arch:property/@UUID"/>
</xsl:variable>






<xsl:variable name="badCOINS"><xsl:value-of select="//oc:metadata/oc:coins"/>
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


<xsl:variable name="num_Summaries">
		<xsl:value-of select="count(//oc:propStats)"/>
</xsl:variable>

<xsl:variable name="num_editors">
	<xsl:value-of select="count(//arch:property/oc:metadata/oc:metadata/dc:creator)"/>
</xsl:variable>

<xsl:variable name="num_contribs">
	<xsl:value-of select="count(//arch:property/oc:metadata/dc:contributor)"/>
</xsl:variable>

<xsl:variable name="citation">
	<xsl:for-each select="//arch:property/oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="//arch:property/oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if>&quot;<xsl:value-of select="//arch:property/oc:metadata/dc:title"/>&quot; (Released <xsl:value-of select="//arch:property/oc:metadata/dc:date"/>). <xsl:for-each select="//arch:property/oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em> &lt;http://opencontext.org/projects/<xsl:value-of select="//arch:property/@UUID"/>&gt; 
</xsl:variable>

<xsl:variable name="citationView">
		<xsl:value-of select="$citation"/>
</xsl:variable>

<xsl:variable name="propVariable">
		<xsl:value-of select="/arch:property/oc:manage_info/oc:propVariable"/>
</xsl:variable>

<xsl:variable name="propValue">
		<xsl:value-of select="/arch:property/oc:manage_info/oc:propValue"/>
</xsl:variable>

<xsl:variable name="numLinks">
		<xsl:value-of select="count(//oc:linkedData/oc:relationLink)"/>
</xsl:variable>


<div id="main">

		<xsl:comment>
		BEGIN Container for gDIV of general item information
		</xsl:comment>
		
		<div id="item_top">
				<div id="item_top_tab">
						<div id="item_top_row">
								<div id="item_top_icon_cell">
										<img>
												<xsl:attribute name="src">../images/layout/linked-data-prop.png</xsl:attribute>
												<xsl:attribute name="alt">Property icon</xsl:attribute>
										</img>
								</div>
								<div id="prop-item_top_name_cell">
										<h1>Property: <xsl:value-of select="$propVariable"/></h1>
										<h2>Value:
												<xsl:choose>
														<xsl:when test="string-length($propValue) &gt; 47">
																<xsl:value-of select="substring($propValue,1,50)"/>...
														</xsl:when>
														<xsl:otherwise><xsl:value-of select="$propValue"/></xsl:otherwise>
												</xsl:choose>
										</h2>
								</div>
								<div id="prop-item_top_des_cell">
										<h2 class="top_detail">Project: <a><xsl:attribute name="href">../projects/<xsl:if test="arch:property/@ownedBy !=0"><xsl:value-of select="arch:property/@ownedBy"/></xsl:if></xsl:attribute><xsl:value-of select="//oc:metadata/oc:project_name"/></a></h2>
										
								</div>
						</div>
				</div><!--end div for the top_tab -->
		</div>
		
		<xsl:comment>
		END code for General Item info DIV
		</xsl:comment>
			 
			 
		
		<xsl:comment>
		Code for showing the main description content
		</xsl:comment>
		
		<div id="main_description">
				<div id="main_description_tab">
						<div id="main_description_row">
								<div id="left_des">
				
				
										<div id="prop-all-des">
												
												<div id="prop-intro">
														<table class="table table-condensed" id="prop-intro-tab">
																<tbody>
																		<tr>
																				<th>Property Variable</th>
																				<td><xsl:value-of select="$propVariable"/></td>
																		</tr>
																		<tr>
																				<th>Description of Variable</th>
																				<td>
																						<xsl:call-template name="makeNote">
																								<xsl:with-param name="note" select="//arch:notes/arch:note[@type='var_des']"/>
																								<xsl:with-param name="noteType">var</xsl:with-param>
																								<xsl:with-param name="numLinks" select="$numLinks" />
																						</xsl:call-template>
																				</td>
																		</tr>
																		<xsl:if test="//oc:manage_info/oc:propVariable/@href">
																				<tr>
																						<th>Unit of Measurement</th>
																						<td>
																								<a>
																										<xsl:attribute name="href"><xsl:value-of select="//oc:manage_info/oc:propVariable/@href"/></xsl:attribute>
																										<xsl:attribute name="title">Definition of unit: (<xsl:value-of select="//oc:manage_info/oc:propVariable/@abrv"/>)</xsl:attribute><xsl:value-of select="//oc:manage_info/oc:propVariable/@name"/>
																								</a>
																						</td>
																				</tr>
																		</xsl:if>
																		<tr>
																				<th>Property Value</th>
																				<td><xsl:value-of select="$propValue"/></td>
																		</tr>
																		<tr>
																				<th>Description of Value</th>
																				<td>
																						<xsl:call-template name="makeNote">
																								<xsl:with-param name="note" select="//arch:notes/arch:note[@type='prop_des']"/>
																								<xsl:with-param name="noteType">prop</xsl:with-param>
																								<xsl:with-param name="numLinks" select="$numLinks" />
																						</xsl:call-template>
																				</td>
																		</tr>
																</tbody>
														</table>
												</div>
												<div id="prop-sum">
														<div>
																<xsl:choose>
																		<xsl:when test="$num_Summaries &gt; 1">
																				<xsl:attribute name="class">item-multi-obs</xsl:attribute>
																				<ul class="nav nav-tabs" id="obsTabs">
																						<xsl:for-each select="//oc:propStats">
																								<li>
																										<xsl:if test="position() = 1">
																												<xsl:attribute name="class">active</xsl:attribute>
																										</xsl:if>
																										<a><xsl:attribute name="href">#obs-<xsl:value-of select="position()"/></xsl:attribute>
																										<xsl:call-template name="summaryTypes">
																										<xsl:with-param name="observeType" select="@observeType"/>
																												</xsl:call-template>
																										</a>
																								</li>
																						</xsl:for-each>
																						<!--
																						<li><a href="#obs-2">Fake</a></li>
																						-->
																				</ul>
																				<div class="tab-content">
																						<xsl:for-each select="//oc:propStats">
																								<div>
																										<xsl:attribute name="id">obs-<xsl:value-of select="position()"/></xsl:attribute>
																										<xsl:if test="position() = 1">
																												<xsl:attribute name="class">tab-pane fade in active</xsl:attribute>
																										</xsl:if>
																										<xsl:if test="position() != 1">
																												<xsl:attribute name="class">tab-pane fade</xsl:attribute>
																										</xsl:if>
																										<h5>Property Summary:
																												<xsl:call-template name="summaryTypes">
																														<xsl:with-param name="observeType" select="@observeType"/>
																												</xsl:call-template>
																												<!--(<xsl:value-of select="//parent::oc:propMaxCount"/>)-->
																										</h5>
																										<table class="table table-hover table-bordered table-condensed barGraphMultiTab">
																												<thead>
																														<tr>
																																<th class="prop-vals">Values</th>
																																<th class="val-count">Count</th>
																														</tr>		
																												</thead>
																												<tbody>
																														<xsl:for-each select="oc:graphData/oc:bar">
																																<xsl:call-template name="makeBar">
																																		<xsl:with-param name="propMaxCount" select="ancestor::oc:propStats/oc:propMaxCount" />
																																		<xsl:with-param name="setURL" select="@setURL" />
																																		<xsl:with-param name="propVal" select="." />
																																		<xsl:with-param name="propCount" select="@count" />
																																</xsl:call-template>
																														</xsl:for-each>
																												</tbody>
																										</table>
																								</div>
																						</xsl:for-each>
																						<!--
																						<div class="tab-pane fade" id="obs-2">
																								<h5>Fake Tab</h5>
																						</div>
																						-->
																				</div>
																		</xsl:when>
																		<xsl:otherwise>
																				<div id="single-prop-stats">
																				<xsl:for-each select="//oc:propStats">
																						<h5>Summary of Values for this Property:
																								<!--
																								<xsl:call-template name="summaryTypes">
																										<xsl:with-param name="observeType" select="@observeType"/>
																								</xsl:call-template>
																								-->
																						</h5>
																						<table class="table table-hover table-bordered table-condensed barGraph">
																								<thead>
																										<tr>
																												<th class="prop-vals">Values</th>
																												<th class="val-count">Count</th>
																										</tr>		
																								</thead>
																								<tbody>
																										<xsl:for-each select="oc:graphData/oc:bar">
																												<xsl:call-template name="makeBar">
																														<xsl:with-param name="propMaxCount" select="//parent::oc:propMaxCount" />
																														<xsl:with-param name="setURL" select="@setURL" />
																														<xsl:with-param name="propVal" select="." />
																														<xsl:with-param name="propCount" select="@count" />
																												</xsl:call-template>
																										</xsl:for-each>
																								</tbody>
																						</table>
																				</xsl:for-each>
																				</div>
																		</xsl:otherwise>
																</xsl:choose>	
														</div>
														
												<xsl:if test="//oc:linkedData">
														<div id="all-linked-data">
																<h5>Linked Data</h5>
																<p>The meaning of this property is closely approximated by or equivalent to:</p>
																<xsl:for-each select="//oc:linkedData/oc:relationLink">
																		<div class="linked-data">
																				<h6>Relation: <xsl:value-of select="oc:label" />::<xsl:value-of select="oc:targetLink/oc:label" />
																				</h6>
																				<table class="table table-striped table-condensed">
																						<thead>
																								<tr>
																										<th></th>
																										<th>Relation</th>
																										<th>Value</th>
																								</tr>
																						</thead>
																						<tbody>
																								<tr>
																										<th>Project specific term</th>
																										<td><xsl:value-of select="$propVariable" /></td>
																										<td><xsl:value-of select="$propValue" /></td>
																								</tr>
																								<tr>
																										<th>Related Concept</th>
																										<td><xsl:value-of select="oc:label" /></td>
																										<td><xsl:value-of select="oc:targetLink/oc:label" /></td>
																								</tr>
																								<tr>
																										<th>Related Concept URI</th>
																										<td>
																												<a>
																														<xsl:attribute name="href"><xsl:value-of select="@href" /></xsl:attribute>
																														<xsl:value-of select="@href" />
																												</a>
																										</td>
																										<td>
																												<a>
																														<xsl:attribute name="href"><xsl:value-of select="oc:targetLink/@href" /></xsl:attribute>
																														<xsl:value-of select="oc:targetLink/@href" />
																												</a>
																										</td>
																								</tr>
																								<tr>
																										<th>Related Vocabulary or Collection</th>
																										<td>
																												<a>
																														<xsl:attribute name="href"><xsl:value-of select="oc:vocabulary/@href" /></xsl:attribute>
																														<xsl:value-of select="oc:vocabulary" />
																												</a>
																										</td>
																										<td>
																												<a>
																														<xsl:attribute name="href"><xsl:value-of select="oc:targetLink/oc:vocabulary/@href" /></xsl:attribute>
																														<xsl:value-of select="oc:targetLink/oc:vocabulary" />
																												</a>
																										</td>
																								</tr>
																						</tbody>
																				</table>
																		</div>
																</xsl:for-each>
														</div>
												</xsl:if>		
														
														
														<xsl:if test="count(descendant::arch:property/oc:metadata/oc:links/oc:link) != 0" >
																<div id="person-links">
																		<xsl:for-each select="//oc:metadata/oc:links/oc:link">
																			<h5>Linked Data:
																				<a>
																						<xsl:attribute name="class">person-link</xsl:attribute>
																						<xsl:attribute name="id">plink-<xsl:value-of select="position()"/></xsl:attribute>
																						<xsl:attribute name="rel"><xsl:value-of select="@rel"/></xsl:attribute>
																						<xsl:attribute name="href"><xsl:value-of select="."/></xsl:attribute>
																						<xsl:value-of select="."/>
																				</a>
																			</h5>
																			<div>
																				<xsl:attribute name="id">plink-data-<xsl:value-of select="position()"/></xsl:attribute>
																				<br/>
																			</div>
																		</xsl:for-each>
																</div>
														</xsl:if>
														
												</div>
												
										
										
												<xsl:if test="count(//dc:contributor) != 0">
														<div id="prop-contribs">
																<h5>Data Contributors Using this Property</h5>
																<p>
																	<xsl:for-each select="//dc:contributor">
																		<a><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute><xsl:value-of select="."/></a><xsl:if test="position() != last()">, </xsl:if>
																	</xsl:for-each>
																</p>
														</div>
												</xsl:if>
										</div>
										<!-- end all prop sum div -->
										
										
										
										<xsl:if test="count(//arch:properties/arch:property[oc:show_val/text()]) !=0 ">
												<div class="properties">
														<h5>Description (<xsl:value-of select="count(descendant::arch:property/arch:properties/arch:property[oc:show_val/text()])"/> properties)</h5>
														<table class="table table-striped table-condensed table-hover table-bordered prop-tab">
																<thead>
																		<tr>
																				<th>Variable</th>
																				<th>Value</th>
																		</tr>
																</thead>
																<tbody> 
																		<xsl:for-each select="//arch:properties/arch:property[oc:show_val/text()]">
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
												</div>
										</xsl:if>
										
										
										<xsl:if test="count(descendant::arch:property/arch:links/oc:person_links/oc:link) != 0">
												<div id="all_people" >
													<h5>Associated People (<xsl:value-of select="count(descendant::arch:property/arch:links/oc:person_links/oc:link)"/> people)</h5>
													<xsl:if test="count(descendant::arch:property/arch:links/oc:person_links/oc:link[oc:name/text() !='']) != 0" >	
														<p>
															<xsl:for-each select="//arch:links/oc:person_links/oc:link[oc:name/text() !='']">
																<a><xsl:attribute name="href">../persons/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a><xsl:if test="position() != last()">, </xsl:if>
															</xsl:for-each>
														</p>
													</xsl:if>
													<br/>
													<br/>	
												</div>
										</xsl:if>
					
								</div> <!-- end left_des cell -->
								<div id="right_des">
										<!-- <div id="ed-spacer"><br/></div> -->
										<div id="editorial">
												<h5>Project Rreview Status</h5>
												<div id="project-edit-status">
														  <span id="project-edit-stars">
																  <xsl:attribute name="title"><xsl:value-of select="//oc:metadata/oc:project_name/@statusDes"/> (Click for more)</xsl:attribute>
																  <a href="../about/publishing#editorial-status">
																		<xsl:choose>
																				<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 1">
																						&#9733;&#9734;&#9734;&#9734;&#9734;
																				</xsl:when>
																				<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 2">
																						&#9733;&#9733;&#9734;&#9734;&#9734;
																				</xsl:when>
																				<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 3">
																						&#9733;&#9733;&#9733;&#9734;&#9734;
																				</xsl:when>
																				<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 4">
																						&#9733;&#9733;&#9733;&#9733;&#9734;
																				</xsl:when>
																				<xsl:when test="//oc:metadata/oc:project_name/@editStatus = 5">
																						&#9733;&#9733;&#9733;&#9733;&#9733;
																				</xsl:when>
																				<xsl:otherwise>
																						Forthcoming
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
														<xsl:value-of select="$citationView"/>
												</div>
										</div>
								
										<div id="all_media" >
												<h5>Linked Media  (<xsl:value-of select="count(descendant::arch:property/arch:links/oc:media_links/oc:link)"/> files)</h5>
												<xsl:if test="count(descendant::arch:property/arch:links/oc:media_links/oc:link) != 0" >
														<div class="list_tab">
																<xsl:for-each select="//arch:links/oc:media_links/oc:link">
																		<div class="list_tab_row">
																				<xsl:choose>
																				<xsl:when test="oc:type = 'csv'">	
																				<div  class="list_tab_cell">
																					<a>
																						<xsl:attribute name="href">../tables/<xsl:value-of select="oc:id"/></xsl:attribute>
																						<xsl:attribute name="title">Downloadable table: <xsl:value-of select="oc:name"/></xsl:attribute>
																						<img>
																							<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
																							<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
																						</img>
																					</a>
																				</div>
																				<div  class="list_tab_cell">
																					<a>
																						<xsl:attribute name="href">../tables/<xsl:value-of select="oc:id"/></xsl:attribute>
																						<xsl:attribute name="title">Downloadable table: <xsl:value-of select="oc:name"/></xsl:attribute>
																						<xsl:value-of select="oc:name"/></a>
																				</div>
																				</xsl:when>
																				<xsl:when test="oc:type = 'acrobat pdf'">	
																				<div  class="list_tab_cell">
																					<a>
																						<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
																						<xsl:attribute name="title">Acrobat Document: <xsl:value-of select="oc:name"/></xsl:attribute>
																						<img>
																							<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
																							<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
																						</img>
																					</a>
																				</div>
																				<div  class="list_tab_cell">
																					<a>
																						<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
																						<xsl:attribute name="title">Acrobat Document: <xsl:value-of select="oc:name"/></xsl:attribute>
																						<xsl:value-of select="oc:name"/></a>
																				</div>
																				</xsl:when>
																				<xsl:otherwise>	
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
																				</xsl:otherwise>
																				</xsl:choose>
																		</div>
																</xsl:for-each>
														</div>
												</xsl:if>
										</div>
								
										<div id="all_keywords">
												<h5>Project Keywords</h5>
													<em><xsl:for-each select="//arch:property/oc:metadata/dc:subject">
														<xsl:value-of select="." /><xsl:if test="position() != last()">, </xsl:if>
														</xsl:for-each>
													</em>
												<br/>	
										</div>
								
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
								
								
								</div>
								<!-- end right des row -->
								
								
								
						
						</div> <!-- end main des row -->
						<div id="main_description_bottom">
						</div> <!-- end main des bottom row -->
				</div> <!-- end main des tab -->
		</div> <!-- end main des -->
		
		
		
		<!--invisible RDFa metadata -->
		<div style="display:none;">
				
				<div>
						<xsl:attribute name="base"><xsl:value-of select="//oc:metadata/dc:identifier"/></xsl:attribute>
						<div property="dcterms:title"><xsl:value-of select="//oc:metadata/dc:title"/></div>
						<div property="dcterms:date"><xsl:value-of select="//oc:metadata/dc:date"/></div>
						<div about="" rel="dcterms:isPartOf"><xsl:attribute name="href"><xsl:value-of select="//oc:metadata/oc:project_name/@href"/></xsl:attribute></div>
						<div about="" rel="dcterms:publisher"><xsl:attribute name="href">http://opencontext.org</xsl:attribute></div>
						
						<div about="" rel="bibo:status"><xsl:attribute name="href"><xsl:value-of select="//oc:metadata/oc:project_name/@statusURI"/></xsl:attribute></div>
						<div about="" rel="bibo:status"><xsl:attribute name="href">http://opencontext.org/about/publishing/#edit-stars-<xsl:value-of select="//oc:metadata/oc:project_name/@editStatus"/></xsl:attribute></div>
				
				
						<xsl:for-each select="//oc:metadata/dc:creator">
						<div about="" rel="dc:creator"><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute></div>
						<div property="rdfs:label"><xsl:attribute name="about"><xsl:value-of select="@href"/></xsl:attribute><xsl:value-of select="."/></div>
						</xsl:for-each>
						
						<xsl:for-each select="//oc:metadata/dc:contributor">
						<div about="" rel="dc:contributor"><xsl:attribute name="href"><xsl:value-of select="@href"/></xsl:attribute></div>
						<div property="rdfs:label"><xsl:attribute name="about"><xsl:value-of select="@href"/></xsl:attribute>
						<xsl:value-of select="."/></div>
						</xsl:for-each>
				
				
				</div>
				
				<div about="http://opencontext.org">
						<div property="rdfs:label">Open Context</div>
				</div>
				
		</div>
		
		
		
		
		
		
		
		
</div>

</xsl:template>







<!-- Template for navigating observation tabs -->
<xsl:template name="summaryTypes">
  
		<xsl:param name="observeType" select="spatial" />
		<xsl:choose>
				<xsl:when test="$observeType = 'spatial'">
						Locations and Objects
				</xsl:when>
				<xsl:when test="$observeType = 'image'">
						Image
				</xsl:when>
				<xsl:when test="$observeType = 'media'">
						Media
				</xsl:when>
				<xsl:when test="$observeType = 'person'">
						People and Organizations
				</xsl:when>
				<xsl:when test="$observeType = 'project'">
						Projects and Collections
				</xsl:when>
				<xsl:when test="$observeType = 'document'">
						Documents
				</xsl:when>
				<xsl:otherwise></xsl:otherwise>
		</xsl:choose>
		
</xsl:template>




<!-- Template for a bar in a bar graph -->
<xsl:template name="makeBar">
		<xsl:param name="propMaxCount" select="1" />
		<xsl:param name="setURL" select="1" />
		<xsl:param name="propVal" select="1" />
		<xsl:param name="propCount" select="1" />
		
		<xsl:variable name="barWidth">
				<xsl:call-template name="makeBarWidth">
						<xsl:with-param name="propMaxCount" select="$propMaxCount" />
						<xsl:with-param name="propCount" select="$propCount" />
				</xsl:call-template>
		</xsl:variable>
		
		<tr>
				<td class="barName"><a>
						<xsl:attribute name="href"><xsl:value-of select="$setURL"/></xsl:attribute>
						<xsl:attribute name="title">Browse these <xsl:value-of select="$propCount"/> of items</xsl:attribute>
						<xsl:value-of select="$propVal"/></a>
				</td>
				<td>
						<div class="barGraphBar">
								<xsl:attribute name="style">width:<xsl:value-of select="$barWidth"/>%;</xsl:attribute>
								(<xsl:value-of select="$propCount"/>)
						</div>
				</td>
		</tr>
</xsl:template>

<xsl:template name="makeBarWidth">
		<xsl:param name="propMaxCount" select="1" />
		<xsl:param name="propCount" select="1" />
		<xsl:variable name="actPropCount">
				<xsl:choose>
						<xsl:when test="$propCount &gt; $propMaxCount">
								<xsl:value-of select="$propMaxCount"/>
						</xsl:when>
						<xsl:otherwise>
								<xsl:value-of select="$propCount"/>
						</xsl:otherwise>
				</xsl:choose>
		</xsl:variable>
		<xsl:choose>
				<xsl:when test="$propMaxCount &lt; 1">1</xsl:when>
				<xsl:otherwise>
						<xsl:choose>
								<xsl:when test="round(($actPropCount div $propMaxCount)*100) &lt; 1">1</xsl:when>
								<xsl:otherwise><xsl:value-of select="round(($actPropCount div $propMaxCount)*100)"/></xsl:otherwise>
						</xsl:choose>
				</xsl:otherwise>
		</xsl:choose>
</xsl:template>




<xsl:template name="makeNote">
		<xsl:param name="note" select="1" />
		<xsl:param name="noteType" select="1" />
		<xsl:param name="numLinks" select="0" />
		<xsl:choose>
				<xsl:when test="($note) and $numLinks = 0">
						<div class="described"><xsl:value-of select="$note"/></div>
				</xsl:when>
				<xsl:when test="($note) and $numLinks != 0">
						<div class="described"><xsl:value-of select="$note"/>
						<br/>
						<br/>
						See the <a href="#all-linked-data">linked data below</a> for additional description.
						</div>
				</xsl:when>
				<xsl:otherwise>
						<xsl:choose>
						<xsl:when test="$noteType = 'var' and $numLinks = 0">
								<div class="not-described">This variable currently has no description noted.</div>
						</xsl:when>
						<xsl:when test="$noteType = 'prop' and $numLinks = 0">
								<div class="not-described">This property value currently has no description noted.</div>
						</xsl:when>
						<xsl:when test="$noteType = 'var' and $numLinks != 0">
								<div class="not-described">This variable is described with the <a href="#all-linked-data">linked data below</a>.</div>
						</xsl:when>
						<xsl:when test="$noteType = 'prop' and $numLinks != 0">
								<div class="not-described">This property value is described with the <a href="#all-linked-data">linked data below</a>.</div>
						</xsl:when>
						<xsl:otherwise>
								<div class="not-described">This has no description noted.</div>
						</xsl:otherwise>
						</xsl:choose>
				</xsl:otherwise>
		</xsl:choose>
</xsl:template>





</xsl:stylesheet>
