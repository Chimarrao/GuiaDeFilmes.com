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
  'United States of America': { name: 'Estados Unidos', flag: '🇺🇸' },
  'United Kingdom': { name: 'Reino Unido', flag: '🇬🇧' },
  'Brazil': { name: 'Brasil', flag: '🇧🇷' },
  'France': { name: 'França', flag: '🇫🇷' },
  'Germany': { name: 'Alemanha', flag: '🇩🇪' },
  'Italy': { name: 'Itália', flag: '🇮🇹' },
  'Spain': { name: 'Espanha', flag: '🇪🇸' },
  'Mexico': { name: 'México', flag: '🇲🇽' },
  'Canada': { name: 'Canadá', flag: '🇨🇦' },
  'Japan': { name: 'Japão', flag: '🇯🇵' },
  'South Korea': { name: 'Coreia do Sul', flag: '🇰🇷' },
  'China': { name: 'China', flag: '🇨🇳' },
  'India': { name: 'Índia', flag: '🇮🇳' },
  'Australia': { name: 'Austrália', flag: '🇦🇺' },
  'New Zealand': { name: 'Nova Zelândia', flag: '🇳🇿' },
  'Argentina': { name: 'Argentina', flag: '🇦🇷' },
  'Russia': { name: 'Rússia', flag: '🇷🇺' },
  'Sweden': { name: 'Suécia', flag: '🇸🇪' },
  'Norway': { name: 'Noruega', flag: '🇳🇴' },
  'Denmark': { name: 'Dinamarca', flag: '🇩🇰' },
  'Finland': { name: 'Finlândia', flag: '🇫🇮' },
  'Netherlands': { name: 'Holanda', flag: '🇳🇱' },
  'Belgium': { name: 'Bélgica', flag: '🇧🇪' },
  'Switzerland': { name: 'Suíça', flag: '🇨🇭' },
  'Austria': { name: 'Áustria', flag: '🇦🇹' },
  'Poland': { name: 'Polônia', flag: '🇵🇱' },
  'Portugal': { name: 'Portugal', flag: '🇵🇹' },
  'Ireland': { name: 'Irlanda', flag: '🇮🇪' },
  'Czech Republic': { name: 'República Tcheca', flag: '🇨🇿' },
  'Hungary': { name: 'Hungria', flag: '🇭🇺' },
  'Romania': { name: 'Romênia', flag: '🇷🇴' },
  'Turkey': { name: 'Turquia', flag: '🇹🇷' },
  'Greece': { name: 'Grécia', flag: '🇬🇷' },
  'Thailand': { name: 'Tailândia', flag: '🇹🇭' },
  'Indonesia': { name: 'Indonésia', flag: '🇮🇩' },
  'Philippines': { name: 'Filipinas', flag: '🇵🇭' },
  'Vietnam': { name: 'Vietnã', flag: '🇻🇳' },
  'Hong Kong': { name: 'Hong Kong', flag: '🇭🇰' },
  'Taiwan': { name: 'Taiwan', flag: '🇹🇼' },
  'Singapore': { name: 'Cingapura', flag: '🇸🇬' },
  'Malaysia': { name: 'Malásia', flag: '🇲🇾' },
  'Chile': { name: 'Chile', flag: '🇨🇱' },
  'Colombia': { name: 'Colômbia', flag: '🇨🇴' },
  'Peru': { name: 'Peru', flag: '🇵🇪' },
  'Venezuela': { name: 'Venezuela', flag: '🇻🇪' },
  'Uruguay': { name: 'Uruguai', flag: '🇺🇾' },
  'South Africa': { name: 'África do Sul', flag: '🇿🇦' },
  'Egypt': { name: 'Egito', flag: '🇪🇬' },
  'Israel': { name: 'Israel', flag: '🇮🇱' },
  'Saudi Arabia': { name: 'Arábia Saudita', flag: '🇸🇦' },
  'United Arab Emirates': { name: 'Emirados Árabes Unidos', flag: '🇦🇪' }
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
