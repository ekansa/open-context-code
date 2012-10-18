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
										<div id="item_top_des_cell">
												<h2 class="top_detail">Project: <a><xsl:attribute name="href">../projects/<xsl:if test="arch:resource/@ownedBy !=0"><xsl:value-of select="arch:resource/@ownedBy"/></xsl:if></xsl:attribute><xsl:value-of select="arch:resource/oc:metadata/oc:project_name"/></a></h2>
												<h2 class="views">Number of Views: <xsl:value-of select="arch:resource/oc:social_usage/oc:item_views/oc:count"/></h2>
										</div>
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
												
												<div id="media-preview-des">
														
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
														
														
														<div id="res-properties">
																<xsl:if test="count(descendant::arch:resource/arch:properties/arch:property[oc:show_val/text()]) !=0 or count(descendant::arch:resource/arch:notes/arch:note) !=0 ">
																		<div class="properties">
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
																<xsl:if test="count(descendant::arch:resource/arch:properties/arch:property[oc:show_val/text()]) =0 and count(descendant::arch:resource/arch:notes/arch:note) =0 ">
																		<div class="item-notes">
																				<div class="item-note">
																				(No descriptive properties provided)
																				</div>
																		</div>
																</xsl:if>
																		
																<!-- linked persons -->
																<xsl:if test="count(descendant::arch:links/oc:person_links/oc:link) != 0" >
																	 <div class="person-links" id="l-persons-1">
								
																		  <h5>Linked Persons / Organizations (<xsl:value-of select="count(descendant::arch:links/oc:person_links/oc:link)"/>)</h5>
																		  <div class="list_tab">
																				<xsl:for-each select="//arch:links/oc:person_links/oc:link[position() mod 2 = 1]">
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
												</div>
												<!--last div of observations related content -->
										</div><!-- end div for left des cell -->
										<div id="right_des">
												<div id="editorial">
														<h5>Project Rreview Status</h5>
														<div id="project-edit-status">
																  <span id="project-edit-stars">
																		  <xsl:attribute name="title"><xsl:value-of select="//oc:metadata/oc:project_name/@statusDes"/></xsl:attribute>
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
																						  (Not applicable)
																				  </xsl:otherwise>
																		  </xsl:choose>
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
												<div id="media-links">
														<h5>Linked Media (<xsl:value-of select="count(descendant::arch:resource/arch:links/oc:media_links/oc:link)"/>)</h5>
														
														<xsl:if test="//arch:links/oc:media_links/oc:link">
																<div class="list_tab">
																		<xsl:for-each select="arch:resource/arch:links/oc:media_links/oc:link[position() mod 2 = 1]">
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
		
		
		</div><!-- End div for main body -->
</xsl:template>








</xsl:stylesheet>
