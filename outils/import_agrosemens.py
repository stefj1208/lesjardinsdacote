#!/usr/bin/env python3
"""
Extraction de fiches produits Agrosemens vers le CSV d'import de la plateforme.

À lancer sur votre ordinateur (pas sur l'hébergement) :

    pip install requests beautifulsoup4
    python3 import_agrosemens.py urls.txt sortie.csv

- urls.txt : une URL de fiche produit agrosemens.com par ligne.
- sortie.csv : à importer dans Back-office > Import CSV.

Important
- Le « catalogue 2027 » en ligne est un catalogue feuilletable (images) : il ne se
  lit pas automatiquement. Ce script lit les fiches produits de la boutique.
- Les prix publics de la boutique ne sont pas vos prix professionnels. La source la
  plus fiable reste la grille tarifaire pro (Excel) fournie par Agrosemens : collez-la
  dans le modèle CSV (colonne « conditionnements »).
- Vérifiez les conditions d'utilisation du site et limitez la cadence (pause de
  2 secondes entre deux pages par défaut).
- Relisez toujours la prévisualisation dans le back-office avant de valider.
"""
import csv
import json
import re
import sys
import time

try:
    import requests
    from bs4 import BeautifulSoup
except ImportError:
    sys.exit("Installez d'abord les dépendances : pip install requests beautifulsoup4")

COLUMNS = ['sku', 'nom', 'variete', 'nom_latin', 'famille', 'categorie', 'unite', 'pmg', 'bio', 'exposition', 'eau', 'difficulte',
           'mois_semis', 'profondeur', 'description', 'conseils_semis', 'image_url', 'url_source', 'conditionnements']
HEADERS = {'User-Agent': "LesJardinsDaCote-import/1.0 (association, usage interne)"}
MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre']


def clean(t):
    return re.sub(r'\s+', ' ', (t or '')).strip()


def parse_qty(label):
    """'Sachet de 250 graines' -> (250, 'graines') ; '1 kg' -> (1000, 'g') ; '50g' -> (50, 'g')."""
    s = label.lower().replace(',', '.')
    m = re.search(r'(\d+(?:\.\d+)?)\s*(kg|g|gr|grammes?|graines?|gn|l|litres?)\b', s)
    if not m:
        return None, None
    q, u = float(m.group(1)), m.group(2)
    if u == 'kg':
        return q * 1000, 'g'
    if u in ('g', 'gr', 'gramme', 'grammes'):
        return q, 'g'
    if u in ('l', 'litre', 'litres'):
        return q, 'l'
    return q, 'graines'


def json_ld(soup):
    out = []
    for tag in soup.find_all('script', type='application/ld+json'):
        try:
            data = json.loads(tag.string or '')
        except (json.JSONDecodeError, TypeError):
            continue
        items = data if isinstance(data, list) else data.get('@graph', [data])
        out += [i for i in items if isinstance(i, dict) and 'Product' in str(i.get('@type'))]
    return out


def extract(url):
    resp = requests.get(url, headers=HEADERS, timeout=30)
    resp.raise_for_status()
    soup = BeautifulSoup(resp.content, 'html.parser', from_encoding='utf-8')
    row = dict.fromkeys(COLUMNS, '')
    row.update({'categorie': 'semences', 'bio': 'oui', 'url_source': url})

    products = json_ld(soup)
    p = products[0] if products else {}
    title = clean(p.get('name') or (soup.find('h1').get_text() if soup.find('h1') else ''))
    # "Tomate Cœur de Bœuf" -> nom + variété (premier mot = espèce)
    parts = title.split(' ', 1)
    row['nom'], row['variete'] = parts[0], (parts[1] if len(parts) > 1 else '')
    row['sku'] = clean(str(p.get('sku') or p.get('mpn') or ''))
    img = p.get('image')
    row['image_url'] = (img[0] if isinstance(img, list) else img) or (soup.find('meta', property='og:image') or {}).get('content', '')
    row['description'] = clean(p.get('description') or (soup.find('meta', attrs={'name': 'description'}) or {}).get('content', ''))[:1000]

    text = clean(soup.get_text(' '))
    latin = soup.find(['em', 'i'], string=re.compile(r'^[A-Z][a-z]+ [a-z]+'))
    if latin:
        row['nom_latin'] = clean(latin.get_text())
    m = re.search(r'PMG\s*[:=]?\s*(\d+(?:[.,]\d+)?)\s*g', text, re.I)
    if m:
        row['pmg'] = m.group(1).replace('.', ',')
    m = re.search(r'[Ss]emis\s*:?\s*(?:de\s+)?(' + '|'.join(MONTHS) + r')\s+(?:à|a)\s+(' + '|'.join(MONTHS) + ')', text, re.I)
    if m:
        a, b = MONTHS.index(m.group(1).lower()) + 1, MONTHS.index(m.group(2).lower()) + 1
        row['mois_semis'] = ','.join(str(x) for x in (range(a, b + 1) if a <= b else list(range(a, 13)) + list(range(1, b + 1))))

    # Conditionnements : offres JSON-LD, sinon options de liste déroulante "250 graines - 6,10 €"
    packs, unit = [], None
    offers = p.get('offers') or []
    offers = offers if isinstance(offers, list) else [offers]
    for o in offers:
        label = clean(o.get('name') or o.get('description') or '')
        q, u = parse_qty(label)
        if q and o.get('price'):
            packs.append((label, q, float(str(o['price']).replace(',', '.'))))
            unit = unit or u
    if not packs:
        for opt in soup.select('select option, .product-variants li, .attribute_list li'):
            t = clean(opt.get_text())
            q, u = parse_qty(t)
            pm = re.search(r'(\d+[.,]\d{2})\s*€', t)
            if q and pm:
                packs.append((t.split('-')[0].strip(), q, float(pm.group(1).replace(',', '.'))))
                unit = unit or u
    row['unite'] = unit or 'g'
    row['conditionnements'] = '|'.join(f"{l}={q:g}={c:.2f}".replace('.', ',') for l, q, c in packs)
    return row


def main():
    if len(sys.argv) < 3:
        sys.exit(__doc__)
    urls = [u.strip() for u in open(sys.argv[1], encoding='utf-8') if u.strip().startswith('http')]
    with open(sys.argv[2], 'w', newline='', encoding='utf-8-sig') as f:
        w = csv.DictWriter(f, fieldnames=COLUMNS, delimiter=';')
        w.writeheader()
        for i, url in enumerate(urls, 1):
            try:
                row = extract(url)
                w.writerow(row)
                flag = '' if row['conditionnements'] else '  ⚠ prix à compléter'
                print(f"[{i}/{len(urls)}] {row['nom']} {row['variete']}{flag}")
            except Exception as e:  # noqa: BLE001
                print(f"[{i}/{len(urls)}] ÉCHEC {url} : {e}")
            time.sleep(2)
    print(f"\nTerminé : {sys.argv[2]}. Complétez les cases vides puis importez le fichier dans le back-office.")


if __name__ == '__main__':
    main()
