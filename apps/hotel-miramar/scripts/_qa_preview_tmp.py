import re, urllib.request, urllib.parse, http.cookiejar, os

BASE = 'http://127.0.0.1:5001'
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
STATIC = os.path.join(ROOT, 'app', 'static')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

cj = http.cookiejar.CookieJar()
op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), NoRedirect)

def get(path):
    req = urllib.request.Request(BASE + path, headers={'User-Agent': 'MiramarQA/1.0'})
    try:
        r = op.open(req)
        return r.status, r.headers, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.headers, e.read().decode('utf-8', 'replace')

def post(path, data):
    body = urllib.parse.urlencode(data).encode()
    req = urllib.request.Request(BASE + path, data=body, headers={
        'User-Agent': 'MiramarQA/1.0', 'Content-Type': 'application/x-www-form-urlencoded'})
    try:
        r = op.open(req)
        return r.status, r.headers, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.headers, e.read().decode('utf-8', 'replace')

def csrf(html):
    m = re.search(r'name="csrf_token"[^>]*value="([^"]+)"', html)
    return m.group(1) if m else None

s, h, html = get('/auth/login')
s, h, html = post('/auth/login', {'csrf_token': csrf(html), 'email': 'admin@hotelmiramarsg.com', 'password': 'admin123'})
print('admin login:', s, h.get('Location'))

s, h, html = get('/admin/dashboard')
print('dashboard:', s)
html = html.replace('<head>', '<head>\n  <base href="%s/">' % BASE, 1)
path = os.path.join(STATIC, '_preview_admin_tmp.html')
with open(path, 'w', encoding='utf-8') as f:
    f.write(html)
print('wrote', path, len(html))

s, h, html = get('/admin/bookings')
html = html.replace('<head>', '<head>\n  <base href="%s/">' % BASE, 1)
path2 = os.path.join(STATIC, '_preview_bookings_tmp.html')
with open(path2, 'w', encoding='utf-8') as f:
    f.write(html)
print('wrote', path2, len(html))
