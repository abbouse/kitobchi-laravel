import { definePayloadPlugin, definePayloadReducer } from '#imports'
import { shouldHydrate } from 'pinia'

export default definePayloadPlugin({
  name: 'fix-pinia-skip-hydrate',
  order: 99,
  setup() {
    definePayloadReducer('skipHydrate', (data) => {
      if (!data || typeof data !== 'object') return false
      // Objects created with Object.create(null) (such as H3 / Nuxt route queries and headers)
      // do not have Object.prototype methods, causing pinia's shouldHydrate(obj) to throw:
      // "TypeError: obj.hasOwnProperty is not a function", which crashes SSR payload serialization.
      if (typeof (data as any).hasOwnProperty !== 'function') return false
      return !shouldHydrate(data) && 1
    })
  }
})
