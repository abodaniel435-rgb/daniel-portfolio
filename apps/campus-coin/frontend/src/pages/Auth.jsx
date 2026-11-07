import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { publicJson } from '../api'
import { COUNTRIES } from '../currency'
import { useSession } from '../session'

export default function Auth({ register = false }) {
  const navigate = useNavigate()
  const { refreshProfile } = useSession()
  const [mode, setMode] = useState(register)
  const [form, setForm] = useState({ username: '', email: '', password: '', first_name: '', country: 'Nigeria' })
  const [note, setNote] = useState('')
  const [tone, setTone] = useState('bad')
  const [busy, setBusy] = useState(false)

  const set = (key) => (event) => setForm({ ...form, [key]: event.target.value })

  const submit = async (event) => {
    event.preventDefault()
    setNote('')
    setBusy(true)
    try {
      if (mode) {
        await publicJson('/auth/register/', form)
        setMode(false)
        setTone('ok')
        setNote('Account created. Sign in and the ledger opens.')
        return
      }
      const data = await publicJson('/auth/login/', { username: form.username, password: form.password })
      localStorage.setItem('campusCoinToken', data.access)
      if (data.refresh) localStorage.setItem('campusCoinRefresh', data.refresh)
      await refreshProfile().catch(() => null)
      navigate('/dashboard')
    } catch (error) {
      setTone('bad')
      setNote(error.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="auth-page auth-campus">
      <img className="auth-bg" src="https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=2400&auto=format&fit=crop" alt="" aria-hidden="true" onError={(event) => { event.currentTarget.src = 'https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?q=80&w=2400&auto=format&fit=crop' }} />
      <img className="auth-student" src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=800&auto=format&fit=crop" alt="Smiling university student" />
      <div className="auth-shape" />
      <div className="auth-shape alt" />
      <Link to="/" className="brand"><span>◒</span> Campus<span>Coin</span></Link>
      <form className="auth-card rise" onSubmit={submit}>
        <p className="overline">{mode ? 'One small step' : 'Welcome back'}</p>
        <h1>{mode ? 'Let’s make your money move.' : 'Good to see you.'}</h1>
        {mode && <label>Your first name<input value={form.first_name} onChange={set('first_name')} autoComplete="given-name" /></label>}
        <label>Username<input required value={form.username} onChange={set('username')} autoComplete="username" /></label>
        {mode && <label>Email<input required type="email" value={form.email} onChange={set('email')} autoComplete="email" /></label>}
        {mode && (
          <label>Your country (sets your currency)
            <select value={form.country} onChange={set('country')}>
              {COUNTRIES.map((entry) => (
                <option key={entry.name} value={entry.name}>{entry.name} — {entry.symbol} {entry.code}</option>
              ))}
            </select>
          </label>
        )}
        <label>Password<input required minLength="8" type="password" value={form.password} onChange={set('password')} autoComplete={mode ? 'new-password' : 'current-password'} /></label>
        {note && <p className={tone === 'ok' ? 'form-note ok' : 'form-note'}>{note}</p>}
        <button className="cta full" disabled={busy}>{busy ? 'One moment…' : mode ? 'Create my account' : 'Enter Campus Coin'} <b>→</b></button>
        <p className="switch">
          {mode ? 'Already on Campus Coin?' : 'New to Campus Coin?'}
          <button type="button" onClick={() => { setMode(!mode); setNote('') }}>{mode ? 'Sign in' : 'Create an account'}</button>
        </p>
        <p className="switch"><Link to="/reset-password">Forgot the password?</Link></p>
      </form>
    </div>
  )
}
