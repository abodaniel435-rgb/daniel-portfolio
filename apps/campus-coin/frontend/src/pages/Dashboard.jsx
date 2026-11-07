import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { api } from '../api'
import { asList, CountUp, EmptyState, ErrorState, PageHead, Skeleton } from '../components'
import { cash } from '../currency'
import { NeonBars, NeonDonut } from '../charts'
import { BrokeMeter, HealthRing } from '../wow'
import { StreakBoard } from '../wow2'
import { useSession } from '../session'

export default function Dashboard() {
  const { profile } = useSession()
  const [state, setState] = useState({ status: 'loading', dashboard: null, report: null, notes: [], pulse: null, goals: [], error: '' })

  const load = () => {
    setState((current) => ({ ...current, status: 'loading', error: '' }))
    Promise.all([
      api('/dashboard/'),
      api('/reports/monthly/').catch(() => null),
      api('/announcements/').catch(() => []),
      api('/pulse/').catch(() => null),
      api('/goals/').catch(() => []),
    ])
      .then(([dashboard, report, notes, pulse, goals]) => setState({ status: 'ready', dashboard, report: report || { by_category: [] }, notes: asList(notes), pulse, goals: asList(goals), error: '' }))
      .catch((error) => setState({ status: 'error', dashboard: null, report: null, notes: [], error: error.message }))
  }

  useEffect(load, [])

  if (state.status === 'loading') return <Skeleton lines={4} />
  if (state.status === 'error') return <ErrorState message={state.error} onRetry={load} />

  const { dashboard, report, notes, pulse, goals } = state
  const categories = report?.by_category || []
  const max = Math.max(...categories.map((row) => Number(row.total)), 1)
  const name = profile?.first_name || profile?.username || 'money maker'
  const budgets = dashboard.budgets || []
  const nextGoal = goals.length ? [...goals].sort((a, b) => (a.deadline || '9999').localeCompare(b.deadline || '9999'))[0] : null

  return (
    <>
      <PageHead
        eyebrow="Your financial pulse"
        title={`Hello, ${name}.`}
        action={<Link className="cta small" to="/transactions">+ Add transaction</Link>}
      />
      {notes.length > 0 && (
        <div className="ticker rise">
          {notes.map((note) => <p key={note.id}><b>{note.title}.</b> {note.body}</p>)}
        </div>
      )}
      <section className="numbers">
        <article className="metric income rise"><span className="metric-ico">&#8601;</span><p>Money in</p><h2><CountUp value={dashboard.income} /></h2><i /></article>
        <article className="metric expense rise delay"><span className="metric-ico">&#8599;</span><p>Money out</p><h2><CountUp value={dashboard.expenses} /></h2><i /></article>
        <article className="metric balance rise delay2"><span className="metric-ico">&#10022;</span><p>Still yours</p><h2><CountUp value={dashboard.balance} /></h2><i /></article>
      </section>
      <section className="wow-grid rise">
        <div className="glass wow-health">
          {pulse ? <HealthRing health={pulse.health} label={pulse.health_label} /> : <p className="hint">Health ring is warming up…</p>}
          <div className="wow-health-meta">
            <div><b>{pulse?.avg_daily_burn != null ? cash(pulse.avg_daily_burn) : '—'}</b><small>avg burn / day</small></div>
            <div><b>{pulse?.log_days ?? '—'}</b><small>active days</small></div>
            <div><b>{pulse?.balance != null ? cash(pulse.balance) : '—'}</b><small>balance</small></div>
          </div>
        </div>
        <BrokeMeter daysLeft={pulse?.days_left} brokeDate={pulse?.broke_date} burn={pulse?.avg_daily_burn} />
        <StreakBoard pulse={pulse} />
      </section>
      <section className="dashboard-grid">
        <Link to="/reports" className="glass big-card rise card-link" aria-label="See spending rhythm in Reports">
          <div className="card-top">
            <div>
              <p className="overline">Spending rhythm</p>
              <h2>Where did it go?</h2>
            </div>
            <span className="orb">&#8599;</span>
          </div>
          {categories.length > 0 ? (
            <NeonBars rows={categories} height={150} />
          ) : (
            <>
              <div className="bars">{[35, 58, 42, 74, 51, 90, 67].map((height, index) => <i key={index} style={{ height: `${height}%` }} />)}</div>
              <p className="chart-caption">Your clearest picture arrives as you add transactions.</p>
            </>
          )}
          <span className="card-cta">Open Reports &#8594;</span>
        </Link>
        <div className="glass rise delay pie-spot card-link" role="button" tabIndex={0} onClick={() => window.location.assign('/reports')} onKeyDown={(e) => { if (e.key === 'Enter') window.location.assign('/reports') }}>
          <p className="overline">Wallet split</p>
          <h2>Pie of the month</h2>
          <NeonDonut rows={categories} size={170} />
          <span className="card-cta">See full breakdown &#8594;</span>
        </div>
        <Link to="/tips" className="tip-card rise delay card-link" aria-label="Explore smart tips">
          <span>&#10022;</span>
          <p className="overline">Smart nudge</p>
          <h2>{dashboard.tips?.[0]?.tip_text || 'Small moves make serious savings.'}</h2>
          <span className="card-cta light">Explore tips &#8594;</span>
        </Link>
        <Link to="/transactions" className="glass rise delay2 card-link" aria-label="See top category in Transactions">
          <p className="overline">Top category</p>
          <h2>{dashboard.top_category?.category__name || 'Nothing yet'}</h2>
          <p>{dashboard.top_category ? <CountUp value={dashboard.top_category.total} /> : 'Your biggest spend will appear here.'}</p>
          <span className="card-cta">Open Transactions &#8594;</span>
        </Link>
        <Link to="/budgets" className="glass rise delay3 card-link" aria-label="See budget lines in Budgets">
          <p className="overline">Budget vs actual</p>
          <h2>This month&#8217;s lines</h2>
          {budgets.length === 0 && <p>Set a budget and this card starts keeping score.</p>}
          {budgets.map((budget) => {
            const limit = Number(budget.limit) || 1
            const ratio = Math.min(100, (Number(budget.spent) / limit) * 100)
            return (
              <div className="mini-budget" key={budget.category}>
                <div><b>{budget.category}</b><span>{Math.round(ratio)}%</span></div>
                <div className="track"><i className={ratio >= 100 ? 'over' : ratio >= 80 ? 'warn' : ''} style={{ width: `${ratio}%` }} /></div>
              </div>
            )
          })}
          <span className="card-cta">Open Budgets &#8594;</span>
        </Link>
        <Link to="/goals" className="glass rise delay3 card-link" aria-label="See goal jars">
          <p className="overline">Goal jars</p>
          <h2>{nextGoal ? `${nextGoal.emoji} ${nextGoal.name}` : 'Start a jar'}</h2>
          {nextGoal ? (
            <>
              <div className="track goal-track"><i style={{ width: `${nextGoal.progress}%` }} /></div>
              <p>{cash(nextGoal.saved_amount)} of {cash(nextGoal.target_amount)} · {nextGoal.progress}% there</p>
            </>
          ) : (
            <p>Save toward the things you actually want. Campus Coin works out the daily drip.</p>
          )}
          <span className="card-cta">Open goal jars &#8594;</span>
        </Link>
      </section>
      {!categories.length && !Number(dashboard.income) && !Number(dashboard.expenses) && (
        <EmptyState icon="&#9678;" title="The month is a blank page." text="Add an allowance or a canteen run. The colours show up with the first naira." />
      )}
    </>
  )
}
