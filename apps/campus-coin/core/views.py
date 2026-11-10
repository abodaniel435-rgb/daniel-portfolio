import csv
from collections import defaultdict
from datetime import date, datetime, timedelta
from decimal import Decimal
from django.db.models import Count, DateField, Q, ProtectedError, Sum
from django.db.models.functions import Cast, Coalesce
from rest_framework import generics, permissions, status
from rest_framework.response import Response
from rest_framework.views import APIView
from .models import Announcement, Budget, Category, Goal, Insight, SplitBill, SplitMember, Transaction
from .currencies import symbol_for
from .serializers import AnnouncementSerializer, BudgetSerializer, CategorySerializer, GoalSerializer, InsightSerializer, SplitBillSerializer, TransactionSerializer


class OwnedQuerysetMixin:
    def get_queryset(self):
        return super().get_queryset().filter(user=self.request.user)


class CategoryListCreateView(OwnedQuerysetMixin, generics.ListCreateAPIView):
    serializer_class = CategorySerializer
    queryset = Category.objects.all()

    def get_queryset(self):
        return (Category.objects.filter(user=self.request.user) | Category.objects.filter(is_default=True)).order_by('type', 'name')

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)


class CategoryDetailView(generics.RetrieveUpdateDestroyAPIView):
    serializer_class = CategorySerializer

    def get_queryset(self):
        return Category.objects.filter(user=self.request.user, is_default=False)

    def perform_destroy(self, instance):
        try:
            instance.delete()
        except ProtectedError:
            from rest_framework.exceptions import ValidationError
            raise ValidationError('This category is already used by transactions, so it stays.')


class TransactionListCreateView(OwnedQuerysetMixin, generics.ListCreateAPIView):
    serializer_class = TransactionSerializer
    queryset = Transaction.objects.filter(is_deleted=False).select_related('category')

    def get_queryset(self):
        queryset = super().get_queryset()
        if category := self.request.query_params.get('category'):
            queryset = queryset.filter(category_id=category)
        if transaction_type := self.request.query_params.get('type'):
            queryset = queryset.filter(type=transaction_type)
        if start := self.request.query_params.get('start'):
            queryset = queryset.filter(transaction_date__gte=start)
        if end := self.request.query_params.get('end'):
            queryset = queryset.filter(transaction_date__lte=end)
        return queryset.filter(is_deleted=False)

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)


class TransactionDetailView(OwnedQuerysetMixin, generics.RetrieveUpdateDestroyAPIView):
    serializer_class = TransactionSerializer
    queryset = Transaction.objects.select_related('category')

    def perform_destroy(self, instance):
        instance.is_deleted = True
        instance.save(update_fields=['is_deleted', 'updated_at'])


class BudgetListCreateView(OwnedQuerysetMixin, generics.ListCreateAPIView):
    serializer_class = BudgetSerializer
    queryset = Budget.objects.select_related('category')

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)


class BudgetDetailView(OwnedQuerysetMixin, generics.RetrieveUpdateDestroyAPIView):
    serializer_class = BudgetSerializer
    queryset = Budget.objects.select_related('category')


class InsightListView(OwnedQuerysetMixin, generics.ListAPIView):
    serializer_class = InsightSerializer
    queryset = Insight.objects.all()


class InsightDetailView(OwnedQuerysetMixin, generics.UpdateAPIView):
    serializer_class = InsightSerializer
    queryset = Insight.objects.all()
    http_method_names = ['patch', 'head', 'options']


class AnnouncementListView(generics.ListAPIView):
    serializer_class = AnnouncementSerializer
    queryset = Announcement.objects.filter(is_active=True)


class DashboardView(APIView):
    def get(self, request):
        today = date.today()
        month_start = today.replace(day=1)
        transactions = Transaction.objects.filter(user=request.user, is_deleted=False, transaction_date__gte=month_start)
        income = transactions.filter(type='income').aggregate(total=Coalesce(Sum('amount'), Decimal('0')))['total']
        expenses = transactions.filter(type='expense').aggregate(total=Coalesce(Sum('amount'), Decimal('0')))['total']
        top_category = transactions.filter(type='expense').values('category__name').annotate(total=Sum('amount')).order_by('-total').first()
        budgets = Budget.objects.filter(user=request.user, month=month_start).select_related('category')
        budget_status = []
        for budget in budgets:
            spent = transactions.filter(type='expense', category=budget.category).aggregate(total=Coalesce(Sum('amount'), Decimal('0')))['total']
            budget_status.append({'category': budget.category.name, 'limit': budget.limit_amount, 'spent': spent})
        return Response({
            'month': month_start,
            'income': income,
            'expenses': expenses,
            'balance': income - expenses,
            'top_category': top_category,
            'budgets': budget_status,
            'tips': InsightSerializer(Insight.objects.filter(user=request.user, is_dismissed=False)[:3], many=True).data,
        })


