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
					 xmlns:lawd="http://lawd.info/ontology/"
					 xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
					 xmlns:crmeh="http://purl.org/crmeh#"
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
    
		  <xsl:variable name="contextClass">
            <xsl:for-each select="arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent">
                <xsl:if test="position() = last()"><xsl:value-of select="oc:item_class/oc:name"/></xsl:if>
            </xsl:for-each>
        </xsl:variable>
	 
    
        <xsl:variable name="max_Tabs">10</xsl:variable>

        
        <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
            <crmeh:EHE0002_ArchaeologicalSite>
                <xsl:attribute name="rdf:about">http://opencontext.org/subjects/<xsl:value-of select="$item_id"/></xsl:attribute> 
                <rdfs:label><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/></rdfs:label>
    
                <crm:P102.has_title>
                    <crm:E35.Title rdf:about="#title">
                      <rdf:value><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/></rdf:value>
                    </crm:E35.Title>
                </crm:P102.has_title>
                
                <rdfs:instanceOf>
								<xsl:attribute name="rdf:resource">http://opencontext.org/about/concepts#subjects</xsl:attribute>
					 </rdfs:instanceOf> 
                        
                <crm:P48.has_preferred_identifier>
                    <crm:E42.Identifier rdf:about="#pref-id">
                        <rdf:value><xsl:value-of select="arch:spatialUnit/arch:name/arch:string"/></rdf:value>
                    </crm:E42.Identifier>
                </crm:P48.has_preferred_identifier>
    
                   
                <xsl:for-each select="//oc:linkedData/oc:relationLink[@href = 'http://www.cidoc-crm.org/rdfs/cidoc-crm#P2.has_type']">
					 <!--Has type (link to a controlled vocabulary)  -->
								<xsl:if test="oc:targetLink/@href != ''">
                    <crm:P2.has_type>
                        <crm:E55.Type>
                            <xsl:attribute name="rdf:about">
                                <xsl:value-of select="oc:targetLink/@href"/>
                            </xsl:attribute>
                            <rdfs:label><xsl:value-of select="oc:targetLink/oc:label"/></rdfs:label>
                        </crm:E55.Type>
                    </crm:P2.has_type>
								</xsl:if>
                </xsl:for-each>
                
					 <!--
                <xsl:choose>
								<xsl:when test="($contextClass = 'Trench') or ($contextClass = 'Square') or ($contextClass = 'Area')  or ($contextClass = 'Operation') or ($contextClass = 'Operation') or ($contextClass = 'Field Project')">
										  <crm:P53.has_former_or_current_location>
												<crmeh:EHE0004_SiteSubDivision>
													 <xsl:attribute name="rdf:about">
														  <xsl:value-of select="$contextURI"/>
													 </xsl:attribute>
													 <rdfs:label><xsl:value-of select="$contextPath"/></rdfs:label>
												</crmeh:EHE0004_SiteSubDivision>
										  </crm:P53.has_former_or_current_location>
										  
										  <lawd:foundAt>
													 <crmeh:EHE0007_Context>
													 <xsl:attribute name="rdf:about">
														  <xsl:value-of select="$contextURI"/>
													 </xsl:attribute>
													 </crmeh:EHE0007_Context>
										  </lawd:foundAt>
								</xsl:when>
								<xsl:when test="$contextClass = 'Site' ">
										  <crm:P53.has_former_or_current_location>
												<crmeh:EHE0002_ArchaeologicalSite>
													 <xsl:attribute name="rdf:about">
														  <xsl:value-of select="$contextURI"/>
													 </xsl:attribute>
													 <rdfs:label><xsl:value-of select="$contextPath"/></rdfs:label>
												</crmeh:EHE0002_ArchaeologicalSite>
										  </crm:P53.has_former_or_current_location>
										  
										  <lawd:foundAt>
													 <crmeh:EHE0002_ArchaeologicalSite>
													 <xsl:attribute name="rdf:about">
														  <xsl:value-of select="$contextURI"/>
													 </xsl:attribute>
													 </crmeh:EHE0002_ArchaeologicalSite>
										  </lawd:foundAt>
								</xsl:when>
								<xsl:otherwise>
										  <crmeh:EHP3_occupied>
												<crmeh:EHE0007_Context>
													 <xsl:attribute name="rdf:about">
														  <xsl:value-of select="$contextURI"/>
													 </xsl:attribute>
													 <rdfs:label><xsl:value-of select="$contextPath"/></rdfs:label>
												</crmeh:EHE0007_Context>
										  </crmeh:EHP3_occupied>
										  
										  <lawd:foundAt>
													 <crmeh:EHE0007_Context>
													 <xsl:attribute name="rdf:about">
														  <xsl:value-of select="$contextURI"/>
													 </xsl:attribute>
													 </crmeh:EHE0007_Context>
										  </lawd:foundAt>
								</xsl:otherwise>
					 </xsl:choose>
                -->
                
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

				
				<geo:lat>
					 <xsl:value-of select="//oc:geo_reference/oc:geo_lat"/>
				</geo:lat>
				<geo:lon>
					 <xsl:value-of select="//oc:geo_reference/oc:geo_long"/>
				</geo:lon>
				
            </crmeh:EHE0002_ArchaeologicalSite>
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
