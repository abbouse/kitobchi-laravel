/**
 * O'zbek tili uchun Lotin <-> Kirill transliteratsiyasi (SEO va Qidiruv uchun).
 */

const LATIN_TO_CYRILLIC: [RegExp, string][] = [
  // Compound letter pairs (Upper / Title case)
  [/Ye/g, 'Е'], [/YE/g, 'Е'], [/ye/g, 'е'],
  [/Yo/g, 'Ё'], [/YO/g, 'Ё'], [/yo/g, 'ё'],
  [/Ch/g, 'Ч'], [/CH/g, 'Ч'], [/ch/g, 'ч'],
  [/Sh/g, 'Ш'], [/SH/g, 'Ш'], [/sh/g, 'ш'],
  [/Yu/g, 'Ю'], [/YU/g, 'Ю'], [/yu/g, 'ю'],
  [/Ya/g, 'Я'], [/YA/g, 'Я'], [/ya/g, 'я'],
  [/Ts/g, 'Ц'], [/TS/g, 'Ц'], [/ts/g, 'ц'],
  [/O['`‘ʼ’]/gi, 'Ў'], [/o['`‘ʼ’]/gi, 'ў'],
  [/G['`‘ʼ’]/gi, 'Ғ'], [/g['`‘ʼ’]/gi, 'ғ'],

  // Single letters
  [/A/g, 'А'], [/a/g, 'а'],
  [/B/g, 'Б'], [/b/g, 'б'],
  [/D/g, 'Д'], [/d/g, 'д'],
  [/E/g, 'Э'], [/e/g, 'э'],
  [/F/g, 'Ф'], [/f/g, 'ф'],
  [/G/g, 'Г'], [/g/g, 'г'],
  [/H/g, 'Ҳ'], [/h/g, 'ҳ'],
  [/I/g, 'И'], [/i/g, 'и'],
  [/J/g, 'Ж'], [/j/g, 'ж'],
  [/K/g, 'К'], [/k/g, 'к'],
  [/L/g, 'Л'], [/l/g, 'л'],
  [/M/g, 'М'], [/m/g, 'м'],
  [/N/g, 'Н'], [/n/g, 'н'],
  [/O/g, 'О'], [/o/g, 'о'],
  [/P/g, 'П'], [/p/g, 'п'],
  [/Q/g, 'Қ'], [/q/g, 'қ'],
  [/R/g, 'Р'], [/r/g, 'р'],
  [/S/g, 'С'], [/s/g, 'с'],
  [/T/g, 'Т'], [/t/g, 'т'],
  [/U/g, 'У'], [/u/g, 'у'],
  [/V/g, 'В'], [/v/g, 'в'],
  [/X/g, 'Х'], [/x/g, 'х'],
  [/Y/g, 'Й'], [/y/g, 'й'],
  [/Z/g, 'З'], [/z/g, 'з'],
]

const CYRILLIC_TO_LATIN: [RegExp, string][] = [
  [/Ё/g, 'Yo'], [/ё/g, 'yo'],
  [/Ч/g, 'Ch'], [/ч/g, 'ch'],
  [/Ш/g, 'Sh'], [/ш/g, 'sh'],
  [/Щ/g, 'Sh'], [/щ/g, 'sh'],
  [/Ю/g, 'Yu'], [/ю/g, 'yu'],
  [/Я/g, 'Ya'], [/я/g, 'ya'],
  [/Ц/g, 'Ts'], [/ц/g, 'ts'],
  [/Ў/g, "O'"], [/ў/g, "o'"],
  [/Ғ/g, "G'"], [/ғ/g, "g'"],
  [/Қ/g, 'Q'], [/қ/g, 'q'],
  [/Ҳ/g, 'H'], [/ҳ/g, 'h'],

  [/А/g, 'A'], [/а/g, 'a'],
  [/Б/g, 'B'], [/б/g, 'b'],
  [/В/g, 'V'], [/в/g, 'v'],
  [/Г/g, 'G'], [/г/g, 'g'],
  [/Д/g, 'D'], [/д/g, 'd'],
  [/Е/g, 'E'], [/е/g, 'e'],
  [/Ж/g, 'J'], [/ж/g, 'j'],
  [/З/g, 'Z'], [/з/g, 'z'],
  [/И/g, 'I'], [/и/g, 'i'],
  [/Й/g, 'Y'], [/й/g, 'y'],
  [/К/g, 'K'], [/к/g, 'k'],
  [/Л/g, 'L'], [/л/g, 'l'],
  [/М/g, 'M'], [/м/g, 'm'],
  [/Н/g, 'N'], [/н/g, 'n'],
  [/О/g, 'O'], [/о/g, 'o'],
  [/П/g, 'P'], [/п/g, 'p'],
  [/Р/g, 'R'], [/р/g, 'r'],
  [/С/g, 'S'], [/с/g, 's'],
  [/Т/g, 'T'], [/т/g, 't'],
  [/У/g, 'U'], [/у/g, 'u'],
  [/Ф/g, 'F'], [/ф/g, 'f'],
  [/Х/g, 'X'], [/х/g, 'x'],
  [/Ъ/g, ''], [/ъ/g, ''],
  [/Ь/g, ''], [/ь/g, ''],
  [/Э/g, 'E'], [/э/g, 'e'],
]

export function latinToCyrillic(text: string): string {
  if (!text) return ''
  let result = text
  for (const [pattern, replacement] of LATIN_TO_CYRILLIC) {
    result = result.replace(pattern, replacement)
  }
  return result
}

export function cyrillicToLatin(text: string): string {
  if (!text) return ''
  let result = text
  for (const [pattern, replacement] of CYRILLIC_TO_LATIN) {
    result = result.replace(pattern, replacement)
  }
  return result
}

export function getDualScriptKeywords(name: string, author?: string): string[] {
  const latinName = cyrillicToLatin(name)
  const cyrillicName = latinToCyrillic(name)
  const list = [latinName, cyrillicName]

  if (author) {
    list.push(cyrillicToLatin(author))
    list.push(latinToCyrillic(author))
    list.push(`${latinName} ${cyrillicToLatin(author)}`)
    list.push(`${cyrillicName} ${latinToCyrillic(author)}`)
  }

  return Array.from(new Set(list.filter(Boolean)))
}
