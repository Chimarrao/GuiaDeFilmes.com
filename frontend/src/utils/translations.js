// Crew roles translations
export const crewRoles = {
  'Director': 'Diretor',
  'Producer': 'Produtor',
  'Executive Producer': 'Produtor Executivo',
  'Writer': 'Roteirista',
  'Screenplay': 'Roteiro',
  'Story': 'História',
  'Characters': 'Personagens',
  'Novel': 'Romance',
  'Cinematography': 'Fotografia',
  'Director of Photography': 'Diretor de Fotografia',
  'Editor': 'Editor',
  'Music': 'Música',
  'Original Music Composer': 'Compositor da Música Original',
  'Production Design': 'Design de Produção',
  'Art Direction': 'Direção de Arte',
  'Costume Design': 'Figurino',
  'Makeup Artist': 'Maquiagem',
  'Sound Designer': 'Designer de Som',
  'Visual Effects': 'Efeitos Visuais',
  'Casting': 'Elenco',
  'Co-Producer': 'Co-Produtor',
  'Associate Producer': 'Produtor Associado',
  'Line Producer': 'Produtor de Linha'
}

// Country translations with flags
export const countries = {
  'United States of America': { name: 'Estados Unidos', flag: '🇺🇸', code: 'US' },
  'United Kingdom': { name: 'Reino Unido', flag: '🇬🇧', code: 'GB' },
  'Brazil': { name: 'Brasil', flag: '🇧🇷', code: 'BR' },
  'France': { name: 'França', flag: '🇫🇷', code: 'FR' },
  'Germany': { name: 'Alemanha', flag: '🇩🇪', code: 'DE' },
  'Italy': { name: 'Itália', flag: '🇮🇹', code: 'IT' },
  'Spain': { name: 'Espanha', flag: '🇪🇸', code: 'ES' },
  'Mexico': { name: 'México', flag: '🇲🇽', code: 'MX' },
  'Canada': { name: 'Canadá', flag: '🇨🇦', code: 'CA' },
  'Japan': { name: 'Japão', flag: '🇯🇵', code: 'JP' },
  'South Korea': { name: 'Coreia do Sul', flag: '🇰🇷', code: 'KR' },
  'China': { name: 'China', flag: '🇨🇳', code: 'CN' },
  'India': { name: 'Índia', flag: '🇮🇳', code: 'IN' },
  'Australia': { name: 'Austrália', flag: '🇦🇺', code: 'AU' },
  'New Zealand': { name: 'Nova Zelândia', flag: '🇳🇿', code: 'NZ' },
  'Argentina': { name: 'Argentina', flag: '🇦🇷', code: 'AR' },
  'Russia': { name: 'Rússia', flag: '🇷🇺', code: 'RU' },
  'Sweden': { name: 'Suécia', flag: '🇸🇪', code: 'SE' },
  'Norway': { name: 'Noruega', flag: '🇳🇴', code: 'NO' },
  'Denmark': { name: 'Dinamarca', flag: '🇩🇰', code: 'DK' },
  'Finland': { name: 'Finlândia', flag: '🇫🇮', code: 'FI' },
  'Netherlands': { name: 'Holanda', flag: '🇳🇱', code: 'NL' },
  'Belgium': { name: 'Bélgica', flag: '🇧🇪', code: 'BE' },
  'Switzerland': { name: 'Suíça', flag: '🇨🇭', code: 'CH' },
  'Austria': { name: 'Áustria', flag: '🇦🇹', code: 'AT' },
  'Poland': { name: 'Polônia', flag: '🇵🇱', code: 'PL' },
  'Portugal': { name: 'Portugal', flag: '🇵🇹', code: 'PT' },
  'Ireland': { name: 'Irlanda', flag: '🇮🇪', code: 'IE' },
  'Czech Republic': { name: 'República Tcheca', flag: '🇨🇿', code: 'CZ' },
  'Hungary': { name: 'Hungria', flag: '🇭🇺', code: 'HU' },
  'Romania': { name: 'Romênia', flag: '🇷🇴', code: 'RO' },
  'Turkey': { name: 'Turquia', flag: '🇹🇷', code: 'TR' },
  'Greece': { name: 'Grécia', flag: '🇬🇷', code: 'GR' },
  'Thailand': { name: 'Tailândia', flag: '🇹🇭', code: 'TH' },
  'Indonesia': { name: 'Indonésia', flag: '🇮🇩', code: 'ID' },
  'Philippines': { name: 'Filipinas', flag: '🇵🇭', code: 'PH' },
  'Vietnam': { name: 'Vietnã', flag: '🇻🇳', code: 'VN' },
  'Hong Kong': { name: 'Hong Kong', flag: '🇭🇰', code: 'HK' },
  'Taiwan': { name: 'Taiwan', flag: '🇹🇼', code: 'TW' },
  'Singapore': { name: 'Cingapura', flag: '🇸🇬', code: 'SG' },
  'Malaysia': { name: 'Malásia', flag: '🇲🇾', code: 'MY' },
  'Chile': { name: 'Chile', flag: '🇨🇱', code: 'CL' },
  'Colombia': { name: 'Colômbia', flag: '🇨🇴', code: 'CO' },
  'Peru': { name: 'Peru', flag: '🇵🇪', code: 'PE' },
  'Venezuela': { name: 'Venezuela', flag: '🇻🇪', code: 'VE' },
  'Uruguay': { name: 'Uruguai', flag: '🇺🇾', code: 'UY' },
  'South Africa': { name: 'África do Sul', flag: '🇿🇦', code: 'ZA' },
  'Egypt': { name: 'Egito', flag: '🇪🇬', code: 'EG' },
  'Israel': { name: 'Israel', flag: '🇮🇱', code: 'IL' },
  'Saudi Arabia': { name: 'Arábia Saudita', flag: '🇸🇦', code: 'SA' },
  'United Arab Emirates': { name: 'Emirados Árabes Unidos', flag: '🇦🇪', code: 'AE' }
}

// Helper function to translate crew role
export function translateCrewRole(role) {
  return crewRoles[role] || role
}

// Helper function to translate country with flag
export function translateCountry(country) {
  const translation = countries[country]
  if (translation) {
    return `${translation.flag} ${translation.name}`
  }
  return country
}

// Helper function to get only country name
export function getCountryName(country) {
  return countries[country]?.name || country
}

// Helper function to get only country flag
export function getCountryFlag(country) {
  return countries[country]?.flag || '🌍'
}

// Helper function to get only country code
export function getCountryCode(country) {
  return countries[country]?.code || ''
}
