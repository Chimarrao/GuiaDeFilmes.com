import sys
import json
import io
from simplejustwatchapi.justwatch import search

# Força UTF-8 no stdout (importante no Windows)
if sys.stdout.encoding != 'utf-8':
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# Recebe título, imdb_id e/ou ano como argumentos
query = sys.argv[1] if len(sys.argv) > 1 else None
imdb_id_filter = sys.argv[2] if len(sys.argv) > 2 and sys.argv[2] else None
year_filter = None
if len(sys.argv) > 3 and sys.argv[3]:
    try:
        year_filter = int(sys.argv[3])
    except ValueError:
        pass  # Ignora se não for um número válido

if not query:
    print(json.dumps({"error": "Nenhum título informado"}))
    sys.exit(1)

# Debug: log dos filtros aplicados (comentar em produção se necessário)
# import sys
# print(f"DEBUG: query={query}, imdb_id={imdb_id_filter}, year={year_filter}", file=sys.stderr)

# Busca no JustWatch Brasil em português
results = search(query, country="BR", language="pt", count=5, best_only=True)

# Prepara saída filtrando pelo imdb_id e/ou ano se informado
output = []
for entry in results:
    # Filtro por IMDB ID
    if imdb_id_filter and entry.imdb_id != imdb_id_filter:
        continue
    
    # Filtro por ano de lançamento
    if year_filter and entry.release_year != year_filter:
        continue
    data = {
        "title": entry.title,
        "imdb_id": entry.imdb_id,
        "release_year": entry.release_year,
        "release_date": entry.release_date,
        "runtime_minutes": entry.runtime_minutes,
        "genres": entry.genres,
        "url": entry.url,
        "poster": entry.poster,
        "offers": [
            {
                "platform": offer.name,
                "type": offer.monetization_type,
                "quality": offer.presentation_type,
                "price": offer.price_string,
                "currency": offer.price_currency,
                "url": offer.url,
                "icon": offer.icon
            }
            for offer in entry.offers
        ]
    }
    output.append(data)

# Retorna JSON
print(json.dumps(output, ensure_ascii=False))
