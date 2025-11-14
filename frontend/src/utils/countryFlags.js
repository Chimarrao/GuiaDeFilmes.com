/**
 * Country Flags System
 * 
 * Sistema completo para resolução de bandeiras de países modernos e históricos.
 * Usa imagens SVG do flagcdn.com para países modernos e placeholders para países extintos.
 */

/**
 * Países modernos com código ISO 3166-1 alpha-2
 * Indexados por código ISO (US, BR, FR, etc.)
 */
export const countryFlags = {
  'AD': { name: 'Andorra', code: 'AD', flag: 'https://flagcdn.com/w40/ad.png', englishName: 'Andorra' },
  'AE': { name: 'Emirados Árabes Unidos', code: 'AE', flag: 'https://flagcdn.com/w40/ae.png', englishName: 'United Arab Emirates' },
  'AF': { name: 'Afeganistão', code: 'AF', flag: 'https://flagcdn.com/w40/af.png', englishName: 'Afghanistan' },
  'AG': { name: 'Antígua e Barbuda', code: 'AG', flag: 'https://flagcdn.com/w40/ag.png', englishName: 'Antigua and Barbuda' },
  'AI': { name: 'Anguilla', code: 'AI', flag: 'https://flagcdn.com/w40/ai.png', englishName: 'Anguilla' },
  'AL': { name: 'Albânia', code: 'AL', flag: 'https://flagcdn.com/w40/al.png', englishName: 'Albania' },
  'AM': { name: 'Armênia', code: 'AM', flag: 'https://flagcdn.com/w40/am.png', englishName: 'Armenia' },
  'AO': { name: 'Angola', code: 'AO', flag: 'https://flagcdn.com/w40/ao.png', englishName: 'Angola' },
  'AQ': { name: 'Antártida', code: 'AQ', flag: 'https://flagcdn.com/w40/aq.png', englishName: 'Antarctica' },
  'AR': { name: 'Argentina', code: 'AR', flag: 'https://flagcdn.com/w40/ar.png', englishName: 'Argentina' },
  'AS': { name: 'Samoa Americana', code: 'AS', flag: 'https://flagcdn.com/w40/as.png', englishName: 'American Samoa' },
  'AT': { name: 'Áustria', code: 'AT', flag: 'https://flagcdn.com/w40/at.png', englishName: 'Austria' },
  'AU': { name: 'Austrália', code: 'AU', flag: 'https://flagcdn.com/w40/au.png', englishName: 'Australia' },
  'AW': { name: 'Aruba', code: 'AW', flag: 'https://flagcdn.com/w40/aw.png', englishName: 'Aruba' },
  'AZ': { name: 'Azerbaijão', code: 'AZ', flag: 'https://flagcdn.com/w40/az.png', englishName: 'Azerbaijan' },
  'BA': { name: 'Bósnia e Herzegovina', code: 'BA', flag: 'https://flagcdn.com/w40/ba.png', englishName: 'Bosnia and Herzegovina' },
  'BB': { name: 'Barbados', code: 'BB', flag: 'https://flagcdn.com/w40/bb.png', englishName: 'Barbados' },
  'BD': { name: 'Bangladesh', code: 'BD', flag: 'https://flagcdn.com/w40/bd.png', englishName: 'Bangladesh' },
  'BE': { name: 'Bélgica', code: 'BE', flag: 'https://flagcdn.com/w40/be.png', englishName: 'Belgium' },
  'BF': { name: 'Burquina Faso', code: 'BF', flag: 'https://flagcdn.com/w40/bf.png', englishName: 'Burkina Faso' },
  'BG': { name: 'Bulgária', code: 'BG', flag: 'https://flagcdn.com/w40/bg.png', englishName: 'Bulgaria' },
  'BH': { name: 'Bahrein', code: 'BH', flag: 'https://flagcdn.com/w40/bh.png', englishName: 'Bahrain' },
  'BI': { name: 'Burundi', code: 'BI', flag: 'https://flagcdn.com/w40/bi.png', englishName: 'Burundi' },
  'BJ': { name: 'Benin', code: 'BJ', flag: 'https://flagcdn.com/w40/bj.png', englishName: 'Benin' },
  'BL': { name: 'Saint Barthélemy', code: 'BL', flag: 'https://flagcdn.com/w40/bl.png', englishName: 'Saint Barthélemy' },
  'BM': { name: 'Bermudas', code: 'BM', flag: 'https://flagcdn.com/w40/bm.png', englishName: 'Bermuda' },
  'BN': { name: 'Brunei', code: 'BN', flag: 'https://flagcdn.com/w40/bn.png', englishName: 'Brunei Darussalam' },
  'BO': { name: 'Bolívia', code: 'BO', flag: 'https://flagcdn.com/w40/bo.png', englishName: 'Bolivia' },
  'BQ': { name: 'Caribe Neerlandês', code: 'BQ', flag: 'https://flagcdn.com/w40/bq.png', englishName: 'Bonaire, Sint Eustatius and Saba' },
  'BR': { name: 'Brasil', code: 'BR', flag: 'https://flagcdn.com/w40/br.png', englishName: 'Brazil' },
  'BS': { name: 'Bahamas', code: 'BS', flag: 'https://flagcdn.com/w40/bs.png', englishName: 'Bahamas' },
  'BT': { name: 'Butão', code: 'BT', flag: 'https://flagcdn.com/w40/bt.png', englishName: 'Bhutan' },
  'BV': { name: 'Ilha Bouvet', code: 'BV', flag: 'https://flagcdn.com/w40/bv.png', englishName: 'Bouvet Island' },
  'BW': { name: 'Botsuana', code: 'BW', flag: 'https://flagcdn.com/w40/bw.png', englishName: 'Botswana' },
  'BY': { name: 'Belarus', code: 'BY', flag: 'https://flagcdn.com/w40/by.png', englishName: 'Belarus' },
  'BZ': { name: 'Belize', code: 'BZ', flag: 'https://flagcdn.com/w40/bz.png', englishName: 'Belize' },
  'CA': { name: 'Canadá', code: 'CA', flag: 'https://flagcdn.com/w40/ca.png', englishName: 'Canada' },
  'CC': { name: 'Ilhas Cocos', code: 'CC', flag: 'https://flagcdn.com/w40/cc.png', englishName: 'Cocos (Keeling) Islands' },
  'CD': { name: 'Congo (RDC)', code: 'CD', flag: 'https://flagcdn.com/w40/cd.png', englishName: 'Democratic Republic of the Congo' },
  'CF': { name: 'República Centro-Africana', code: 'CF', flag: 'https://flagcdn.com/w40/cf.png', englishName: 'Central African Republic' },
  'CG': { name: 'Congo', code: 'CG', flag: 'https://flagcdn.com/w40/cg.png', englishName: 'Republic of the Congo' },
  'CH': { name: 'Suíça', code: 'CH', flag: 'https://flagcdn.com/w40/ch.png', englishName: 'Switzerland' },
  'CI': { name: 'Costa do Marfim', code: 'CI', flag: 'https://flagcdn.com/w40/ci.png', englishName: 'Côte d\'Ivoire' },
  'CK': { name: 'Ilhas Cook', code: 'CK', flag: 'https://flagcdn.com/w40/ck.png', englishName: 'Cook Islands' },
  'CL': { name: 'Chile', code: 'CL', flag: 'https://flagcdn.com/w40/cl.png', englishName: 'Chile' },
  'CM': { name: 'Camarões', code: 'CM', flag: 'https://flagcdn.com/w40/cm.png', englishName: 'Cameroon' },
  'CN': { name: 'China', code: 'CN', flag: 'https://flagcdn.com/w40/cn.png', englishName: 'China' },
  'CO': { name: 'Colômbia', code: 'CO', flag: 'https://flagcdn.com/w40/co.png', englishName: 'Colombia' },
  'CR': { name: 'Costa Rica', code: 'CR', flag: 'https://flagcdn.com/w40/cr.png', englishName: 'Costa Rica' },
  'CU': { name: 'Cuba', code: 'CU', flag: 'https://flagcdn.com/w40/cu.png', englishName: 'Cuba' },
  'CV': { name: 'Cabo Verde', code: 'CV', flag: 'https://flagcdn.com/w40/cv.png', englishName: 'Cabo Verde' },
  'CW': { name: 'Curaçao', code: 'CW', flag: 'https://flagcdn.com/w40/cw.png', englishName: 'Curaçao' },
  'CX': { name: 'Ilha Christmas', code: 'CX', flag: 'https://flagcdn.com/w40/cx.png', englishName: 'Christmas Island' },
  'CY': { name: 'Chipre', code: 'CY', flag: 'https://flagcdn.com/w40/cy.png', englishName: 'Cyprus' },

  'CZ': { name: 'Tchéquia', code: 'CZ', flag: 'https://flagcdn.com/w40/cz.png', englishName: 'Czechia' },
  'CZ': { name: 'Tchéquia', code: 'CZ', flag: 'https://flagcdn.com/w40/cz.png', englishName: 'Czech Republic' },

  'DE': { name: 'Alemanha', code: 'DE', flag: 'https://flagcdn.com/w40/de.png', englishName: 'Germany' },
  'DJ': { name: 'Djibuti', code: 'DJ', flag: 'https://flagcdn.com/w40/dj.png', englishName: 'Djibouti' },
  'DK': { name: 'Dinamarca', code: 'DK', flag: 'https://flagcdn.com/w40/dk.png', englishName: 'Denmark' },
  'DM': { name: 'Dominica', code: 'DM', flag: 'https://flagcdn.com/w40/dm.png', englishName: 'Dominica' },
  'DO': { name: 'República Dominicana', code: 'DO', flag: 'https://flagcdn.com/w40/do.png', englishName: 'Dominican Republic' },
  'DZ': { name: 'Argélia', code: 'DZ', flag: 'https://flagcdn.com/w40/dz.png', englishName: 'Algeria' },
  'EC': { name: 'Equador', code: 'EC', flag: 'https://flagcdn.com/w40/ec.png', englishName: 'Ecuador' },
  'EE': { name: 'Estônia', code: 'EE', flag: 'https://flagcdn.com/w40/ee.png', englishName: 'Estonia' },
  'EG': { name: 'Egito', code: 'EG', flag: 'https://flagcdn.com/w40/eg.png', englishName: 'Egypt' },
  'EH': { name: 'Saara Ocidental', code: 'EH', flag: 'https://flagcdn.com/w40/eh.png', englishName: 'Western Sahara' },
  'ER': { name: 'Eritreia', code: 'ER', flag: 'https://flagcdn.com/w40/er.png', englishName: 'Eritrea' },
  'ES': { name: 'Espanha', code: 'ES', flag: 'https://flagcdn.com/w40/es.png', englishName: 'Spain' },
  'ET': { name: 'Etiópia', code: 'ET', flag: 'https://flagcdn.com/w40/et.png', englishName: 'Ethiopia' },
  'FI': { name: 'Finlândia', code: 'FI', flag: 'https://flagcdn.com/w40/fi.png', englishName: 'Finland' },
  'FJ': { name: 'Fiji', code: 'FJ', flag: 'https://flagcdn.com/w40/fj.png', englishName: 'Fiji' },
  'FK': { name: 'Ilhas Falkland', code: 'FK', flag: 'https://flagcdn.com/w40/fk.png', englishName: 'Falkland Islands' },
  'FM': { name: 'Micronésia', code: 'FM', flag: 'https://flagcdn.com/w40/fm.png', englishName: 'Micronesia' },
  'FO': { name: 'Ilhas Faroé', code: 'FO', flag: 'https://flagcdn.com/w40/fo.png', englishName: 'Faroe Islands' },
  'FR': { name: 'França', code: 'FR', flag: 'https://flagcdn.com/w40/fr.png', englishName: 'France' },
  'GA': { name: 'Gabão', code: 'GA', flag: 'https://flagcdn.com/w40/ga.png', englishName: 'Gabon' },
  'GB': { name: 'Reino Unido', code: 'GB', flag: 'https://flagcdn.com/w40/gb.png', englishName: 'United Kingdom' },
  'GD': { name: 'Granada', code: 'GD', flag: 'https://flagcdn.com/w40/gd.png', englishName: 'Grenada' },
  'GE': { name: 'Geórgia', code: 'GE', flag: 'https://flagcdn.com/w40/ge.png', englishName: 'Georgia' },
  'GF': { name: 'Guiana Francesa', code: 'GF', flag: 'https://flagcdn.com/w40/gf.png', englishName: 'French Guiana' },
  'GG': { name: 'Guernsey', code: 'GG', flag: 'https://flagcdn.com/w40/gg.png', englishName: 'Guernsey' },
  'GH': { name: 'Gana', code: 'GH', flag: 'https://flagcdn.com/w40/gh.png', englishName: 'Ghana' },
  'GI': { name: 'Gibraltar', code: 'GI', flag: 'https://flagcdn.com/w40/gi.png', englishName: 'Gibraltar' },
  'GL': { name: 'Groenlândia', code: 'GL', flag: 'https://flagcdn.com/w40/gl.png', englishName: 'Greenland' },
  'GM': { name: 'Gâmbia', code: 'GM', flag: 'https://flagcdn.com/w40/gm.png', englishName: 'Gambia' },
  'GN': { name: 'Guiné', code: 'GN', flag: 'https://flagcdn.com/w40/gn.png', englishName: 'Guinea' },
  'GP': { name: 'Guadalupe', code: 'GP', flag: 'https://flagcdn.com/w40/gp.png', englishName: 'Guadeloupe' },
  'GQ': { name: 'Guiné Equatorial', code: 'GQ', flag: 'https://flagcdn.com/w40/gq.png', englishName: 'Equatorial Guinea' },
  'GR': { name: 'Grécia', code: 'GR', flag: 'https://flagcdn.com/w40/gr.png', englishName: 'Greece' },
  'GS': { name: 'Ilhas Geórgia do Sul e Sandwich do Sul', code: 'GS', flag: 'https://flagcdn.com/w40/gs.png', englishName: 'South Georgia and the South Sandwich Islands' },
  'GT': { name: 'Guatemala', code: 'GT', flag: 'https://flagcdn.com/w40/gt.png', englishName: 'Guatemala' },
  'GU': { name: 'Guam', code: 'GU', flag: 'https://flagcdn.com/w40/gu.png', englishName: 'Guam' },
  'GW': { name: 'Guiné-Bissau', code: 'GW', flag: 'https://flagcdn.com/w40/gw.png', englishName: 'Guinea-Bissau' },
  'GY': { name: 'Guiana', code: 'GY', flag: 'https://flagcdn.com/w40/gy.png', englishName: 'Guyana' },
  'HK': { name: 'Hong Kong', code: 'HK', flag: 'https://flagcdn.com/w40/hk.png', englishName: 'Hong Kong' },
  'HM': { name: 'Ilha Heard e Ilhas McDonald', code: 'HM', flag: 'https://flagcdn.com/w40/hm.png', englishName: 'Heard Island and McDonald Islands' },
  'HN': { name: 'Honduras', code: 'HN', flag: 'https://flagcdn.com/w40/hn.png', englishName: 'Honduras' },
  'HR': { name: 'Croácia', code: 'HR', flag: 'https://flagcdn.com/w40/hr.png', englishName: 'Croatia' },
  'HT': { name: 'Haiti', code: 'HT', flag: 'https://flagcdn.com/w40/ht.png', englishName: 'Haiti' },
  'HU': { name: 'Hungria', code: 'HU', flag: 'https://flagcdn.com/w40/hu.png', englishName: 'Hungary' },
  'ID': { name: 'Indonésia', code: 'ID', flag: 'https://flagcdn.com/w40/id.png', englishName: 'Indonesia' },
  'IE': { name: 'Irlanda', code: 'IE', flag: 'https://flagcdn.com/w40/ie.png', englishName: 'Ireland' },
  'IL': { name: 'Israel', code: 'IL', flag: 'https://flagcdn.com/w40/il.png', englishName: 'Israel' },
  'IM': { name: 'Ilha de Man', code: 'IM', flag: 'https://flagcdn.com/w40/im.png', englishName: 'Isle of Man' },
  'IN': { name: 'Índia', code: 'IN', flag: 'https://flagcdn.com/w40/in.png', englishName: 'India' },
  'IO': { name: 'Território Britânico do Oceano Índico', code: 'IO', flag: 'https://flagcdn.com/w40/io.png', englishName: 'British Indian Ocean Territory' },
  'IQ': { name: 'Iraque', code: 'IQ', flag: 'https://flagcdn.com/w40/iq.png', englishName: 'Iraq' },
  'IR': { name: 'Irã', code: 'IR', flag: 'https://flagcdn.com/w40/ir.png', englishName: 'Iran' },
  'IS': { name: 'Islândia', code: 'IS', flag: 'https://flagcdn.com/w40/is.png', englishName: 'Iceland' },
  'IT': { name: 'Itália', code: 'IT', flag: 'https://flagcdn.com/w40/it.png', englishName: 'Italy' },
  'JE': { name: 'Jersey', code: 'JE', flag: 'https://flagcdn.com/w40/je.png', englishName: 'Jersey' },
  'JM': { name: 'Jamaica', code: 'JM', flag: 'https://flagcdn.com/w40/jm.png', englishName: 'Jamaica' },
  'JO': { name: 'Jordânia', code: 'JO', flag: 'https://flagcdn.com/w40/jo.png', englishName: 'Jordan' },
  'JP': { name: 'Japão', code: 'JP', flag: 'https://flagcdn.com/w40/jp.png', englishName: 'Japan' },
  'KE': { name: 'Quênia', code: 'KE', flag: 'https://flagcdn.com/w40/ke.png', englishName: 'Kenya' },
  'KG': { name: 'Quirguistão', code: 'KG', flag: 'https://flagcdn.com/w40/kg.png', englishName: 'Kyrgyzstan' },
  'KH': { name: 'Camboja', code: 'KH', flag: 'https://flagcdn.com/w40/kh.png', englishName: 'Cambodia' },
  'KI': { name: 'Kiribati', code: 'KI', flag: 'https://flagcdn.com/w40/ki.png', englishName: 'Kiribati' },
  'KM': { name: 'Comores', code: 'KM', flag: 'https://flagcdn.com/w40/km.png', englishName: 'Comoros' },
  'KN': { name: 'São Cristóvão e Nevis', code: 'KN', flag: 'https://flagcdn.com/w40/kn.png', englishName: 'Saint Kitts and Nevis' },
  'KP': { name: 'Coreia do Norte', code: 'KP', flag: 'https://flagcdn.com/w40/kp.png', englishName: 'North Korea' },
  'KR': { name: 'Coreia do Sul', code: 'KR', flag: 'https://flagcdn.com/w40/kr.png', englishName: 'South Korea' },
  'KW': { name: 'Kuwait', code: 'KW', flag: 'https://flagcdn.com/w40/kw.png', englishName: 'Kuwait' },
  'KY': { name: 'Ilhas Cayman', code: 'KY', flag: 'https://flagcdn.com/w40/ky.png', englishName: 'Cayman Islands' },
  'KZ': { name: 'Cazaquistão', code: 'KZ', flag: 'https://flagcdn.com/w40/kz.png', englishName: 'Kazakhstan' },
  'LA': { name: 'Laos', code: 'LA', flag: 'https://flagcdn.com/w40/la.png', englishName: 'Laos' },
  'LB': { name: 'Líbano', code: 'LB', flag: 'https://flagcdn.com/w40/lb.png', englishName: 'Lebanon' },
  'LC': { name: 'Santa Lúcia', code: 'LC', flag: 'https://flagcdn.com/w40/lc.png', englishName: 'Saint Lucia' },
  'LI': { name: 'Liechtenstein', code: 'LI', flag: 'https://flagcdn.com/w40/li.png', englishName: 'Liechtenstein' },
  'LK': { name: 'Sri Lanka', code: 'LK', flag: 'https://flagcdn.com/w40/lk.png', englishName: 'Sri Lanka' },
  'LR': { name: 'Libéria', code: 'LR', flag: 'https://flagcdn.com/w40/lr.png', englishName: 'Liberia' },
  'LS': { name: 'Lesoto', code: 'LS', flag: 'https://flagcdn.com/w40/ls.png', englishName: 'Lesotho' },
  'LT': { name: 'Lituânia', code: 'LT', flag: 'https://flagcdn.com/w40/lt.png', englishName: 'Lithuania' },
  'LU': { name: 'Luxemburgo', code: 'LU', flag: 'https://flagcdn.com/w40/lu.png', englishName: 'Luxembourg' },
  'LV': { name: 'Letônia', code: 'LV', flag: 'https://flagcdn.com/w40/lv.png', englishName: 'Latvia' },
  'LY': { name: 'Líbia', code: 'LY', flag: 'https://flagcdn.com/w40/ly.png', englishName: 'Libya' },
  'MA': { name: 'Marrocos', code: 'MA', flag: 'https://flagcdn.com/w40/ma.png', englishName: 'Morocco' },
  'MC': { name: 'Mônaco', code: 'MC', flag: 'https://flagcdn.com/w40/mc.png', englishName: 'Monaco' },
  'MD': { name: 'Moldávia', code: 'MD', flag: 'https://flagcdn.com/w40/md.png', englishName: 'Moldova' },
  'ME': { name: 'Montenegro', code: 'ME', flag: 'https://flagcdn.com/w40/me.png', englishName: 'Montenegro' },
  'MF': { name: 'São Martinho (França)', code: 'MF', flag: 'https://flagcdn.com/w40/mf.png', englishName: 'Saint Martin' },
  'MG': { name: 'Madagáscar', code: 'MG', flag: 'https://flagcdn.com/w40/mg.png', englishName: 'Madagascar' },
  'MH': { name: 'Ilhas Marshall', code: 'MH', flag: 'https://flagcdn.com/w40/mh.png', englishName: 'Marshall Islands' },
  'MK': { name: 'Macedônia do Norte', code: 'MK', flag: 'https://flagcdn.com/w40/mk.png', englishName: 'North Macedonia' },
  'ML': { name: 'Mali', code: 'ML', flag: 'https://flagcdn.com/w40/ml.png', englishName: 'Mali' },
  'MN': { name: 'Mongólia', code: 'MN', flag: 'https://flagcdn.com/w40/mn.png', englishName: 'Mongolia' },
  'MO': { name: 'Macau', code: 'MO', flag: 'https://flagcdn.com/w40/mo.png', englishName: 'Macao' },
  'MP': { name: 'Ilhas Marianas do Norte', code: 'MP', flag: 'https://flagcdn.com/w40/mp.png', englishName: 'Northern Mariana Islands' },
  'MQ': { name: 'Martinica', code: 'MQ', flag: 'https://flagcdn.com/w40/mq.png', englishName: 'Martinique' },
  'MR': { name: 'Mauritânia', code: 'MR', flag: 'https://flagcdn.com/w40/mr.png', englishName: 'Mauritania' },
  'MS': { name: 'Montserrat', code: 'MS', flag: 'https://flagcdn.com/w40/ms.png', englishName: 'Montserrat' },
  'MT': { name: 'Malta', code: 'MT', flag: 'https://flagcdn.com/w40/mt.png', englishName: 'Malta' },
  'MU': { name: 'Maurício', code: 'MU', flag: 'https://flagcdn.com/w40/mu.png', englishName: 'Mauritius' },
  'MV': { name: 'Maldivas', code: 'MV', flag: 'https://flagcdn.com/w40/mv.png', englishName: 'Maldives' },
  'MW': { name: 'Malawi', code: 'MW', flag: 'https://flagcdn.com/w40/mw.png', englishName: 'Malawi' },
  'MX': { name: 'México', code: 'MX', flag: 'https://flagcdn.com/w40/mx.png', englishName: 'Mexico' },
  'MY': { name: 'Malásia', code: 'MY', flag: 'https://flagcdn.com/w40/my.png', englishName: 'Malaysia' },
  'MZ': { name: 'Moçambique', code: 'MZ', flag: 'https://flagcdn.com/w40/mz.png', englishName: 'Mozambique' },
  'NA': { name: 'Namíbia', code: 'NA', flag: 'https://flagcdn.com/w40/na.png', englishName: 'Namibia' },
  'NC': { name: 'Nova Caledônia', code: 'NC', flag: 'https://flagcdn.com/w40/nc.png', englishName: 'New Caledonia' },
  'NE': { name: 'Níger', code: 'NE', flag: 'https://flagcdn.com/w40/ne.png', englishName: 'Niger' },
  'NF': { name: 'Ilha Norfolk', code: 'NF', flag: 'https://flagcdn.com/w40/nf.png', englishName: 'Norfolk Island' },
  'NG': { name: 'Nigéria', code: 'NG', flag: 'https://flagcdn.com/w40/ng.png', englishName: 'Nigeria' },
  'NI': { name: 'Nicarágua', code: 'NI', flag: 'https://flagcdn.com/w40/ni.png', englishName: 'Nicaragua' },
  'NL': { name: 'Holanda', code: 'NL', flag: 'https://flagcdn.com/w40/nl.png', englishName: 'Netherlands' },
  'NO': { name: 'Noruega', code: 'NO', flag: 'https://flagcdn.com/w40/no.png', englishName: 'Norway' },
  'NP': { name: 'Nepal', code: 'NP', flag: 'https://flagcdn.com/w40/np.png', englishName: 'Nepal' },
  'NR': { name: 'Nauru', code: 'NR', flag: 'https://flagcdn.com/w40/nr.png', englishName: 'Nauru' },
  'NU': { name: 'Niue', code: 'NU', flag: 'https://flagcdn.com/w40/nu.png', englishName: 'Niue' },
  'NZ': { name: 'Nova Zelândia', code: 'NZ', flag: 'https://flagcdn.com/w40/nz.png', englishName: 'New Zealand' },
  'OM': { name: 'Omã', code: 'OM', flag: 'https://flagcdn.com/w40/om.png', englishName: 'Oman' },
  'PA': { name: 'Panamá', code: 'PA', flag: 'https://flagcdn.com/w40/pa.png', englishName: 'Panama' },
  'PE': { name: 'Peru', code: 'PE', flag: 'https://flagcdn.com/w40/pe.png', englishName: 'Peru' },
  'PF': { name: 'Polinésia Francesa', code: 'PF', flag: 'https://flagcdn.com/w40/pf.png', englishName: 'French Polynesia' },
  'PG': { name: 'Papua-Nova Guiné', code: 'PG', flag: 'https://flagcdn.com/w40/pg.png', englishName: 'Papua New Guinea' },
  'PH': { name: 'Filipinas', code: 'PH', flag: 'https://flagcdn.com/w40/ph.png', englishName: 'Philippines' },
  'PK': { name: 'Paquistão', code: 'PK', flag: 'https://flagcdn.com/w40/pk.png', englishName: 'Pakistan' },
  'PL': { name: 'Polônia', code: 'PL', flag: 'https://flagcdn.com/w40/pl.png', englishName: 'Poland' },
  'PM': { name: 'Saint Pierre e Miquelon', code: 'PM', flag: 'https://flagcdn.com/w40/pm.png', englishName: 'Saint Pierre and Miquelon' },
  'PN': { name: 'Ilhas Pitcairn', code: 'PN', flag: 'https://flagcdn.com/w40/pn.png', englishName: 'Pitcairn' },
  'PR': { name: 'Porto Rico', code: 'PR', flag: 'https://flagcdn.com/w40/pr.png', englishName: 'Puerto Rico' },
  'PS': { name: 'Palestina', code: 'PS', flag: 'https://flagcdn.com/w40/ps.png', englishName: 'Palestine' },
  'PT': { name: 'Portugal', code: 'PT', flag: 'https://flagcdn.com/w40/pt.png', englishName: 'Portugal' },
  'PW': { name: 'Palau', code: 'PW', flag: 'https://flagcdn.com/w40/pw.png', englishName: 'Palau' },
  'PY': { name: 'Paraguai', code: 'PY', flag: 'https://flagcdn.com/w40/py.png', englishName: 'Paraguay' },
  'QA': { name: 'Catar', code: 'QA', flag: 'https://flagcdn.com/w40/qa.png', englishName: 'Qatar' },
  'RE': { name: 'Reunião', code: 'RE', flag: 'https://flagcdn.com/w40/re.png', englishName: 'Réunion' },
  'RO': { name: 'Romênia', code: 'RO', flag: 'https://flagcdn.com/w40/ro.png', englishName: 'Romania' },
  'RS': { name: 'Sérvia', code: 'RS', flag: 'https://flagcdn.com/w40/rs.png', englishName: 'Serbia' },
  'RU': { name: 'Rússia', code: 'RU', flag: 'https://flagcdn.com/w40/ru.png', englishName: 'Russia' },
  'RW': { name: 'Ruanda', code: 'RW', flag: 'https://flagcdn.com/w40/rw.png', englishName: 'Rwanda' },
  'SA': { name: 'Arábia Saudita', code: 'SA', flag: 'https://flagcdn.com/w40/sa.png', englishName: 'Saudi Arabia' },
  'SB': { name: 'Ilhas Salomão', code: 'SB', flag: 'https://flagcdn.com/w40/sb.png', englishName: 'Solomon Islands' },
  'SC': { name: 'Seicheles', code: 'SC', flag: 'https://flagcdn.com/w40/sc.png', englishName: 'Seychelles' },
  'SD': { name: 'Sudão', code: 'SD', flag: 'https://flagcdn.com/w40/sd.png', englishName: 'Sudan' },
  'SE': { name: 'Suécia', code: 'SE', flag: 'https://flagcdn.com/w40/se.png', englishName: 'Sweden' },
  'SG': { name: 'Singapura', code: 'SG', flag: 'https://flagcdn.com/w40/sg.png', englishName: 'Singapore' },
  'SH': { name: 'Santa Helena', code: 'SH', flag: 'https://flagcdn.com/w40/sh.png', englishName: 'Saint Helena' },
  'SI': { name: 'Eslovênia', code: 'SI', flag: 'https://flagcdn.com/w40/si.png', englishName: 'Slovenia' },
  'SJ': { name: 'Svalbard e Jan Mayen', code: 'SJ', flag: 'https://flagcdn.com/w40/sj.png', englishName: 'Svalbard and Jan Mayen' },
  'SK': { name: 'Eslováquia', code: 'SK', flag: 'https://flagcdn.com/w40/sk.png', englishName: 'Slovakia' },
  'SL': { name: 'Serra Leoa', code: 'SL', flag: 'https://flagcdn.com/w40/sl.png', englishName: 'Sierra Leone' },
  'SM': { name: 'San Marino', code: 'SM', flag: 'https://flagcdn.com/w40/sm.png', englishName: 'San Marino' },
  'SN': { name: 'Senegal', code: 'SN', flag: 'https://flagcdn.com/w40/sn.png', englishName: 'Senegal' },
  'SO': { name: 'Somália', code: 'SO', flag: 'https://flagcdn.com/w40/so.png', englishName: 'Somalia' },
  'SR': { name: 'Suriname', code: 'SR', flag: 'https://flagcdn.com/w40/sr.png', englishName: 'Suriname' },
  'SS': { name: 'Sudão do Sul', code: 'SS', flag: 'https://flagcdn.com/w40/ss.png', englishName: 'South Sudan' },
  'ST': { name: 'São Tomé e Príncipe', code: 'ST', flag: 'https://flagcdn.com/w40/st.png', englishName: 'Sao Tome and Principe' },
  'SV': { name: 'El Salvador', code: 'SV', flag: 'https://flagcdn.com/w40/sv.png', englishName: 'El Salvador' },
  'SX': { name: 'São Martinho (Holanda)', code: 'SX', flag: 'https://flagcdn.com/w40/sx.png', englishName: 'Sint Maarten' },
  'SY': { name: 'Síria', code: 'SY', flag: 'https://flagcdn.com/w40/sy.png', englishName: 'Syria' },
  'TC': { name: 'Ilhas Turcas e Caicos', code: 'TC', flag: 'https://flagcdn.com/w40/tc.png', englishName: 'Turks and Caicos Islands' },
  'TD': { name: 'Chade', code: 'TD', flag: 'https://flagcdn.com/w40/td.png', englishName: 'Chad' },
  'TF': { name: 'Territórios Franceses do Sul', code: 'TF', flag: 'https://flagcdn.com/w40/tf.png', englishName: 'French Southern Territories' },
  'TG': { name: 'Togo', code: 'TG', flag: 'https://flagcdn.com/w40/tg.png', englishName: 'Togo' },
  'TH': { name: 'Tailândia', code: 'TH', flag: 'https://flagcdn.com/w40/th.png', englishName: 'Thailand' },
  'TJ': { name: 'Tajiquistão', code: 'TJ', flag: 'https://flagcdn.com/w40/tj.png', englishName: 'Tajikistan' },
  'TK': { name: 'Tokelau', code: 'TK', flag: 'https://flagcdn.com/w40/tk.png', englishName: 'Tokelau' },
  'TL': { name: 'Timor-Leste', code: 'TL', flag: 'https://flagcdn.com/w40/tl.png', englishName: 'Timor-Leste' },
  'TM': { name: 'Turcomenistão', code: 'TM', flag: 'https://flagcdn.com/w40/tm.png', englishName: 'Turkmenistan' },
  'TN': { name: 'Tunísia', code: 'TN', flag: 'https://flagcdn.com/w40/tn.png', englishName: 'Tunisia' },
  'TO': { name: 'Tonga', code: 'TO', flag: 'https://flagcdn.com/w40/to.png', englishName: 'Tonga' },
  'TR': { name: 'Turquia', code: 'TR', flag: 'https://flagcdn.com/w40/tr.png', englishName: 'Turkey' },
  'TT': { name: 'Trinidad e Tobago', code: 'TT', flag: 'https://flagcdn.com/w40/tt.png', englishName: 'Trinidad and Tobago' },
  'TV': { name: 'Tuvalu', code: 'TV', flag: 'https://flagcdn.com/w40/tv.png', englishName: 'Tuvalu' },
  'TW': { name: 'Taiwan', code: 'TW', flag: 'https://flagcdn.com/w40/tw.png', englishName: 'Taiwan' },
  'TZ': { name: 'Tanzânia', code: 'TZ', flag: 'https://flagcdn.com/w40/tz.png', englishName: 'Tanzania' },
  'UA': { name: 'Ucrânia', code: 'UA', flag: 'https://flagcdn.com/w40/ua.png', englishName: 'Ukraine' },
  'UG': { name: 'Uganda', code: 'UG', flag: 'https://flagcdn.com/w40/ug.png', englishName: 'Uganda' },
  'UM': { name: 'Ilhas Menores Distantes dos EUA', code: 'UM', flag: 'https://flagcdn.com/w40/um.png', englishName: 'United States Minor Outlying Islands' },
  'US': { name: 'Estados Unidos', code: 'US', flag: 'https://flagcdn.com/w40/us.png', englishName: 'United States' },
  'UY': { name: 'Uruguai', code: 'UY', flag: 'https://flagcdn.com/w40/uy.png', englishName: 'Uruguay' },
  'UZ': { name: 'Uzbequistão', code: 'UZ', flag: 'https://flagcdn.com/w40/uz.png', englishName: 'Uzbekistan' },
  'VA': { name: 'Vaticano', code: 'VA', flag: 'https://flagcdn.com/w40/va.png', englishName: 'Holy See' },
  'VC': { name: 'São Vicente e Granadinas', code: 'VC', flag: 'https://flagcdn.com/w40/vc.png', englishName: 'Saint Vincent and the Grenadines' },
  'VE': { name: 'Venezuela', code: 'VE', flag: 'https://flagcdn.com/w40/ve.png', englishName: 'Venezuela' },
  'VG': { name: 'Ilhas Virgens Britânicas', code: 'VG', flag: 'https://flagcdn.com/w40/vg.png', englishName: 'British Virgin Islands' },
  'VI': { name: 'Ilhas Virgens Americanas', code: 'VI', flag: 'https://flagcdn.com/w40/vi.png', englishName: 'U.S. Virgin Islands' },
  'VN': { name: 'Vietnã', code: 'VN', flag: 'https://flagcdn.com/w40/vn.png', englishName: 'Vietnam' },
  'VU': { name: 'Vanuatu', code: 'VU', flag: 'https://flagcdn.com/w40/vu.png', englishName: 'Vanuatu' },
  'WF': { name: 'Wallis e Futuna', code: 'WF', flag: 'https://flagcdn.com/w40/wf.png', englishName: 'Wallis and Futuna' },
  'WS': { name: 'Samoa', code: 'WS', flag: 'https://flagcdn.com/w40/ws.png', englishName: 'Samoa' },
  'YE': { name: 'Iêmen', code: 'YE', flag: 'https://flagcdn.com/w40/ye.png', englishName: 'Yemen' },
  'YT': { name: 'Mayotte', code: 'YT', flag: 'https://flagcdn.com/w40/yt.png', englishName: 'Mayotte' },
  'ZA': { name: 'África do Sul', code: 'ZA', flag: 'https://flagcdn.com/w40/za.png', englishName: 'South Africa' },
  'ZM': { name: 'Zâmbia', code: 'ZM', flag: 'https://flagcdn.com/w40/zm.png', englishName: 'Zambia' },
  'ZW': { name: 'Zimbábue', code: 'ZW', flag: 'https://flagcdn.com/w40/zw.png', englishName: 'Zimbabwe' },
};

