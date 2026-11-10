import { useEffect, useMemo, useState } from 'react'
import { api } from '../api'
import {
  asList,
  BudgetLine,
  Card,
  EmptyState,
  ErrorState,
  Icon,
  Modal,
  PageHead,
  SkeletonCard,
  StatTile,
  money,
  useToast,
} from '../components'
import { BudgetDialog } from '../forms'

const THIS_MONTH = () => `${new Date().toISOString().slice(0, 7)}-01`

const monthKey = (value) => String(value || '').slice(0, 7)

function monthName(value) {
  if (!value) return 'This month'
  const date = new Date(`${String(value).slice(0, 7)}-01T12:00:00`)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
}

export default function Budgets() {
  const { toast } = useToast()
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [budgets, setBudgets] = useState([])
  const [categories, setCategories] = useState([])
  const [month, setMonth] = useState('current')
  const [dialog, setDialog] = useState(null)
  const [confirming, setConfirming] = useState(null)
  const [busyId, setBusyId] = useState(null)

  const load = () => {
    setStatus((current) => (current === 'ready' ? 'ready' : 'loading'))
    setError('')
    Promise.all([api('/budgets/'), api('/categories/')])
      .then(([budgetRows, categoryRows]) => {
        setBudgets(asList(budgetRows))
        setCategories(asList(categoryRows))
        setStatus('ready')
      })
      .catch((err) => {
        setError(err.message)
        setStatus('error')
      })
  }

  useEffect(load, [])

  const months = useMemo(() => {
    const unique = Array.from(new Set(budgets.map((budget) => monthKey(budget.month)))).filter(Boolean)
    unique.sort((a, b) => b.localeCompare(a))
    return unique
  }, [budgets])

  const currentMonth = THIS_MONTH().slice(0, 7)
  const activeMonth = month === 'current' ? currentMonth : month === 'all' ? 'all' : month

  const visible = useMemo(
    () => budgets.filter((budget) => activeMonth === 'all' || monthKey(budget.month) === activeMonth),
    [budgets, activeMonth],
  )

  const totals = useMemo(
    () =>
      visible.reduce(
        (acc, budget) => {
          acc.limit += Number(budget.limit_amount) || 0
          acc.spent += Number(budget.spent) || 0
          return acc
        },
        { limit: 0, spent: 0 },
      ),
    [visible],
  )
  const left = totals.limit - totals.spent
  const usedPct = totals.limit > 0 ? Math.round((totals.spent / totals.limit) * 100) : 0

  const hot = visible.filter((budget) => {
    const limit = Number(budget.limit_amount) || 0
    return limit > 0 && Number(budget.spent) / limit >= 0.8
  })

  const remove = async () => {
    if (!confirming) return
    const target = confirming
    setBusyId(target.id)
    try {
      await api(`/budgets/${target.id}/`, { method: 'DELETE' })
      setBudgets((current) => current.filter((budget) => budget.id !== target.id))
      toast({ title: 'Budget removed', message: `${target.category_name} · ${monthName(target.month)}` })
      setConfirming(null)
    } catch (err) {
      toast({ tone: 'error', title: 'Could not remove it', message: err.message })
    } finally {
      setBusyId(null)
    }
  }

  if (status === 'loading' && budgets.length === 0) {
    return (
      <>
        <PageHead eyebrow="Give your money a job" title="Budgets" />
        <div className="cc-dash">
          <div className="cc-span-7">
            <SkeletonCard lines={5} />
          </div>
          <div className="cc-span-5">
            <SkeletonCard lines={4} />
          </div>
        </div>
      </>
    )
  }

  if (status === 'error' && budgets.length === 0) return <ErrorState message={error} onRetry={load} />

  return (
    <>
      <PageHead
        eyebrow="Give your money a job"
        title="Budgets"
        subtitle="Bright lines for each category, so the month does not run away with your money."
        action={
          <button type="button" className="cc-btn cc-btn--primary" onClick={() => setDialog({ mode: 'new' })}>
            <Icon name="plus" size={16} /> New budget
          </button>
        }
      />

      {hot.length > 0 && (
        <div className="cc-banner" role="status">
          <span className="cc-banner-mark" aria-hidden="true">
            <Icon name="alert" size={16} />
          </span>
          <div>
            <b>{hot.length === 1 ? 'One budget needs attention' : `${hot.length} budgets need attention`}</b>
            <p>
              {hot
                .slice(0, 3)
                .map((budget) => {
                  const ratio = Number(budget.spent) / (Number(budget.limit_amount) || 1)
                  return `${budget.category_name} is ${ratio >= 1 ? 'past' : 'near'} its line (${Math.round(ratio * 100)}%)`
                })
                .join(' · ')}
              {hot.length > 3 ? ` · +${hot.length - 3} more` : ''}
            </p>
          </div>
        </div>
      )}

      <div className="cc-stats">
        <StatTile label="Total guardrails" value={money(totals.limit, { decimals: 0 })} meta={`${visible.length} categor${visible.length === 1 ? 'y' : 'ies'} covered`} />
        <StatTile label="Spent against them" value={money(totals.spent, { decimals: 0 })} meta={`${usedPct}% of the plan`} tone={usedPct >= 100 ? 'warn' : ''} />
        <StatTile
          label={left >= 0 ? 'Still available' : 'Over the plan'}
          value={money(Math.abs(left), { decimals: 0 })}
          meta={left >= 0 ? 'across these categories' : 'trim or rebalance'}
          tone={left >= 0 ? 'accent' : 'warn'}
        />
      </div>

      <div className="cc-toolbar">
        <label className="cc-field" style={{ minWidth: 210 }}>
          <span className="cc-label">Month in view</span>
          <select className="cc-select" value={activeMonth} onChange={(event) => setMonth(event.target.value)}>
            <option value={currentMonth}>{monthName(currentMonth)} (current)</option>
            {months.filter((entry) => entry !== currentMonth).map((entry) => (
              <option key={entry} value={entry}>
                {monthName(entry)}
              </option>
            ))}
            <option value="all">Every month</option>
          </select>
        </label>
        <p className="cc-hint" style={{ marginLeft: 'auto' }}>
          Budgets reset each month — copy a good one forward with New budget.
        </p>
      </div>

      {visible.length === 0 ? (
        <Card>
          <EmptyState
            icon="wallet"
            title={activeMonth === 'all' ? 'No budgets yet' : `Nothing set for ${monthName(activeMonth)}`}
            text="Pick a category — food, transport, data — and give it a monthly limit. Campus Coin tracks the rest."
            actionLabel="Create a budget"
            onAction={() => setDialog({ mode: 'new', month: activeMonth === 'all' ? currentMonth : activeMonth })}
          />
        </Card>
      ) : (
        <div className="cc-budget-grid">
          {visible.map((budget) => (
            <BudgetLine
              key={budget.id}
              budget={budget}
              onEdit={(row) => setDialog({ mode: 'edit', item: row })}
              onDelete={(row) => setConfirming(row)}
            />
          ))}
        </div>
      )}

      <BudgetDialog
        open={Boolean(dialog)}
        initial={dialog?.item || null}
        defaultMonth={dialog?.month || ''}
        categories={categories}
        onClose={() => setDialog(null)}
        onSaved={() => load()}
      />

      <Modal
        open={Boolean(confirming)}
        onClose={() => (busyId ? null : setConfirming(null))}
        eyebrow="Remove budget"
        title="Drop this guardrail?"
        footer={
          <>
            <button type="button" className="cc-btn cc-btn--ghost" onClick={() => setConfirming(null)} disabled={Boolean(busyId)}>
              Keep it
            </button>
            <button type="button" className="cc-btn cc-btn--danger" onClick={remove} disabled={Boolean(busyId)}>
              {busyId ? 'Removing…' : 'Yes, remove'}
            </button>
          </>
        }
      >
        {confirming && (
          <p className="cc-sub">
            <b>{confirming.category_name}</b> for {monthName(confirming.month)} — {money(confirming.limit_amount)} a month.
            Your transactions stay untouched; only the guardrail goes away.
          </p>
        )}
      </Modal>
    </>
  )
}
