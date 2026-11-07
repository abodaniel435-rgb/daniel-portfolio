import os
from django.contrib.auth.tokens import default_token_generator
from django.core.mail import send_mail
from django.utils.encoding import force_bytes, force_str
from django.utils.http import urlsafe_base64_decode, urlsafe_base64_encode
from rest_framework import generics, permissions, status
from rest_framework.response import Response
from rest_framework.views import APIView
from .models import User
from .serializers import RegisterSerializer, UserSerializer


class RegisterView(generics.CreateAPIView):
    queryset = User.objects.all()
    serializer_class = RegisterSerializer
    permission_classes = [permissions.AllowAny]


class MeView(generics.RetrieveUpdateAPIView):
    serializer_class = UserSerializer

    def get_object(self):
        return self.request.user


class PasswordResetRequestView(APIView):
    permission_classes = [permissions.AllowAny]

    def post(self, request):
        user = User.objects.filter(email__iexact=request.data.get('email', '')).first()
        if user:
            uid = urlsafe_base64_encode(force_bytes(user.pk))
            token = default_token_generator.make_token(user)
            origin = os.getenv('FRONTEND_URL', 'http://127.0.0.1:5173').rstrip('/')
            link = f'{origin}/reset-password?uid={uid}&token={token}'
            send_mail(
                'Campus Coin password reset',
                f'Open this link to choose a new password:\n{link}\n\nOr paste these values on the reset page:\nUID: {uid}\nToken: {token}',
                None,
                [user.email],
            )
        return Response({'detail': 'If that email exists, reset instructions have been sent.'})


class PasswordResetConfirmView(APIView):
    permission_classes = [permissions.AllowAny]

    def post(self, request):
        try:
            user = User.objects.get(pk=force_str(urlsafe_base64_decode(request.data['uid'])))
        except (KeyError, User.DoesNotExist, ValueError, TypeError):
            return Response({'detail': 'Invalid reset link.'}, status=status.HTTP_400_BAD_REQUEST)
        if not default_token_generator.check_token(user, request.data.get('token')):
            return Response({'detail': 'Invalid or expired reset link.'}, status=status.HTTP_400_BAD_REQUEST)
        password = request.data.get('password', '')
        if len(password) < 8:
            return Response({'password': 'Use at least 8 characters.'}, status=status.HTTP_400_BAD_REQUEST)
        user.set_password(password)
        user.save(update_fields=['password'])
        return Response({'detail': 'Password updated.'})