/**
 * Países históricos, extintos ou aliases antigos
 * Indexados pelo NOME EXATO que vem do banco de dados
 */
export const extinctCountryFlags = {
  // Países extintos
  'Czechoslovakia': {
    code: 'CZE',
    name: 'Tchecoslováquia',
    flag: 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cb/Flag_of_the_Czech_Republic.svg/1920px-Flag_of_the_Czech_Republic.svg.png',
    englishName: 'Czechoslovakia'
  },

  'East Germany': {
    code: 'GDR',
    name: 'Alemanha Oriental',
    flag: 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/97/Flag_of_the_German_Democratic_Republic.svg/2560px-Flag_of_the_German_Democratic_Republic.svg.png',
    englishName: 'East Germany'
  },

  'Soviet Union': {
    code: 'SU',
    name: 'União Soviética',
    flag: 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a9/Flag_of_the_Soviet_Union.svg/2560px-Flag_of_the_Soviet_Union.svg.png',
    englishName: 'Soviet Union'
  },

  'Yugoslavia': {
    code: 'YU',
    name: 'Iugoslávia',
    flag: 'https://upload.wikimedia.org/wikipedia/commons/thumb/6/61/Flag_of_Yugoslavia_%281946-1992%29.svg/2560px-Flag_of_Yugoslavia_%281946-1992%29.svg.png',
    englishName: 'Yugoslavia'
  },

  'Serbia and Montenegro': {
    code: 'SAM',
    name: 'Sérvia e Montenegro',
    flag: 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3e/Flag_of_Serbia_and_Montenegro_%281992%E2%80%932006%29.svg/2560px-Flag_of_Serbia_and_Montenegro_%281992%E2%80%932006%29.svg.png',
    englishName: 'Serbia and Montenegro'
  },

  'Netherlands Antilles': {
    code: 'AN',
    name: 'Antilhas Holandesas',
    flag: 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ae/Flag_of_the_Netherlands_Antilles_%281986%E2%80%932010%29.svg/1920px-Flag_of_the_Netherlands_Antilles_%281986%E2%80%932010%29.svg.png',
    englishName: 'Netherlands Antilles'
  },

  // Nomes antigos que foram atualizados
  'Libyan Arab Jamahiriya': { name: 'Líbia', code: 'LY', flag: 'https://flagcdn.com/w40/ly.png', englishName: 'Libyan Arab Jamahiriya' },
  'Macedonia': { name: 'Macedônia do Norte', code: 'MK', flag: 'https://flagcdn.com/w40/mk.png', englishName: 'Macedonia' },
  'Macao': { name: 'Macau', code: 'MO', flag: 'https://flagcdn.com/w40/mo.png', englishName: 'Macao' },
  "Lao People's Democratic Republic": { name: 'Laos', code: 'LA', flag: 'https://flagcdn.com/w40/la.png', englishName: "Lao People's Democratic Republic" },
  'Holy See': { name: 'Vaticano', code: 'VA', flag: 'https://flagcdn.com/w40/va.png', englishName: 'Holy See' },

  // Aliases comuns
  'United States of America': { name: 'Estados Unidos', code: 'US', flag: 'https://flagcdn.com/w40/us.png', englishName: 'United States of America' },
  'USA': { name: 'Estados Unidos', code: 'US', flag: 'https://flagcdn.com/w40/us.png', englishName: 'USA' },
  'UK': { name: 'Reino Unido', code: 'GB', flag: 'https://flagcdn.com/w40/gb.png', englishName: 'UK' },
  'England': { name: 'Inglaterra', code: 'GB', flag: 'https://flagcdn.com/w40/gb.png', englishName: 'England' },
  'Scotland': { name: 'Escócia', code: 'GB', flag: 'https://flagcdn.com/w40/gb.png', englishName: 'Scotland' },
  'Wales': { name: 'País de Gales', code: 'GB', flag: 'https://flagcdn.com/w40/gb.png', englishName: 'Wales' },
  'Northern Ireland': { name: 'Irlanda do Norte', code: 'GB', flag: 'https://flagcdn.com/w40/gb.png', englishName: 'Northern Ireland' }
}

