import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { api } from '../api'
import {
  Card,
  CardHead,
  ErrorState,
  Field,
  Icon,
  PageHead,
  Pill,
  SkeletonCard,
  money,
  useToast,
} from '../components'
import { COUNTRIES, getCurrency, setCurrency } from '../currency'
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
  const { setProfile, clearSession } = useSession()
  const { toast } = useToast()
  const navigate = useNavigate()
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [me, setMe] = useState(null)
  const [form, setForm] = useState(EMPTY)
  const [errors, setErrors] = useState({})
  const [pending, setPending] = useState(false)
  const [formError, setFormError] = useState('')

  const load = () => {
    setStatus((current) => (current === 'ready' ? 'ready' : 'loading'))
    setError('')
    api('/auth/me/')
      .then((profile) => {
        setMe(profile)
        setForm({
          first_name: profile.first_name || '',
          last_name: profile.last_name || '',
          email: profile.email || '',
          academic_year: profile.academic_year || '',
          monthly_allowance_baseline: profile.monthly_allowance_baseline ?? '',
          monthly_savings_goal: profile.monthly_savings_goal ?? '',
          country: profile.country || 'Nigeria',
        })
        setCurrency(profile.country)
        setProfile(profile)
        setStatus('ready')
      })
      .catch((err) => {
        setError(err.message)
        setStatus('error')
      })
  }

  useEffect(load, [])

  const set = (key) => (event) => {
    const value = event?.target ? event.target.value : event
    setForm((current) => ({ ...current, [key]: value }))
    setErrors((current) => ({ ...current, [key]: undefined }))
  }

  const save = async (event) => {
    event.preventDefault()
    const found = {}
    if (!form.email.trim()) found.email = 'An email keeps your account recoverable.'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) found.email = 'That email does not look right.'
    if (form.monthly_allowance_baseline !== '' && Number(form.monthly_allowance_baseline) < 0)
      found.monthly_allowance_baseline = 'Allowance cannot be negative.'
    if (form.monthly_savings_goal !== '' && Number(form.monthly_savings_goal) < 0)
      found.monthly_savings_goal = 'Savings goal cannot be negative.'
    if (Object.keys(found).length) {
      setErrors(found)
      setFormError('Check the highlighted fields.')
      return
    }
    setPending(true)
    setFormError('')
    try {
      const profile = await api('/auth/me/', {
        method: 'PATCH',
        body: {
          ...form,
          email: form.email.trim(),
          monthly_allowance_baseline: form.monthly_allowance_baseline === '' ? null : String(form.monthly_allowance_baseline),
          monthly_savings_goal: form.monthly_savings_goal === '' ? null : String(form.monthly_savings_goal),
        },
      })
      setCurrency(profile?.country)
      setProfile(profile)
      setMe(profile)
      toast({ title: 'Profile saved', message: 'Your details and currency are up to date.' })
    } catch (err) {
      if (err.fields) setErrors(err.fields)
      setFormError(err.message)
      toast({ tone: 'error', title: 'Could not save', message: err.message })
    } finally {
      setPending(false)
    }
  }

  const signOut = () => {
    clearSession()
    toast({ title: 'Signed out', message: 'See you next time.' })
    navigate('/login')
  }

  if (status === 'loading') {
    return (
      <>
        <PageHead eyebrow="Settings" title="Profile" />
        <div className="cc-settings">
          <div className="cc-settings-main">
            <SkeletonCard lines={7} />
          </div>
          <div className="cc-settings-side">
            <SkeletonCard lines={4} />
          </div>
        </div>
      </>
    )
  }

  if (status === 'error') return <ErrorState message={error} onRetry={load} />

  const initials = `${form.first_name || ''} ${form.last_name || ''}`.trim()
    ? `${(form.first_name || '?').slice(0, 1)}${(form.last_name || '').slice(0, 1)}`.toUpperCase()
    : (me?.username || 'CC').slice(0, 2).toUpperCase()
  const currency = getCurrency()
  const allowance = Number(form.monthly_allowance_baseline) || 0
  const savings = Number(form.monthly_savings_goal) || 0

  return (
    <>
      <PageHead
        eyebrow="Settings"
        title="Profile"
        subtitle="Tune the person behind the ledger — your country sets the currency everywhere."
      />

      <div className="cc-settings">
        <form className="cc-settings-main" onSubmit={save} noValidate>
          <Card>
            <CardHead eyebrow="Identity" title="Who is saving?" action={<Pill tone="ok">Signed in</Pill>} />
            <div className="cc-form-grid">
              <Field label="First name" htmlFor="pf-first" error={errors.first_name}>
                <input
                  id="pf-first"
                  className="cc-input"
                  value={form.first_name}
                  onChange={set('first_name')}
                  aria-invalid={Boolean(errors.first_name)}
                  placeholder="Ada"
                />
              </Field>
              <Field label="Last name" htmlFor="pf-last" error={errors.last_name}>
                <input
                  id="pf-last"
                  className="cc-input"
                  value={form.last_name}
                  onChange={set('last_name')}
                  aria-invalid={Boolean(errors.last_name)}
                  placeholder="Okafor"
                />
              </Field>
              <Field label="Email" htmlFor="pf-email" error={errors.email} className="span-2" hint="Used by your school email or personal inbox.">
                <input
                  id="pf-email"
                  className="cc-input"
                  type="email"
                  value={form.email}
                  onChange={set('email')}
                  aria-invalid={Boolean(errors.email)}
                  placeholder="ada@university.edu"
                />
              </Field>
              <Field label="Academic year" htmlFor="pf-year" error={errors.academic_year}>
                <input
                  id="pf-year"
                  className="cc-input"
                  value={form.academic_year}
                  onChange={set('academic_year')}
                  placeholder="e.g. 300 level"
                />
              </Field>
              <Field label="Username" htmlFor="pf-user" hint="Your handle cannot be changed here.">
                <input id="pf-user" className="cc-input" value={me?.username || ''} disabled />
              </Field>
            </div>
          </Card>

          <div className="cc-split-gap" />

          <Card>
            <CardHead eyebrow="Money setup" title="Baselines and currency" />
            <div className="cc-form-grid">
              <Field
                label={`Monthly allowance (${currency.symbol})`}
                htmlFor="pf-allowance"
                error={errors.monthly_allowance_baseline}
                hint="What normally comes in each month."
              >
                <input
                  id="pf-allowance"
                  className="cc-input cc-num-input"
                  type="number"
                  inputMode="decimal"
                  min="0"
                  step="0.01"
                  value={form.monthly_allowance_baseline}
                  onChange={set('monthly_allowance_baseline')}
                  aria-invalid={Boolean(errors.monthly_allowance_baseline)}
                  placeholder="85000"
                />
              </Field>
              <Field
                label={`Monthly savings goal (${currency.symbol})`}
                htmlFor="pf-savings"
                error={errors.monthly_savings_goal}
                hint={
                  allowance > 0 && savings > allowance
                    ? 'That is more than your allowance — double-check it.'
                    : 'The slice you want to keep each month.'
                }
              >
                <input
                  id="pf-savings"
                  className="cc-input cc-num-input"
                  type="number"
                  inputMode="decimal"
                  min="0"
                  step="0.01"
                  value={form.monthly_savings_goal}
                  onChange={set('monthly_savings_goal')}
                  aria-invalid={Boolean(errors.monthly_savings_goal)}
                  placeholder="15000"
                />
              </Field>
              <Field label="Country and currency" htmlFor="pf-country" className="span-2" hint="Every amount in Campus Coin renders in this currency.">
                <select id="pf-country" className="cc-select" value={form.country} onChange={set('country')}>
                  {COUNTRIES.map((entry) => (
                    <option key={entry.name} value={entry.name}>
                      {entry.name} — {entry.symbol} {entry.code}
                    </option>
                  ))}
                </select>
              </Field>
            </div>

            {formError && (
              <p className="cc-inline-error" role="alert" style={{ marginTop: 14 }}>
                <Icon name="alert" size={14} /> {formError}
              </p>
            )}

            <div className="cc-form-actions">
              <button type="submit" className="cc-btn cc-btn--primary" disabled={pending}>
                {pending ? 'Saving…' : 'Save profile'}
              </button>
              <button type="button" className="cc-btn cc-btn--ghost" onClick={load} disabled={pending}>
                Revert
              </button>
            </div>
          </Card>
        </form>

        <aside className="cc-settings-side">
          <Card>
            <div className="cc-profile-id">
              <span className="cc-avatar cc-avatar--lg" aria-hidden="true">
                {initials}
              </span>
              <div>
                <b>{`${form.first_name} ${form.last_name}`.trim() || me?.username}</b>
                <small>@{me?.username}</small>
              </div>
            </div>
            <div className="cc-kv">
              <span>Academic year</span>
              <b>{form.academic_year || '—'}</b>
            </div>
            <div className="cc-kv">
              <span>Country</span>
              <b>{form.country || '—'}</b>
            </div>
            <div className="cc-kv">
              <span>Currency</span>
              <b>
                {currency.code} · {currency.symbol}
              </b>
            </div>
            <div className="cc-kv">
              <span>Monthly allowance</span>
              <b>{money(allowance, { decimals: 0 })}</b>
            </div>
            <div className="cc-kv">
              <span>Monthly savings goal</span>
              <b>{money(savings, { decimals: 0 })}</b>
            </div>
          </Card>

          <Card>
            <CardHead eyebrow="Account" title="Security" />
            <p className="cc-sub" style={{ marginBottom: 14 }}>
              Change your password from the reset screen. You will need access to your email.
            </p>
            <Link className="cc-btn cc-btn--ghost cc-btn--block" to="/reset-password">
              Reset password
            </Link>
            <div style={{ height: 8 }} />
            <button type="button" className="cc-btn cc-btn--danger cc-btn--block" onClick={signOut}>
              <Icon name="logout" size={16} /> Sign out
            </button>
          </Card>
        </aside>
      </div>
    </>
  )
}
