<?xml version="1.0" encoding="utf-8"?>
<!-- DWXMLSource="../../../Documents and Settings/Eric Kansa/Desktop/atomSample.xml" -->
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
   <!ENTITY lsquo "&#171;">
   <!ENTITY rsquo "&#187;">
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:gml="http://www.opengis.net/gml" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:oc="http://opencontext.org/schema/project_schema_v1.xsd" xmlns:arch="http://ochre.lib.uchicago.edu/schema/Project/Project.xsd" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/elements/1.1/">
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


<xsl:variable name="lastDate"><xsl:value-of select="substring(atom:feed/atom:updated,1,10)"/></xsl:variable>

<xsl:variable name="fixedCOINS">ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc&amp;rft.type=dataset&amp;rft.title=Open%20Context%20Projects%20and%20Collections&amp;rft.date=<xsl:value-of select="$lastDate"/>&amp;rft.creator=Sarah%20Whitcher%20Kansa&amp;rft.subject=archaeology&amp;rft.subject=dataset&amp;rft.subject=excavation&amp;rft.subject=survey&amp;rft.subject=data&amp;rft.subject=field%20research&amp;rft.subject=collections&amp;rft.format=XML&amp;rft.format=Text%2FHTML&amp;rft.format=.jpg&amp;rft.format=.gif&amp;rft.coverage=Near%20East&amp;rft.language=eng&amp;rft.publisher=Open%20Context&amp;rft.rights=&amp;rft.source=Open%20Context&amp;rft.rights=Creative%20Commons%20Attribution%203&amp;rft_id=http%3A%2F%2Fwww.opencontext.org%2Fprojects%2F
</xsl:variable>


<xsl:variable name="citation">&quot;Open Context Projects and Collections&quot; (Updated <xsl:value-of select="$lastDate"/>). Edited by Sarah Whicher Kansa</xsl:variable>





<xsl:comment>
BEGIN Container for main page content
</xsl:comment>
<div id="main">
		
		
		<div id="into_contain">
				<div id="intro_tab">
						<div id="intro_row">
								<div id="intro_cell">
                            <h3>Projects and Collections</h3>
                            <p>Each project represents a specific study or research effort conducted by a single investigator or a
                            team of collaborating investigators. The data and documentation presented in a project can be freely used and
                            referenced by the research community and public for re-analysis, comparison with other collections, visualization
                            , or other applications provided data contributors are properly credited with citation.</p>
                            <br/>
                            <p>Open Context currently publishes <strong><xsl:value-of select="count(//atom:entry)"/></strong> projects.</p>
								</div>
								<div id="carosel_cell">
                            <div id="myCarousel" class="carousel slide">
                            <!-- Carousel items -->
                                <div class="carousel-inner">
                                    <xsl:for-each select="atom:feed/atom:entry">
                                        <div>
                                        <xsl:choose>
                                            <xsl:when test="position()=1">
                                                <xsl:attribute name="class">item active</xsl:attribute>
                                            </xsl:when>
                                            <xsl:otherwise>
                                                <xsl:attribute name="class">item</xsl:attribute>
                                            </xsl:otherwise>
                                        </xsl:choose>
                                            <div class="hero_image">
                                                <img>
                                                    <xsl:attribute name="src"><xsl:value-of select="./atom:link[@rel='enclosure']/@href" /></xsl:attribute>
                                                    <xsl:attribute name="alt">Representative image of the '<xsl:value-of select="arch:project/arch:name/arch:string" />' project</xsl:attribute>
                                                </img>
                                            </div>
                                            <div class="carousel-caption">
                                                <h4><a><xsl:attribute name="href">../projects/<xsl:value-of select="arch:project/@UUID"/></xsl:attribute><xsl:value-of select="arch:project/arch:name/arch:string" /></a></h4>
                                                <p><xsl:value-of select="arch:project/arch:notes/arch:note[@type='short_des']/arch:string" disable-output-escaping="yes" /></p>
                                            </div>
                                        </div>
                                    </xsl:for-each>
                                </div>
                                <!-- Carousel nav -->
                                <a class="carousel-control left" href="#myCarousel" data-slide="prev">&#171;</a>
                                <a class="carousel-control right" href="#myCarousel" data-slide="next">&#187;</a>
                            </div>
								</div>
						</div>
				</div>
		</div>
		
		<xsl:comment>
		BEGIN Container for gDIV of general item information

		</xsl:comment>
		
		<div id="proj_tab_outer">
				<div id="proj_tab_container">
				<table class="table table-striped" id="tabLinkRows">
					<thead>
                    <tr>
                        <th>Project</th>
								<th>Editorial Status</th>
                        <th>Description</th>
                        <th>Primary People</th>
                        <th>Keywords</th>
                    </tr>
               </thead>
               <tbody>
						<xsl:for-each select="atom:feed/atom:entry">
							<tr>
                        
								<xsl:choose>
								<xsl:when test="position() mod 2 = 1">
                            <!--
                            <xsl:attribute name="style">background-color: #F4F4F4;</xsl:attribute>
                            -->
								</xsl:when>
								<xsl:otherwise>
								 
								</xsl:otherwise>
								</xsl:choose>
								<td>
									<a><xsl:attribute name="href">../projects/<xsl:value-of select="arch:project/@UUID"/></xsl:attribute><xsl:value-of select="arch:project/arch:name/arch:string" /></a>
								</td>
								<td>
									<span class="project-edit-stars">
										<xsl:attribute name="title"><xsl:value-of select="arch:project/oc:metadata/oc:project_name/@statusDes"/> (Click for more)</xsl:attribute>
										<!-- <a href="../about/publishing#editorial-status"> -->
												<xsl:choose>
														<xsl:when test="arch:project/oc:metadata/oc:project_name/@editStatus = 1">
																&#9679;&#9675;&#9675;&#9675;&#9675;
														</xsl:when>
														<xsl:when test="arch:project/oc:metadata/oc:project_name/@editStatus = 2">
																&#9679;&#9679;&#9675;&#9675;&#9675;
														</xsl:when>
														<xsl:when test="arch:project/oc:metadata/oc:project_name/@editStatus = 3">
																&#9679;&#9679;&#9679;&#9675;&#9675;
														</xsl:when>
														<xsl:when test="arch:project/oc:metadata/oc:project_name/@editStatus = 4">
																&#9679;&#9679;&#9679;&#9679;&#9675;
														</xsl:when>
														<xsl:when test="arch:project/oc:metadata/oc:project_name/@editStatus = 5">
																&#9679;&#9679;&#9679;&#9679;&#9679;
														</xsl:when>
														<xsl:otherwise>
																(Forthcoming)
														</xsl:otherwise>
												</xsl:choose>
										<!--  </a> -->
								</span>
									
									
								</td>
								<td>
										<xsl:value-of select="arch:project/arch:notes/arch:note[@type='short_des']/arch:string" disable-output-escaping="yes" /></td>
								<td>
								<xsl:for-each select="atom:author">
									<xsl:value-of select="." /><xsl:if test="position() != last()">, 
									</xsl:if>
								</xsl:for-each>
								</td>
								<td class="tinyText"><em>
								<xsl:for-each select="arch:project/oc:metadata/dc:subject">
									<xsl:value-of select="." /><xsl:if test="position() != last()">, 
									</xsl:if>
								</xsl:for-each>
								
								</em></td>
							</tr>
						</xsl:for-each>
					</tbody>
				</table>
				</div>
		</div>
</div>


</xsl:template>
</xsl:stylesheet>
