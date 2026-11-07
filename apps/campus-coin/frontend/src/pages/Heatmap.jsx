import { useEffect, useState } from 'react'
import { api } from '../api'
import { cash, PageHead, Skeleton } from '../components'

const DAY = 86400000
const CELL = 24

export function HeatmapChart() {
  const [data, setData] = useState(null)

  useEffect(() => {
    api('/heatmap/').then(setData).catch(() => setData({ days: {} }))
  }, [])

  if (!data) return <Skeleton lines={3} />

  const totals = data.days || {}
  const today = new Date()
  today.setHours(12, 0, 0, 0)
  const start = new Date(today.getTime() - 364 * DAY)
  start.setDate(start.getDate() - ((start.getDay() + 6) % 7))
  const span = Math.round((today - start) / DAY) + 1
  const nWeeks = Math.ceil(span / 7)
  const grid = Array.from({ length: nWeeks }, () => Array(7).fill(null))
  const values = []
  for (let i = 0; i < span; i++) {
    const d = new Date(start.getTime() + i * DAY)
    const key = d.toISOString().slice(0, 10)
    const v = Number(totals[key] || 0)
    grid[Math.floor(i / 7)][i % 7] = { key, v, day: d.getDate(), label: d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }
    if (v > 0) values.push(v)
  }
  const max = Math.max(...values, 1)
  const level = (v) => (v <= 0 ? 0 : v <= max * 0.25 ? 1 : v <= max * 0.5 ? 2 : v <= max * 0.75 ? 3 : 4)
  const months = grid.map((week, col) => {
    const first = new Date(start.getTime() + col * 7 * DAY)
    const prev = col > 0 ? new Date(start.getTime() + (col - 1) * 7 * DAY) : null
    return !prev || first.getMonth() !== prev.getMonth() ? first.toLocaleDateString(undefined, { month: 'short' }) : ''
  })

  return (
    <>
      <div className="heat-scroll">
        <div className="heat-months" style={{ gridTemplateColumns: `repeat(${nWeeks}, ${CELL}px)` }}>
          {months.map((label, i) => <span key={i}>{label}</span>)}
        </div>
        <div className="heat-body">
          <div className="heat-days">{['M', '', 'W', '', 'F', '', ''].map((label, i) => <span key={i}>{label}</span>)}</div>
          <div className="heat-grid" style={{ gridTemplateColumns: `repeat(${nWeeks}, ${CELL}px)` }}>
            {grid.map((week, c) => week.map((cell, r) => (cell
              ? <i key={`${c}-${r}`} className={`heat-cell lv${level(cell.v)}`} title={`${cell.label} — ${cell.v > 0 ? cash(cell.v) : 'no spend'}`}>{cell.day}</i>
              : <i key={`${c}-${r}`} className="heat-cell lv0 blank" />)))}
          </div>
        </div>
      </div>
      <p className="chart-caption heat-legend">
        Less <i className="heat-cell lv0" /><i className="heat-cell lv1" /><i className="heat-cell lv2" /><i className="heat-cell lv3" /><i className="heat-cell lv4" /> More
        — hover any square for the exact day. {values.length} spending day{values.length === 1 ? '' : 's'} in the last 12 months.
      </p>
    </>
  )
}

export default function Heatmap() {
  return (
    <>
      <PageHead eyebrow="Spending heatmap" title="A year of money, one square a day." />
      <section className="report-panel rise">
        <div>
          <p className="overline">Last 12 months</p>
          <h2>Where the heavy days hide</h2>
        </div>
        <HeatmapChart />
      </section>
    </>
  )
}
