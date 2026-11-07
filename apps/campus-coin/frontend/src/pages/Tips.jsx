import { useEffect, useState } from 'react'
import { api } from '../api'
import { asList, EmptyState, ErrorState, PageHead, Skeleton } from '../components'

export default function Tips() {
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [tips, setTips] = useState([])
  const [busy, setBusy] = useState(false)
  const [note, setNote] = useState('')
  const [showSaved, setShowSaved] = useState(false)

  const load = () => {
    setStatus('loading')
    api('/insights/')
      .then((rows) => {
        setTips(asList(rows))
        setStatus('ready')
      })
      .catch((err) => {
        setError(err.message)
        setStatus('error')
      })
  }

  useEffect(load, [])

  const generate = async () => {
    setBusy(true)
    setNote('')
    try {
      await api('/insights/generate/', { method: 'POST' })
      setNote('A fresh nudge is on the table.')
      load()
    } catch (err) {
      setNote(err.message)
    } finally {
      setBusy(false)
    }
  }

  const patch = async (tip, changes) => {
    try {
      await api(`/insights/${tip.id}/`, { method: 'PATCH', body: changes })
      setTips((current) => current.map((item) => (item.id === tip.id ? { ...item, ...changes } : item)))
    } catch (err) {
      setNote(err.message)
    }
  }

  if (status === 'loading' && tips.length === 0) return <Skeleton lines={3} />
  if (status === 'error' && tips.length === 0) return <ErrorState message={error} onRetry={load} />

  const saved = tips.filter((tip) => tip.is_bookmarked && !tip.is_dismissed)
  const visible = tips.filter((tip) => !tip.is_dismissed && (showSaved ? tip.is_bookmarked : true))

  return (
    <>
      <PageHead
        eyebrow="A little wisdom, on demand"
        title="Your smart money coach."
        action={<button className="cta small" type="button" onClick={generate} disabled={busy}>{busy ? 'Thinking…' : 'Generate a tip ✦'}</button>}
      />
      {note && <p className={note.includes('fresh') ? 'form-note ok' : 'form-note'}>{note}</p>}
      <div className="list-tools no-print">
        <button type="button" className={showSaved ? 'chip on' : 'chip'} onClick={() => setShowSaved((value) => !value)}>
          {showSaved ? 'Showing pinned' : 'Show pinned only'}
        </button>
        <p className="hint">{saved.length} pinned</p>
      </div>
      <section className="tips-grid">
        {visible.map((tip, index) => (
          <article className={`tip-tile tile-${index % 3} rise`} key={tip.id} style={{ animationDelay: `${index * 0.06}s` }}>
            <span>{tip.is_bookmarked ? '★' : '✦'}</span>
            <p className="overline">{tip.source} powered</p>
            <h2>{tip.summary_text}</h2>
            <p>{tip.tip_text}</p>
            <div className="row-actions coin-actions">
              <button type="button" className="coin-btn pin" onClick={() => patch(tip, { is_bookmarked: !tip.is_bookmarked })}>{tip.is_bookmarked ? '★ Unpin' : '☆ Pin'}</button>
              <button type="button" className="coin-btn delete" onClick={() => patch(tip, { is_dismissed: true })}>✕ Dismiss</button>
            </div>
          </article>
        ))}
      </section>
      {visible.length === 0 && <EmptyState icon="✦" title="Your next bright idea is waiting." text="Add expenses, then ask Campus Coin for a nudge." />}
    </>
  )
}
