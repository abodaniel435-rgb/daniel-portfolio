import { useEffect, useState } from 'react'
import { api } from '../api'
import { asList, Burst, cash, EmptyState, ErrorState, Field, PageHead, Skeleton } from '../components'
import { rememberCategory, suggestCategory } from '../suggest'

const today = () => new Date().toISOString().slice(0, 10)
const blank = () => ({ type: 'expense', category: '', amount: '', description: '', transaction_date: today(), is_recurring: false })

export default function Transactions() {
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [items, setItems] = useState([])
  const [categories, setCategories] = useState([])
  const [form, setForm] = useState(blank())
  const [editing, setEditing] = useState(null)
  const [manual, setManual] = useState(false)
  const [hint, setHint] = useState('')
  const [note, setNote] = useState('')
  const [filter, setFilter] = useState('all')
  const [query, setQuery] = useState('')
  const [burst, setBurst] = useState(false)
  const [csvNote, setCsvNote] = useState('')

  const load = () => {
    setStatus('loading')
    setError('')
    Promise.all([api('/transactions/'), api('/categories/')])
      .then(([transactions, cats]) => {
        setItems(asList(transactions))
        setCategories(asList(cats))
        setStatus('ready')
      })
      .catch((err) => {
        setError(err.message)
        setStatus('error')
      })
  }

  useEffect(load, [])

  const norm = (value) => String(value || '').trim().toLowerCase()
  const pool = categories.filter((category) => norm(category.type) === norm(form.type))
  const fallbackPool = pool.length > 0 ? pool : categories
  const visible = items.filter((item) => {
    const typeOk = filter === 'all' || item.type === filter
    const text = `${item.description || ''} ${item.category_name || ''}`.toLowerCase()
    return typeOk && text.includes(query.trim().toLowerCase())
  })

  const onDescription = (value) => {
    const next = { ...form, description: value }
    if (!manual) {
      const guess = suggestCategory(value, categories, form.type)
      if (guess) {
        next.category = String(guess.id)
        setHint(`Suggested ${guess.name}. Change it if that is off — Campus Coin will remember.`)
      } else {
        setHint('')
      }
    }
    setForm(next)
  }

  const save = async (event) => {
    event.preventDefault()
    setNote('')
    const payload = {
      ...form,
      category: Number(form.category),
      amount: form.amount,
      is_recurring: Boolean(form.is_recurring),
    }
    const duplicate = items.some((item) => (
      item.id !== editing
      && item.type === form.type
      && String(item.category) === String(form.category)
      && Number(item.amount) === Number(form.amount)
      && item.transaction_date === form.transaction_date
      && (item.description || '') === (form.description || '')
    ))
    if (duplicate && !window.confirm('This looks like a duplicate of a transaction you already saved. Save it anyway?')) return
    try {
      if (editing) await api(`/transactions/${editing}/`, { method: 'PATCH', body: payload })
      else await api('/transactions/', { method: 'POST', body: payload })
      rememberCategory(form.type, form.description || '', form.category)
      setForm(blank())
      setEditing(null)
      setManual(false)
      setHint('')
      setBurst(true)
      setTimeout(() => setBurst(false), 800)
      setNote(editing ? 'Transaction updated.' : 'Saved. The story just got a new line.')
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  const startEdit = (item) => {
    setEditing(item.id)
    setManual(true)
    setHint('')
    setForm({
      type: item.type,
      category: String(item.category),
      amount: item.amount,
      description: item.description || '',
      transaction_date: item.transaction_date,
      is_recurring: Boolean(item.is_recurring),
    })
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const remove = async (item) => {
    if (!window.confirm(`Remove ${item.category_name} · ${cash(item.amount)}? It stays in the history as deleted.`)) return
    try {
      await api(`/transactions/${item.id}/`, { method: 'DELETE' })
      if (editing === item.id) {
        setEditing(null)
        setForm(blank())
      }
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  const importCsv = async (event) => {
    const file = event.target.files?.[0]
    event.target.value = ''
    if (!file) return
    const body = new FormData()
    body.append('file', file)
    setCsvNote('')
    try {
      const result = await api('/transactions/import-csv/', { method: 'POST', body })
      const problems = (result.errors || []).length
      setCsvNote(`Imported ${result.created} row${result.created === 1 ? '' : 's'}${problems ? ` · ${problems} need another look` : ''}.`)
      load()
    } catch (err) {
      setCsvNote(err.message)
    }
  }

  if (status === 'loading' && items.length === 0) return <Skeleton lines={5} />
  if (status === 'error' && items.length === 0) return <ErrorState message={error} onRetry={load} />

  return (
    <>
      <PageHead eyebrow="The daily details" title="Every naira has a story." />
      <section className="two-col">
        <form className="glass transaction-form rise" onSubmit={save}>
          <p className="overline">{editing ? 'Edit entry' : 'Quick entry'}</p>
          <h2>{editing ? 'Rewrite this line.' : 'What happened?'}</h2>
          <div className="toggle">
            <button type="button" className={form.type === 'expense' ? 'selected' : ''} onClick={() => { setForm({ ...form, type: 'expense', category: '' }); setManual(false); setHint('') }}>I spent</button>
            <button type="button" className={form.type === 'income' ? 'selected' : ''} onClick={() => { setForm({ ...form, type: 'income', category: '' }); setManual(false); setHint('') }}>I earned</button>
          </div>
          <Field label="What was it?">
            <input value={form.description} placeholder="e.g. Lunch at the canteen" onChange={(event) => onDescription(event.target.value)} />
          </Field>
          {hint && <p className="hint">{hint}</p>}
          <Field label="Category">
            <select required value={form.category} onChange={(event) => { setManual(true); setForm({ ...form, category: event.target.value }) }}>
              <option value="">{fallbackPool.length === 0 ? 'No categories found — add one under Categories' : 'Pick a category'}</option>
              {fallbackPool.map((category) => <option key={category.id} value={category.id}>{category.name} ({category.type})</option>)}
            </select>
          </Field>
          {categories.length === 0 && <p className="hint">Still empty after reload? Your login token may be stale — log out and back in, or add one under Categories.</p>}{categories.length > 0 && pool.length === 0 && <p className="hint">Showing all categories because none match "{form.type}".</p>}
          <Field label="Amount">
            <input required type="number" step="0.01" min="0.01" placeholder="0.00" value={form.amount} onChange={(event) => setForm({ ...form, amount: event.target.value })} />
          </Field>
          <Field label="Date">
            <input required type="date" value={form.transaction_date} onChange={(event) => setForm({ ...form, transaction_date: event.target.value })} />
          </Field>
          <label className="check">
            <input type="checkbox" checked={form.is_recurring} onChange={(event) => setForm({ ...form, is_recurring: event.target.checked })} />
            This repeats every month
          </label>
          {note && <p className={note.includes('Saved') || note.includes('updated') ? 'form-note ok' : 'form-note'}>{note}</p>}
          <div className="button-row">
            <button className="cta full" type="submit">{editing ? 'Update it' : 'Save it'} <b>→</b></button>
            <Burst active={burst} />
          </div>
          {editing && <button className="text-btn" type="button" onClick={() => { setEditing(null); setForm(blank()); setManual(false) }}>Cancel edit</button>}
          <div className="csv-box">
            <p className="overline">CSV import</p>
            <p className="hint">Columns: type, category, amount, description, transaction_date (YYYY-MM-DD).</p>
            <label className="file-btn">
              Choose a CSV
              <input type="file" accept=".csv,text/csv" onChange={importCsv} />
            </label>
            {csvNote && <p className="hint">{csvNote}</p>}
          </div>
        </form>
        <section className="transaction-list rise delay">
          <div className="list-tools">
            <div>
              <p className="overline">Your latest moves</p>
              <h2>Recent activity</h2>
            </div>
            <div className="filters">
              <select value={filter} onChange={(event) => setFilter(event.target.value)} aria-label="Filter by type">
                <option value="all">All</option>
                <option value="expense">Spent</option>
                <option value="income">Earned</option>
              </select>
              <input value={query} placeholder="Search notes" onChange={(event) => setQuery(event.target.value)} aria-label="Search transactions" />
            </div>
          </div>
          {visible.map((item, index) => (
            <article className="activity rise" style={{ animationDelay: `${Math.min(index, 8) * 0.05}s` }} key={item.id}>
              <span className={item.type}>{item.type === 'income' ? '↓' : '↑'}</span>
              <div>
                <b>{item.category_name}{item.is_recurring ? ' · monthly' : ''}</b>
                <p>{item.description || item.transaction_date}</p>
              </div>
              <strong className={item.type}>{cash(item.amount)}</strong>
            <div className="row-actions coin-actions">
              <button type="button" className="coin-btn edit" onClick={() => startEdit(item)}>Edit</button>
              <button type="button" className="coin-btn delete" onClick={() => remove(item)}>Delete</button>
            </div>
            </article>
          ))}
          {visible.length === 0 && <EmptyState icon="⇄" title="Nothing in this view." text="Log a transaction, or clear the search." />}
        </section>
      </section>
    </>
  )
}
