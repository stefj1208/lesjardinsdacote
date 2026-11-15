"""Construit la plaquette PDF à partir du gabarit HTML et des chiffres du catalogue.
Usage : python3 build.py chiffres.json  (chiffres calculés par le site : voir deploy/plaquette/README)."""
import json, re, sys, asyncio, pathlib
from playwright.async_api import async_playwright
d = pathlib.Path(__file__).resolve().parent
root = d.parent.parent
n = json.load(open(sys.argv[1]))
eur = lambda x: (f"{x:,.2f}".replace(',', ' ').replace('.', ',') + ' €').replace(',00 €', ' €')
h = (d / 'collectivites.html').read_text(encoding='utf-8')
vals = {'N_SEEDS': str(n['n_seeds']), 'PACK_N': str(n['pack_n']), 'PACK_PRICE': eur(n['pack_price']), 'PACK_PRICE_R': eur(n['pack_price']),
        'FEE': eur(n['fee']), 'PACK_ALLIN': eur(n['all_in']), 'PACK_MARKET': eur(n['market']), 'PACK_SAVE': eur(n['save']),
        'PCT': str(n['pct']), 'MARKET': eur(n['unit_market']), 'BAR_H': str(max(12, round(n['all_in'] / n['market'] * 100)))}
for k, v in vals.items(): h = h.replace('{{' + k + '}}', v)
assert '{{' not in h, re.findall(r'\{\{\w+\}\}', h)
qr = re.sub(r'<\?xml.*?\?>', '', (d / 'qr.svg').read_text(encoding='utf-8'))
tmp = d / '_build.html'; tmp.write_text(h.replace('QRCODE', qr), encoding='utf-8')
async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch(); pg = await b.new_page(viewport={'width': 794, 'height': 1123})
        await pg.goto(tmp.as_uri()); await pg.wait_for_timeout(800)
        over = await pg.evaluate('''() => [...document.querySelectorAll('.page')].map((p, i) => {
            const lim = Math.min(...[...p.querySelectorAll(':scope > .foot, :scope > .cta')].map(e => e.getBoundingClientRect().top));
            let max = 0; p.querySelectorAll(':scope > *:not(.foot):not(.cta)').forEach(e => { max = Math.max(max, e.getBoundingClientRect().bottom); });
            return [i + 1, Math.round(lim - max)]; })''')
        print('marge avant pied de page (px) :', over)
        await pg.pdf(path=str(root / 'public_html/assets/docs/plaquette-collectivites.pdf'), format='A4', print_background=True, prefer_css_page_size=True)
        await b.close()
asyncio.run(main()); tmp.unlink()
print('ok', vals)
