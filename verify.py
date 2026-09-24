import re
from playwright.sync_api import sync_playwright
with sync_playwright() as p:
    b = p.chromium.launch(headless=True, channel="chrome")
    pg = b.new_context(viewport={"width":900,"height":1400}).new_page()
    pg.goto(f"file://{__import__('os').getcwd()}/index.html", wait_until="load")
    pg.wait_for_timeout(300)
    text = pg.inner_text("body")
    print("=== RAW PARSED TEXT (what a screener sees) ===\n")
    print(text[:1200])
    print("\n...\n")
    # keyword check against the job posting
    KEYWORDS = ["Project Management","Jira","ClickUp","Notion","Agile","Sprint","Release",
                "UAT","risk","stakeholder","AWS","serverless","API","AI","Technical Project Manager"]
    print("=== KEYWORD COVERAGE vs job posting ===")
    for k in KEYWORDS:
        print(f"  {'✅' if k.lower() in text.lower() else '❌ MISSING'}  {k}")
    # watermarks / junk check
    print("\n=== JUNK CHECK ===")
    for bad in ["CVwizard", "cvmkr", "under construction"]:
        print(f"  {'⚠️ FOUND' if bad.lower() in text.lower() else '✅ clean'}  ({bad})")
    pg.pdf(path="resume.pdf", format="A4", print_background=True, margin={"top":"0.4in","bottom":"0.4in","left":"0.4in","right":"0.4in"})
    b.close()
