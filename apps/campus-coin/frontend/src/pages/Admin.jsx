import { useEffect, useState } from 'react'
import { api } from '../api'
import { CountUp, ErrorState, PageHead, Skeleton } from '../components'

export default function Admin() {
  const [state, setState] = useState({ status: 'loading', data: null, error: '' })

  const load = () => {
    setState({ status: 'loading', data: null, error: '' })
    api('/admin/analytics/')
      .then((data) => setState({ status: 'ready', data, error: '' }))
      .catch((error) => setState({
        status: 'error',
        data: null,
        error: error.status === 403 ? 'The admin studio opens for staff accounts only.' : error.message,
      }))
  }

  useEffect(load, [])

  if (state.status === 'loading') return <Skeleton lines={3} />
  if (state.status === 'error') return <ErrorState message={state.error} onRetry={load} />

  const data = state.data || {}
  const categories = data.top_categories || []
  const max = Math.max(...categories.map((row) => Number(row.total)), 1)

  return (
    <>
      <PageHead eyebrow="Campus control room" title="How the whole house is spending." />
      <section className="numbers">
        <article className="metric income rise"><p>Active students</p><h2><CountUp value={data.active_users} /></h2></article>
        <article className="metric balance rise delay"><p>Live transactions</p><h2><CountUp value={data.total_transactions} /></h2></article>
        <article className="metric expense rise delay2"><p>Categories in the lead</p><h2>{categories.length}</h2></article>
      </section>
      <section className="report-panel rise">
        <p className="overline">Most used expense categories</p>
        <h2>Where the campus clusters</h2>
        {categories.map((row) => (
          <div className="report-line" key={row.category__name}>
            <span>{row.category__name}</span>
            <div><i style={{ width: `${(Number(row.total) / max) * 100}%` }} /></div>
            <b>{row.total}</b>
          </div>
        ))}
        {categories.length === 0 && <p>No transactions have been logged yet.</p>}
      </section>
    </>
  )
}
