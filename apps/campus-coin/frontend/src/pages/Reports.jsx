import { useEffect, useState } from 'react'
import { api } from '../api'
import { cash, ErrorState, PageHead, Skeleton } from '../components'
import { MoneyDuoBars, NeonBars, NeonDonut } from '../charts'

const monthValue = (value) => (value ? String(value).slice(0, 7) : new Date().toISOString().slice(0, 7))

function Bars({ rows, labelKey }) {
  const max = Math.max(...rows.map((row) => Number(row.total)), 1)
  if (!rows.length) return <p className="chart-caption">Nothing in this slice yet.</p>
  return rows.map((row) => (
    <div className="report-line" key={row[labelKey]}>
      <span>{row[labelKey]}</span>
      <div><i style={{ width: `${(Number(row.total) / max) * 100}%` }} /></div>
      <b>{cash(row.total)}</b>
    </div>
  ))
}

export default function Reports() {
  const [month, setMonth] = useState(monthValue())
  const [state, setState] = useState({ status: 'loading', data: null, error: '' })

  const load = (value = month) => {
    setState({ status: 'loading', data: null, error: '' })
    api(`/reports/monthly/?month=${value}-01`)
      .then((data) => setState({ status: 'ready', data, error: '' }))
      .catch((error) => setState({ status: 'error', data: null, error: error.message }))
  }

  useEffect(() => { load(month) }, [month])

  if (state.status === 'loading') return <Skeleton lines={5} />
  if (state.status === 'error') return <ErrorState message={state.error} onRetry={() => load(month)} />

  const data = state.data || {}
  const months = data.six_months || []
  const peak = Math.max(...months.flatMap((row) => [Number(row.income), Number(row.expenses)]), 1)

  return (
    <div className="report-sheet">
      <PageHead
        eyebrow="Look back, move forward"
        title="Your monthly rewind."
        action={<button className="cta small" type="button" onClick={() => window.print()}>Save as PDF ↗</button>}
      />
      <div className="report-tools no-print">
        <label>Month
          <input type="month" value={month} onChange={(event) => setMonth(event.target.value)} />
        </label>
        <p className="hint">Use the browser print dialog and choose “Save as PDF”. The sidebar stays off the page.</p>
      </div>
      <section className="numbers slim">
        <article className="metric income"><p>Income</p><h2>{cash(data.income)}</h2></article>
        <article className="metric expense"><p>Expenses</p><h2>{cash(data.expenses)}</h2></article>
        <article className="metric balance"><p>Balance</p><h2>{cash(data.balance)}</h2></article>
      </section>
      <section className="report-panel rise">
        <div>
          <p className="overline">Category breakdown</p>
          <h2>What shaped this month?</h2>
        </div>
        <NeonBars rows={data.by_category || []} />
        <div className="report-subblock">
          <Bars rows={data.by_category || []} labelKey="category__name" />
        </div>
        <div className="report-subblock">
          <NeonDonut rows={data.by_category || []} labelKey="category__name" />
        </div>
      </section>
      <section className="report-panel rise delay">
        <div>
          <p className="overline">Income sources</p>
          <h2>Where the naira arrived from</h2>
        </div>
        <Bars rows={data.income_by_category || []} labelKey="category__name" />
      </section>
      <section className="report-panel rise delay2">
        <div>
          <p className="overline">Six-month swing</p>
          <h2>Income against expenses</h2>
        </div>
        <MoneyDuoBars months={months} />
        <p className="chart-caption"><span className="swatch in" /> Income <span className="swatch out" /> Expenses — hover any pair for exact naira.</p>
      </section>
      <section className="split-panels">
        <article className="report-panel">
          <p className="overline">Daily spending</p>
          <h2>This month, day by day</h2>
          <Bars rows={(data.daily || []).map((row) => ({ ...row, total: row.total, day: row.day?.slice(5) }))} labelKey="day" />
        </article>
        <article className="report-panel">
          <p className="overline">Weekly summary</p>
          <h2>The weeks inside the month</h2>
          <Bars rows={data.weekly || []} labelKey="week" />
        </article>
      </section>
    </div>
  )
}
