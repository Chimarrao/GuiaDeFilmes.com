/**
 * Country Flags - Using FlagCDN SVG images
 * 
 * Usa imagens SVG do flagcdn.com para garantir compatibilidade cross-browser
 * ao invés de emojis Unicode que podem ter problemas de renderização.
 */

export const countryFlags = {
  // Países principais com URLs de bandeiras SVG
  'BR': { name: 'Brasil', code: 'BR', flag: 'https://flagcdn.com/w40/br.png' },
  'US': { name: 'Estados Unidos', code: 'US', flag: 'https://flagcdn.com/w40/us.png' },
  'GB': { name: 'Reino Unido', code: 'GB', flag: 'https://flagcdn.com/w40/gb.png' },
  'FR': { name: 'França', code: 'FR', flag: 'https://flagcdn.com/w40/fr.png' },
  'DE': { name: 'Alemanha', code: 'DE', flag: 'https://flagcdn.com/w40/de.png' },
  'IT': { name: 'Itália', code: 'IT', flag: 'https://flagcdn.com/w40/it.png' },
  'ES': { name: 'Espanha', code: 'ES', flag: 'https://flagcdn.com/w40/es.png' },
  'MX': { name: 'México', code: 'MX', flag: 'https://flagcdn.com/w40/mx.png' },
  'CA': { name: 'Canadá', code: 'CA', flag: 'https://flagcdn.com/w40/ca.png' },
  'JP': { name: 'Japão', code: 'JP', flag: 'https://flagcdn.com/w40/jp.png' },
  'KR': { name: 'Coreia do Sul', code: 'KR', flag: 'https://flagcdn.com/w40/kr.png' },
  'CN': { name: 'China', code: 'CN', flag: 'https://flagcdn.com/w40/cn.png' },
  'IN': { name: 'Índia', code: 'IN', flag: 'https://flagcdn.com/w40/in.png' },
  'AU': { name: 'Austrália', code: 'AU', flag: 'https://flagcdn.com/w40/au.png' },
  'AR': { name: 'Argentina', code: 'AR', flag: 'https://flagcdn.com/w40/ar.png' },
  'SE': { name: 'Suécia', code: 'SE', flag: 'https://flagcdn.com/w40/se.png' },
  'NO': { name: 'Noruega', code: 'NO', flag: 'https://flagcdn.com/w40/no.png' },
  'RU': { name: 'Rússia', code: 'RU', flag: 'https://flagcdn.com/w40/ru.png' },
  'NL': { name: 'Holanda', code: 'NL', flag: 'https://flagcdn.com/w40/nl.png' },
  'BE': { name: 'Bélgica', code: 'BE', flag: 'https://flagcdn.com/w40/be.png' },
  'CH': { name: 'Suíça', code: 'CH', flag: 'https://flagcdn.com/w40/ch.png' },
  'AT': { name: 'Áustria', code: 'AT', flag: 'https://flagcdn.com/w40/at.png' },
  'PL': { name: 'Polônia', code: 'PL', flag: 'https://flagcdn.com/w40/pl.png' },
  'PT': { name: 'Portugal', code: 'PT', flag: 'https://flagcdn.com/w40/pt.png' },
  'DK': { name: 'Dinamarca', code: 'DK', flag: 'https://flagcdn.com/w40/dk.png' },
  'FI': { name: 'Finlândia', code: 'FI', flag: 'https://flagcdn.com/w40/fi.png' },
  'IE': { name: 'Irlanda', code: 'IE', flag: 'https://flagcdn.com/w40/ie.png' },
  'NZ': { name: 'Nova Zelândia', code: 'NZ', flag: 'https://flagcdn.com/w40/nz.png' },
  'ZA': { name: 'África do Sul', code: 'ZA', flag: 'https://flagcdn.com/w40/za.png' }
}

/**
 * Retorna a URL da bandeira SVG
 * @param {string} countryCode Código do país (ex: 'BR', 'US')
 * @returns {string} URL da imagem da bandeira
 */
export function getCountryFlag(countryCode) {
  const country = countryFlags[countryCode.toUpperCase()]
  return country ? country.flag : `https://flagcdn.com/w40/${countryCode.toLowerCase()}.png`
}

/**
 * Retorna apenas o nome do país
 * @param {string} countryCode Código do país (ex: 'BR', 'US')
 * @returns {string} Nome do país (ex: 'Brasil')
 */
export function getCountryName(countryCode) {
  const country = countryFlags[countryCode.toUpperCase()]
  return country ? country.name : countryCode
}

/**
 * Retorna o objeto completo do país
 * @param {string} countryCode Código do país (ex: 'BR', 'US')
 * @returns {object} Objeto com name, code, flag
 */
export function getCountryData(countryCode) {
  const country = countryFlags[countryCode.toUpperCase()]
  return country || { 
    name: countryCode, 
    code: countryCode, 
    flag: `https://flagcdn.com/w40/${countryCode.toLowerCase()}.png` 
  }
}

export default countryFlags
