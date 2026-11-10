import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { publicJson } from '../api'
import { COUNTRIES } from '../currency'
import { Field, Icon, useToast } from '../components'
import { useSession } from '../session'

const BLANK = {
  first_name: '',
  username: '',
  email: '',
  password: '',
  academic_year: '',
  country: 'Nigeria',
}

export default function Auth({ register = false }) {
  const navigate = useNavigate()
  const { refreshProfile } = useSession()
  const { toast } = useToast()
  const [mode, setMode] = useState(register)
  const [form, setForm] = useState(BLANK)
  const [errors, setErrors] = useState({})
  const [formError, setFormError] = useState('')
  const [busy, setBusy] = useState(false)

  const set = (key) => (event) => {
    setForm({ ...form, [key]: event.target.value })
    setErrors((current) => ({ ...current, [key]: undefined }))
    setFormError('')
  }

  const login = async () => {
    const data = await publicJson('/auth/login/', { username: form.username.trim(), password: form.password })
    localStorage.setItem('campusCoinToken', data.access)
    if (data.refresh) localStorage.setItem('campusCoinRefresh', data.refresh)
    await refreshProfile().catch(() => null)
    navigate('/dashboard')
  }

  const submit = async (event) => {
    event.preventDefault()
    const found = {}
    if (!form.username.trim()) found.username = 'Pick a username.'
    if (mode) {
      if (!form.email.trim()) found.email = 'We need an email for password resets.'
      if (!/@/.test(form.email)) found.email = 'That email looks incomplete.'
    }
    if (!form.password) found.password = 'Enter your password.'
    else if (mode && form.password.length < 8) found.password = 'Use at least 8 characters.'
    if (Object.keys(found).length) {
      setErrors(found)
      return
    }

    setBusy(true)
    setFormError('')
    setErrors({})
    try {
      if (mode) {
        await publicJson('/auth/register/', {
          username: form.username.trim(),
          email: form.email.trim(),
          password: form.password,
          first_name: form.first_name.trim(),
          academic_year: form.academic_year.trim(),
          country: form.country,
        })
        await login()
        toast({ title: 'Welcome to Campus Coin', message: 'Your ledger is open. Log your allowance to begin.' })
      } else {
        await login()
        toast({ title: 'Signed in', message: 'Good to see you again.' })
      }
    } catch (error) {
      if (error.fields) setErrors(error.fields)
      setFormError(error.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="cc-auth">
      <aside className="cc-auth-aside">
        <Link to="/" className="cc-brand" aria-label="Campus Coin home">
          <span className="cc-brand-mark">C</span>
          Campus<em>Coin</em>
        </Link>
        <div className="cc-auth-quote">
          <p className="cc-eyebrow">{mode ? 'Start here' : 'Welcome back'}</p>
          <h2>{mode ? 'The ledger your allowance deserves.' : 'Your month, still on track.'}</h2>
          <p>
            Track what comes in, guard what goes out, and watch a money-health score you can actually explain. No bank
            connection, no card, no noise.
          </p>
        </div>
        <div className="cc-auth-stats">
          <div>
            <b>1</b>
            <small>ledger</small>
          </div>
          <div>
            <b>3</b>
            <small>score inputs</small>
          </div>
          <div>
            <b>₦0</b>
            <small>to start</small>
          </div>
        </div>
      </aside>

      <main className="cc-auth-main">
        <div className="cc-auth-card">
          <Link to="/" className="cc-auth-back">
            <Icon name="chevronLeft" size={14} /> Back to home
          </Link>
          <div>
            <p className="cc-eyebrow">{mode ? 'Create your account' : 'Sign in'}</p>
            <h1>{mode ? 'Open your money desk.' : 'Good to see you.'}</h1>
          </div>

          <form className="cc-auth-form" onSubmit={submit} noValidate>
            {mode && (
              <div className="cc-form-grid">
                <Field label="First name" htmlFor="first_name">
                  <input
                    id="first_name"
                    className="cc-input"
                    value={form.first_name}
                    onChange={set('first_name')}
                    autoComplete="given-name"
                    placeholder="Ada"
                  />
                </Field>
                <Field label="Year of study" htmlFor="academic_year" hint="Optional">
                  <input
                    id="academic_year"
                    className="cc-input"
                    value={form.academic_year}
                    onChange={set('academic_year')}
                    placeholder="300 level"
                  />
                </Field>
              </div>
            )}

            <Field label="Username" htmlFor="username" error={errors.username}>
              <input
                id="username"
                className="cc-input"
                value={form.username}
                onChange={set('username')}
                autoComplete="username"
                aria-invalid={Boolean(errors.username)}
                placeholder="adachidi"
                required
              />
            </Field>

            {mode && (
              <Field label="Email" htmlFor="email" error={errors.email}>
                <input
                  id="email"
                  className="cc-input"
                  type="email"
                  value={form.email}
                  onChange={set('email')}
                  autoComplete="email"
                  aria-invalid={Boolean(errors.email)}
                  placeholder="you@school.edu.ng"
                  required
                />
              </Field>
            )}

            <Field
              label="Password"
              htmlFor="password"
              error={errors.password}
              hint={mode ? 'At least 8 characters.' : undefined}
            >
              <input
                id="password"
                className="cc-input"
                type="password"
                value={form.password}
                onChange={set('password')}
                autoComplete={mode ? 'new-password' : 'current-password'}
                aria-invalid={Boolean(errors.password)}
                required
              />
            </Field>

            {mode && (
              <Field label="Country" htmlFor="country" hint="Sets the currency used across the app.">
                <select id="country" className="cc-select" value={form.country} onChange={set('country')}>
                  {COUNTRIES.map((entry) => (
                    <option key={entry.name} value={entry.name}>
                      {entry.name} — {entry.symbol} {entry.code}
                    </option>
                  ))}
                </select>
              </Field>
            )}

            {formError && (
              <p className="cc-inline-error" role="alert">
                <Icon name="alert" size={14} /> {formError}
              </p>
            )}

            <button type="submit" className="cc-btn cc-btn--primary cc-btn--block" disabled={busy}>
              {busy ? 'One moment…' : mode ? 'Create my account' : 'Sign in'}
              {!busy && <Icon name="arrow" size={15} />}
            </button>
          </form>

          <p className="cc-auth-foot">
            {mode ? 'Already have an account?' : 'New to Campus Coin?'}
            <button type="button" onClick={() => { setMode(!mode); setErrors({}); setFormError('') }}>
              {mode ? 'Sign in' : 'Create one free'}
            </button>
          </p>
          <p className="cc-auth-foot">
            <Link to="/reset-password" style={{ color: 'var(--muted)', fontWeight: 600 }}>
              Forgot your password?
            </Link>
          </p>
        </div>
      </main>
    </div>
  )
}
