// Platform URL generators
const platformUrls = {
  'Netflix': (tmdbId) => 'https://www.netflix.com/search?q=',
  'Amazon Prime Video': (tmdbId) => 'https://www.primevideo.com/search/ref=atv_nb_sr?phrase=',
  'Amazon Video': (tmdbId) => 'https://www.primevideo.com/search/ref=atv_nb_sr?phrase=',
  'Disney Plus': (tmdbId) => 'https://www.disneyplus.com/search',
  'HBO Max': (tmdbId) => 'https://www.max.com/search?q=',
  'Globoplay': (tmdbId) => 'https://globoplay.globo.com/busca/?q=',
  'Apple TV': (tmdbId) => 'https://tv.apple.com/search?q=',
  'Apple TV Plus': (tmdbId) => 'https://tv.apple.com/search?q=',
  'Google Play Movies': (tmdbId) => 'https://play.google.com/store/search?q=',
  'YouTube': (tmdbId) => 'https://www.youtube.com/results?search_query=',
  'Paramount Plus': (tmdbId) => 'https://www.paramountplus.com/search/',
  'Star Plus': (tmdbId) => 'https://www.starplus.com/search',
  'Claro video': (tmdbId) => 'https://www.clarovideo.com/buscar?q=',
  'Telecine': (tmdbId) => `https://www.telecine.com.br/`,
  'Looke': (tmdbId) => 'https://www.looke.com.br/busca?q=',
  'NOW': (tmdbId) => 'https://www.discoveryplus.com/br/search?query=',
  'Oi Play': (tmdbId) => 'https://oiplay.oi.com.br/busca?q='
}

export function getPlatformUrl(platformName, movieTitle, tmdbLink) {
  const normalizedName = platformName.trim()
  
  // Try exact match first
  if (platformUrls[normalizedName]) {
    return platformUrls[normalizedName]() + encodeURIComponent(movieTitle)
  }
  
  // Try partial match
  for (const [key, urlGenerator] of Object.entries(platformUrls)) {
    if (normalizedName.includes(key) || key.includes(normalizedName)) {
      return urlGenerator() + encodeURIComponent(movieTitle)
    }
  }
  
  // Fallback to TMDB link
  return tmdbLink || '#'
}

export function shouldOpenPlatformLink(platformName) {
  const normalizedName = platformName.trim()
  return platformUrls.hasOwnProperty(normalizedName) || 
         Object.keys(platformUrls).some(key => normalizedName.includes(key) || key.includes(normalizedName))
}
