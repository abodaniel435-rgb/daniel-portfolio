import { useEffect, useState } from 'react'
import { api } from '../api'
import { asList, cash, EmptyState, ErrorState, Field, PageHead, Skeleton } from '../components'

const monthStart = () => `${new Date().toISOString().slice(0, 7)}-01`

export default function Budgets() {
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [budgets, setBudgets] = useState([])
  const [categories, setCategories] = useState([])
  const [form, setForm] = useState({ category: '', limit_amount: '', month: monthStart() })
  const [note, setNote] = useState('')

  const load = () => {
    setStatus('loading')
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

  const save = async (event) => {
    event.preventDefault()
    setNote('')
    try {
      await api('/budgets/', { method: 'POST', body: { ...form, category: Number(form.category) } })
      setForm({ ...form, category: '', limit_amount: '' })
      setNote('Guardrail locked in.')
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  const remove = async (budget) => {
    if (!window.confirm(`Remove the ${budget.category_name} budget?`)) return
    try {
      await api(`/budgets/${budget.id}/`, { method: 'DELETE' })
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  if (status === 'loading' && budgets.length === 0) return <Skeleton lines={4} />
  if (status === 'error' && budgets.length === 0) return <ErrorState message={error} onRetry={load} />

  const hot = budgets.filter((budget) => Number(budget.limit_amount) > 0 && Number(budget.spent) / Number(budget.limit_amount) >= 0.8)

  return (
    <>
      <PageHead eyebrow="Give your money a job" title="Budget with intention." />
      {hot.length > 0 && (
        <div className="alert-banner rise">
          {hot.map((budget) => {
            const ratio = Number(budget.spent) / Number(budget.limit_amount)
            return <p key={budget.id}>{budget.category_name} is {ratio >= 1 ? 'past' : 'near'} its line — {cash(budget.spent)} of {cash(budget.limit_amount)}.</p>
          })}
        </div>
      )}
      <section className="two-col">
        <form className="glass rise" onSubmit={save}>
          <h2>Set your guardrail</h2>
          <Field label="Expense category">
            <select required value={form.category} onChange={(event) => setForm({ ...form, category: event.target.value })}>
              <option value="">Choose a category</option>
              {categories.filter((category) => category.type === 'expense').map((category) => (
                <option key={category.id} value={category.id}>{category.name}</option>
              ))}
            </select>
          </Field>
          <Field label="Monthly limit">
            <input required type="number" min="0.01" step="0.01" value={form.limit_amount} onChange={(event) => setForm({ ...form, limit_amount: event.target.value })} />
          </Field>
          <Field label="Month">
            <input required type="month" value={form.month.slice(0, 7)} onChange={(event) => setForm({ ...form, month: `${event.target.value}-01` })} />
          </Field>
          {note && <p className={note.includes('locked') ? 'form-note ok' : 'form-note'}>{note}</p>}
          <button className="cta full">Lock in this budget <b>→</b></button>
        </form>
        <section className="budget-cards rise delay">
          {budgets.map((budget, index) => {
            const limit = Number(budget.limit_amount) || 1
            const spent = Number(budget.spent) || 0
            const remaining = limit - spent
            const ratio = Math.min(100, (spent / limit) * 100)
            const tone = ratio >= 100 ? 'over' : ratio >= 80 ? 'warn' : ''
            return (
              <article key={budget.id} className={`budget-card color-${index % 3}`}>
                <div className="budget-card-inner">
                  <div className="card-top">
                    <p title={budget.category_name}>{budget.category_name}</p>
                    <button type="button" className="text-btn" onClick={() => remove(budget)}>Remove ✕</button>
                  </div>
                  <h2>{cash(spent)} <small>of {cash(limit)}</small></h2>
                  <div className="budget-money">
                    <span className="spent-pill">Spent {cash(spent)}</span>
                    <span className={remaining < 0 ? 'remain-pill negative' : 'remain-pill'}>{remaining < 0 ? `Over by ${cash(Math.abs(remaining))}` : `Left ${cash(remaining)}`}</span>
                  </div>
                  <div className="track" role="progressbar" aria-valuenow={Math.round(ratio)} aria-valuemin="0" aria-valuemax="100"><i className={tone} style={{ width: `${ratio}%` }} /></div>
                  <p className="budget-meta"><span>{budget.month?.slice(0, 7)} · {Math.round(ratio)}% used</span><span className="budget-pct">{ratio >= 100 ? 'OVER!' : ratio >= 80 ? 'HOT!' : 'ON TRACK'}</span></p>
                </div>
              </article>
            )
          })}
          {budgets.length === 0 && <EmptyState icon="◎" title="Set your first budget" text="Pick a category and give yourself a bright line to follow." />}
        </section>
      </section>
    </>
  )
}
