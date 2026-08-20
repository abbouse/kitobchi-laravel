import { defineEventHandler, setResponseHeader } from 'h3'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const apiBase = (config.public?.apiBase as string) || 'https://kitobchi.com/api'
  const backendBase = apiBase.replace(/\/api\/?$/, '')
  const siteUrl = (config.public?.siteUrl as string) || 'https://kitobchi.com'

  setResponseHeader(event, 'content-type', 'text/xml; charset=utf-8')
  setResponseHeader(event, 'cache-control', 'public, max-age=3600, s-maxage=86400')

  try {
    const xml = await $fetch<string>(`${backendBase}/google-merchant.xml`, {
      headers: {
        'Accept': 'text/xml, application/xml'
      },
      timeout: 10000
    })
    return xml
  } catch (err) {
    try {
      const xml = await $fetch<string>(`${apiBase}/google-merchant.xml`, {
        headers: {
          'Accept': 'text/xml, application/xml'
        },
        timeout: 10000
      })
      return xml
    } catch (e) {
      return `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title>Kitobchi — Google Merchant Product Feed</title>
    <link>${siteUrl}</link>
    <description>Kitobchi kitob va kanselyariya marketpleysi mahsulotlar lentalari</description>
  </channel>
</rss>`
    }
  }
})
