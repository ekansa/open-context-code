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


<xsl:variable name="max_Tabs">9</xsl:variable>

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
												<p>Class: <xsl:value-of select="//arch:spatialUnit/oc:item_class/oc:name"/></p>
										</div>
										<div id="item_top_des_cell">Project: <a><xsl:attribute name="href">../projects/<xsl:if test="arch:spatialUnit/@ownedBy !=0"><xsl:value-of select="arch:spatialUnit/@ownedBy"/></xsl:if></xsl:attribute><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:project_name"/></a>
										</div>
										<div id="item_top_view_cell">Number of Views: <strong><xsl:value-of select="arch:spatialUnit/oc:social_usage/oc:item_views/oc:count"/></strong>
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
																<xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>                        						</a>
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
																<xsl:when test="$num_Obs != 1">
																		<ul class="nav nav-tabs" id="obsTabs" style="width:100%; min-width:625px;">
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
														
														<div style="width:100%;">
																<xsl:if test="$num_Obs != 1">
																		<xsl:attribute name="class">tab-content</xsl:attribute>
																		<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation">
																				<div style="width:100%;">
																						<xsl:attribute name="id">obs-<xsl:value-of select="position()"/></xsl:attribute>
																						<xsl:if test="position() = 1">
																								<xsl:attribute name="class">tab-pane fade in active</xsl:attribute>
																						</xsl:if>
																						<xsl:if test="position() != 1">
																								<xsl:attribute name="class">tab-pane fade</xsl:attribute>
																						</xsl:if>
																						<xsl:if test="count(descendant::arch:properties/arch:property) !=0 ">
																								<div class="properties">
																										<xsl:choose>
																												<xsl:when test="oc:obs_metadata/oc:name">
																														<h5><xsl:value-of select="oc:obs_metadata/oc:name"/> Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property)"/>)<span style="margin-left:25px;">[Observation <xsl:value-of select="position()"/>]</span></h5>
																												</xsl:when>
																												<xsl:otherwise>
																														<h5>Observation <xsl:value-of select="position()"/> Properties (<xsl:value-of select="count(descendant::arch:properties/arch:property)"/>)</h5>
																												</xsl:otherwise>
																										</xsl:choose>
																										<div class="list_tab"> 
																												<xsl:for-each select="arch:properties/arch:property">
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
																								</div>
																						</xsl:if>
																				</div>
																		</xsl:for-each>
																</xsl:if>
														</div>
												
												</div>
												<!--last div of observations related content -->
																									
												
												
												
										</div><!-- end div for left des cell -->
										<div id="right_des">
										</div><!-- end div for right des cell -->
								</div><!-- end div for main des row -->
						</div><!-- end div for left des tab -->
				</div><!-- end div for main des -->
		
		
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
