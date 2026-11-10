class RoommateView(APIView):
    """Rule-based money roommate with the user's REAL numbers."""

    def post(self, request):
        q = str(request.data.get('question', '')).strip()
        ql = q.lower()
        today = date.today()
        month = today.replace(day=1)
        tx = Transaction.objects.filter(user=request.user, is_deleted=False)
        month_tx = tx.filter(transaction_date__gte=month)
        income = float(month_tx.filter(type='income').aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or 0)
        expenses = float(month_tx.filter(type='expense').aggregate(t=Coalesce(Sum('amount'), Decimal('0')))['t'] or 0)
        balance = income - expenses
        top = month_tx.filter(type='expense').values('category__name').annotate(total=Sum('amount')).order_by('-total').first()
        top_name, top_amt = (top['category__name'], float(top['total'])) if top else ('nothing yet', 0.0)
        burn = (expenses / max(today.day, 1)) or 1.0
        days_left = int(balance // burn) if balance > 0 else 0
        name = (request.user.first_name or request.user.username or 'champ').split()[0]
        pidgin = any(w in ql for w in ['abeg', 'sapa', 'shege', 'wahala', 'how far', 'omo'])
        if not q:
            answer = f"Ask me anything, {name} - try 'can I survive on 15k for 12 days?'"
        elif any(w in ql for w in ['survive', 'last', 'enough', 'carry']):
            import re
            nums = [float(n.replace(',', '')) for n in re.findall(r'\d[\d,]*', ql)]
            amount = nums[0] if nums else balance
            m = re.search(r'(\d+)\s*days?', ql)
            days = int(m.group(1)) if m else 7
            need = burn * days
            if amount >= need:
                answer = f"Yes {name}! {amount:,.0f} covers ~{days} days at your pace ({burn:,.0f}/day). You keep ~{amount-need:,.0f}. Lock Food at 60% and you cruise."
            else:
                answer = f"Tight, {name}. {amount:,.0f} for {days} days needs {need:,.0f} - short by {need-amount:,.0f}. Cut {top_name} by 30% ({top_amt:,.0f} so far) and cook twice this week."
            if pidgin:
                answer += " Omo, you fit manage am if you hold Food well well."
        elif any(w in ql for w in ['broke', 'sapa', 'run out', 'finish']):
            answer = f"At {burn:,.0f}/day with {balance:,.0f} left, you land broke in ~{days_left} days. Biggest leak: {top_name} ({top_amt:,.0f}). Cap it and you add 5+ days."
        elif any(w in ql for w in ['save', 'saving', 'budget']):
            pct = (balance / income * 100) if income else 0
            answer = f"You dey save {pct:.0f}% this month ({balance:,.0f} of {income:,.0f}). Move 20% to savings on allowance day. Top spend: {top_name}."
        elif any(w in ql for w in ['spend', 'spent', 'where']):
            answer = f"This month: {expenses:,.0f} out, {income:,.0f} in. Most went to {top_name} ({top_amt:,.0f}). Pace: {burn:,.0f}/day."
        else:
            answer = f"Gist: balance {balance:,.0f} (in {income:,.0f} / out {expenses:,.0f}), burn {burn:,.0f}/day (~{days_left} days left). Biggest: {top_name}. Ask 'can 15k last 12 days?'"
        return Response({'answer': answer, 'balance': balance, 'burn': burn, 'days_left': days_left, 'top': {'name': top_name, 'total': top_amt}})