class MonthlyReportView(APIView):
    def get(self, request):
        raw_month = request.query_params.get('month')
        try:
            month = datetime.strptime(raw_month, '%Y-%m-%d').date().replace(day=1) if raw_month else date.today().replace(day=1)
        except ValueError:
            return Response({'month': 'Use YYYY-MM-DD.'}, status=status.HTTP_400_BAD_REQUEST)
        all_transactions = Transaction.objects.filter(user=request.user, is_deleted=False)
        transactions = all_transactions.filter(transaction_date__year=month.year, transaction_date__month=month.month)
        categories = list(transactions.filter(type='expense').values('category__name').annotate(total=Sum('amount')).order_by('-total'))
        income_by_category = list(transactions.filter(type='income').values('category__name').annotate(total=Sum('amount')).order_by('-total'))
        income = transactions.filter(type='income').aggregate(total=Coalesce(Sum('amount'), Decimal('0')))['total']
        expenses = transactions.filter(type='expense').aggregate(total=Coalesce(Sum('amount'), Decimal('0')))['total']
        daily = [
            {'day': row['day'].isoformat() if row['day'] else '', 'total': row['total']}
            for row in transactions.filter(type='expense').annotate(day=Cast('transaction_date', DateField())).values('day').annotate(total=Coalesce(Sum('amount'), Decimal('0'))).order_by('day')
        ]
        weekly_totals = defaultdict(lambda: Decimal('0'))
        for row in transactions.filter(type='expense').values('transaction_date', 'amount'):
            weekly_totals[row['transaction_date'].isocalendar().week] += row['amount']
        weekly = [{'week': f'Week {number}', 'total': total} for number, total in sorted(weekly_totals.items())]
        six_months = []
        cursor = month
        for _ in range(6):
            rows = all_transactions.filter(transaction_date__year=cursor.year, transaction_date__month=cursor.month)
            six_months.append({'month': cursor.strftime('%b %Y'), 'income': rows.filter(type='income').aggregate(total=Coalesce(Sum('amount'), Decimal('0')))['total'], 'expenses': rows.filter(type='expense').aggregate(total=Coalesce(Sum('amount'), Decimal('0')))['total']})
            cursor = (cursor.replace(day=1) - __import__('datetime').timedelta(days=1)).replace(day=1)
        return Response({'month': month, 'income': income, 'expenses': expenses, 'balance': income - expenses, 'by_category': categories, 'income_by_category': income_by_category, 'daily': daily, 'weekly': weekly, 'six_months': list(reversed(six_months))})


class GoalListCreateView(OwnedQuerysetMixin, generics.ListCreateAPIView):
    serializer_class = GoalSerializer
    queryset = Goal.objects.all()
    permission_classes = [permissions.IsAuthenticated]

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)


class GoalDetailView(OwnedQuerysetMixin, generics.RetrieveUpdateDestroyAPIView):
    serializer_class = GoalSerializer
    queryset = Goal.objects.all()
    permission_classes = [permissions.IsAuthenticated]


class HeatmapView(APIView):
    def get(self, request):
        start = date.today() - timedelta(days=364)
        rows = (
            Transaction.objects.filter(user=request.user, type='expense', is_deleted=False, transaction_date__gte=start)
            .annotate(day=Cast('transaction_date', DateField()))
            .values('day')
            .annotate(total=Coalesce(Sum('amount'), Decimal('0')))
        )
        return Response({'start': start.isoformat(), 'days': {row['day'].isoformat(): row['total'] for row in rows}})


class SplitListCreateView(OwnedQuerysetMixin, generics.ListCreateAPIView):
    serializer_class = SplitBillSerializer
    queryset = SplitBill.objects.prefetch_related('members')
    permission_classes = [permissions.IsAuthenticated]

    def perform_create(self, serializer):
        serializer.save(user=self.request.user)


class SplitDetailView(OwnedQuerysetMixin, generics.RetrieveDestroyAPIView):
    serializer_class = SplitBillSerializer
    queryset = SplitBill.objects.prefetch_related('members')
    permission_classes = [permissions.IsAuthenticated]


