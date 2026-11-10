import { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'
import { asList, Card, CardHead, EmptyState, ErrorState, Icon, Meter, PageHead, Pill, Ring, SkeletonCard, StatTile, TransactionRow, useToast } from '../components'
import { money, pct } from '../components'
import { dateLabel, monthLabel } from '../currency'
import { TransactionDialog } from '../forms'
import { useSession } from '../session'

const addMonths = (value, months) => {
  const date = new Date(`${String(value).slice(0, 10)}T12:00:00`)
  const next = new Date(date)
  next.setMonth(next.getMonth() + months)
  return next
}

const isoDate = (date) => date.toISOString().slice(0, 10)

function upcomingBills(transactions) {
  const today = new Date()
  today.setHours(12, 0, 0, 0)
  const seen = new Set()
  return transactions
    .filter((item) => item.is_recurring && item.type === 'expense')
    .map((item) => {
      let next = new Date(`${String(item.transaction_date).slice(0, 10)}T12:00:00`)
      let guard = 0
      while (next < today && guard < 24) {
        next = addMonths(isoDate(next), 1)
        guard += 1
      }
      const days = Math.round((next - today) / 86_400_000)
      return { ...item, next: isoDate(next), days }
    })
    .filter((item) => {
      const key = `${item.category_name}-${item.amount}`
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })
    .sort((a, b) => a.next.localeCompare(b.next))
    .slice(0, 5)
}

export default function Dashboard() {
  const { profile } = useSession()
  const { toast } = useToast()
  const [state, setState] = useState({ status: 'loading', error: '' })
  const [categories, setCategories] = useState([])
  const [dialog, setDialog] = useState(false)

  const load = () => {
    setState((current) => ({ ...current, status: 'loading', error: '' }))
    Promise.all([
      api('/dashboard/'),
      api('/pulse/').catch(() => null),
      api('/reports/monthly/').catch(() => null),
      api('/transactions/').catch(() => []),
      api('/goals/').catch(() => []),
      api('/announcements/').catch(() => []),
      api('/categories/').catch(() => []),
    ])
      .then(([dashboard, pulse, report, transactions, goals, announcements, cats]) =>
        setState({
          status: 'ready',
          dashboard,
          pulse,
          report: report || { by_category: [] },
          transactions: asList(transactions),
          goals: asList(goals),
          announcements: asList(announcements),
          error: '',
        }),
      )
      .then(() => setCategories((current) => current))
      .catch((error) => setState({ status: 'error', error: error.message }))
  }

  useEffect(() => {
    load()
    api('/categories/')
      .then((data) => setCategories(asList(data)))
      .catch(() => {})
  }, [])

  const bills = useMemo(() => upcomingBills(state.transactions || []), [state.transactions])

  if (state.status === 'loading') {
    return (
      <>
        <PageHead eyebrow="Overview" title="Loading your month…" subtitle="Pulling balances, budgets and health score." />
        <div className="cc-dash">
          <div className="cc-span-7">
            <SkeletonCard lines={3} />
          </div>
          <div className="cc-span-5">
            <SkeletonCard lines={4} />
          </div>
          <div className="cc-span-12">
            <SkeletonCard lines={2} />
          </div>
        </div>
      </>
    )
  }

  if (state.status === 'error') {
    return (
      <>
        <PageHead eyebrow="Overview" title="Overview" />
        <ErrorState message={state.error} onRetry={load} />
      </>
    )
  }

  const { dashboard, pulse, report, transactions, goals, announcements } = state
  const income = Number(dashboard.income) || 0
  const expenses = Number(dashboard.expenses) || 0
  const balance = Number(dashboard.balance) || 0
  const budgetLines = dashboard.budgets || []
  const spentPct = income > 0 ? Math.round((expenses / income) * 100) : 0
  const name = profile?.first_name || profile?.username || 'there'
  const recent = (transactions || []).slice(0, 6)
  const breakdown = pulse?.health_breakdown
  const savingsPoints = breakdown?.savings_points ?? 0
  const budgetPoints = breakdown?.budget_points ?? 0
  const loggingPoints = breakdown?.logging_points ?? 0
  const categoryRows = report?.by_category || []
  const activeGoal = goals[0]

  return (
    <>
      <PageHead
        eyebrow={monthLabel(dashboard.month)}
        title={`Hello, ${name}.`}
        subtitle="Here is how the month is actually going — balance, spending and the score behind it."
        action={
          <button type="button" className="cc-btn cc-btn--primary" onClick={() => setDialog(true)}>
            <Icon name="plus" size={15} /> Log a transaction
          </button>
        }
      />

      {announcements.length > 0 && (
        <Card className="cc-card--quiet" style={{ marginBottom: 16 }}>
          <p className="cc-eyebrow">From campus</p>
          {announcements.slice(0, 2).map((note) => (
            <p className="cc-sub" key={note.id} style={{ marginTop: 6 }}>
              <b style={{ color: 'var(--text)' }}>{note.title}.</b> {note.body}
            </p>
          ))}
        </Card>
      )}

      <div className="cc-dash">
        <Card className="cc-span-7 cc-hero" style={{ padding: 0, border: 0 }}>
          <div className="cc-hero-top">
            <div>
              <p className="cc-eyebrow">Total balance</p>
              <p className="cc-hero-value">{money(balance)}</p>
            </div>
            <Pill tone={balance >= 0 ? 'ok' : 'over'} dot>
              {balance >= 0 ? 'In the black' : 'Below zero'}
            </Pill>
          </div>
          <div className="cc-hero-foot">
            <span>
              In <b>{money(income)}</b>
            </span>
            <span>
              Out <b>{money(expenses)}</b>
            </span>
            <span>
              Kept{' '}
              <b style={{ color: balance >= 0 ? 'var(--lime)' : 'var(--coral)' }}>
                {income > 0 ? `${Math.max(0, 100 - spentPct)}%` : '—'}
              </b>{' '}
              of income
            </span>
          </div>
          <div>
            <p className="cc-mono" style={{ marginBottom: 8 }}>
              Spend · last 4 weeks
            </p>
            <div className="cc-spark" aria-hidden="true">
              {(pulse?.weeks || []).map((week, index) => {
                const peak = Math.max(...(pulse?.weeks || []).map((row) => Number(row.total)), 1)
                return (
                  <i
                    key={`${week.week}-${index}`}
                    style={{ height: `${Math.max(8, (Number(week.total) / peak) * 100)}%` }}
                    title={`${week.week}: ${money(week.total)}`}
                  />
                )
              })}
            </div>
          </div>
          <div className="cc-hero-actions" style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
            <Link to="/transactions" className="cc-btn cc-btn--ghost cc-btn--sm">
              View transactions
            </Link>
            <Link to="/budgets" className="cc-btn cc-btn--ghost cc-btn--sm">
              Check budgets
            </Link>
          </div>
        </Card>

        <Card className="cc-span-5">
          <CardHead eyebrow="Money health" title={pulse?.health_label || 'Getting started'} action={<Pill tone={pulse?.health >= 75 ? 'ok' : pulse?.health >= 50 ? 'warn' : 'over'}>{pulse?.health ?? '—'}/100</Pill>} />
          <div className="cc-health">
            <Ring value={pulse?.health || 0} label="score" />
            <div className="cc-breakdown">
              <div className="cc-breakdown-row">
                <span>Savings rate</span>
                <b>
                  {savingsPoints} / {breakdown?.savings_max ?? 40}
                </b>
                <div className="cc-breakdown-bar">
                  <i style={{ width: `${pct(savingsPoints, breakdown?.savings_max ?? 40)}%` }} />
                </div>
              </div>
              <div className="cc-breakdown-row">
                <span>Budgets kept</span>
                <b>
                  {budgetPoints} / {breakdown?.budget_max ?? 30}
                </b>
                <div className="cc-breakdown-bar">
                  <i style={{ width: `${pct(budgetPoints, breakdown?.budget_max ?? 30)}%` }} />
                </div>
              </div>
              <div className="cc-breakdown-row">
                <span>Days logged</span>
                <b>
                  {loggingPoints} / {breakdown?.logging_max ?? 30}
                </b>
                <div className="cc-breakdown-bar">
                  <i style={{ width: `${pct(loggingPoints, breakdown?.logging_max ?? 30)}%` }} />
                </div>
              </div>
            </div>
          </div>
          <p className="cc-sub" style={{ marginTop: 14 }}>
            Built from your ledger: {breakdown?.savings_rate ?? 0}% of income kept
            {breakdown?.budget_lines
              ? `, ${breakdown.budget_lines_ok} of ${breakdown.budget_lines} budget lines inside their limit`
              : `, no budgets yet (${budgetPoints} free points)`}
            , {breakdown?.log_days ?? 0} days with activity in the last 30.
          </p>
        </Card>

        <div className="cc-span-12 cc-stats">
          <StatTile
            label="This month's spend"
            value={money(expenses)}
            meta={
              <>
                <Pill tone={spentPct >= 100 ? 'over' : spentPct >= 80 ? 'warn' : 'ok'}>{spentPct}% of income</Pill>
                <span>top: {dashboard.top_category?.category__name || 'nothing yet'}</span>
              </>
            }
          />
          <StatTile label="Money in" value={money(income)} meta={<span>{monthLabel(dashboard.month)} so far</span>} tone="accent" />
          <StatTile
            label="Runway"
            value={pulse?.days_left != null ? `${pulse.days_left} days` : '—'}
            meta={<span>{money(pulse?.avg_daily_burn || 0)} avg burn / day</span>}
            tone={pulse?.days_left != null && pulse.days_left <= 7 ? 'over' : ''}
          />
        </div>

        <Card className="cc-span-7 cc-card--flush">
          <div style={{ padding: '24px 24px 8px' }}>
            <CardHead
              eyebrow="Ledger"
              title="Recent transactions"
              action={
                <Link to="/transactions" className="cc-link">
                  All <Icon name="arrow" size={13} />
                </Link>
              }
            />
          </div>
          {recent.length ? (
            <div className="cc-txlist">
              {recent.map((item) => (
                <TransactionRow key={item.id} item={item} />
              ))}
            </div>
          ) : (
            <EmptyState
              icon="list"
              title="No transactions yet"
              text="Log your allowance first — every chart and score starts from there."
              actionLabel="Log your first transaction"
              onAction={() => setDialog(true)}
            />
          )}
        </Card>

        <Card className="cc-span-5">
          <CardHead
            eyebrow="Guardrails"
            title="Budget progress"
            action={
              <Link to="/budgets" className="cc-link">
                Manage <Icon name="arrow" size={13} />
              </Link>
            }
          />
          {budgetLines.length ? (
            <div style={{ display: 'grid', gap: 18 }}>
              {budgetLines.slice(0, 4).map((budget) => {
                const limit = Number(budget.limit) || 0
                const spent = Number(budget.spent) || 0
                const ratio = pct(spent, limit)
                return (
                  <Meter
                    key={budget.category}
                    label={budget.category}
                    value={spent}
                    max={limit}
                    note={`${Math.round(ratio)}% used · ${money(Math.max(0, limit - spent))} left`}
                  />
                )
              })}
            </div>
          ) : (
            <EmptyState
              icon="wallet"
              title="No budgets this month"
              text="Pick one category — food is a good start — and the meter turns amber before you overshoot."
              action={
                <Link className="cc-btn cc-btn--primary cc-btn--sm" to="/budgets">
                  Set a budget
                </Link>
              }
            />
          )}
        </Card>

        <Card className="cc-span-7">
          <CardHead
            eyebrow="On the horizon"
            title="Upcoming bills"
            action={<Pill tone="info">{bills.length} monthly</Pill>}
          />
          {bills.length ? (
            <div className="cc-upcoming">
              {bills.map((bill) => {
                const next = new Date(`${bill.next}T12:00:00`)
                return (
                  <div className="cc-upcoming-row" key={`${bill.id}-${bill.next}`}>
                    <div className="cc-upcoming-when">
                      <b>{next.getDate()}</b>
                      <small>{next.toLocaleDateString(undefined, { month: 'short' })}</small>
                    </div>
                    <div className="cc-upcoming-body">
                      <b>{bill.description || bill.category_name}</b>
                      <small>
                        {bill.category_name} · {bill.days === 0 ? 'due today' : `in ${bill.days} day${bill.days === 1 ? '' : 's'}`}
                      </small>
                    </div>
                    <span className="cc-tx-amount">{money(bill.amount)}</span>
                  </div>
                )
              })}
              <Link to="/transactions" className="cc-link" style={{ marginTop: 12 }}>
                Manage recurring bills <Icon name="arrow" size={13} />
              </Link>
            </div>
          ) : (
            <EmptyState
              icon="calendar"
              title="No monthly bills tracked"
              text="Mark rent, data or a subscription as a monthly bill and it will show up here before it lands."
              action={
                <Link className="cc-btn cc-btn--primary cc-btn--sm" to="/transactions">
                  Add a recurring bill
                </Link>
              }
            />
          )}
        </Card>

        <Card className="cc-span-5">
          <CardHead eyebrow="Nudge" title={dashboard.tips?.[0] ? 'This week’s tip' : 'Your next best move'} />
          <p className="cc-sub" style={{ marginBottom: 14 }}>
            {dashboard.tips?.[0]?.tip_text ||
              'Log seven days in a row and the money-health score becomes genuinely useful — it stops guessing.'}
          </p>
          {activeGoal && (
            <div style={{ marginBottom: 14 }}>
              <div className="cc-meter-head" style={{ marginBottom: 6 }}>
                <b>
                  {activeGoal.emoji} {activeGoal.name}
                </b>
                <span>{activeGoal.progress}%</span>
              </div>
              <Meter label="" value={activeGoal.saved_amount} max={activeGoal.target_amount} compact />
            </div>
          )}
          <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
            <Link to="/tips" className="cc-btn cc-btn--ghost cc-btn--sm">
              Smart tips
            </Link>
            <Link to="/goals" className="cc-btn cc-btn--ghost cc-btn--sm">
              {activeGoal ? 'Open goal jars' : 'Start a goal jar'}
            </Link>
          </div>
        </Card>

        <Card className="cc-span-12">
          <CardHead
            eyebrow="Where it went"
            title="Spending by category"
            action={
              <Link to="/reports" className="cc-link">
                Full report <Icon name="arrow" size={13} />
              </Link>
            }
          />
          {categoryRows.length ? (
            <>
              <div className="cc-bars" role="img" aria-label="Spending by category">
                {categoryRows.slice(0, 7).map((row, index) => {
                  const peak = Math.max(...categoryRows.map((item) => Number(item.total)), 1)
                  return (
                    <div className="cc-bars-col" key={row.category__name} title={`${row.category__name}: ${money(row.total)}`}>
                      <div className="cc-bars-track">
                        <i
                          className={`cc-bars-fill ${index === 0 ? '' : index % 2 ? 'amber' : 'dim'}`.trim()}
                          style={{ height: `${Math.max(6, (Number(row.total) / peak) * 100)}%`, animationDelay: `${index * 0.06}s` }}
                        />
                      </div>
                      <span className="cc-bars-label">{money(row.total, { decimals: 0 })}</span>
                      <span className="cc-bars-label" style={{ color: 'var(--text-soft)' }}>
                        {row.category__name.slice(0, 10)}
                      </span>
                    </div>
                  )
                })}
              </div>
              <p className="cc-hint" style={{ marginTop: 10 }}>
                {categoryRows[0].category__name} is {Math.round((Number(categoryRows[0].total) / (expenses || 1)) * 100)}% of
                this month's spending.
              </p>
            </>
          ) : (
            <EmptyState
              icon="trend"
              title="Charts need a little data"
              text="Two or three expenses are enough for the breakdown to take shape."
              actionLabel="Log an expense"
              onAction={() => setDialog(true)}
            />
          )}
        </Card>
      </div>

      <TransactionDialog
        open={dialog}
        onClose={() => setDialog(false)}
        categories={categories}
        onSaved={() => {
          setDialog(false)
          load()
          toast({ title: 'Overview updated', message: 'Balances and health score refreshed.' })
        }}
      />
    </>
  )
}
