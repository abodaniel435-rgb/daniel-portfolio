from django.contrib.auth.models import AbstractUser
from django.db import models


class User(AbstractUser):
    """
    Custom user model for Campus Coin.
    Students and admins share the same table.
    """
    academic_year = models.CharField(max_length=50, blank=True, null=True)
    monthly_allowance_baseline = models.DecimalField(
        max_digits=12, decimal_places=2, default=0.00
    )
    monthly_savings_goal = models.DecimalField(
        max_digits=12, decimal_places=2, default=0.00
    )
    is_admin = models.BooleanField(default=False)
    country = models.CharField(max_length=64, blank=True, default='Nigeria')

    REQUIRED_FIELDS = ['email']

    def __str__(self):
        return self.email or self.username  
