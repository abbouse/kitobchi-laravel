import { defineEventHandler, setResponseHeader } from 'h3'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const apiBase = (config.public?.apiBase as string) || 'https://kitobchi.com/api'
  const backendBase = apiBase.replace(/\/api\/?$/, '')
  const siteUrl = (config.public?.siteUrl as string) || 'https://kitobchi.com'

  setResponseHeader(event, 'content-type', 'text/xml; charset=utf-8')
  setResponseHeader(event, 'cache-control', 'public, max-age=3600, s-maxage=86400')

  try {
    const xml = await $fetch<string>(`${backendBase}/sitemap.xml`, {
      headers: {
        'Accept': 'text/xml, application/xml'
      },
      timeout: 10000
    })
    return xml
  } catch (err) {
    try {
      const xml = await $fetch<string>(`${apiBase}/sitemap.xml`, {
        headers: {
          'Accept': 'text/xml, application/xml'
        },
        timeout: 10000
      })
      return xml
    } catch (e) {
      const today = new Date().toISOString().split('T')[0]
      return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>${siteUrl}</loc>
        <lastmod>${today}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>${siteUrl}/catalog</loc>
        <lastmod>${today}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.95</priority>
    </url>
    <url>
        <loc>${siteUrl}/catalog?type=book</loc>
        <lastmod>${today}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.90</priority>
    </url>
    <url>
        <loc>${siteUrl}/catalog?type=stationery</loc>
        <lastmod>${today}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.90</priority>
    </url>
    <url>
        <loc>${siteUrl}/about</loc>
        <lastmod>${today}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc>${siteUrl}/contacts</loc>
        <lastmod>${today}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
</urlset>`
    }
  }
})