class SplitSettleView(APIView):
    permission_classes = [permissions.IsAuthenticated]

    def post(self, request, pk, member_id):
        member = generics.get_object_or_404(SplitMember, pk=member_id, bill__pk=pk, bill__user=request.user)
        bill = member.bill
        share = round(float(bill.amount) / (bill.members.count() or 1), 2)
        if 'paid' in request.data:
            try:
                paid = round(float(request.data.get('paid') or 0), 2)
            except (TypeError, ValueError):
                return Response({'paid': 'Enter a valid amount.'}, status=status.HTTP_400_BAD_REQUEST)
            member.paid = min(share, max(0.0, round(float(member.paid or 0) + paid, 2)))
        else:
            member.paid = 0 if float(member.paid or 0) >= share else share
        member.settled = float(member.paid) >= share
        member.save()
        return Response(SplitBillSerializer(bill).data)


class CsvImportView(APIView):
    def post(self, request):
        upload = request.FILES.get('file')
        if not upload or not upload.name.lower().endswith('.csv'):
            return Response({'file': 'Upload a CSV file.'}, status=status.HTTP_400_BAD_REQUEST)
        rows = csv.DictReader((line.decode('utf-8-sig') for line in upload))
        created, errors = 0, []
        for number, row in enumerate(rows, start=2):
            try:
                transaction_type = row.get('type', '').lower().strip()
                category_name = row.get('category', '').strip()
                category = Category.objects.filter(type=transaction_type, name__iexact=category_name).filter(user=request.user).first() or Category.objects.filter(type=transaction_type, name__iexact=category_name, is_default=True).first()
                if transaction_type not in ('income', 'expense') or not category:
                    raise ValueError('Use a valid type and an existing category.')
                Transaction.objects.create(user=request.user, category=category, type=transaction_type, amount=row['amount'], description=row.get('description', ''), transaction_date=datetime.strptime(row['transaction_date'], '%Y-%m-%d').date())
                created += 1
            except (KeyError, ValueError, TypeError) as error:
                errors.append({'row': number, 'error': str(error)})
        return Response({'created': created, 'errors': errors})


class TipGenerationView(APIView):
    def post(self, request):
        month = date.today().replace(day=1)
        spending = Transaction.objects.filter(user=request.user, type='expense', is_deleted=False, transaction_date__gte=month).values('category__name').annotate(total=Sum('amount')).order_by('-total').first()
        if not spending:
            return Response({'detail': 'Add expense transactions first.'}, status=status.HTTP_400_BAD_REQUEST)
        tip = Insight.objects.create(user=request.user, month=month, summary_text=f"Your highest spending category this month is {spending['category__name']}.", tip_text=f"Set a weekly limit for {spending['category__name']} to reduce your monthly spending.", source='rule')
        return Response(InsightSerializer(tip).data, status=status.HTTP_201_CREATED)


