import { useEffect, useState } from 'react'
import { cash } from './currency'

export { cash }

export function asList(data) {
  if (Array.isArray(data)) return data
  if (Array.isArray(data?.results)) return data.results
  return []
}

export function CountUp({ value }) {
  const target = Number(value) || 0
  const reduce = typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const [shown, setShown] = useState(reduce ? target : 0)

  useEffect(() => {
    if (reduce) {
      setShown(target)
      return undefined
    }
    const start = performance.now()
    let frame = 0
    const tick = (now) => {
      const progress = Math.min(1, (now - start) / 1100)
      const eased = 1 - (1 - progress) ** 3
      setShown(target * eased)
      if (progress < 1) frame = requestAnimationFrame(tick)
    }
    frame = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(frame)
  }, [target, reduce])

  return <span>{cash(shown)}</span>
}

export function PageHead({ eyebrow, title, action }) {
  return (
    <header className="page-head">
      <div>
        <p className="overline">{eyebrow}</p>
        <h1>{title}</h1>
      </div>
      {action}
    </header>
  )
}

export function ErrorState({ message, onRetry }) {
  return (
    <article className="empty error-state rise">
      <span>!</span>
      <h2>This page hit a snag.</h2>
      <p>{message}</p>
      {onRetry && <button className="cta small" type="button" onClick={onRetry}>Try again</button>}
    </article>
  )
}

export function EmptyState({ icon = '✦', title, text }) {
  return (
    <article className="empty rise">
      <span>{icon}</span>
      <h2>{title}</h2>
      <p>{text}</p>
    </article>
  )
}

export function Skeleton({ lines = 3 }) {
  return (
    <div className="skeleton" aria-busy="true" aria-label="Loading">
      {Array.from({ length: lines }, (_, index) => <i key={index} style={{ animationDelay: `${index * 0.12}s` }} />)}
    </div>
  )
}

export function Field({ label, children }) {
  return <label className="field">{label}{children}</label>
}

export function Burst({ active }) {
  if (!active) return null
  return (
    <span className="burst" aria-hidden="true">
      {Array.from({ length: 10 }, (_, index) => <i key={index} style={{ '--i': index }} />)}
    </span>
  )
}
