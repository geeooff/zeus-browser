<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="html" omit-xml-declaration="yes" indent="no"/>

	<xsl:template match="Mediainfo">
		<div class="mi-files"><xsl:apply-templates select="File"/></div>
	</xsl:template>

	<xsl:template match="File">
		<div class="mi-file">
			<div class="mi-tracks">
				<xsl:apply-templates select="track"/>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="track">
		<div class="card mi-track">
			<xsl:choose>
				<xsl:when test="@streamid">
					<h3 class="card-header"><xsl:value-of select="@type"/><xsl:text> </xsl:text><xsl:value-of select="@streamid"/></h3>
				</xsl:when>
				<xsl:otherwise>
					<h3 class="card-header"><xsl:value-of select="@type"/></h3>
				</xsl:otherwise>
			</xsl:choose>
			<div class="card-block">
				<dl>
					<xsl:for-each select="node()[not(self::text()[not(normalize-space())])]">
						<dt><xsl:value-of select="name()"/></dt>
						<dd><xsl:value-of select="."/></dd>
					</xsl:for-each>
				</dl>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
