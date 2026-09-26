import { defineEventHandler, getHeader, sendRedirect } from 'h3'

// Ilovani yuklab olish uchun yagona havola (Instagram bio, QR kod va h.k.):
// Android → Google Play, iPhone/iPad → App Store, kompyuter → bosh sahifa.
export default defineEventHandler((event) => {
  const userAgent = (getHeader(event, 'user-agent') || '').toLowerCase()

  if (userAgent.includes('android')) {
    return sendRedirect(event, 'https://play.google.com/store/apps/details?id=com.kitobchi.kitobchi', 302)
  }
  if (userAgent.includes('iphone') || userAgent.includes('ipad')) {
    return sendRedirect(event, 'https://apps.apple.com/uz/app/kitobchi/id6753818078', 302)
  }
  return sendRedirect(event, '/', 302)
})
