import json, sys, re
from playwright.sync_api import sync_playwright

B = "http://localhost:8080"
CODES = [l.split(';')[1].strip() for l in open('BG_karacsonyi_kuponkodok_200db.csv', encoding='utf-8-sig').read().splitlines()[1:] if l.strip()]
res = {}

def run_case(br, name, cart, town='Budapest', zip_='1111', email='teszt@example.com', coupon=None, place=True):
    ctx = br.new_context(viewport={"width": 1280, "height": 900})
    pg = ctx.new_page()
    errs = []
    pg.on("pageerror", lambda e: errs.append(str(e)))
    pg.goto(B + "/kosar/", wait_until="networkidle")
    pg.evaluate("([c,t]) => { localStorage.setItem('bg_cart', JSON.stringify(c)); localStorage.setItem('bg_town', JSON.stringify(t)); }", [cart, town])
    pg.goto(B + "/kosar/", wait_until="networkidle")
    btn = pg.locator("#cart-root a", has_text="Tovább a pénztárhoz")
    out = {"cart_button": btn.count()}
    if not btn.count():
        out["cart_text"] = pg.locator("#cart-root").inner_text()[:300]
        ctx.close(); return out
    btn.first.click()
    pg.wait_for_url(re.compile(r".*/penztar/.*"), timeout=15000)
    pg.wait_for_load_state("networkidle")
    if coupon:
        pg.locator("a.showcoupon").click()
        pg.fill("#coupon_code", coupon)
        pg.locator("button[name=apply_coupon]").click()
        pg.wait_for_timeout(2500)
        out["coupon_notice"] = " | ".join(pg.locator(".woocommerce-error, .woocommerce-message, .coupon-error-notice, #coupon-error-notice, .wc-block-components-notice-banner").all_inner_texts())[:300]
    pg.fill("#billing_last_name", "Teszt")
    pg.fill("#billing_first_name", "Elek")
    pg.fill("#billing_postcode", zip_)
    pg.select_option("#billing_city", town)
    pg.wait_for_timeout(300)
    out["day_options"] = pg.locator("#bg_day option").all_inner_texts()
    out["day_selected"] = pg.eval_on_selector("#bg_day", "e => e.value")
    pg.fill("#billing_address_1", "Teszt utca 1.")
    pg.fill("#billing_phone", "+36301234567")
    pg.fill("#billing_email", email)
    pg.wait_for_timeout(1500)
    out["total"] = pg.locator(".order-total .amount").last.inner_text() if pg.locator(".order-total .amount").count() else None
    if place:
        if pg.locator("#terms").count():
            pg.check("#terms")
        pg.locator("#place_order").click()
        try:
            pg.wait_for_url(re.compile(r".*order-received.*"), timeout=12000)
            pg.wait_for_load_state("networkidle")
            out["result"] = "ORDER OK"
            out["delivery_line"] = pg.locator(".bg-delivery-line").inner_text() if pg.locator(".bg-delivery-line").count() else None
            out["local_cart_after"] = pg.evaluate("localStorage.getItem('bg_cart')")
        except Exception:
            pg.wait_for_timeout(1500)
            out["result"] = "ERROR: " + " | ".join(pg.locator(".woocommerce-error li, .woocommerce-NoticeGroup li").all_inner_texts())[:500]
    out["js_errors"] = errs
    pg.screenshot(path=f"/home/claude/bgwp/test/e2e-{name}.png", full_page=True)
    ctx.close()
    return out

with sync_playwright() as p:
    br = p.chromium.launch(executable_path="/usr/bin/google-chrome", args=["--no-sandbox"])
    res["A_normal"] = run_case(br, "A", [{"id": "csontos-tarja", "qty": 2}, {"id": "kolozsvari-szalonna", "qty": 1, "opt": "Szeletelve"}], email="a@example.com")
    res["D_coupon_low"] = run_case(br, "D", [{"id": "csontos-tarja", "qty": 2}], coupon=CODES[5], place=False)
    res["F_same_code"] = run_case(br, "F", [{"id": "csontos-tarja", "qty": 5}], coupon=CODES[0], email="masik@example.com", place=False)
    br.close()
print(json.dumps(res, ensure_ascii=False, indent=1))