/**
 * Resolve país moderno ou histórico de forma universal
 * 
 * @param {null|string} countryCode - Código ISO do país (ex: 'US', 'BR')
 * @param {null|string} countryName - Nome do país que vem do banco de dados
 * @returns {object} Objeto com { name, code?, flag }
 */
export function resolveCountry(countryCode, countryName) {
  // 1. Tenta buscar pelo código ISO em países modernos
  if (countryCode && countryFlags[countryCode.toUpperCase()]) {
    return countryFlags[countryCode.toUpperCase()]
  }

  // 2. Tenta buscar pelo nome em países históricos/extintos (case-sensitive primeiro)
  if (countryName && extinctCountryFlags[countryName]) {
    return extinctCountryFlags[countryName]
  }

  // 2.b Também tenta busca case-insensitive nas chaves de extintos
  if (countryName) {
    const nameLower = countryName.trim().toLowerCase()
    for (const k in extinctCountryFlags) {
      if (k && k.toLowerCase() === nameLower) {
        return extinctCountryFlags[k]
      }
      const v = extinctCountryFlags[k]
      if (v && v.englishName && v.englishName.toLowerCase() === nameLower) {
        return v
      }
      if (v && v.name && v.name.toLowerCase() === nameLower) {
        return v
      }
    }
  }

  // 3. Busca apenas pelo nome (procura em inglês e pt-BR dentro do mapa moderno)
  if (countryCode === null && countryName !== null) {
    const nameLower = countryName.trim().toLowerCase()

    // procura em countryFlags (valores)
    for (const code in countryFlags) {
      const county = countryFlags[code]
      if (!county) {
        continue
      }

      if (county.name && county.name.toLowerCase() === nameLower) {
        return county
      }

      if (county.englishName && county.englishName.toLowerCase() === nameLower) {
        return county
      }

      // correspondência flexível (ex: 'United States of America' x 'United States')
      if (county.englishName) {
        const en = county.englishName.toLowerCase()
        if (nameLower.includes(en) || en.includes(nameLower)) return county
      }
    }
  }

  // 4. Fallback: retorna genérico
  return {
    name: countryName || countryCode || 'Desconhecido',
    code: countryCode,
    flag: '/img/flags/placeholder.png'
  }
}

/**
 * Retorna a URL da bandeira SVG (compatibilidade com código antigo)
 * @param {string} countryCode Código do país (ex: 'BR', 'US')
 * @returns {string} URL da imagem da bandeira
 */
export function getCountryFlag(countryCode) {
  const country = resolveCountry(countryCode, null)
  return country.flag
}

/**
 * Retorna apenas o nome do país (compatibilidade com código antigo)
 * @param {string} countryCode Código do país (ex: 'BR', 'US')
 * @returns {string} Nome do país (ex: 'Brasil')
 */
export function getCountryName(countryCode) {
  const country = resolveCountry(countryCode, null)
  return country.name
}

/**
 * Retorna o objeto completo do país (compatibilidade com código antigo)
 * @param {string} countryCode Código do país (ex: 'BR', 'US')
 * @returns {object} Objeto com name, code, flag
 */
export function getCountryData(countryCode) {
  return resolveCountry(countryCode, null)
}

export default { countryFlags, extinctCountryFlags, resolveCountry }
