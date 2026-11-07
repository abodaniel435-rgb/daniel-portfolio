import { useState } from 'react'
import { cash } from './components'

const PALETTE = ['#ff2fb3', '#ff6b35', '#7c5cff', '#00a8cc', '#ffc300', '#2ec46f', '#ff4757', '#3867d6']

export function NeonBars({ rows = [], height = 170 }) {
  const max = Math.max(...rows.map((r) => Number(r.total) || 0), 1)
  if (!rows.length) return <p className="chart-caption">No bars yet — add a transaction and watch them pop.</p>
  return (
    <div className="neon-bars" style={{ '--chart-h': `${height}px` }} role="img" aria-label={`Bar chart with ${rows.length} categories`}>
      {rows.slice(0, 7).map((row, i) => {
        const pct = Math.max(6, (Number(row.total) / max) * 100)
        const label = row.category__name || row.label || row.day || row.week || row.month || `Item ${i + 1}`
        return (
          <div className="neon-bar-col" key={`${label}-${i}`} title={`${label}: ${cash(row.total)}`}>
            <b className="neon-bar-val">{Number(row.total) >= 1000 ? `${Math.round(Number(row.total) / 1000)}k` : Math.round(Number(row.total))}</b>
            <div className="neon-bar-track">
              <div
                className="neon-bar-fill"
                style={{ height: `${pct}%`, background: PALETTE[i % PALETTE.length], animationDelay: `${i * 0.08}s` }}
              />
            </div>
            <small>{String(label).slice(0, 10)}</small>
          </div>
        )
      })}
    </div>
  )
}

export function NeonDonut({ rows = [], size = 190, labelKey = 'category__name' }) {
  const [hot, setHot] = useState(-1)
  const clean = (rows || []).filter((r) => Number(r.total) > 0).slice(0, 6)
  const total = clean.reduce((s, r) => s + Number(r.total), 0)
  if (!total) return <p className="chart-caption">Nothing to slice yet — your pie appears with the first expense.</p>
  const R = 62
  const C = 2 * Math.PI * R
  const GAP = clean.length > 1 ? 3 : 0
  let acc = 0
  const segs = clean.map((row, i) => {
    const frac = Number(row.total) / total
    const arc = Math.max(0, frac * C - GAP)
    const seg = { ...row, frac, arc, rotate: acc, mid: acc + (frac * 360) / 2, color: PALETTE[i % PALETTE.length] }
    acc += frac * 360
    return seg
  })
  const active = hot >= 0 ? segs[hot] : null
  return (
    <div className="neon-pie-wrap">
      <div className="neon-pie-chart" style={{ width: size, height: size }}>
        <svg viewBox="0 0 180 180" width={size} height={size} role="img" aria-label="Pie chart of spending by category">
          <circle cx="90" cy="90" r={R} fill="#ffffff" stroke="rgba(14,31,24,.14)" strokeWidth="26" />
          {segs.map((s, i) => (
            <circle
              key={i}
              cx="90"
              cy="90"
              r={R}
              fill="none"
              stroke={s.color}
              strokeWidth="26"
              strokeDasharray={`${s.arc} ${C - s.arc}`}
              strokeLinecap="round"
              pathLength={C}
              transform={`rotate(${s.rotate - 90} 90 90)`}
              className={hot === i ? 'pie-seg hot' : hot >= 0 ? 'pie-seg dim' : 'pie-seg'}
              style={{ animationDelay: `${i * 0.12}s` }}
              onMouseEnter={() => setHot(i)}
              onMouseLeave={() => setHot(-1)}
            >
              <title>{`${s[labelKey]}: ${cash(s.total)} (${Math.round(s.frac * 100)}%)`}</title>
            </circle>
          ))}
          {segs.map((s, i) => {
            if (s.frac < 0.07) return null
            const rad = ((s.mid - 90) * Math.PI) / 180
            const x = 90 + R * Math.cos(rad)
            const y = 90 + R * Math.sin(rad)
            return (
              <text key={`lbl-${i}`} x={x} y={y} className="pie-label" textAnchor="middle" dominantBaseline="central" style={{ fill: '#fff' }}>
                {Math.round(s.frac * 100)}%
              </text>
            )
          })}
        </svg>
        <div className="pie-center">
          {active ? (
            <>
              <small style={{ color: active.color }}>{active[labelKey]}</small>
              <b>{cash(active.total)}</b>
              <span className="pie-count">{Math.round(active.frac * 100)}% of spend</span>
            </>
          ) : (
            <>
              <small>Total</small>
              <b>{cash(total)}</b>
              <span className="pie-count">{clean.length} {clean.length === 1 ? 'category' : 'categories'}</span>
            </>
          )}
        </div>
      </div>
      <ul className="pie-legend" onMouseLeave={() => setHot(-1)}>
        {segs.map((s, i) => (
          <li
            key={i}
            className={hot === i ? 'hot' : hot >= 0 ? 'dim' : ''}
            title={`${s[labelKey]}: ${cash(s.total)}`}
            onMouseEnter={() => setHot(i)}
          >
            <i style={{ background: s.color }} />
            <span className="pie-name">{s[labelKey]}</span>
            <b className="pie-pct">{Math.round(s.frac * 100)}%</b>
            <div className="pie-bar"><em style={{ width: `${Math.max(4, s.frac * 100)}%`, background: s.color }} /></div>
            <em className="pie-amt">{cash(s.total)}</em>
          </li>
        ))}
      </ul>
    </div>
  )
}

export function MoneyDuoBars({ months = [] }) {
  const peak = Math.max(...months.flatMap((m) => [Number(m.income), Number(m.expenses)]), 1)
  if (!months.length) return <p className="chart-caption">No months yet.</p>
  return (
    <div className="duo-bars" role="img" aria-label="Grouped bar chart of income versus expenses">
      {months.map((row) => (
        <div className="duo-col" key={row.month} title={`${row.month}: in ${cash(row.income)}, out ${cash(row.expenses)}`}>
          <div className="duo-pair">
            <div className="duo-track">
              <div className="duo-fill in" style={{ height: `${Math.max(4, (Number(row.income) / peak) * 100)}%` }} />
            </div>
            <div className="duo-track">
              <div className="duo-fill out" style={{ height: `${Math.max(4, (Number(row.expenses) / peak) * 100)}%` }} />
            </div>
          </div>
          <small>{row.month}</small>
        </div>
      ))}
    </div>
  )
}
