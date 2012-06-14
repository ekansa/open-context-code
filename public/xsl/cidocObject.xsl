<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE rdf:RDF [
<!ENTITY rdf "http://www.w3.org/1999/02/22-rdf-syntax-ns#">
<!ENTITY rdfs "http://www.w3.org/2000/01/rdf-schema#">
<!ENTITY xsd "http://www.w3.org/2001/XMLSchema#">
<!ENTITY owl "http://www.w3.org/2002/07/owl#">
<!ENTITY crm "http://purl.org/NET/crm-owl#">
<!ENTITY claros "http://purl.org/NET/Claros/vocab#">
<!ENTITY claros_place "http://purl.org/NET/Claros/place#">
<!ENTITY claros_placeid "http://purl.org/NET/Claros/placeid#">
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
                xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
                xmlns:owl="http://www.w3.org/2002/07/owl#"
                xmlns:crm="http://purl.org/NET/crm-owl#"
                xmlns:claros="http://purl.org/NET/Claros/vocab#"
                xmlns:atom="http://www.w3.org/2005/Atom"
                xmlns:georss="http://www.georss.org/georss"
                xmlns:oc="http://opencontext.org/schema/space_schema_v1.xsd"
                xmlns:arch="http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                >
    <xsl:output method="xml" indent="yes" encoding="utf-8" />



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
    
    
        <xsl:variable name="Material">
            <xsl:choose>
                <xsl:when test="//arch:properties/arch:property[oc:var_label = 'Material']">
                    <xsl:value-of select="//arch:properties/arch:property[oc:var_label = 'Material']/oc:show_val"/>
				</xsl:when>
                <xsl:otherwise>
                    <xsl:choose>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Groundstone'">
                            Stone
                        </xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Non Diag. Bone'">
                            Bone
                        </xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Animal Bone'">
                            Bone
                        </xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Human Bone'">
                            Bone
                        </xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Pottery'">
                            Cermamic
                        </xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Glass'">
                            Glass
                        </xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Shell'">
                            Shell
                        </xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Coin'">
                            Metal
                        </xsl:when>
                        <xsl:otherwise>
                            Not specified
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
    
        <xsl:variable name="contextPath">
            <xsl:for-each select="arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent">
                <xsl:value-of select="oc:name"/>
                <xsl:if test="position() != last()"> / </xsl:if>
            </xsl:for-each>
        </xsl:variable>
    
        <xsl:variable name="contextURI">
            <xsl:for-each select="arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent">
                <xsl:if test="position() = last()">http://opencontext.org/subjects/<xsl:value-of select="oc:id"/></xsl:if>
            </xsl:for-each>
        </xsl:variable>
    
    
        <xsl:variable name="max_Tabs">10</xsl:variable>

        
        <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
            <crm:E22.Man-Made_Object>
                <xsl:attribute name="rdf:about">http://opencontext.org/subjects/<xsl:value-of select="$item_id"/></xsl:attribute> 
                <rdfs:label><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/></rdfs:label>
    
                <crm:P102.has_title>
                    <crm:E35.Title>
                      <rdf:value><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/></rdf:value>
                    </crm:E35.Title>
                </crm:P102.has_title>
                
                <crm:P2.has_type>
                    <crm:E55.Type>
                        <rdf:value><xsl:value-of select="arch:spatialUnit/oc:item_class/oc:name"/></rdf:value>
                    </crm:E55.Type>
                </crm:P2.has_type>
    
                <crm:P48.has_preferred_identifier>
                    <crm:E42.Identifier>
                        <rdf:value><xsl:value-of select="arch:spatialUnit/arch:name/arch:string"/></rdf:value>
                    </crm:E42.Identifier>
                </crm:P48.has_preferred_identifier>
    
                <crm:P108I.was_produced_by>
                    <crm:E12.Production>
                        <rdfs:label>Production of '<xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/>'</rdfs:label>
                            
                            <crm:P126.employed>
                                <crm:E57.Material>
                                  <rdfs:label><xsl:value-of select="$Material"/></rdfs:label>
                                </crm:E57.Material>
                            </crm:P126.employed>
                            
                            <crm:P4.has_time-span>
                            <crm:E52.Time-Span>
                              <crm:P82.at_some_time_within>
                                <claros:Period>
                                  <claros:period_begin rdf:datatype="http://www.w3.org/2001/XMLSchema#gYear"><xsl:value-of select="//oc:social_usage/oc:user_tags/oc:tag/oc:chrono/oc:time_start"/></claros:period_begin>
                                  <claros:period_end rdf:datatype="http://www.w3.org/2001/XMLSchema#gYear"><xsl:value-of select="//oc:social_usage/oc:user_tags/oc:tag/oc:chrono/oc:time_finish"/></claros:period_end>
                                </claros:Period>
                              </crm:P82.at_some_time_within>
                            </crm:E52.Time-Span>
                          </crm:P4.has_time-span>
                            
                            <xsl:for-each select="//oc:linkedData/oc:relationLink[@href = 'http://gawd.atlantides.org/terms/origin']">
				<!--Use Cocordia Origin for location of production event  -->
				<crm:P7.took_place_at>
					<crm:E53.Place>
						<xsl:attribute name="rdf:about"><xsl:value-of select="oc:targetLink/@href"/>#this</xsl:attribute>
						<rdfs:label><xsl:value-of select="oc:targetLink/oc:label"/></rdfs:label>
					</crm:E53.Place>
				</crm:P7.took_place_at>
                            </xsl:for-each>
                            
                            
                    </crm:E12.Production>
                </crm:P108I.was_produced_by>
    
    
                <xsl:for-each select="//arch:properties/arch:property[arch:decimal/@href | arch:integeter/@href]">
                   
                    <crm:P43F.has_dimension>
                        <crm:E54.Dimension>
                            <crm:P91F.has_unit>
                                <xsl:attribute name="rdf:resource">
                                    <xsl:value-of select="arch:decimal/@href | arch:integeter/@href"/>
                                </xsl:attribute>
                            </crm:P91F.has_unit>
                            <crm:P90F.has_value>
                                <xsl:value-of select="arch:decimal | arch:integeter"/>
                            </crm:P90F.has_value>
                            <rdfs:label><xsl:value-of select="oc:var_label"/>: <xsl:value-of select="oc:show_val"/> (<xsl:value-of select="arch:decimal/@abrv | arch:integeter/@abrv"/>)</rdfs:label>
                        </crm:E54.Dimension>
                    </crm:P43F.has_dimension>
                     
                </xsl:for-each>
    
                <!--
                <crm:P53.has_former_or_current_location>
                    <xsl:attribute name="rdf:resource">
                        <xsl:value-of select="$contextURI"/>
                    </xsl:attribute>
                </crm:P53.has_former_or_current_location>
                -->
                
                <crm:P53.has_former_or_current_location>
                    <crm:E53.Place>
                        <xsl:attribute name="rdf:about">
                            <xsl:value-of select="$contextURI"/>
                        </xsl:attribute>
                        <rdfs:label><xsl:value-of select="$contextPath"/></rdfs:label>
                    </crm:E53.Place>
                </crm:P53.has_former_or_current_location>
                
                
                <xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link[oc:type = 'image']">
					<crm:P138I.has_representation>
                        <crm:E38.Image>
                            <xsl:attribute name="rdf:about">http://opencontext.org/media/<xsl:value-of select="oc:id"/></xsl:attribute>
                            <rdfs:label><xsl:value-of select="oc:name"/></rdfs:label>
                        </crm:E38.Image>
                    </crm:P138I.has_representation>
				</xsl:for-each>
                
                <xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link[oc:type != 'image']">
					<crm:P70I.is_documented_in>
                        <xsl:attribute name="rdf:resource">http://opencontext.org/media/<xsl:value-of select="oc:id"/></xsl:attribute>
                    </crm:P70I.is_documented_in>
				</xsl:for-each>
                
                <xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:diary_links/oc:link">
					<crm:P70I.is_documented_in>
                        <xsl:attribute name="rdf:resource">http://opencontext.org/documents/<xsl:value-of select="oc:id"/></xsl:attribute>
                    </crm:P70I.is_documented_in>
				</xsl:for-each>

            </crm:E22.Man-Made_Object>
        </rdf:RDF>
    </xsl:template>


<xsl:template name="obsShow">
  
		<xsl:param name="totalObs" select="1"/>
		<xsl:param name="obsPos" select="1"/>
		<xsl:param name="notCurrent" select="0"/>
		<xsl:param name="citationView" select="0"/>
		<xsl:param name="num_externalRefs" select="0"/>
		<xsl:param name="max_Tabs" select="10"/>
		<xsl:param name="propBaseURI">../properties/</xsl:param>
		
		<div xmlns="http://www.w3.org/1999/xhtml">
				<xsl:choose>
				<xsl:when test="$totalObs = 1">
						<xsl:attribute name="style">margin-top:10px;</xsl:attribute>
				</xsl:when>
				<xsl:when test="(($notCurrent = 1) and ($totalObs &lt; $max_Tabs)) or (($notCurrent = 1) and ($max_Tabs = $obsPos))">
						<!-- <xsl:attribute name="class">hideObs rounded-corners</xsl:attribute> -->
						<xsl:attribute name="class">tabbertab</xsl:attribute>
						<xsl:attribute name="title">(Prelim. Version)</xsl:attribute>
				</xsl:when>
				<xsl:when test="$obsPos = 1">
						<!-- <xsl:attribute name="class">hideObs rounded-corners</xsl:attribute> -->
						<xsl:attribute name="class">tabbertab</xsl:attribute>
						<xsl:attribute name="title">Main Obs.</xsl:attribute>
				</xsl:when>
				<xsl:when test="($obsPos &gt;= $max_Tabs) and ($totalObs &gt; $max_Tabs)">
						<xsl:attribute name="class">obs rounded-corners</xsl:attribute>
						<xsl:if test="$obsPos mod 2 = 1">
								<xsl:attribute name="style">background-color:#F8F8F8;</xsl:attribute>
						</xsl:if>
				</xsl:when>
				
				<xsl:otherwise>
						<!-- <xsl:attribute name="class">obs rounded-corners</xsl:attribute> -->
						<xsl:attribute name="class">tabbertab</xsl:attribute>
						<xsl:attribute name="title">Obs. <xsl:value-of select="position()"/></xsl:attribute>
				</xsl:otherwise>
				</xsl:choose>
						
				<xsl:choose>
				<xsl:when test="$totalObs = 1">
				</xsl:when>
				<xsl:when test="$notCurrent = 1">
						<br/>
						<span class="bodyText" style="margin-left:10px;"><strong>Observation:</strong> <em>Preliminary / Draft Version</em></span>
				</xsl:when>
				<xsl:otherwise>
						<br/>
						<span class="bodyText" style="margin-left:10px;"><strong>Observation:</strong> <em><xsl:value-of select="oc:obs_metadata/oc:name"/></em></span>
				</xsl:otherwise>
				</xsl:choose>
				<div class="obsProps rounded-corners">
					<p class="subHeader"><xsl:if test="$notCurrent = 1"><xsl:attribute name="style">color: #7D5757;</xsl:attribute></xsl:if>	
									<xsl:value-of select="oc:var_label"/><xsl:if test="$notCurrent = 1">No Longer Current </xsl:if>Description (<xsl:value-of select="count(descendant::arch:properties/arch:property)"/> properties)</p>
					
					<table border="0" cellpadding="1">
						 <xsl:for-each select="arch:properties/arch:property">
							  <tr>
								<td style='width:95px;'>
									<xsl:if test="$notCurrent = 1">
										<xsl:attribute name="style">color: #716F6F;</xsl:attribute>
										</xsl:if>	
									<xsl:value-of select="oc:var_label"/>            </td>
								<td> </td>
								<td>
									<xsl:if test="$notCurrent = 1">
										<xsl:attribute name="style">color: #716F6F;</xsl:attribute>
										</xsl:if>
									<xsl:if test="oc:var_label/@type != 'alphanumeric'">
									<a>
										<xsl:attribute name="href"><xsl:value-of select="$propBaseURI"/><xsl:value-of select="oc:propid"/></xsl:attribute>
										<xsl:if test="$notCurrent = 1">
										<xsl:attribute name="style">color: #716F6F;</xsl:attribute>
										</xsl:if>
										<xsl:choose>
										<xsl:when test="contains(oc:show_val, 'http://')">
										(Outside Link)
										</xsl:when>
										<xsl:otherwise>
											<xsl:if test="oc:var_label/@type='calendar'">
													<xsl:value-of select="oc:show_val"/>
											</xsl:if>
											<xsl:if test="oc:var_label/@type != 'calendar'">
												<xsl:value-of select="oc:show_val"/>
											</xsl:if>
										</xsl:otherwise>
										</xsl:choose>
									</a>
									
										
									
									</xsl:if>
									<xsl:if test="oc:var_label/@type = 'alphanumeric'">
										<xsl:choose>
										<xsl:when test="arch:variableID = 'GHF1VAR0000000948'">
										Obsolete Outside Link (see XML version for archived link)
										</xsl:when>
										<xsl:otherwise>
											<xsl:value-of select="oc:show_val"/>
										</xsl:otherwise>
										</xsl:choose>
									</xsl:if>
								</td>
								
							  </tr>
						  </xsl:for-each>
						  <xsl:if test="count(descendant::arch:properties/arch:property) = 0">
							<tr><td><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:no_props"/></td></tr>
						  </xsl:if>
					</table>
				</div>
			
				<div class="allnotes bodyText">
					<p class="subHeader">Item Notes</p>
					<xsl:if test="count(descendant::arch:links/oc:diary_links/oc:link) != 0" >
						<div style="padding-bottom:4px; width:90%; ">
								
							<xsl:for-each select="./arch:links/oc:diary_links/oc:link">
								<xsl:choose>
										<xsl:when test="position() mod 2 = 1">
											<div class="container_a">
												<div class="container">
												<a><xsl:attribute name="href">../documents/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a> (Associated Document / Log)
												</div>
											</div>
										</xsl:when>
										<xsl:otherwise>
											<div class="clear_container">
											<div class="container">
												<a><xsl:attribute name="href">../documents/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a> (Associated Document / Log)
											</div>
											</div>
										</xsl:otherwise>
								</xsl:choose>
							</xsl:for-each>
							<p class="bodyText"></p><br/>
						</div>
						<p class="bodyText"></p><br/>
					</xsl:if>
					<xsl:if test="$num_externalRefs != 0" >	
						<p class="bodyText"> 
							<xsl:for-each select="../oc:external_references/oc:reference">
							   <a><xsl:attribute name="href"><xsl:value-of select="oc:ref_URI"/></xsl:attribute><em><xsl:value-of select="oc:name" disable-output-escaping="yes"/></em></a>
							   <xsl:if test="position() != last()"> , </xsl:if>
							</xsl:for-each>
						</p>
				       </xsl:if>
					
					
					<xsl:if test="count(descendant::arch:notes/arch:note) = 0" >
					<p class="bodyText">(This item has no additional notes)</p>
					</xsl:if>
					<xsl:for-each select="arch:notes/arch:note">
						<div class="bodyText"><xsl:value-of select="arch:string" disable-output-escaping="yes" /></div><br/>
					</xsl:for-each>
					<xsl:if test="$obsPos = 1">
					<p class="bodyText"><span style='text-decoration:underline;'>Suggested Citation:</span><br/><xsl:value-of select="$citationView"/></p>
				</xsl:if>
				</div>
			
				<xsl:if test="count(descendant::arch:links/oc:space_links/oc:link) != 0" >
					<div id="all_links">
						<p class="subHeader">Linked Items (<xsl:value-of select="count(descendant::arch:links/oc:space_links/oc:link)"/> items)</p>
							<xsl:for-each select="arch:links/oc:space_links/oc:link">
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
				
				
				<div>
				<xsl:choose>
				<xsl:when test="$totalObs= 1">
						<xsl:attribute name="id">all_people</xsl:attribute>
				</xsl:when>
				<xsl:otherwise>
						<xsl:attribute name="class">obsPeople bodyText</xsl:attribute>
				</xsl:otherwise>
				</xsl:choose>
						
		<p class="subHeader">Associated People (<xsl:value-of select="count(descendant::arch:links/oc:person_links/oc:link)"/> items)</p>
		<xsl:if test="count(descendant::arch:links/oc:person_links/oc:link) != 0" >	
			<p class="bodyText">
				<xsl:for-each select="arch:links/oc:person_links/oc:link">
				   <a><xsl:attribute name="href">../persons/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a> ( <xsl:value-of select="oc:relation"/> )<xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
		</xsl:if>
		<br/>
		<br/>	
	</div>
	
</div>      
</xsl:template>










</xsl:stylesheet>
