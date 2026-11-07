import { useEffect, useState } from 'react'
import { api } from '../api'
import { cash, EmptyState, ErrorState, Field, PageHead, Skeleton } from '../components'

const todayStr = () => new Date().toISOString().slice(0, 10)
const BLANK = { title: '', amount: '', bill_date: todayStr(), names: '' }

const parseNames = (value) => Array.from(new Set(value.split(',').map((n) => n.trim()).filter(Boolean)))

export default function Splits() {
  const [bills, setBills] = useState(null)
  const [error, setError] = useState('')
  const [form, setForm] = useState(BLANK)
  const [showForm, setShowForm] = useState(false)
  const [busy, setBusy] = useState(false)
  const [note, setNote] = useState('')
  const [payments, setPayments] = useState({})

  const load = () => {
    setError('')
    api('/splits/').then((data) => setBills(Array.isArray(data) ? data : data.results || [])).catch((err) => setError(err.message))
  }

  useEffect(() => { load() }, [])

  const set = (key) => (event) => setForm({ ...form, [key]: event.target.value })
  const names = parseNames(form.names)

  const submit = async (event) => {
    event.preventDefault()
    setBusy(true)
    setNote('')
    try {
      await api('/splits/', { method: 'POST', body: { title: form.title.trim(), amount: form.amount, bill_date: form.bill_date, paid_by: 'me', names } })
      setForm(BLANK)
      setShowForm(false)
      load()
    } catch (err) {
      setNote(err.message)
    } finally {
      setBusy(false)
    }
  }

  const recordPayment = async (bill, member) => {
    const raw = payments[member.id]
    const amount = Number(raw)
    if (raw === undefined || raw === '' || Number.isNaN(amount)) {
      setNote('Enter the amount this person is paying now.')
      return
    }
    setNote('')
    try {
      await api(`/splits/${bill.id}/members/${member.id}/settle/`, { method: 'POST', body: { paid: amount } })
      setPayments((p) => ({ ...p, [member.id]: undefined }))
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  const markSettled = async (bill, member) => {
    setNote('')
    try {
      await api(`/splits/${bill.id}/members/${member.id}/settle/`, { method: 'POST', body: { paid: member.share } })
      setPayments((p) => ({ ...p, [member.id]: undefined }))
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  const remove = async (bill) => {
    if (!window.confirm(`Delete the "${bill.title}" bill?`)) return
    await api(`/splits/${bill.id}/`, { method: 'DELETE' }).catch((err) => setNote(err.message))
    load()
  }

  if (error) return <ErrorState message={error} onRetry={load} />
  if (bills === null) return <Skeleton lines={4} />

  let owedIn = 0
  let owedOut = 0
  bills.forEach((b) => {
    owedOut += Number(b.my_owes) || 0
    b.members.forEach((m) => { if (!m.is_user && m.owes > 0) owedIn += Number(m.owes) })
  })
  const net = owedIn - owedOut

  return (
    <>
      <PageHead
        eyebrow="Split & settle"
        title="Shared bills, zero arguments."
        action={<button className="cta small" type="button" onClick={() => { setShowForm(!showForm); setNote('') }}>{showForm ? 'Close form' : '+ New bill'}</button>}
      />
      <section className="numbers slim">
        <article className="metric income"><p>They owe you</p><h2>{cash(owedIn)}</h2></article>
        <article className="metric expense"><p>You owe</p><h2>{cash(owedOut)}</h2></article>
        <article className="metric balance"><p>Net position</p><h2>{net < 0 ? '-' : ''}{cash(Math.abs(net))}</h2></article>
      </section>
      {showForm && (
        <form className="goal-form glass rise" onSubmit={submit}>
          <p className="overline">Add a shared bill</p>
          <div className="form-grid">
            <Field label="What was it for?"><input required maxLength={120} value={form.title} onChange={set('title')} placeholder="Rent for the flat" /></Field>
            <Field label="Total amount"><input required type="number" min="0.01" step="0.01" value={form.amount} onChange={set('amount')} placeholder="450" /></Field>
            <Field label="Date"><input required type="date" value={form.bill_date} onChange={set('bill_date')} /></Field>
            <Field label="Friends sharing it (comma separated)"><input required value={form.names} onChange={set('names')} placeholder="Kofi, Ama, Tunde" /></Field>
          </div>
          <p className="hint">You are added automatically. Each person&#8217;s share is the total split evenly — then you record what each of you actually pays.</p>
          {names.length > 0 && form.amount && (
            <p className="hint">Splits {names.length + 1} ways — {cash(Number(form.amount || 0) / (names.length + 1))} per head.</p>
          )}
          {note && <p className="form-note">{note}</p>}
          <div className="button-row">
            <button className="cta small" disabled={busy}>Create bill <b>→</b></button>
            <button type="button" className="coin-btn" onClick={() => { setShowForm(false); setNote('') }}>Cancel</button>
          </div>
        </form>
      )}
      {bills.length === 0 && !showForm && (
        <EmptyState icon="⑂" title="No shared bills yet." text="Add a bill you split with friends — Campus Coin works out each person's share, then tracks what everyone has paid and who still owes." />
      )}
      <div className="jar-grid">
        {bills.map((bill) => (
          <article className="split-card glass rise" key={bill.id}>
            <div className="jar-top">
              <span className="jar-emoji">⑂</span>
              <div><b>{bill.title}</b><small>{bill.bill_date} · {bill.members.length} people</small></div>
            </div>
            <p className="jar-line">Total <b>{cash(bill.amount)}</b> · <b>{cash(bill.per_head)}</b> each</p>
            <p className="jar-line">Collected <b>{cash(bill.total_paid)}</b> of {cash(bill.amount)} · {bill.remaining > 0 ? <>{cash(bill.remaining)} still to gather</> : <>fully paid</>}</p>
            <p className="jar-line hint">Type what each person pays now — entries add up until their share is covered.</p>
            <div className="member-rows">
              {bill.members.map((m) => {
                const owes = Number(m.owes)
                const settled = owes <= 0
                return (
                  <div className={`member-row ${settled ? 'settled' : ''}`} key={m.id}>
                    <div className="member-head">
                      <span>{m.is_user ? 'You' : m.name}</span>
                      {settled
                        ? <b className="pill ok">Paid ✓</b>
                        : owes > Number(m.share)
                          ? <b className="pill over">Overpaid {cash(owes)}</b>
                          : <b className="pill">Owes {cash(owes)}</b>}
                    </div>
                    <div className="member-nums">
                      <small>share <b>{cash(m.share)}</b></small>
                      <small>paid <b>{cash(m.paid)}</b></small>
                    </div>
                    <div className="member-pay">
                      <input
                        type="number"
                        step="0.01"
                        placeholder={settled ? 'Paid in full' : `Pays now (${cash(m.paid)} paid)`}
                        value={payments[m.id] === undefined ? '' : payments[m.id]}
                        onChange={(e) => setPayments((p) => ({ ...p, [m.id]: e.target.value }))}
                      />
                      <button type="button" className="coin-btn edit" onClick={() => recordPayment(bill, m)}>Add</button>
                      {!settled && <button type="button" className="coin-btn" onClick={() => markSettled(bill, m)}>Full share</button>}
                    </div>
                  </div>
                )
              })}
            </div>
            {note && <p className="form-note">{note}</p>}
            <p className={`jar-line hint ${bill.my_owes <= 0 ? 'good-line' : ''}`}>
              {bill.my_owes <= 0
                ? 'You are square on this bill.'
                : `You still owe ${cash(bill.my_owes)} on this bill.`}
            </p>
            <div className="button-row">
              <button type="button" className="coin-btn delete" onClick={() => remove(bill)}>Delete bill</button>
            </div>
          </article>
        ))}
      </div>
    </>
  )
}
