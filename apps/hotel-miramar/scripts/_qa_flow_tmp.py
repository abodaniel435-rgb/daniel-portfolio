import re, urllib.request, urllib.parse, http.cookiejar

BASE = 'http://127.0.0.1:5001'

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

def make_session():
    cj = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj), NoRedirect)

def get(opener, path):
    req = urllib.request.Request(BASE + path, headers={'User-Agent': 'MiramarQA/1.0'})
    try:
        r = opener.open(req)
        return r.status, r.headers, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.headers, e.read().decode('utf-8', 'replace')

def post(opener, path, data):
    body = urllib.parse.urlencode(data).encode()
    req = urllib.request.Request(BASE + path, data=body, headers={
        'User-Agent': 'MiramarQA/1.0',
        'Content-Type': 'application/x-www-form-urlencoded'})
    try:
        r = opener.open(req)
        return r.status, r.headers, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.headers, e.read().decode('utf-8', 'replace')

def csrf(html):
    m = re.search(r'name="csrf_token"[^>]*value="([^"]+)"', html)
    if not m:
        m = re.search(r'value="([^"]+)"[^>]*name="csrf_token"', html)
    return m.group(1) if m else None

def show(label, status, headers, html, needles=()):
    print(f'\n=== {label} ===')
    print('status:', status, '| location:', headers.get('Location'))
    for n in needles:
        print(f'contains {n!r}:', n in html)
    return html

guest = make_session()
print('1) Guest login')
s, h, html = get(guest, '/auth/login')
tok = csrf(html)
print('  GET /auth/login:', s, 'csrf:', bool(tok))
s, h, html = post(guest, '/auth/login', {
    'csrf_token': tok, 'email': 'amelia.tan.1109@example.com',
    'password': 'Miramar#2026', 'remember': 'y'})
show('POST /auth/login (guest)', s, h, html, ['Good to see you', 'Amelia', 'dashboard'])

print('\n2) Booking form')
s, h, html = get(guest, '/customer/book/2?check_in=2026-11-12&check_out=2026-11-15&guests=2')
tok = csrf(html)
print('  GET /customer/book/2:', s, 'csrf:', bool(tok),
      '| rate attr:', 'data-rate' in html, '| summary:', 'data-price-root' in html)

print('\n3) Submit booking')
s, h, html = post(guest, '/customer/book/2', {
    'csrf_token': tok, 'room_id': '2', 'check_in': '2026-11-12',
    'check_out': '2026-11-15', 'num_guests': '2',
    'special_requests': 'High floor facing the bay if possible. Quiet room preferred.'})
show('POST /customer/book/2', s, h, html, ['My bookings', 'confirmed'])
loc = h.get('Location') or '/customer/bookings'
s2, h2, html2 = get(guest, loc if loc.startswith('/') else '/customer/bookings')
show('GET confirmation ' + loc, s2, h2, html2,
     ['Booking request', 'MMR-', 'Marina Deluxe', '12 November'])
refs = re.findall(r'MMR-\d+', html2)
print('  references found:', sorted(set(refs)))
print('  totals shown:', sorted(set(re.findall(r'S\$\s?[\d,]+\.\d{2}', html2)))[:5])
m = re.search(r'confirmed=(\d+)', loc)
booking_id = m.group(1) if m else None
print('  booking id:', booking_id)

print('\n4) Contact form')
s, h, html = get(guest, '/contact')
tok = csrf(html)
print('  GET /contact:', s, 'csrf:', bool(tok))
s, h, html = post(guest, '/contact', {
    'csrf_token': tok, 'name': 'Amelia Tan', 'email': 'amelia.tan.1109@example.com',
    'subject': 'Airport transfer enquiry',
    'message': 'Could you arrange a car from Changi T3 at 14:00 on 12 November? Two passengers with luggage.'})
show('POST /contact', s, h, html, ['Thank', 'received', 'message', 'Airport transfer'])

print('\n5) Guest pages')
for p in ['/', '/rooms', '/rooms/2', '/about', '/attractions', '/location', '/feedback',
          '/customer/dashboard', '/customer/bookings', '/customer/profile',
          '/customer/search?check_in=2026-12-01&check_out=2026-12-03&guests=2']:
    s, h, html = get(guest, p)
    print(f'  {p}: {s}')

print('\n6) Admin login via /admin/login')
admin = make_session()
s, h, html = get(admin, '/admin/login')
print('  GET /admin/login:', s, '| location:', h.get('Location'))
if h.get('Location'):
    s, h, html = get(admin, h.get('Location'))
    print('  follow login:', s)
tok = csrf(html)
s, h, html = post(admin, '/auth/login', {
    'csrf_token': tok, 'email': 'admin@hotelmiramarsg.com', 'password': 'admin123'})
show('POST /auth/login (admin)', s, h, html, ['Operations console', 'Dashboard'])
loc = h.get('Location') or '/admin/dashboard'
s, h, html = get(admin, loc if loc.startswith('/') else '/admin/dashboard')
show('Admin landing ' + loc, s, h, html, ['Operations console', 'Dashboard'])

print('\n7) Admin pages')
for p in ['/admin/dashboard', '/admin/bookings', '/admin/rooms', '/admin/customers',
          '/admin/feedback', '/admin/messages', '/admin/slider', '/admin/profile']:
    s, h, html = get(admin, p)
    print(f'  {p}: {s} len={len(html)}')
if booking_id:
    s, h, html = get(admin, '/admin/bookings/' + booking_id)
    print(f'  /admin/bookings/{booking_id}: {s} len={len(html)} | status form:', 'status' in html and 'csrf_token' in html)
    print('  ref visible:', ('MMR-%05d' % int(booking_id)) in html)
    s, h, html = get(admin, '/admin/bookings')
    print('  bookings list ref:', ('MMR-%05d' % int(booking_id)) in html, '| guest name:', 'Amelia' in html)
    s, h, html = get(admin, '/admin/messages')
    print('  messages has contact subject:', 'Airport transfer' in html)

print('\n8) Invalid booking validation (past dates)')
s, h, html = get(guest, '/customer/book/2')
tok = csrf(html)
s, h, html = post(guest, '/customer/book/2', {
    'csrf_token': tok, 'room_id': '2', 'check_in': '2020-01-01',
    'check_out': '2020-01-03', 'num_guests': '2', 'special_requests': ''})
print('  status:', s, '| past-date message:', ('past' in html.lower() or 'valid' in html.lower()),
      '| form re-rendered:', 'csrf_token' in html)
