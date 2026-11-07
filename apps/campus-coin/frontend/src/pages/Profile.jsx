import { useEffect, useState } from 'react'
import { api } from '../api'
import { ErrorState, Field, PageHead, Skeleton } from '../components'
import { COUNTRIES, setCurrency } from '../currency'
import { useSession } from '../session'

const EMPTY = {
  first_name: '',
  last_name: '',
  email: '',
  academic_year: '',
  monthly_allowance_baseline: '',
  monthly_savings_goal: '',
  country: 'Nigeria',
}

export default function Profile() {
  const { setProfile } = useSession()
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [username, setUsername] = useState('')
  const [form, setForm] = useState(EMPTY)
  const [note, setNote] = useState('')

  const load = () => {
    setStatus('loading')
    api('/auth/me/')
      .then((me) => {
        setUsername(me.username || '')
        setForm({
          first_name: me.first_name || '',
          last_name: me.last_name || '',
          email: me.email || '',
          academic_year: me.academic_year || '',
          monthly_allowance_baseline: me.monthly_allowance_baseline ?? '',
          monthly_savings_goal: me.monthly_savings_goal ?? '',
          country: me.country || 'Nigeria',
        })
        setProfile(me)
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
      const me = await api('/auth/me/', { method: 'PATCH', body: form })
      setCurrency(me?.country)
      setProfile(me)
      setNote('Profile saved.')
    } catch (err) {
      setNote(err.message)
    }
  }

  if (status === 'loading') return <Skeleton lines={4} />
  if (status === 'error') return <ErrorState message={error} onRetry={load} />

  const set = (key) => (event) => setForm({ ...form, [key]: event.target.value })

  return (
    <>
      <PageHead eyebrow="This is you" title="Shape the person behind the ledger." />
      <form className="glass profile-form rise" onSubmit={save}>
        <p className="hint">Signed in as <b>{username}</b></p>
        <div className="form-grid">
          <Field label="First name"><input value={form.first_name} onChange={set('first_name')} /></Field>
          <Field label="Last name"><input value={form.last_name} onChange={set('last_name')} /></Field>
          <Field label="Email"><input required type="email" value={form.email} onChange={set('email')} /></Field>
          <Field label="Academic year"><input value={form.academic_year} placeholder="e.g. 300 level" onChange={set('academic_year')} /></Field>
          <Field label="Monthly allowance baseline"><input type="number" min="0" step="0.01" value={form.monthly_allowance_baseline} onChange={set('monthly_allowance_baseline')} /></Field>
          <Field label="Monthly savings goal"><input type="number" min="0" step="0.01" value={form.monthly_savings_goal} onChange={set('monthly_savings_goal')} /></Field>
          <Field label="Country (sets your currency)">
            <select value={form.country} onChange={set('country')}>
              {COUNTRIES.map((entry) => (
                <option key={entry.name} value={entry.name}>{entry.name} — {entry.symbol} {entry.code}</option>
              ))}
            </select>
          </Field>
        </div>
        {note && <p className={note.includes('saved') ? 'form-note ok' : 'form-note'}>{note}</p>}
        <button className="cta">Save profile <b>→</b></button>
      </form>
    </>
  )
}