class PulseView(APIView):
    """Broke-date predictor, money health, streaks/XP/levels + leaderboard."""

    def get(self, request):
        from datetime import timedelta
        today = date.today()
        month = today.replace(day=1)
        tx = Transaction.objects.filter(user=request.user, is_deleted=False)
        month_tx = tx.filter(transaction_date__gte=month)
        income = month_tx.filter(type='income').aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
        expenses = month_tx.filter(type='expense').aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
        balance = income - expenses
        window_start = today - timedelta(days=30)
        burn_total = tx.filter(type='expense', transaction_date__gte=window_start).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
        avg_burn = float(burn_total) / 30.0 if burn_total else float(expenses) / max(today.day, 1)
        avg_burn = max(avg_burn, 1.0)
        broke_date, days_left = None, None
        if float(balance) > 0 and avg_burn > 0:
            days_left = int(float(balance) // avg_burn)
            broke_date = (today + timedelta(days=days_left)).isoformat()
        elif float(balance) <= 0:
            days_left = 0
            broke_date = today.isoformat()
        savings_rate = (float(balance) / float(income)) if float(income) > 0 else 0.0
        savings_points = max(0.0, min(40.0, savings_rate * 80))
        score = savings_points
        budgets = Budget.objects.filter(user=request.user, month=month).select_related('category')
        budget_lines = budgets.count()
        budget_ok = 0
        if budgets.exists():
            ok = 0
            for b in budgets:
                spent = tx.filter(type='expense', category=b.category, transaction_date__gte=month).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
                if float(spent) <= float(b.limit_amount):
                    ok += 1
            budget_points = 30.0 * ok / budget_lines
            budget_ok = ok
        else:
            budget_points = 15.0 if float(expenses) < float(income) else 5.0
        score += budget_points
        log_days = tx.filter(transaction_date__gte=window_start).values('transaction_date').distinct().count()
        logging_points = min(30.0, float(log_days))
        score += logging_points
        health = int(round(max(5, min(99, score))))
        days = set(tx.values_list('transaction_date', flat=True))
        streak = 0
        cursor = today
        if cursor not in days:
            cursor = cursor - timedelta(days=1)
        while cursor in days:
            streak += 1
            cursor = cursor - timedelta(days=1)
        xp = int(log_days * 10 + streak * 8 + budgets.count() * 5)
        level = 1 + xp // 150
        names = ['Fresher', 'Hustler', 'Ball Manager', 'Campus Tycoon', 'Sapa Slayer']
        level_name = names[min(level - 1, len(names) - 1)]
        board = []
        from accounts.models import User as U
        for u in U.objects.filter(is_active=True)[:50]:
            u_inc = Transaction.objects.filter(user=u, is_deleted=False, type='income', transaction_date__gte=month).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
            if float(u_inc) <= 0:
                continue
            u_exp = Transaction.objects.filter(user=u, is_deleted=False, type='expense', transaction_date__gte=month).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
            rate = (float(u_inc - u_exp) / float(u_inc)) if float(u_inc) else 0
            nm = (u.first_name or u.username or 'Anon')
            board.append({'name': ('You' if u.id == request.user.id else nm[:1] + '***'), 'me': u.id == request.user.id, 'rate': round(rate * 100, 1)})
        board = sorted(board, key=lambda r: r['rate'], reverse=True)[:7]
        rank = next((i + 1 for i, r in enumerate(board) if r['me']), None)
        pace_income = float(income) or 1.0
        pace_day = max(1, today.day)
        pace_burn = float(expenses) / pace_day
        pace_left_balance = float(balance)
        try:
            import calendar as _cal2
            _dim2 = _cal2.monthrange(today.year, today.month)[1]
        except Exception:
            _dim2 = 30
        _days_to_end = max(0, _dim2 - today.day + 1)
        _pace_need = (pace_left_balance / _days_to_end) if _days_to_end and pace_left_balance > 0 else 0.0
        _pace_verdict = 'on track' if _pace_need >= pace_burn or pace_left_balance <= 0 else 'over pace'
        _runway = int(pace_left_balance // pace_burn) if pace_burn > 0 and pace_left_balance > 0 else (0 if pace_left_balance <= 0 else 999)
        weeks = []
        for _w in range(4):
            _s = today - timedelta(days=today.weekday() + 7 * (3 - _w))
            _e = _s + timedelta(days=6)
            _t = tx.filter(type='expense', transaction_date__gte=_s, transaction_date__lte=_e).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
            weeks.append({'week': 'W%d' % (_w + 1), 'total': float(_t)})
        return Response({
            'balance': balance, 'income': income, 'expenses': expenses,
            'avg_daily_burn': round(avg_burn, 2),
            'broke_date': broke_date, 'days_left': days_left,
            'health': health,
            'health_label': 'Solid' if health >= 75 else 'Growing' if health >= 50 else 'On thin ice',
            # Additive detail so the UI can explain the score instead of
            # printing a number with no reasoning behind it.
            'health_breakdown': {
                'savings_rate': round(savings_rate * 100, 1),
                'savings_points': round(savings_points, 1),
                'savings_max': 40,
                'budget_points': round(budget_points, 1),
                'budget_max': 30,
                'budget_lines': budget_lines,
                'budget_lines_ok': budget_ok,
                'logging_points': round(logging_points, 1),
                'logging_max': 30,
                'log_days': log_days,
            },
            'streak': streak, 'xp': xp, 'level': level, 'level_name': level_name,
            'next_level_in': 150 - (xp % 150), 'log_days': log_days,
            'leaderboard': board, 'rank': rank,
            'pace': {'burn_today': round(pace_burn, 2), 'need': round(_pace_need, 2), 'days_to_month_end': _days_to_end, 'verdict': _pace_verdict},
            'runway': {'days': _runway, 'burn': round(pace_burn, 2)},
            'weeks': weeks,
        })


class RoommateView(APIView):
    """Rule-based money roommate that answers with the user's REAL numbers."""

    def post(self, request):
        import re
        from django.db.models import Q
        q = str(request.data.get('question', '')).strip()
        ql = q.lower()
        history = request.data.get('history') or []
        try:
            history = [{'q': str(h.get('q', ''))[:300], 'a': str(h.get('a', ''))[:600]} for h in history if isinstance(h, dict)][:8]
        except Exception:
            history = []
        allowance_day = request.data.get('allowance_day')
        try:
            allowance_day = int(allowance_day) if allowance_day not in (None, '') else None
            if allowance_day is not None and not 1 <= allowance_day <= 28:
                allowance_day = None
        except Exception:
            allowance_day = None
        horizon_days = request.data.get('horizon_days')
        try:
            horizon_days = int(horizon_days) if horizon_days not in (None, '') else None
            if horizon_days is not None and not 1 <= horizon_days <= 120:
                horizon_days = None
        except Exception:
            horizon_days = None
        today = date.today()
        month = today.replace(day=1)
        last_month = (month - timedelta(days=1)).replace(day=1)
        week_start = today - timedelta(days=6)
        tx = Transaction.objects.filter(user=request.user, is_deleted=False)
        month_tx = tx.filter(transaction_date__gte=month)
        last_tx = tx.filter(transaction_date__gte=last_month, transaction_date__lt=month)
        week_tx = tx.filter(transaction_date__gte=week_start)

        def total(qs, kind):
            return float(qs.filter(type=kind).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or 0)

        income, expenses = total(month_tx, 'income'), total(month_tx, 'expense')
        last_income, last_expenses = total(last_tx, 'income'), total(last_tx, 'expense')
        week_expenses = total(week_tx, 'expense')
        all_balance = total(tx, 'income') - total(tx, 'expense')
        burn = (expenses / max(today.day, 1)) or 1.0
        days_left = int(all_balance // burn) if all_balance > 0 else 0
        cat_rows = list(month_tx.filter(type='expense').values('category__name').annotate(total=Sum('amount')).order_by('-total'))
        top_name = cat_rows[0]['category__name'] if cat_rows else 'nothing yet'
        top_amt = float(cat_rows[0]['total']) if cat_rows else 0.0
        name = (request.user.first_name or request.user.username or 'champ').split()[0]
        hist_txt = ''
        try:
            if history:
                _lq = str(history[-1].get('q', ''))[:60]
                if _lq:
                    hist_txt = " (noting you asked '" + _lq + "' before)"
        except Exception:
            hist_txt = ''
        limit_phrase = ''
        if allowance_day:
            limit_phrase = ' Allowance lands day %d, so this limit resets then.' % allowance_day
        horizon = horizon_days
        if not horizon:
            try:
                import calendar as _cal
                _dim = _cal.monthrange(today.year, today.month)[1]
            except Exception:
                _dim = 30
            horizon = max(1, _dim - today.day + 1)
        pace_need = (all_balance / horizon) if horizon and all_balance > 0 else 0.0
        exp_rows = list(month_tx.filter(type='expense').values('category__name').annotate(total=Sum('amount')).order_by('-total'))
        inc_rows = list(month_tx.filter(type='income').values('category__name').annotate(total=Sum('amount')).order_by('-total'))
        inc_total = sum(float(r['total'] or 0) for r in inc_rows) or income
        plan_needs = round(inc_total * 0.5, 2)
        plan_wants = round(inc_total * 0.3, 2)
        plan_save = round(inc_total * 0.2, 2)
        pidgin = any(w in ql for w in ['abeg', 'sapa', 'shege', 'wahala', 'how far', 'omo'])
        sym = symbol_for(getattr(request.user, 'country', '') or 'Nigeria')

        def money(v):
            return f"{sym}{abs(v):,.0f}"

        def parse_money(text):
            out = []
            for num, suf in re.findall(r'(\d[\d,]*(?:\.\d+)?)\s*([km])?', text):
                v = float(num.replace(',', ''))
                out.append(v * {'k': 1000, 'm': 1000000}.get(suf, 1))
            return out

        cat_names = [c['name'] for c in Category.objects.filter(type='expense').filter(Q(user=request.user) | Q(is_default=True)).values('name')]
        matched_cat = next((c for c in cat_names if c.lower() in ql), None)

        def cat_total(qs, cat):
            return float(qs.filter(type='expense', category__name=cat).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or 0)

        answer = ''
        if not q:
            answer = f"Hi {name}! Ask me anything about your money. Try 'am I spending rightly?', 'how much did I spend on food?' or 'who owes me?'"
        elif re.match(r'^(hi|hello|hey|yo|sup|good (morning|afternoon|evening)|how far)\b', ql):
            answer = (f"Hey {name}! You have {money(all_balance)} on hand right now. "
                      f"This month: {money(income)} in, {money(expenses)} out. "
                      f"Ask me anything - 'am I spending rightly?', 'can I afford 5k?', 'when will I go broke?'")
        elif 'afford' in ql:
            nums = parse_money(ql)
            amount = nums[0] if nums else 0.0
            left = all_balance - amount
            if left >= 0:
                answer = (f"Yes, you can afford {money(amount)}. Your balance is {money(all_balance)}, "
                          f"so after it you keep {money(left)} - about {int(left // burn)} days of your normal spending.")
            else:
                answer = (f"Not right now. {money(amount)} is {money(-left)} more than your {money(all_balance)} balance. "
                          f"Wait for your next income or trim {top_name} first.")
        elif any(w in ql for w in ['survive', 'last', 'enough', 'carry']):
            nums = parse_money(ql)
            amount = nums[0] if nums else all_balance
            m = re.search(r'(\d+)\s*days?', ql)
            days = int(m.group(1)) if m else 7
            need = burn * days
            if amount >= need:
                answer = (f"Yes! {money(amount)} is enough for {days} days. You spend about {money(burn)} a day, "
                          f"so {days} days will cost about {money(need)}. You will still have {money(amount - need)} left.")
            else:
                answer = (f"Not quite. {days} days will cost about {money(need)}, but you only have {money(amount)} - "
                          f"you are short by {money(need - amount)}. Spend less on {top_name} this week and you will manage.")
            if pidgin:
                answer += " But no worry, you fit manage am if you watch your spending well."
        elif any(w in ql for w in ['broke', 'run out', 'finish', 'sapa']):
            answer = (f"You have {money(all_balance)} left and you spend about {money(burn)} a day. "
                      f"At this pace your money will finish in about {days_left} days. "
                      f"Your biggest spending is {top_name} - cut it a little and your money will last longer.")
        elif any(w in ql for w in ['rightly', 'too much', 'overspend', 'spending right', 'is my spending', 'spend too much', 'spending ok', 'spending well']):
            parts = []
            if income > 0 and expenses / income <= 0.75:
                parts.append(f"Yes, you are spending rightly - {money(expenses)} of {money(income)} this month, so you kept {money(income - expenses)}.")
            elif income > 0:
                parts.append(f"Careful - you spent {money(expenses)} of {money(income)} this month, only {money(income - expenses)} is left.")
            else:
                parts.append(f"You spent {money(expenses)} this month with no income logged, so watch the jar closely.")
            if last_expenses > 0:
                delta = expenses - last_expenses
                parts.append(f"That is {'more' if delta > 0 else 'less'} than last month's {money(last_expenses)} by {money(delta)}.")
            if cat_rows:
                parts.append(f"{top_name} takes the biggest slice ({money(top_amt)}, {round(top_amt / expenses * 100) if expenses else 0}% of your spend).")
            over = []
            for budget in Budget.objects.filter(user=request.user, month=month).select_related('category'):
                line_spent = month_tx.filter(type='expense', category=budget.category).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
                if float(line_spent) > float(budget.limit_amount):
                    over.append(budget.category.name)

            if over:
                parts.append(f"And your {', '.join(over)} budget is already crossed.")
            answer = ' '.join(parts)
        elif matched_cat and any(w in ql for w in ['spend', 'spent', 'cost', 'pay', 'paid', 'use', 'used', 'how much']):
            cat_total_now = cat_total(month_tx, matched_cat)
            count = month_tx.filter(type='expense', category__name=matched_cat).count()
            cat_total_last = cat_total(last_tx, matched_cat)
            if cat_total_now == 0:
                answer = f"Nothing spent on {matched_cat} this month yet. Last month it was {money(cat_total_last)}."
            else:
                answer = (f"You spent {money(cat_total_now)} on {matched_cat} this month across {count} transaction{'' if count == 1 else 's'}. "
                          f"Last month it was {money(cat_total_last)}.")
        elif 'daily limit' in ql or 'per day' in ql or 'a day' in ql or 'month end' in ql or 'every day' in ql or 'each day' in ql:
            end_label = ('day %d' % allowance_day) if allowance_day else 'month end'
            trim = max(0.0, burn - pace_need)
            answer = ('Spend no more than ' + money(pace_need) + '/day for the next %d days to reach ' % horizon + end_label + ' with ' + money(all_balance) + ' intact' + hist_txt + '.' + limit_phrase + ' You are burning ' + money(burn) + '/day now, so trim ' + money(trim) + '/day - start with ' + top_name + '.')
        elif 'which' in ql or 'cut first' in ql or 'reduce' in ql or 'too much' in ql or 'overspend' in ql:
            bits = []
            for r in exp_rows[:2]:
                bits.append(r['category__name'] + ' (' + money(float(r['total'])) + ' - cut 30 pct to save ' + money(float(r['total']) * 0.3) + ')')
            joined = ' and '.join(bits) if bits else top_name
            answer = 'Cut these two first: ' + joined + '. That keeps your ' + money(all_balance) + ' alive %d days instead of melting on ' % days_left + top_name + '.'
        elif '50/30/20' in ql or 'plan for next month' in ql or 'next month' in ql:
            answer = ('Next-month plan on ' + money(inc_total) + ' income: Needs ' + money(plan_needs) + ' (food, transport, hostel), Wants ' + money(plan_wants) + ' (fun, data, drip), Save ' + money(plan_save) + ' first on allowance day. Your ' + top_name + ' habit (' + money(top_amt) + ') lives inside Wants - cap it at ' + money(top_amt * 0.7) + '.')
        elif 'budget' in ql:
            if matched_cat:
                budget = Budget.objects.filter(user=request.user, category__name=matched_cat, month=month).first()
                if budget:
                    left = float(budget.limit_amount) - float(budget.spent or 0)
                    answer = (f"Your {matched_cat} budget is {money(budget.limit_amount)} for this month. "
                              f"You have spent {money(budget.spent or 0)}, so {money(left)} is left." if left >= 0 else
                              f"Your {matched_cat} budget is {money(budget.limit_amount)} but you have spent {money(budget.spent or 0)} - you crossed it by {money(-left)}.")
                else:
                    answer = f"No budget set for {matched_cat} this month. Set one in Budgets and I will keep score for you."
            else:
                budgets = Budget.objects.filter(user=request.user, month=month)
                if budgets:
                    lines = [f"{b.category.name}: {money(b.spent or 0)} of {money(b.limit_amount)}" for b in budgets]
                    answer = f"This month's lines - {'; '.join(lines)}."
                else:
                    answer = "You have no budgets this month. Set one in Budgets and I will tell you exactly how much line is left."
        elif any(w in ql for w in ['balance', 'how much do i have', 'left in my', 'my money left', 'how much is left', 'how much i have']):
            net = income - expenses
            answer = (f"Your balance is {money(all_balance)} (everything in minus everything out). "
                      f"This month alone you are {'up' if net >= 0 else 'down'} {money(net)}.")
        elif any(w in ql for w in ['income', 'earn', 'receive', 'allowance', 'paid me']):
            sources = list(month_tx.filter(type='income').values('category__name').annotate(total=Sum('amount')).order_by('-total')[:2])
            if income == 0:
                answer = "No income logged this month yet. Add your allowance and I will track it from there."
            else:
                extra = f" It came from {' and '.join(f'{s['category__name']} ({money(s['total'])})' for s in sources)}." if sources else ''
                answer = f"This month you received {money(income)}.{extra} Last month it was {money(last_income)}."
        elif 'week' in ql or '7 days' in ql:
            week_top = week_tx.filter(type='expense').values('category__name').annotate(total=Sum('amount')).order_by('-total').first()
            extra = f" Most of it went to {week_top['category__name']} ({money(week_top['total'])})." if week_top else ''
            answer = f"In the last 7 days you spent {money(week_expenses)} - about {money(week_expenses / 7)} a day.{extra}"
        elif 'last month' in ql or 'previous month' in ql:
            delta = expenses - last_expenses
            answer = (f"Last month: {money(last_income)} in, {money(last_expenses)} out. "
                      f"This month you are at {money(income)} in, {money(expenses)} out - spending is {money(delta)} {'higher' if delta > 0 else 'lower'} so far.")
        elif any(w in ql for w in ['where', 'biggest', 'top', 'most of my']):
            if not cat_rows:
                answer = "No spending this month yet, so there is nowhere your money went. Log an expense and ask me again."
            else:
                lines = [f"{i + 1}) {r['category__name']} {money(r['total'])} ({round(float(r['total']) / expenses * 100) if expenses else 0}%)" for i, r in enumerate(cat_rows[:3])]
                answer = f"Your money went to: {'; '.join(lines)}."
        elif 'recurring' in ql or 'subscription' in ql:
            rows = list(tx.filter(type='expense', is_recurring=True).values('category__name', 'amount').order_by('-amount')[:6])
            if not rows:
                answer = "You have no recurring expenses logged. Mark rent or subscriptions as monthly in Transactions and I will total them here."
            else:
                month_sum = sum(float(r['amount']) for r in rows)
                answer = f"Your recurring costs: {', '.join(f'{r['category__name']} {money(r['amount'])}' for r in rows)}. Together about {money(month_sum)} every cycle."
        elif 'goal' in ql or 'jar' in ql:
            goals = list(Goal.objects.filter(user=request.user))
            if not goals:
                answer = "No jars yet. Open Goal jars and start one - I will work out the daily drip for you."
            else:
                g = min(goals, key=lambda x: x.deadline or date.max)
                answer = (f"You have {len(goals)} jars. Nearest deadline: {g.name} at {g.progress}% "
                          f"({money(g.saved_amount)} of {money(g.target_amount)})." + (f" Drop {money(g.per_day)}/day to land by {g.deadline.strftime('%b %d')}." if g.deadline and g.progress < 100 else ''))
        elif any(w in ql for w in ['owe', 'owes', 'split', 'settle', 'roommate']):
            bills = list(SplitBill.objects.filter(user=request.user).prefetch_related('members'))
            in_total, in_names, my_owed = 0.0, [], 0.0
            for b in bills:
                heads = b.members.count() or 1
                share = float(b.amount) / heads
                for m in b.members.all():
                    owes = share - float(m.paid or 0)
                    if owes <= 0:
                        continue
                    if m.is_user:
                        my_owed += owes
                    else:
                        in_total += owes
                        in_names.append(f"{m.name} {money(owes)}")
            if not bills:
                answer = "No splits yet. Open Split & settle, add a bill you shared, and I will track who owes who here."
            elif in_total == 0 and my_owed == 0:
                answer = "All settled - nobody owes anybody right now. Clean sheet!"
            else:
                parts = []
                if in_total:
                    parts.append(f"Roommates still owe you {money(in_total)} ({', '.join(in_names)})")
                if my_owed:
                    parts.append(f"you still owe {money(my_owed)}")
                answer = ' and '.join(parts) + '.'
        elif any(w in ql for w in ['save', 'saving']):
            kept = income - expenses
            goal = float(getattr(request.user, 'monthly_savings_goal', 0) or 0)
            base = f"This month you kept {money(kept)} of {money(income)}."
            if goal > 0:
                base += f" Your savings goal is {money(goal)}, so you are {round(kept / goal * 100)}% there."
            base += f" A simple rule: move 20% of every allowance ({money(income * 0.2) if income else money(0)}) into a jar the day it lands."
            answer = base
        elif any(w in ql for w in ['tip', 'advice', 'help me']):
            tip = Insight.objects.filter(user=request.user, is_dismissed=False).first()
            answer = f"Try this: {tip.tip_text}" if tip else f"Small moves first: track every spend for 7 days, then cut your top category ({top_name}) by 20% and push the saving into a jar."
        else:
            answer = (f"Quick summary: you received {money(income)}, spent {money(expenses)}, and still have {money(all_balance)}. "
                      f"You spend about {money(burn)} a day, so your money can last about {days_left} days. "
                      f"Try asking 'how much did I spend on {top_name if cat_rows else 'food'}?', 'can I afford 5k?' or 'who owes me?'.")
        if pidgin and not answer.endswith('.'):
            answer += '.'
        followups = [
            'Can 15k last 12 days?',
            'What is my daily limit to month end?',
            'Which 2 categories should I cut first?',
            'Build me a 50/30/20 plan for next month',
        ]
        if top_name not in ('nothing yet', 'No spend yet'):
            followups.insert(0, f'How do I cut {top_name}?')
        return Response({
            'answer': answer, 'balance': all_balance, 'burn': burn, 'days_left': days_left,
            'top': {'name': top_name, 'total': top_amt},
            'followups': followups[:4],
            'echo': {'allowance_day': allowance_day, 'horizon_days': horizon_days, 'history_seen': len(history)},
        })




class AdminAnalyticsView(APIView):
    permission_classes = [permissions.IsAdminUser]

    def get(self, request):
        return Response({'active_users': __import__('accounts.models', fromlist=['User']).User.objects.filter(is_active=True).count(), 'total_transactions': Transaction.objects.filter(is_deleted=False).count(), 'top_categories': list(Transaction.objects.filter(is_deleted=False, type='expense').values('category__name').annotate(total=Count('id')).order_by('-total')[:5])})
