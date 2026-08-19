import { defineEventHandler, getRouterParam, getHeader, sendRedirect, setResponseHeader } from 'h3'

export default defineEventHandler((event) => {
  const type = getRouterParam(event, 'type') || 'book'
  const id = getRouterParam(event, 'id') || '1'
  const userAgent = (getHeader(event, 'user-agent') || '').toLowerCase()
  const isAndroid = userAgent.includes('android')
  const isIos = userAgent.includes('iphone') || userAgent.includes('ipad')

  const webPath = type === 'stationery' || type === 'k'
    ? `/stationery/${id}`
    : (type === 'seller' || type === 's' ? `/catalog?seller=${id}` : `/books/${id}`)

  const appScheme = `kitobchi://share/product/${id}`
  const playStore = 'https://play.google.com/store/apps/details?id=com.kitobchi.app'
  const appStore = 'https://apps.apple.com/app/kitobchi/id6470000000'

  if (isAndroid || isIos) {
    const fallbackUrl = isAndroid ? playStore : appStore
    setResponseHeader(event, 'content-type', 'text/html; charset=utf-8')
    return `<?doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitobchi ochilmoqda...</title>
    <script>
        window.location.href = "${appScheme}";
        setTimeout(function() {
            window.location.href = "${webPath}";
        }, 1200);
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #fafafa; color: #333; text-align: center; }
        .spinner { width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top: 3px solid #4f46e5; border-radius: 50%;
            animation: spin 0.8s linear infinite; margin-bottom: 14px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        a { color: #4f46e5; text-decoration: none; font-weight: 600; margin-top: 10px; display: inline-block; font-size: 14px; }
    </style>
</head>
<body>
    <div class="spinner"></div>
    <h3 style="margin: 0 0 6px 0; font-size: 17px;">Kitobchi ochilmoqda...</h3>
    <p style="margin: 0; font-size: 13px; color: #666;">Ilova ochilmasa, <a href="${webPath}">saytda ko'rish</a> yoki <a href="${fallbackUrl}">ilovada yuklab olish</a></p>
</body>
</html>`
  }

  return sendRedirect(event, webPath, 302)
})
