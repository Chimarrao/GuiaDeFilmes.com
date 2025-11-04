import sys
import json
from simplejustwatchapi.justwatch import search

# Recebe título ou imdb_id como argumento
query = sys.argv[1] if len(sys.argv) > 1 else None
imdb_id_filter = sys.argv[2] if len(sys.argv) > 2 else None

if not query:
    print(json.dumps({"error": "Nenhum título informado"}))
    sys.exit(1)

# Busca no JustWatch Brasil em português
results = search(query, country="BR", language="pt", count=5, best_only=True)

# Prepara saída filtrando pelo imdb_id se informado
output = []
for entry in results:
    if imdb_id_filter and entry.imdb_id != imdb_id_filter:
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
