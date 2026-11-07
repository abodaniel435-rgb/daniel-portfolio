import { useEffect, useState } from 'react'
import { api } from '../api'
import { cash, PageHead, Skeleton } from '../components'

const monthValue = (value) => (value ? String(value).slice(0, 7) : new Date().toISOString().slice(0, 7))

export function SimulatorPanel({ categories, month }) {
  const [pulse, setPulse] = useState(undefined)
  const [cuts, setCuts] = useState({})

  useEffect(() => {
    api('/pulse/').then(setPulse).catch(() => setPulse(null))
  }, [])

  if (pulse === undefined) return <Skeleton lines={3} />

  const top = (categories || []).slice(0, 5)
  const balance = Number(pulse?.balance ?? 0)
  const burn = Number(pulse?.avg_daily_burn ?? 0)
  const now = new Date()
  const isCurrent = month === `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  const elapsed = isCurrent ? now.getDate() : new Date(Number(month.slice(0, 4)), Number(month.slice(5, 7)), 0).getDate()
  const rates = top.map((row) => ({ name: row.category__name, rate: Number(row.total) / Math.max(1, elapsed) }))
  const savedPerDay = rates.reduce((sum, row) => sum + (row.rate * (cuts[row.name] || 0)) / 100, 0)
  const newBurn = Math.max(0, burn - savedPerDay)
  const saved30 = savedPerDay * 30
  const brokeNow = burn > 0 ? balance / burn : null
  const brokeSim = newBurn > 0 ? balance / newBurn : null
  const extraDays = brokeNow != null && brokeSim != null ? Math.round(brokeSim - brokeNow) : null

  const maxVal = Math.max(balance, 1)
  const points = (b) => Array.from({ length: 31 }, (_, d) => `${d * 10},${120 - (Math.max(0, balance - b * d) / maxVal) * 110}`).join(' ')

  if (!pulse || top.length === 0) return <p className="chart-caption">Log a few expenses and the simulator starts bending your future.</p>

  const anyCut = rates.some((row) => (cuts[row.name] || 0) > 0)
  const verdict = !anyCut
    ? 'Drag any slider to cut that category and see how much further your money stretches.'
    : `If you make these cuts you keep an extra ${cash(saved30)} over 30 days, your daily spending drops from ${cash(burn)} to ${cash(newBurn)}, and your money lasts ${extraDays != null && extraDays > 0 ? `${extraDays} more days` : 'the same length of time'}.`

  return (
    <div className="sim-wrap">
      <p className="sim-intro">Each slider is one of your top spending categories this month. Drag it to cut that category by a percentage — the numbers below update instantly to show your <b>current</b> path against your <b>new</b> path.</p>
      <div className="sim-sliders">
        {rates.map((row) => (
          <label className="sim-row" key={row.name}>
            <span>{row.name}</span>
            <input type="range" min="0" max="100" step="5" value={cuts[row.name] || 0} onChange={(event) => setCuts({ ...cuts, [row.name]: Number(event.target.value) })} aria-label={`Cut ${row.name} spending by a percentage`} />
            <b>{cuts[row.name] || 0}% cut</b>
            <small>{(cuts[row.name] || 0) > 0 ? `saves ${cash((row.rate * (cuts[row.name] || 0) * 30) / 100)}/month` : `you spend ${cash(row.rate * 30)}/month`}</small>
          </label>
        ))}
      </div>
      <div className="sim-out">
        <div>
          <p className="overline">You spend per day</p>
          <h3><span className="sim-now">{cash(burn)}</span> <span className="sim-arrow">→</span> <b className="good">{cash(newBurn)}</b></h3>
          <small>now → after your cuts</small>
        </div>
        <div>
          <p className="overline">Left in 30 days</p>
          <h3><span className="sim-now">{cash(Math.max(0, balance - burn * 30))}</span> <span className="sim-arrow">→</span> <b className="good">{cash(Math.max(0, balance - newBurn * 30))}</b></h3>
          <small>{anyCut ? `+${cash(saved30)} kept in your pocket` : 'no change yet'}</small>
        </div>
        <div>
          <p className="overline">Money runs out in</p>
          <h3><span className="sim-now">{brokeNow == null ? 'never' : `${Math.round(brokeNow)} days`}</span> <span className="sim-arrow">→</span> <b className="good">{brokeSim == null ? 'never' : `${Math.round(brokeSim)} days`}</b></h3>
          <small>{extraDays != null && extraDays > 0 ? `+${extraDays} extra days of road` : 'at your current balance'}</small>
        </div>
      </div>
      <p className={`sim-verdict ${anyCut ? 'on' : ''}`}>{verdict}</p>
      <svg className="sim-chart" viewBox="0 0 300 130" role="img" aria-label="Projected balance over 30 days, current habits against simulated habits">
        <polyline points={points(burn)} fill="none" stroke="#ff2fb3" strokeWidth="3" strokeDasharray="6 5" />
        <polyline points={points(newBurn)} fill="none" stroke="#0e1f18" strokeWidth="3.5" className="sim-line" />
      </svg>
      <p className="chart-caption"><span className="swatch out" /> Where you are headed now &nbsp;·&nbsp; <span className="swatch sim" /> Where you would be headed after the cuts — over the next 30 days.</p>
    </div>
  )
}

export default function Simulator() {
  const [month, setMonth] = useState(monthValue())
  const [data, setData] = useState(null)

  useEffect(() => {
    setData(null)
    api(`/reports/monthly/?month=${month}-01`).then(setData).catch(() => setData({ by_category: [] }))
  }, [month])

  return (
    <>
      <PageHead
        eyebrow="What-if simulator"
        title="Bend your habits, watch the future move."
        action={<label className="sim-month">Month<input type="month" value={month} onChange={(event) => setMonth(event.target.value)} /></label>}
      />
      <section className="report-panel rise">
        <div>
          <p className="overline">Play with your money</p>
          <h2>Cut a category, see the road stretch</h2>
        </div>
        {data === null ? <Skeleton lines={3} /> : <SimulatorPanel categories={data.by_category} month={month} />}
      </section>
    </>
  )
}
