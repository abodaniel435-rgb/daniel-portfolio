import { useEffect, useState } from 'react'
import { api } from '../api'
import { cash, EmptyState, ErrorState, Field, PageHead, Skeleton } from '../components'

const BLANK = { name: '', emoji: '🎯', target_amount: '', saved_amount: '0', deadline: '' }

const fmtDate = (value) => {
  try {
    return new Date(value + 'T12:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
  } catch {
    return value
  }
}

function paceLine(goal) {
  const short = Math.max(0, Number(goal.target_amount) - Number(goal.saved_amount))
  if (goal.progress >= 100) return 'Jar full. Go get the thing! 🎉'
  if (!goal.deadline) return 'No deadline — every coin you drop counts.'
  if (goal.days_left > 0) return `Drop ${cash(goal.per_day)}/day for ${goal.days_left} days to land by ${fmtDate(goal.deadline)}.`
  if (goal.days_left === 0) return short > 0 ? `Deadline is today — ${cash(short)} to go. One last push!` : 'You made it right on the day!'
  return `Deadline passed with ${cash(short)} to go. Move the date or top up the jar.`
}

export default function Goals() {
  const [goals, setGoals] = useState(null)
  const [error, setError] = useState('')
  const [form, setForm] = useState(BLANK)
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)
  const [busy, setBusy] = useState(false)
  const [note, setNote] = useState('')
  const [deposit, setDeposit] = useState({})

  const load = () => {
    setError('')
    api('/goals/')
      .then((data) => setGoals(Array.isArray(data) ? data : data.results || []))
      .catch((err) => setError(err.message))
  }

  useEffect(() => { load() }, [])

  const set = (key) => (event) => setForm({ ...form, [key]: event.target.value })

  const submit = async (event) => {
    event.preventDefault()
    setBusy(true)
    setNote('')
    const payload = { name: form.name.trim(), emoji: form.emoji || '🎯', target_amount: form.target_amount, deadline: form.deadline || null }
    if (editing) payload.saved_amount = form.saved_amount
    try {
      if (editing) await api(`/goals/${editing.id}/`, { method: 'PATCH', body: payload })
      else await api('/goals/', { method: 'POST', body: payload })
      setForm(BLANK)
      setEditing(null)
      setShowForm(false)
      load()
    } catch (err) {
      setNote(err.message)
    } finally {
      setBusy(false)
    }
  }

  const startEdit = (goal) => {
    setEditing(goal)
    setForm({ name: goal.name, emoji: goal.emoji, target_amount: String(goal.target_amount), saved_amount: String(goal.saved_amount), deadline: goal.deadline || '' })
    setShowForm(true)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  const cancelForm = () => {
    setShowForm(false)
    setEditing(null)
    setForm(BLANK)
    setNote('')
  }

  const remove = async (goal) => {
    if (!window.confirm(`Smash the "${goal.name}" jar? This cannot be undone.`)) return
    await api(`/goals/${goal.id}/`, { method: 'DELETE' }).catch((err) => setNote(err.message))
    load()
  }

  const addMoney = async (goal) => {
    const amount = Number(deposit[goal.id])
    if (!amount || amount <= 0) return
    try {
      await api(`/goals/${goal.id}/`, { method: 'PATCH', body: { saved_amount: Number(goal.saved_amount) + amount } })
      setDeposit({ ...deposit, [goal.id]: '' })
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  if (error) return <ErrorState message={error} onRetry={load} />
  if (goals === null) return <Skeleton lines={4} />

  return (
    <>
      <PageHead
        eyebrow="Goal jars"
        title="Save toward the things you want."
        action={<button className="cta small" type="button" onClick={() => (showForm ? cancelForm() : setShowForm(true))}>{showForm ? 'Close form' : '+ New jar'}</button>}
      />
      {showForm && (
        <form className="goal-form glass rise" onSubmit={submit}>
          <p className="overline">{editing ? 'Rewrite this jar' : 'Start a new jar'}</p>
          <div className="form-grid">
            <Field label="Jar name"><input required maxLength={120} value={form.name} onChange={set('name')} placeholder="New laptop" /></Field>
            <Field label="Emoji"><input value={form.emoji} onChange={set('emoji')} maxLength={4} /></Field>
            <Field label="Target amount"><input required type="number" min="1" step="0.01" value={form.target_amount} onChange={set('target_amount')} placeholder="450000" /></Field>
            <Field label="Deadline (optional)"><input type="date" value={form.deadline} onChange={set('deadline')} /></Field>
            {editing && <Field label="Already saved"><input type="number" min="0" step="0.01" value={form.saved_amount} onChange={set('saved_amount')} /></Field>}
          </div>
          {note && <p className="form-note">{note}</p>}
          <div className="button-row">
            <button className="cta small" disabled={busy}>{editing ? 'Save changes' : 'Create jar'} <b>→</b></button>
            <button type="button" className="coin-btn" onClick={cancelForm}>Cancel</button>
          </div>
        </form>
      )}
      {goals.length === 0 && !showForm && (
        <EmptyState icon="🫙" title="No jars on the shelf yet." text="Create a jar for anything you are saving for — a phone, a trip, rent — and Campus Coin works out the daily drip." />
      )}
      <div className="jar-grid">
        {goals.map((goal) => (
          <article className="jar-card glass rise" key={goal.id}>
            <div className="jar-top">
              <span className="jar-emoji">{goal.emoji}</span>
              <div><b>{goal.name}</b><small>{goal.deadline ? `by ${fmtDate(goal.deadline)}` : 'no deadline'}</small></div>
              <span className={goal.progress >= 100 ? 'jar-pct done' : 'jar-pct'}>{goal.progress}%</span>
            </div>
            <div className="jar" role="img" aria-label={`${goal.progress}% of ${goal.name} saved`}>
              <i style={{ height: `${goal.progress}%` }} />
              <b>{cash(goal.saved_amount)}</b>
            </div>
            <p className="jar-line">of <b>{cash(goal.target_amount)}</b> · <b>{cash(Math.max(0, Number(goal.target_amount) - Number(goal.saved_amount)))}</b> to go</p>
            <p className="jar-line hint">{paceLine(goal)}</p>
            <div className="jar-actions">
              <input type="number" min="0" step="0.01" placeholder="Add money" value={deposit[goal.id] || ''} onChange={(event) => setDeposit({ ...deposit, [goal.id]: event.target.value })} aria-label={`Amount to add to ${goal.name}`} />
              <button type="button" className="coin-btn edit" onClick={() => addMoney(goal)}>Drop in</button>
              <button type="button" className="coin-btn edit" onClick={() => startEdit(goal)}>Edit</button>
              <button type="button" className="coin-btn delete" onClick={() => remove(goal)}>Delete</button>
            </div>
          </article>
        ))}
      </div>
    </>
  )
}
