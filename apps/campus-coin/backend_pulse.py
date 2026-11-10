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
        score = max(0, min(40, savings_rate * 80))
        budgets = Budget.objects.filter(user=request.user, month=month).select_related('category')
        if budgets.exists():
            ok = 0
            for b in budgets:
                spent = tx.filter(type='expense', category=b.category, transaction_date__gte=month).aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or Decimal('0')
                if float(spent) <= float(b.limit_amount):
                    ok += 1
            score += 30.0 * ok / budgets.count()
        else:
            score += 15.0 if float(expenses) < float(income) else 5.0
        log_days = tx.filter(transaction_date__gte=window_start).values('transaction_date').distinct().count()
        score += min(30.0, log_days)
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
        return Response({
            'balance': balance, 'income': income, 'expenses': expenses,
            'avg_daily_burn': round(avg_burn, 2),
            'broke_date': broke_date, 'days_left': days_left,
            'health': health,
            'health_label': 'Solid' if health >= 75 else 'Growing' if health >= 50 else 'On thin ice',
            'streak': streak, 'xp': xp, 'level': level, 'level_name': level_name,
            'next_level_in': 150 - (xp % 150), 'log_days': log_days,
            'leaderboard': board, 'rank': rank,
        })
