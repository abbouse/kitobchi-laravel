import { defineEventHandler, getHeader, readRawBody, setResponseStatus, createError } from 'h3'

/**
 * /broadcasting/auth — eski ilova versiyalari kanal avtorizatsiyasini shu manzilga yuboradi.
 * Saytda bu yo'l Nuxt'ga tushadi, shuning uchun so'rovni Laravel'dagi /api/broadcasting/auth ga uzatamiz.
 */
export default defineEventHandler(async (event) => {
  const method = event.method?.toUpperCase() || 'GET'
  if (method !== 'POST' && method !== 'GET') {
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }

  const config = useRuntimeConfig()
  const apiBase = ((config.public?.apiBase as string) || 'https://kitobchi.com/api').replace(/\/$/, '')

  const headers: Record<string, string> = { Accept: 'application/json' }
  for (const name of ['authorization', 'content-type', 'x-socket-id', 'accept-language', 'user-agent']) {
    const value = getHeader(event, name)
    if (value) headers[name] = value
  }
  const forwardedFor = getHeader(event, 'x-forwarded-for') || event.node.req.socket?.remoteAddress
  if (forwardedFor) headers['x-forwarded-for'] = String(forwardedFor)

  const body = method === 'POST' ? await readRawBody(event, 'utf8') : undefined

  const response = await $fetch.raw(`${apiBase}/broadcasting/auth`, {
    method,
    headers,
    body: body || undefined,
    ignoreResponseError: true,
    timeout: 10000,
  })

  setResponseStatus(event, response.status)
  return response._data ?? {}
})
