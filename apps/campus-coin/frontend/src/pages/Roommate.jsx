import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'
import { cash } from '../currency'
import { ErrorState, PageHead, Skeleton } from '../components'

const QUICK = ['Can 15k last 12 days?', 'What is my daily limit to month end?', 'Which 2 categories should I cut first?', 'Build me a 50/30/20 plan for next month']

export default function RoommatePage() {
  const [msgs, setMsgs] = useState([{ me: false, text: 'Yo! I am your Money Roommate — I read your real ledger. Ask me anything, or tap a chip below.' }])
  const [followups, setFollowups] = useState(QUICK)
  const [q, setQ] = useState('')
  const [busy, setBusy] = useState(false)
  const [allowance, setAllowance] = useState(() => localStorage.getItem('cc-allowance-day') || '')
  const [horizon, setHorizon] = useState('')
  const [history, setHistory] = useState([])
  const [stats, setStats] = useState(null)
  const [err, setErr] = useState('')
  const box = useRef(null)

  useEffect(() => { api('/pulse/').then(setStats).catch(() => setStats(null)) }, [])
  useEffect(() => { box.current?.scrollTo({ top: 99999, behavior: 'smooth' }) }, [msgs])
  useEffect(() => { localStorage.setItem('cc-allowance-day', allowance) }, [allowance])

  const ask = async (preset) => {
    const question = String(preset ?? q).trim()
    if (!question || busy) return
    setErr('')
    setMsgs((m) => [...m, { me: true, text: question }])
    setQ('')
    setBusy(true)
    try {
      const res = await api('/roommate/', { method: 'POST', body: { question, history, allowance_day: allowance || undefined, horizon_days: horizon || undefined } })
      const full = res.answer || 'Hmm.'
      setHistory((h) => [...h.slice(-7), { q: question, a: full }])
      if (Array.isArray(res.followups) && res.followups.length) setFollowups(res.followups)
      setMsgs((m) => [...m, { me: false, text: '' }])
      let i = 0
      const timer = setInterval(() => {
        i += 2
        const slice = full.slice(0, i)
        setMsgs((m) => { const c = [...m]; c[c.length - 1] = { me: false, text: slice }; return c })
        if (i >= full.length) clearInterval(timer)
      }, 14)
    } catch (e) { setErr(e.message); setMsgs((m) => [...m, { me: false, text: 'Network wahala: ' + e.message }]) }
    finally { setTimeout(() => setBusy(false), 650) }
  }

  return (
    <>
      <PageHead eyebrow="Money roommate" title="Talk money like gist." action={<Link className="cta small" to="/forecast">Open forecast →</Link>} />
      <section className="mate-page-grid">
        <div className="mate-page-main rise">
          <div className="mate-page-controls">
            <label>Allowance day<input type="number" min="1" max="28" placeholder="1-28" value={allowance} onChange={(e) => setAllowance(e.target.value)} /></label>
            <label>Plan horizon (days)<input type="number" min="1" max="120" placeholder="e.g. 30" value={horizon} onChange={(e) => setHorizon(e.target.value)} /></label>
            {stats && <span className="mate-stat">Balance <b>{cash(stats.balance)}</b> · Burn <b>{cash(stats.avg_daily_burn)}/day</b> · <b>{stats.days_left ?? '—'} days</b> left</span>}
          </div>
          {err ? <ErrorState message={err} onRetry={() => setErr('')} /> : null}
          <div className="mate-page-msgs" ref={box}>
            {msgs.length === 0 && <Skeleton lines={3} />}
            {msgs.map((m, i) => (
              <div key={i} className={m.me ? 'mate-row me' : 'mate-row bot'}>
                <p className={m.me ? 'me' : 'bot'}>{m.text}</p>
              </div>
            ))}
            {busy && <p className="bot typing"><i /><i /><i /></p>}
          </div>
          <div className="mate-chips">
            {followups.map((c) => <button key={c} onClick={() => ask(c)} disabled={busy}>{c}</button>)}
          </div>
          <div className="mate-input">
            <input value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && ask()} placeholder="Ask about your money… (try: am I spending rightly?)" disabled={busy} />
            <button onClick={() => ask()} disabled={busy}>{busy ? '…' : 'Send'}</button>
          </div>
        </div>
        <aside className="mate-page-side">
          <div className="glass rise"><p className="overline">Try these</p>
            {QUICK.map((c) => <button key={c} className="coin-btn" onClick={() => ask(c)}>{c}</button>)}
          </div>
          <div className="glass rise delay"><p className="overline">Remembers context</p>
            <p className="hint">{history.length ? `${history.length} question${history.length === 1 ? '' : 's'} this session — follow-ups reference them.` : 'Ask twice in a row — the second answer nods to the first.'}</p>
            {history.slice(-3).map((h, i) => <p className="hint" key={i}>“{h.q}”</p>)}
          </div>
        </aside>
      </section>
    </>
  )
}
