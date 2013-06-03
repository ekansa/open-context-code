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
    
    
        <xsl:variable name="Material">
            <xsl:choose>
                <xsl:when test="//arch:properties/arch:property[oc:var_label = 'Material']">
                    <xsl:value-of select="//arch:properties/arch:property[oc:var_label = 'Material']/oc:show_val"/>
				</xsl:when>
                <xsl:otherwise>
                    <xsl:choose>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Groundstone'">Stone</xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Non Diag. Bone'">Bone</xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Animal Bone'">Bone</xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Human Bone'">Bone</xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Pottery'">Cermamic</xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Glass'">Glass</xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Shell'">Shell</xsl:when>
                        <xsl:when test="//arch:spatialUnit/oc:item_class/oc:name = 'Coin'">Metal</xsl:when>
                        <xsl:otherwise>Not specified</xsl:otherwise>
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
    
		  <xsl:variable name="contextClass">
            <xsl:for-each select="arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent">
                <xsl:if test="position() = last()"><xsl:value-of select="oc:item_class/oc:name"/></xsl:if>
            </xsl:for-each>
        </xsl:variable>
    
        <xsl:variable name="max_Tabs">10</xsl:variable>

        
        <rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
            <crm:E20.Biological_Object>
                <xsl:attribute name="rdf:about">http://opencontext.org/subjects/<xsl:value-of select="$item_id"/></xsl:attribute> 
                <rdfs:label><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/></rdfs:label>
    
                <crm:P102.has_title>
                    <crm:E35.Title rdf:about="#title">
                      <rdf:value><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/></rdf:value>
                    </crm:E35.Title>
                </crm:P102.has_title>
					 
					 <rdf:type>
								<xsl:attribute name="rdf:resource">http://opencontext.org/about/concepts#subjects</xsl:attribute>
					 </rdf:type>
                
                <crm:P2.has_type>
                    <crm:E55.Type>
                            <xsl:attribute name="rdf:about">http://purl.obolibrary.org/obo/UBERON_0004765</xsl:attribute>
                            <rdfs:label>skeletal element</rdfs:label>
								</crm:E55.Type>
                </crm:P2.has_type>
    
                <crm:P48.has_preferred_identifier>
                    <crm:E42.Identifier rdf:about="#pref-id">
                        <rdf:value><xsl:value-of select="arch:spatialUnit/arch:name/arch:string"/></rdf:value>
                    </crm:E42.Identifier>
                </crm:P48.has_preferred_identifier>
    
                <crm:P92B.was_brought_into_existence_by>
                    <crm:E63.Beginning_of_Existence rdf:about="#begin-exist">
                        <rdfs:label>Beginning of '<xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/>'</rdfs:label>
                            
                            <crm:P126.employed>
                                <crm:E57.Material rdf:about="#material">
                                  <rdfs:label><xsl:value-of select="$Material"/></rdfs:label>
                                </crm:E57.Material>
                            </crm:P126.employed>
                            
                            <crm:P4.has_time-span>
                            <crm:E52.Time-Span rdf:about="#time-span">
                              <crm:P82.at_some_time_within>
                                <claros:Period rdf:about="#period">
                                  <claros:period_begin rdf:datatype="http://www.w3.org/2001/XMLSchema#gYear"><xsl:value-of select="//oc:social_usage/oc:user_tags/oc:tag/oc:chrono/oc:time_start"/></claros:period_begin>
                                  <claros:period_end rdf:datatype="http://www.w3.org/2001/XMLSchema#gYear"><xsl:value-of select="//oc:social_usage/oc:user_tags/oc:tag/oc:chrono/oc:time_finish"/></claros:period_end>
                                </claros:Period>
                              </crm:P82.at_some_time_within>
                            </crm:E52.Time-Span>
                          </crm:P4.has_time-span>
                    </crm:E63.Beginning_of_Existence>
                </crm:P92B.was_brought_into_existence_by>
    
    
                <xsl:for-each select="//arch:properties/arch:property[arch:decimal/@href | arch:integeter/@href]">
                   
                    <crm:P43F.has_dimension>
                        <crm:E54.Dimension>
								<xsl:attribute name="rdf:about"><xsl:value-of select="oc:propid/@href" /></xsl:attribute>
								
                            <crm:P91.has_unit>
                                <xsl:attribute name="rdf:resource">
                                    <xsl:value-of select="arch:decimal/@href | arch:integeter/@href"/>
                                </xsl:attribute>
                            </crm:P91.has_unit>
                            <crm:P90.has_value>
                                <xsl:value-of select="arch:decimal | arch:integeter"/>
                            </crm:P90.has_value>
                            <rdfs:label><xsl:value-of select="oc:var_label"/>: <xsl:value-of select="oc:show_val"/> (<xsl:value-of select="arch:decimal/@abrv | arch:integeter/@abrv"/>)</rdfs:label>
										  <xsl:for-each select="oc:annotations[@aboutType='variable']/oc:annotation">
													 <xsl:if test="oc:relationLink/@type='Measurement type'">
																<rdf:type>
																		  <xsl:attribute name="rdf:resource">
																					 <xsl:value-of select="oc:targetLink/@href"/>
																		  </xsl:attribute>
																</rdf:type>
													 </xsl:if>
										  </xsl:for-each>
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
                
					 <xsl:for-each select="//oc:linkedData/oc:relationLink[@href = 'http://purl.org/NET/biol/ns#term_hasTaxonomy']">
					 <!--Biological Taxonomy Present  -->
                    <crm:P2.has_type>
                        <crm:E55.Type>
                            <xsl:attribute name="rdf:about">
                                <xsl:value-of select="oc:targetLink/@href"/>
                            </xsl:attribute>
                            <rdfs:label><xsl:value-of select="oc:targetLink/oc:label"/></rdfs:label>
                        </crm:E55.Type>
                    </crm:P2.has_type>
                </xsl:for-each>
					 
					 <xsl:for-each select="//oc:linkedData/oc:relationLink[@href = 'http://opencontext.org/vocabularies/open-context-zooarch/zoo-0079']">
					 <!--Anatomical Entity Present  -->
                    <crm:P2.has_type>
                        <crm:E55.Type>
                            <xsl:attribute name="rdf:about">
                                <xsl:value-of select="oc:targetLink/@href"/>
                            </xsl:attribute>
                            <rdfs:label><xsl:value-of select="oc:targetLink/oc:label"/></rdfs:label>
                        </crm:E55.Type>
                    </crm:P2.has_type>
                </xsl:for-each>
					 
					 <xsl:for-each select="//oc:linkedData/oc:relationLink[@href = 'http://opencontext.org/vocabularies/open-context-zooarch/zoo-0077']">
					 <!--Fusion characterization Present  -->
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
					 
					 <xsl:for-each select="//oc:linkedData/oc:relationLink[@href = 'http://opencontext.org/vocabularies/open-context-zooarch/zoo-0078']">
					 <!--Physiological Sex characterization Present  -->
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

            </crm:E20.Biological_Object>
        </rdf:RDF>
    </xsl:template>

</xsl:stylesheet>
