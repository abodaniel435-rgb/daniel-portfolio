from datetime import date
from django.db import models
from django.conf import settings


class Category(models.Model):
    TYPE_CHOICES = (
        ('income', 'Income'),
        ('expense', 'Expense'),
    )
    name = models.CharField(max_length=100)
    type = models.CharField(max_length=10, choices=TYPE_CHOICES)
    is_default = models.BooleanField(default=False)
    user = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        null=True,
        blank=True,
        related_name='categories'
    )
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        verbose_name_plural = 'categories'
        unique_together = ('user', 'name', 'type')

    def __str__(self):
        return f"{self.name} ({self.type})"


class Transaction(models.Model):
    TYPE_CHOICES = (
        ('income', 'Income'),
        ('expense', 'Expense'),
    )
    user = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='transactions'
    )
    category = models.ForeignKey(Category, on_delete=models.PROTECT)
    amount = models.DecimalField(max_digits=12, decimal_places=2)
    type = models.CharField(max_length=10, choices=TYPE_CHOICES)
    description = models.CharField(max_length=255, blank=True, null=True)
    transaction_date = models.DateField()
    is_recurring = models.BooleanField(default=False)
    recurrence_rule = models.CharField(max_length=50, blank=True, null=True)
    is_deleted = models.BooleanField(default=False)  # soft delete for history
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-transaction_date', '-created_at']

    def __str__(self):
        return f"{self.type} {self.amount} - {self.category.name}"


class Budget(models.Model):
    user = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='budgets'
    )
    category = models.ForeignKey(Category, on_delete=models.CASCADE)
    month = models.DateField()  # first day of the month
    limit_amount = models.DecimalField(max_digits=12, decimal_places=2)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        unique_together = ('user', 'category', 'month')

    def __str__(self):
        return f"{self.user} - {self.category.name} - {self.month}"


class Insight(models.Model):
    SOURCE_CHOICES = (
        ('rule', 'Rule-based'),
        ('ai', 'AI'),
    )
    user = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='insights'
    )
    month = models.DateField(null=True, blank=True)
    summary_text = models.TextField(blank=True, null=True)
    tip_text = models.TextField()
    source = models.CharField(max_length=10, choices=SOURCE_CHOICES, default='rule')
    is_bookmarked = models.BooleanField(default=False)
    is_dismissed = models.BooleanField(default=False)
    generated_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-generated_at']

    def __str__(self):
        return f"Insight for {self.user} - {self.generated_at.date()}"


class Announcement(models.Model):
    title = models.CharField(max_length=200)
    body = models.TextField()
    is_active = models.BooleanField(default=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return self.title


class Goal(models.Model):
    user = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='goals'
    )
    name = models.CharField(max_length=120)
    emoji = models.CharField(max_length=8, blank=True, default='🎯')
    target_amount = models.DecimalField(max_digits=12, decimal_places=2)
    saved_amount = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    deadline = models.DateField(null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.name} ({self.saved_amount}/{self.target_amount})"


class SplitBill(models.Model):
    user = models.ForeignKey(
        settings.AUTH_USER_MODEL,
        on_delete=models.CASCADE,
        related_name='split_bills'
    )
    title = models.CharField(max_length=120)
    amount = models.DecimalField(max_digits=12, decimal_places=2)
    bill_date = models.DateField(default=date.today)
    paid_by = models.CharField(max_length=120, default='me')
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ['-created_at']

    def __str__(self):
        return f"{self.title} ({self.amount})"


class SplitMember(models.Model):
    bill = models.ForeignKey(SplitBill, on_delete=models.CASCADE, related_name='members')
    name = models.CharField(max_length=120)
    is_user = models.BooleanField(default=False)
    settled = models.BooleanField(default=False)
    paid = models.DecimalField(max_digits=12, decimal_places=2, default=0)

    def __str__(self):
        return f"{self.name} on {self.bill.title}"