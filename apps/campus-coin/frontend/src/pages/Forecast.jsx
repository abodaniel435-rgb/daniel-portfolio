import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'
import { cash } from '../currency'
import { EmptyState, ErrorState, PageHead, Skeleton } from '../components'
import { BrokeMeter, HealthRing } from '../wow'
import { StreakBoard } from '../wow2'

const num = (v) => Number(v) || 0

export default function Forecast() {
  const [pulse, setPulse] = useState(undefined)
  const [error, setError] = useState('')
  const [cuts, setCuts] = useState({})
  const [allowance, setAllowance] = useState(() => localStorage.getItem('cc-allowance-day') || '')

  const load = () => {
    setError('')
    setPulse(undefined)
    api('/pulse/').then(setPulse).catch((e) => setError(e.message))
  }
  useEffect(load, [])
  useEffect(() => { localStorage.setItem('cc-allowance-day', allowance) }, [allowance])

  if (pulse === undefined && !error) return <Skeleton lines={5} />
  if (error) return <ErrorState message={error} onRetry={load} />
  const p = pulse || {}
  const burn = num(p.avg_daily_burn)
  const balance = num(p.balance)
  const pace = p.pace || {}
  const runway = p.runway || {}
  const weeks = p.weeks || []
  const maxW = Math.max(...weeks.map((w) => num(w.total)), 1)

  const cutPct = Object.values(cuts).reduce((a, b) => a + num(b), 0)
  const savedPerDay = burn * (cutPct / 100)
  const newBurn = Math.max(0, burn - savedPerDay)
  const extraDays = burn > 0 && newBurn > 0 ? Math.max(0, Math.round(balance / newBurn - balance / burn)) : 0

  return (
    <>
      <PageHead
        eyebrow="Forecast room"
        title="When does the money run out?"
        action={<Link className="cta small" to="/transactions">+ Add transaction</Link>}
      />
      <section className="numbers slim">
        <article className="metric income"><p>Money in</p><h2>{cash(p.income)}</h2></article>
        <article className="metric expense"><p>Money out</p><h2>{cash(p.expenses)}</h2></article>
        <article className="metric balance"><p>Still yours</p><h2>{cash(balance)}</h2></article>
      </section>
      <section className="report-panel rise">
        <div className="forecast-head">
          <div>
            <p className="overline">Broke-date predictor</p>
            <h2>Your runway, in plain days</h2>
            <p className="hint">Burn {cash(burn)}/day against {cash(balance)} on hand. Allowance day tunes the daily limit below.</p>
          </div>
          <label className="forecast-allow">Allowance day
            <input type="number" min="1" max="28" placeholder="e.g. 1" value={allowance} onChange={(e) => setAllowance(e.target.value)} />
          </label>
        </div>
        <BrokeMeter daysLeft={p.days_left} brokeDate={p.broke_date} burn={burn} />
        <div className="forecast-grid">
          <div className="glass forecast-card">
            {p ? <HealthRing health={num(p.health)} label={p.health_label || ''} /> : <p className="hint">Health warming up…</p>}
            <p className="hint">Savings rate, budget discipline and logging streak feed this ring.</p>
          </div>
          <div className="glass forecast-card">
            <p className="overline">Daily pace to month end</p>
            <h2>{cash(pace.need)}/day</h2>
            <p className="hint">You burn {cash(pace.burn_today)}/day with {pace.days_to_month_end ?? '—'} days to go — <b>{pace.verdict || '—'}</b>.</p>
            <div className="pace-track"><i style={{ width: `${Math.min(100, (num(pace.burn_today) / Math.max(num(pace.need), 1)) * 100)}%` }} /></div>
          </div>
          <div className="glass forecast-card">
            <p className="overline">Runway at this pace</p>
            <h2>{runway.days > 500 ? '500+ days' : `${runway.days ?? '—'} days`}</h2>
            <p className="hint">Ask the Roommate for a daily limit tuned to allowance day {allowance || '—'}.</p>
            <Link className="card-cta" to="/roommate">Ask the Roommate →</Link>
          </div>
        </div>
      </section>
      <section className="report-panel rise delay">
        <p className="overline">What-if cuts</p>
        <h2>Trim the burn, stretch the road</h2>
        <label className="sim-row"><span>Cut daily burn by</span>
          <input type="range" min="0" max="80" step="5" value={cuts.all || 0} onChange={(e) => setCuts({ all: Number(e.target.value) })} />
          <b>{cuts.all || 0}%</b>
        </label>
        <p className="sim-verdict on">{cuts.all ? `New burn ${cash(newBurn)}/day — money lasts ${extraDays} more days.` : 'Drag the slider to feel the runway stretch.'}</p>
      </section>
      <section className="report-panel rise delay2">
        <p className="overline">Last 4 weeks</p>
        <h2>Where the pace came from</h2>
        {weeks.length === 0 ? <p className="hint">Log spending and the weeks fill in.</p> : (
          <div className="week-bars">
            {weeks.map((w) => (
              <div className="week-col" key={w.week}>
                <i style={{ height: `${Math.max(6, (num(w.total) / maxW) * 120)}px` }} title={`${w.week} — ${cash(w.total)}`} />
                <span>{w.week}</span><b>{cash(w.total)}</b>
              </div>
            ))}
          </div>
        )}
      </section>
      {(!num(p.income) && !num(p.expenses)) && <EmptyState icon="◔" title="No runway yet." text="Add income and a few expenses — the broke date, pace and weeks light up." />}
    </>
  )
}
