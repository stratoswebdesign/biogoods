import sys, json
from playwright.sync_api import sync_playwright
B="http://localhost:8080"
out={}
with sync_playwright() as p:
    br=p.chromium.launch(executable_path="/usr/bin/google-chrome", args=["--no-sandbox"])
    pg=br.new_page(viewport={"width":1366,"height":900})
    errs=[]
    pg.on("pageerror", lambda e: errs.append(str(e)))
    pg.on("console", lambda m: errs.append("console:"+m.text) if m.type=="error" else None)
    pg.on("response", lambda r: errs.append("404:"+r.url) if r.status>=400 else None)
    for path,name in [("/","home"),("/termekek/","list"),("/kategoria/kolbaszok/","cat"),("/termek/csontos-tarja/","pdp"),("/szallitas/","szall")]:
        errs.clear()
        pg.goto(B+path, wait_until="networkidle")
        pg.screenshot(path=f"/home/claude/bgwp/test/{name}.png", full_page=False)
        out[name]={"errors":list(errs),"h1":pg.locator("h1").first.inner_text() if pg.locator("h1").count() else None,"cards":pg.locator(".pcard").count()}
    br.close()
print(json.dumps(out,ensure_ascii=False,indent=1))
