import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { publicJson } from '../api'

export default function ResetPassword() {
  const [params] = useSearchParams()
  const [email, setEmail] = useState('')
  const [uid, setUid] = useState(params.get('uid') || '')
  const [token, setToken] = useState(params.get('token') || '')
  const [password, setPassword] = useState('')
  const [note, setNote] = useState('')
  const [tone, setTone] = useState('bad')
  const [busy, setBusy] = useState(false)

  const requestLink = async (event) => {
    event.preventDefault()
    setBusy(true)
    setNote('')
    try {
      const body = await publicJson('/auth/password-reset/', { email })
      setTone('ok')
      setNote(body.detail || 'If that email exists, reset instructions are on the way.')
    } catch (error) {
      setTone('bad')
      setNote(error.message)
    } finally {
      setBusy(false)
    }
  }

  const confirm = async (event) => {
    event.preventDefault()
    setBusy(true)
    setNote('')
    try {
      const body = await publicJson('/auth/password-reset-confirm/', { uid, token, password })
      setTone('ok')
      setNote(body.detail || 'Password updated.')
      setPassword('')
    } catch (error) {
      setTone('bad')
      setNote(error.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="auth-page">
      <div className="auth-shape" />
      <Link to="/" className="brand"><span>◒</span> Campus<span>Coin</span></Link>
      <div className="auth-card rise reset-card">
        <p className="overline">Password recovery</p>
        <h1>Find your way back in.</h1>
        <form onSubmit={requestLink}>
          <label>Account email<input required type="email" value={email} onChange={(event) => setEmail(event.target.value)} /></label>
          <button className="cta full" disabled={busy}>Email me a reset link <b>→</b></button>
        </form>
        <form onSubmit={confirm}>
          <p className="hint">The message includes a link. In local development it prints in the API terminal. Paste the UID and token here if you already have them.</p>
          <label>UID<input required value={uid} onChange={(event) => setUid(event.target.value)} /></label>
          <label>Token<input required value={token} onChange={(event) => setToken(event.target.value)} /></label>
          <label>New password<input required minLength="8" type="password" value={password} onChange={(event) => setPassword(event.target.value)} /></label>
          <button className="cta full" disabled={busy}>Set the new password <b>→</b></button>
        </form>
        {note && <p className={tone === 'ok' ? 'form-note ok' : 'form-note'}>{note}</p>}
        <p className="switch"><Link to="/login">Back to sign in</Link></p>
      </div>
    </div>
  )
}
