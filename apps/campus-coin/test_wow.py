import os, django
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'config.settings')
django.setup()
from django.test import RequestFactory
from rest_framework.test import force_authenticate
from accounts.models import User
from core.views import MonthlyReportView, PulseView, RoommateView
u = User.objects.filter(username__iexact='Steez').first()
rf = RequestFactory()
req = rf.get('/api/pulse/')
force_authenticate(req, user=u)
resp = PulseView.as_view()(req)
resp.render()
print('PULSE', resp.status_code, resp.content[:1200].decode(errors='ignore'))
req2 = rf.post('/api/roommate/', {'question': 'Can 15k last 12 days?'}, format='json')
force_authenticate(req2, user=u)
resp2 = RoommateView.as_view()(req2)
resp2.render()
print('MATE', resp2.status_code, resp2.content[:1200].decode(errors='ignore'))
