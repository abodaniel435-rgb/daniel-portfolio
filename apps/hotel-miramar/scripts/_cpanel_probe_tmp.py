"""Temp cPanel probe - will be deleted after run. Lists home/public_html."""
import ssl, urllib.request, urllib.parse, http.cookiejar, json, re, sys

USER = "cityofgoldestate"
PASS = r"ROi]};)LfQu3"

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

def try_basic(host_base):
    print(f"\n== Trying BasicAuth UAPI on {host_base} ==")
    try:
        creds = f"{USER}:{PASS}".encode()
        b64 = __import__('base64').b64encode(creds).decode()
        for api_path in ["/execute/Fileman/get_pwd", "/execute/Fileman/list_files?path=%2Fhome%2Fcityofgoldestate"]:
            url = host_base.rstrip("/") + api_path
            req = urllib.request.Request(url, headers={"Authorization": f"Basic {b64}"})
            try:
                with urllib.request.urlopen(req, context=ctx, timeout=20) as r:
                    body = r.read().decode('utf-8', errors='replace')[:3000]
                    print(f"OK {r.status} {api_path}\n{body[:2000]}")
            except Exception as e:
                # print error body if HTTPError
                try:
                    eb = e.read().decode('utf-8', errors='replace')[:2000] if hasattr(e, 'read') else str(e)
                except Exception:
                    eb = str(e)
                print(f"FAIL {api_path}: {e} | {eb[:1000]}")
    except Exception as e:
        print(f"basic error: {e}")

def try_form_login(host_base):
    print(f"\n== Trying form login on {host_base} ==")
    try:
        cj = http.cookiejar.CookieJar()
        opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), urllib.request.HTTPRedirectHandler())
        login_url = host_base.rstrip("/") + "/login/?login_only=1"
        # cPanel login_only=1 expects user+pass, returns json with redirect
        data = urllib.parse.urlencode({"user": USER, "pass": PASS}).encode()
        req = urllib.request.Request(login_url, data=data, headers={"Content-Type": "application/x-www-form-urlencoded"})
        with opener.open(req, context=ctx, timeout=20) as r:
            body = r.read().decode('utf-8', errors='replace')
            print(f"login status {r.status}: {body[:3000]}")
            print(f"cookies: {[str(c) for c in cj]}")
            try:
                j = json.loads(body)
                if j.get("status") == 1 and j.get("redirect"):
                    redir = j["redirect"]
                    print(f"redirect path: {redir}")
                    # redir like /cpsess1234567890/
                    # try fileman via same host + redir + execute/...
                    base2 = host_base.rstrip("/") + redir.rstrip("/")
                    for api in ["/execute/Fileman/get_pwd", "/execute/Fileman/list_files?path=/home/cityofgoldestate"]:
                        url2 = base2 + api
                        print(f"GET {url2}")
                        req2 = urllib.request.Request(url2)
                        with opener.open(req2, context=ctx, timeout=20) as r2:
                            b2 = r2.read().decode('utf-8', errors='replace')[:4000]
                            print(f"OK {r2.status}: {b2[:3000]}")
                    return True
            except Exception as e:
                print(f"json parse fail: {e}")
    except Exception as e:
        try:
            eb = e.read().decode('utf-8', errors='replace')[:2000] if hasattr(e, 'read') else str(e)
        except Exception:
            eb = str(e)
        print(f"form login FAIL: {e} | {eb[:1500]}")
    return False

for h in ["https://cityofgoldestate.com:2083", "http://cityofgoldestate.com:2082", "https://cityofgoldestate.com"]:
    try_basic(h)

for h in ["https://cityofgoldestate.com:2083", "https://cityofgoldestate.com"]:
    try_form_login(h)
    break
print("\nDONE")
