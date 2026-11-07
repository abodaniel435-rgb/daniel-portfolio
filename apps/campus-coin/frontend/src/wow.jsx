import { useEffect, useState } from 'react'
import { cash } from './components'

export function HealthRing({ health = 0, label = '' }) {
  const r = 52
  const c = 2 * Math.PI * r
  const [shown, setShown] = useState(0)
  useEffect(() => {
    let f = 0
    const t0 = performance.now()
    const tick = (t) => {
      const p = Math.min(1, (t - t0) / 1200)
      setShown(Math.round(health * (1 - (1 - p) ** 3)))
      if (p < 1) f = requestAnimationFrame(tick)
    }
    f = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(f)
  }, [health])
  const color = health >= 75 ? '#0e1f18' : health >= 50 ? '#7c2d12' : '#9f1239'
  const ring = health >= 75 ? '#0e1f18' : health >= 50 ? '#ff6b35' : '#ff2fb3'
  return (
    <div className="health-ring">
      <svg viewBox="0 0 130 130" width="130" height="130">
        <circle cx="65" cy="65" r={r} fill="none" strokeWidth="13" stroke="rgba(14,31,24,.12)" />
        <circle cx="65" cy="65" r={r} fill="none" strokeWidth="13" strokeLinecap="round"
          stroke={ring} strokeDasharray={c} strokeDashoffset={c - (c * shown) / 100} />
        <text x="65" y="62" textAnchor="middle" fontSize="30" fontWeight="900" fill={color}>{shown}</text>
        <text x="65" y="82" textAnchor="middle" fontSize="11" fontWeight="800" fill={color}>/ 100</text>
      </svg>
      <div><p className="overline">Money health</p><h2>{label}</h2></div>
    </div>
  )
}

export function BrokeMeter({ daysLeft, brokeDate, burn }) {
  const urgent = daysLeft != null && daysLeft <= 7
  const pct = daysLeft == null ? 100 : Math.min(100, Math.max(4, (daysLeft / 31) * 100))
  let dateStr = 'Never'
  try { dateStr = brokeDate ? new Date(brokeDate + 'T12:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) : 'Never' } catch { dateStr = brokeDate || 'Never' }
  return (
    <div className={`broke-card ${urgent ? 'urgent' : 'chill'}`}>
      <div className="broke-top"><p className="overline">Broke-date predictor</p><span className="broke-fire">{urgent ? 'ACT FAST' : 'SAFE'}</span></div>
      <h2>{daysLeft == null ? 'Add spending to ignite this.' : daysLeft === 0 ? 'Today is the line.' : `${daysLeft} days of road left`}</h2>
      <p className="broke-sub">Broke date: <b>{daysLeft != null && daysLeft > 60 ? 'Beyond 2 months - flying' : dateStr}</b> - Burn <b>{cash(burn)}/day</b></p>
      <div className="broke-track"><i style={{ width: `${pct}%` }} /></div>
    </div>
  )
}
