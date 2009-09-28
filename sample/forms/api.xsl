<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
        <xsl:output method="html" indent="yes" />

        <xsl:template match="/">
        <html xmlns="http://www.w3.org/1999/xhtml" version="-//W3C//DTD XHTML 1.1//EN" xml:lang="en">
        <head>
		<link rel="stylesheet" type="text/css" href="api.css" />
		<title>Nuclear API</title>
        </head>
          <body>
		<xsl:apply-templates select="apicall" />
          </body>
        </html>
        </xsl:template>

        <xsl:template match="apicall">
		<xsl:element name="form">
			<xsl:attribute name="id">b-api</xsl:attribute>
			<xsl:attribute name="method">post</xsl:attribute>
			<xsl:attribute name="action">/api.php</xsl:attribute>
			<xsl:attribute name="target">api_output</xsl:attribute>
			<xsl:attribute name="enctype">multipart/form-data</xsl:attribute>


			<h1><xsl:value-of select="@op" /></h1>
			<input type="hidden" id="format" name="format" value="rest" />
			<xsl:if test="@output = 'xml'">
			<input type="hidden" id="output" name="output" value="xml" />
			</xsl:if>


			<xsl:element name="input">
				<xsl:attribute name="id">op</xsl:attribute>
				<xsl:attribute name="name">op</xsl:attribute>
				<xsl:attribute name="type">hidden</xsl:attribute>
				<xsl:attribute name="value"><xsl:value-of select="@op" /></xsl:attribute>
			</xsl:element>

			<xsl:apply-templates select="field" />

			<hr />
			<input id="bttn" type="submit" value="Post" />
		</xsl:element>

		<iframe id="api_output" name="api_output" src="about:blank"></iframe>
		
		<h3>Other Methods</h3>
		<xsl:call-template name="method">
			<xsl:with-param name="str">account/login|account/logout|account/register|account/registerverify|account/username|password/reset|password/verify|social/request|social/accept</xsl:with-param>
		</xsl:call-template>

        </xsl:template>
	
	<xsl:template name="method">
		<xsl:param name="str" />
		<xsl:choose>
		<xsl:when test="contains($str,'|')">
		<xsl:element name="a"><xsl:attribute name="href">../<xsl:value-of select="substring-before($str,'|')" />.xml</xsl:attribute><xsl:value-of select="substring-before($str,'|')" /></xsl:element><br />
<xsl:call-template name="method"><xsl:with-param name="str" select="substring-after($str,'|')" /></xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
		<xsl:element name="a"><xsl:attribute name="href">../<xsl:value-of select="$str" />.xml</xsl:attribute><xsl:value-of select="$str" /></xsl:element>
		</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

        <xsl:template match="field">
		<xsl:if test="@display != ''">
		<label><xsl:value-of select="@display" /></label><br />
		</xsl:if>
		<xsl:choose>
			<xsl:when test="@type = 'input'">
				<xsl:element name="input">
					<xsl:attribute name="id"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="type">text</xsl:attribute>
					<xsl:attribute name="autocomplete">off</xsl:attribute>
				</xsl:element><br />
			</xsl:when>
			<xsl:when test="@type = 'hidden'">
				<xsl:element name="input">
					<xsl:attribute name="id"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="type">hidden</xsl:attribute>
					<xsl:attribute name="value"><xsl:value-of select="@value" /></xsl:attribute>
				</xsl:element>
			</xsl:when>
			<xsl:when test="@type = 'password'">
				<xsl:element name="input">
					<xsl:attribute name="id"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="type">password</xsl:attribute>
				</xsl:element><br />
			</xsl:when>
			<xsl:when test="@type = 'file'">
				<xsl:element name="input">
					<xsl:attribute name="id"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="type">file</xsl:attribute>
				</xsl:element>
			</xsl:when>
			<xsl:when test="@type = 'select'">
				<xsl:element name="select">
					<xsl:attribute name="id"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:for-each select="option">
					<xsl:copy-of select="." />
					</xsl:for-each>
				</xsl:element>
			</xsl:when>
			<xsl:when test="@type = 'text'">
				<xsl:element name="textarea">
					<xsl:attribute name="id"><xsl:value-of select="@name" /></xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name" /></xsl:attribute>
				</xsl:element>
			</xsl:when>
		</xsl:choose>
        </xsl:template>

</xsl:stylesheet>
