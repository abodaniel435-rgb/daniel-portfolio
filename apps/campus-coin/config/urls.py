"""
URL configuration for config project.

The `urlpatterns` list routes URLs to views. For more information please see:
    https://docs.djangoproject.com/en/6.1/topics/http/urls/
Examples:
Function views
    1. Add an import:  from my_app import views
    2. Add a URL to urlpatterns:  path('', views.home, name='home')
Class-based views
    1. Add an import:  from other_app.views import Home
    2. Add a URL to urlpatterns:  path('', Home.as_view(), name='home')
Including another URLconf
    1. Import the include() function: from django.urls import include, path
    2. Add a URL to urlpatterns:  path('blog/', include('blog.urls'))
"""
from django.conf import settings
from django.contrib import admin
from django.http import HttpResponse
from django.urls import include, path, re_path
from django.views.static import serve

urlpatterns = [
    path('admin/', admin.site.urls),
    path('api/auth/', include('accounts.urls')),
    path('api/', include('core.urls')),
]

if settings.SERVE_SPA:
    index_file = settings.FRONTEND_DIST / 'index.html'

    def spa_index(request):
        """Serve the single-page client for every non-API path.

        The built HTML hard-codes root-absolute asset URLs. When Passenger
        mounts this app under a sub-path (e.g. /CampusCoin) those URLs have to
        carry the mount prefix, so it is injected per request.
        """
        try:
            html = index_file.read_text(encoding='utf-8')
        except FileNotFoundError:
            return HttpResponse(
                'Frontend build missing: run `npm run build` in frontend/.',
                status=500,
                content_type='text/plain; charset=utf-8',
            )

        prefix = request.META.get('SCRIPT_NAME', '').rstrip('/')
        if prefix:
            html = html.replace('"/static/spa/', f'"{prefix}/static/spa/')
        html = html.replace(
            '</head>',
            f'<script>window.__CC_API__="{prefix}/api";window.__CC_BASE__="{prefix}";</script></head>',
            1,
        )
        return HttpResponse(html, content_type='text/html; charset=utf-8')

    urlpatterns += [
        re_path(r'^static/spa/(?P<path>.*)$', serve, {'document_root': settings.FRONTEND_DIST}),
        re_path(r'^(?!api/|admin/|static/|django-admin/).*$', spa_index),
    ]
