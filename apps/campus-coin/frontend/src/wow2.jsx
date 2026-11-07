import { useEffect, useRef, useState } from 'react'
import { api } from './api'

export function StreakBoard({ pulse }) {
  if (!pulse) return null
  return (
    <div className="streak-card rise">
      {pulse.streak >= 3 && <span className="confetti" aria-hidden="true">{Array.from({ length: 14 }, (_, i) => <i key={i} style={{ '--i': i }} />)}</span>}
      <div className="streak-row">
        <div className="streak-flame"><span>🔥</span><b>{pulse.streak}</b><small>day streak</small></div>
        <div className="streak-level"><p className="overline">Level {pulse.level} - {pulse.level_name}</p>
          <div className="xp-track"><i style={{ width: `${Math.min(100, ((pulse.xp || 0) % 150) / 150 * 100)}%` }} /></div>
          <small>{pulse.xp} XP - {pulse.next_level_in} to Level {pulse.level + 1}</small>
        </div>
      </div>
      <div className="board">
        <p className="overline">Campus leaderboard {pulse.rank ? `- you are #${pulse.rank}` : ''}</p>
        {(pulse.leaderboard || []).map((row, i) => (
          <div key={`${row.name}-${i}`} className={`board-row ${row.me ? 'me' : ''}`}>
            <b>#{i + 1}</b><span>{row.name}</span><i>{row.rate}% saved</i>
          </div>
        ))}
        {!(pulse.leaderboard || []).length && <p className="hint">Log income to join the board.</p>}
      </div>
    </div>
  )
}

export function Roommate() {
  const [open, setOpen] = useState(false)
  const [q, setQ] = useState('')
  const [busy, setBusy] = useState(false)
  const now = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  const [msgs, setMsgs] = useState([{ me: false, text: 'Yo! I am your Money Roommate. Ask: can 15k last 12 days? Type abeg and I switch vibe.', time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }])
  const box = useRef(null)
  useEffect(() => { box.current?.scrollTo({ top: 99999, behavior: 'smooth' }) }, [msgs, open])
  const ask = async (preset) => {
    const question = String(preset ?? q).trim()
    if (!question || busy) return
    setMsgs((m) => [...m, { me: true, text: question, time: now() }])
    setQ('')
    setBusy(true)
    try {
      const res = await api('/roommate/', { method: 'POST', body: { question } })
      const full = res.answer || 'Hmm.'
      setMsgs((m) => [...m, { me: false, text: '', time: now() }])
      let i = 0
      const timer = setInterval(() => {
        i += 3
        const slice = full.slice(0, i)
        setMsgs((m) => { const c = [...m]; c[c.length - 1] = { me: false, text: slice, time: now() }; return c })
        if (i >= full.length) clearInterval(timer)
      }, 18)
    } catch (e) { setMsgs((m) => [...m, { me: false, text: 'Network wahala: ' + e.message, time: now() }]) }
    finally { setTimeout(() => setBusy(false), 700) }
  }
  return (
    <>
      <button className={open ? 'mate-fab on' : 'mate-fab'} onClick={() => setOpen((v) => !v)} aria-label="Chat with money roommate">
        <span className="mate-fab-ico">{open ? '✕' : '💬'}</span>
        <span className="mate-fab-txt">{open ? 'Close' : 'Roommate'}</span>
      </button>
      {open && (
        <div className="mate-panel rise">
          <div className="mate-head"><span>RM</span><div><b>Money Roommate</b><small>{busy ? 'typing…' : 'reads your real ledger'}</small></div><button onClick={() => setOpen(false)} aria-label="Close chat">✕</button></div>
          <div className="mate-msgs" ref={box}>
            {msgs.map((m, i) => (
              <div key={i} className={m.me ? 'mate-row me' : 'mate-row bot'}>
                <p className={m.me ? 'me' : 'bot'}>{m.text}</p>
                {m.text && <time>{m.time}</time>}
              </div>
            ))}
            {busy && <p className="bot typing"><i /><i /><i /></p>}
          </div>
          <div className="mate-chips">
            {['Can 15k last 12 days?', 'When will I go broke?', 'Where did my money go?'].map((c) => <button key={c} onClick={() => ask(c)} disabled={busy}>{c}</button>)}
          </div>
          <div className="mate-input">
            <input value={q} onChange={(e) => setQ(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && ask()} placeholder="Ask about your money…" disabled={busy} />
            <button onClick={() => ask()} disabled={busy}>{busy ? '…' : 'Send'}</button>
          </div>
        </div>
      )}
    </>
  )
}
