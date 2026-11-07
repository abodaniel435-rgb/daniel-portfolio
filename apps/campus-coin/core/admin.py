from django.contrib import admin
from .models import Category, Transaction, Budget, Insight, Announcement


@admin.register(Category)
class CategoryAdmin(admin.ModelAdmin):
    list_display = ('name', 'type', 'is_default', 'user')
    list_filter = ('type', 'is_default')
    search_fields = ('name',)


@admin.register(Transaction)
class TransactionAdmin(admin.ModelAdmin):
    list_display = ('user', 'category', 'amount', 'type', 'transaction_date', 'is_deleted')
    list_filter = ('type', 'is_deleted', 'transaction_date')
    search_fields = ('description', 'user__email')


@admin.register(Budget)
class BudgetAdmin(admin.ModelAdmin):
    list_display = ('user', 'category', 'month', 'limit_amount')
    list_filter = ('month',)


@admin.register(Insight)
class InsightAdmin(admin.ModelAdmin):
    list_display = ('user', 'source', 'is_bookmarked', 'generated_at')
    list_filter = ('source', 'is_bookmarked')


@admin.register(Announcement)
class AnnouncementAdmin(admin.ModelAdmin):
    list_display = ('title', 'is_active', 'created_at')
    list_filter = ('is_active',)     