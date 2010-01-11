<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" indent="yes" omit-xml-declaration="no" cdata-section-elements="description title" />
  
  <xsl:template match="/response">
  <rss version="2.0">
    <channel>
      <title>Packet Inbox</title>
      <link>/home</link>
      <description>Packet subscription timeline</description>
      <language>en</language>
      <lastBuildDate><xsl:value-of select="//fp/created_at" /></lastBuildDate>
      <generator>Nuclear API</generator>
      <xsl:apply-templates select="fp" />
    </channel>
  </rss>
  </xsl:template>

  <xsl:template match="fp">
    <item>
      <title><xsl:value-of select="text/text()" /></title>
      <link>http://<xsl:value-of select="user/domain" />/<xsl:value-of select="user/name" />/<xsl:value-of select="id" /></link>
      <description><xsl:value-of select="text" /></description>
      <pubDate><xsl:value-of select="created_at" /></pubDate>
      <link>http://<xsl:value-of select="user/domain" />/<xsl:value-of select="user/name" />/<xsl:value-of select="id" /></link>
    </item>
  </xsl:template>
</xsl:stylesheet>
