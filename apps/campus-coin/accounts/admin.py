from django.contrib import admin
from django.contrib.auth.admin import UserAdmin
from .models import User


@admin.register(User)
class CampusCoinUserAdmin(UserAdmin):
    fieldsets = UserAdmin.fieldsets + (
        ('Campus Coin profile', {'fields': ('academic_year', 'monthly_allowance_baseline', 'monthly_savings_goal', 'is_admin')}),
    )
    list_display = ('username', 'email', 'academic_year', 'is_staff', 'is_active')
