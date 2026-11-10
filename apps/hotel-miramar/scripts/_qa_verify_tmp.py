import re, urllib.request, urllib.parse, http.cookiejar

BASE = 'http://127.0.0.1:5001'

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

def text(html):
    t = re.sub(r'<script.*?</script>', ' ', html, flags=re.S)
    t = re.sub(r'<style.*?</style>', ' ', t, flags=re.S)
    t = re.sub(r'<[^>]+>', '\n', t)
    return re.sub(r'\n\s*\n+', '\n', t)

s, h, html = get('/auth/login')
s, h, html = post('/auth/login', {'csrf_token': csrf(html), 'email': 'amelia.tan.1109@example.com',
                                  'password': 'Miramar#2026', 'remember': 'y'})
print('login:', s, h.get('Location'))

s, h, html = get('/customer/bookings?confirmed=12')
print('confirmation 12:', s)
for needle in ['MMR-00012', 'Marina Deluxe', '12 November', '15 November 2026', 'S$840.00', 'Request received', 'Thank you, Amelia']:
    print('  contains', repr(needle), ':', needle in html)

s, h, html = get('/customer/bookings')
print('bookings list:', s, '| ref in list:', 'MMR-00012' in html)

print('\ncontact submit')
s, h, html = get('/contact')
s, h, html = post('/contact', {'csrf_token': csrf(html), 'name': 'Amelia Tan',
                               'email': 'amelia.tan.1109@example.com',
                               'subject': 'Airport transfer enquiry',
                               'message': 'Could you arrange a car from Changi T3 at 14:00 on 12 November? Two passengers with luggage.'})
print('  post:', s, h.get('Location'))
loc = h.get('Location') or '/contact'
s, h, html = get(loc)
t = text(html)
print('  follow:', s, '| success flash:', ('Thank you' in t or 'received' in t.lower() or 'sent' in t.lower()))
flash = [ln.strip() for ln in t.splitlines() if 'Thank' in ln or 'message' in ln.lower() or 'enquiry' in ln.lower()]
print('  flash lines:', flash[:6])
