from decimal import Decimal
from datetime import date
from django.db.models import Sum
from django.db.models.functions import Coalesce
from rest_framework import serializers
from .models import Announcement, Budget, Category, Goal, Insight, SplitBill, SplitMember, Transaction


class CategorySerializer(serializers.ModelSerializer):
    class Meta:
        model = Category
        fields = ('id', 'name', 'type', 'is_default', 'created_at')
        read_only_fields = ('is_default', 'created_at')


class TransactionSerializer(serializers.ModelSerializer):
    category_name = serializers.CharField(source='category.name', read_only=True)

    class Meta:
        model = Transaction
        fields = ('id', 'category', 'category_name', 'amount', 'type', 'description', 'transaction_date', 'is_recurring', 'recurrence_rule', 'created_at', 'updated_at')
        read_only_fields = ('created_at', 'updated_at')

    def validate(self, attrs):
        category = attrs.get('category', getattr(self.instance, 'category', None))
        transaction_type = attrs.get('type', getattr(self.instance, 'type', None))
        if category and transaction_type and category.type != transaction_type:
            raise serializers.ValidationError({'category': 'The category type must match the transaction type.'})
        return attrs


class BudgetSerializer(serializers.ModelSerializer):
    category_name = serializers.CharField(source='category.name', read_only=True)
    spent = serializers.SerializerMethodField()

    class Meta:
        model = Budget
        fields = ('id', 'category', 'category_name', 'month', 'limit_amount', 'spent', 'created_at', 'updated_at')
        read_only_fields = ('created_at', 'updated_at', 'spent')

    def get_spent(self, obj):
        total = Transaction.objects.filter(
            user=obj.user,
            category=obj.category,
            type='expense',
            is_deleted=False,
            transaction_date__year=obj.month.year,
            transaction_date__month=obj.month.month,
        ).aggregate(total=Coalesce(Sum('amount'), Decimal('0')))['total']
        return total


class InsightSerializer(serializers.ModelSerializer):
    class Meta:
        model = Insight
        fields = ('id', 'month', 'summary_text', 'tip_text', 'source', 'is_bookmarked', 'is_dismissed', 'generated_at')
        read_only_fields = ('month', 'summary_text', 'tip_text', 'source', 'generated_at')


class AnnouncementSerializer(serializers.ModelSerializer):
    class Meta:
        model = Announcement
        fields = ('id', 'title', 'body', 'created_at')


class GoalSerializer(serializers.ModelSerializer):
    progress = serializers.SerializerMethodField()
    days_left = serializers.SerializerMethodField()
    per_day = serializers.SerializerMethodField()

    class Meta:
        model = Goal
        fields = ('id', 'name', 'emoji', 'target_amount', 'saved_amount', 'deadline', 'progress', 'days_left', 'per_day', 'created_at')
        read_only_fields = ('created_at',)

    def validate_target_amount(self, value):
        if value <= 0:
            raise serializers.ValidationError('Target must be more than zero.')
        return value

    def validate_saved_amount(self, value):
        if value < 0:
            raise serializers.ValidationError('Saved amount cannot be negative.')
        return value

    def get_progress(self, obj):
        if obj.target_amount <= 0:
            return 0.0
        return round(min(100.0, float(obj.saved_amount) / float(obj.target_amount) * 100), 1)

    def get_days_left(self, obj):
        if not obj.deadline:
            return None
        return (obj.deadline - date.today()).days

    def get_per_day(self, obj):
        if not obj.deadline:
            return None
        remaining = float(obj.target_amount - obj.saved_amount)
        if remaining <= 0:
            return 0.0
        days = (obj.deadline - date.today()).days
        if days <= 0:
            return round(remaining, 2)
        return round(remaining / days, 2)


class SplitMemberSerializer(serializers.ModelSerializer):
    share = serializers.SerializerMethodField()
    owes = serializers.SerializerMethodField()
    settled = serializers.SerializerMethodField()

    class Meta:
        model = SplitMember
        fields = ('id', 'name', 'is_user', 'settled', 'share', 'paid', 'owes')

    def get_share(self, obj):
        heads = obj.bill.members.count() or 1
        return round(float(obj.bill.amount) / heads, 2)

    def get_owes(self, obj):
        return round(self.get_share(obj) - float(obj.paid or 0), 2)

    def get_settled(self, obj):
        return self.get_owes(obj) <= 0


class SplitBillSerializer(serializers.ModelSerializer):
    members = SplitMemberSerializer(many=True, read_only=True)
    names = serializers.ListField(child=serializers.CharField(), write_only=True)
    per_head = serializers.SerializerMethodField()
    total_paid = serializers.SerializerMethodField()
    remaining = serializers.SerializerMethodField()
    total_owed = serializers.SerializerMethodField()
    my_paid = serializers.SerializerMethodField()
    my_owes = serializers.SerializerMethodField()

    class Meta:
        model = SplitBill
        fields = ('id', 'title', 'amount', 'bill_date', 'paid_by', 'names', 'members', 'per_head', 'total_paid', 'remaining', 'total_owed', 'my_paid', 'my_owes', 'created_at')
        read_only_fields = ('created_at',)

    def validate_names(self, value):
        names = [n.strip() for n in value if n and n.strip()]
        if not names:
            raise serializers.ValidationError('Add at least one roommate name.')
        if len(names) != len(set(n.lower() for n in names)):
            raise serializers.ValidationError('Each roommate name must be unique.')
        return names

    def validate_amount(self, value):
        if value <= 0:
            raise serializers.ValidationError('Amount must be more than zero.')
        return value

    def create(self, validated_data):
        names = validated_data.pop('names')
        bill = SplitBill.objects.create(**validated_data)
        SplitMember.objects.create(bill=bill, name='You', is_user=True)
        for n in names:
            SplitMember.objects.create(bill=bill, name=n)
        return bill

    def get_per_head(self, obj):
        heads = obj.members.count() or 1
        return round(float(obj.amount) / heads, 2)

    def get_total_paid(self, obj):
        return round(sum(float(m.paid or 0) for m in obj.members.all()), 2)

    def get_remaining(self, obj):
        return round(float(obj.amount) - self.get_total_paid(obj), 2)

    def get_total_owed(self, obj):
        share = self.get_per_head(obj)
        return round(sum(max(0.0, share - float(m.paid or 0)) for m in obj.members.all()), 2)

    def get_my_paid(self, obj):
        me = obj.members.filter(is_user=True).first()
        return round(float(me.paid or 0), 2) if me else 0.0

    def get_my_owes(self, obj):
        me = obj.members.filter(is_user=True).first()
        return round(max(0.0, self.get_per_head(obj) - float(me.paid or 0)), 2) if me else 0.0
