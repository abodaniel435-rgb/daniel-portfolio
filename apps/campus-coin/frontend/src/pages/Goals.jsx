import { useEffect, useMemo, useState } from 'react'
import { api } from '../api'
import {
  Card,
  EmptyState,
  ErrorState,
  Icon,
  Modal,
  PageHead,
  Pill,
  SkeletonCard,
  StatTile,
  money,
  useToast,
} from '../components'
import { GoalDialog } from '../forms'

function parse(value) {
  return Array.isArray(value) ? value : value?.results || []
}

function prettyDate(value) {
  if (!value) return null
  const date = new Date(`${String(value).slice(0, 10)}T12:00:00`)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}

function paceLine(goal, saved) {
  const target = Number(goal.target_amount) || 0
  const remaining = Math.max(0, target - saved)
  if (remaining === 0) return { text: 'Jar full — go get the thing.', tone: 'ok' }
  if (!goal.deadline) return { text: 'No deadline set. Drop in whenever you can.', tone: '' }
  const days = Number(goal.days_left)
  if (Number.isFinite(days) && days > 0) {
    return {
      text: `${money(remaining / days)} a day for ${days} day${days === 1 ? '' : 's'} lands it by ${prettyDate(goal.deadline)}.`,
      tone: '',
    }
  }
  if (Number.isFinite(days) && days === 0) {
    return { text: `Deadline is today — ${money(remaining)} to go.`, tone: 'warn' }
  }
  return { text: `Deadline passed with ${money(remaining)} to go. Move the date or top it up.`, tone: 'over' }
}

export default function Goals() {
  const { toast } = useToast()
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [goals, setGoals] = useState([])
  const [dialog, setDialog] = useState(null)
  const [confirming, setConfirming] = useState(null)
  const [busyId, setBusyId] = useState(null)
  const [drafts, setDrafts] = useState({})

  const load = () => {
    setStatus((current) => (current === 'ready' ? 'ready' : 'loading'))
    setError('')
    api('/goals/')
      .then((data) => {
        setGoals(parse(data))
        setStatus('ready')
      })
      .catch((err) => {
        setError(err.message)
        setStatus('error')
      })
  }

  useEffect(load, [])

  const totals = useMemo(
    () =>
      goals.reduce(
        (acc, goal) => {
          acc.target += Number(goal.target_amount) || 0
          acc.saved += Number(goal.saved_amount) || 0
          if (Number(goal.saved_amount) >= Number(goal.target_amount)) acc.done += 1
          return acc
        },
        { target: 0, saved: 0, done: 0 },
      ),
    [goals],
  )

  const depositInto = async (goal) => {
    const raw = drafts[goal.id]
    const amount = Number(raw)
    if (!raw || Number.isNaN(amount) || amount <= 0) {
      toast({ tone: 'error', title: 'Enter an amount', message: 'Type how much you want to drop into the jar.' })
      return
    }
    const previous = goals
    const nextSaved = Number(goal.saved_amount) + amount
    setBusyId(goal.id)
    // Optimistic: the jar fills instantly, and we roll back if the API says no.
    setGoals((current) =>
      current.map((row) =>
        row.id === goal.id
          ? {
              ...row,
              saved_amount: nextSaved.toFixed(2),
              progress: Math.min(100, (nextSaved / (Number(row.target_amount) || 1)) * 100),
            }
          : row,
      ),
    )
    setDrafts((current) => ({ ...current, [goal.id]: '' }))
    try {
      const updated = await api(`/goals/${goal.id}/`, {
        method: 'PATCH',
        body: { saved_amount: String(nextSaved.toFixed(2)) },
      })
      setGoals((current) => current.map((row) => (row.id === goal.id ? updated : row)))
      toast({ title: 'Money dropped in', message: `${money(amount)} → ${goal.name}` })
    } catch (err) {
      setGoals(previous)
      setDrafts((current) => ({ ...current, [goal.id]: raw }))
      toast({ tone: 'error', title: 'Deposit failed', message: err.message })
    } finally {
      setBusyId(null)
    }
  }

  const remove = async () => {
    if (!confirming) return
    const target = confirming
    setBusyId(target.id)
    try {
      await api(`/goals/${target.id}/`, { method: 'DELETE' })
      setGoals((current) => current.filter((goal) => goal.id !== target.id))
      toast({ title: 'Jar removed', message: target.name })
      setConfirming(null)
    } catch (err) {
      toast({ tone: 'error', title: 'Could not remove it', message: err.message })
    } finally {
      setBusyId(null)
    }
  }

  if (status === 'loading' && goals.length === 0) {
    return (
      <>
        <PageHead eyebrow="Goal jars" title="Goals" />
        <div className="cc-goal-grid">
          <SkeletonCard lines={5} />
          <SkeletonCard lines={5} />
        </div>
      </>
    )
  }

  if (status === 'error' && goals.length === 0) return <ErrorState message={error} onRetry={load} />

  return (
    <>
      <PageHead
        eyebrow="Goal jars"
        title="Goals"
        subtitle="Save toward the things you actually want — and see the daily drip that gets you there."
        action={
          <button type="button" className="cc-btn cc-btn--primary" onClick={() => setDialog({ mode: 'new' })}>
            <Icon name="plus" size={16} /> New jar
          </button>
        }
      />

      {goals.length > 0 && (
        <div className="cc-stats">
          <StatTile label="Saved across jars" value={money(totals.saved, { decimals: 0 })} meta={`of ${money(totals.target, { decimals: 0 })} targeted`} tone="accent" />
          <StatTile label="Still to save" value={money(Math.max(0, totals.target - totals.saved), { decimals: 0 })} meta="keep the drip going" />
          <StatTile label="Jars completed" value={`${totals.done} / ${goals.length}`} meta={totals.done ? 'nice work' : 'first one is coming'} />
        </div>
      )}

      {goals.length === 0 ? (
        <Card>
          <EmptyState
            icon="jar"
            title="No jars on the shelf yet"
            text="A phone, a trip, rent for next session — name it, set a target, and Campus Coin works out the daily amount."
            actionLabel="Start your first jar"
            onAction={() => setDialog({ mode: 'new' })}
          />
        </Card>
      ) : (
        <div className="cc-goal-grid">
          {goals.map((goal) => {
            const target = Number(goal.target_amount) || 0
            const saved = Number(goal.saved_amount) || 0
            const ratio = target > 0 ? Math.max(0, Math.min(100, (saved / target) * 100)) : 0
            const left = Math.max(0, target - saved)
            const pace = paceLine(goal, saved)
            const done = ratio >= 100
            return (
              <article className={`cc-goal ${done ? 'is-done' : ''}`.trim()} key={goal.id}>
                <div className="cc-goal-top">
                  <span className="cc-goal-emoji" aria-hidden="true">
                    {goal.emoji || '🎯'}
                  </span>
                  <div className="cc-goal-name">
                    <b>{goal.name}</b>
                    <small>{goal.deadline ? `by ${prettyDate(goal.deadline)}` : 'no deadline'}</small>
                  </div>
                  <Pill tone={done ? 'ok' : ratio >= 50 ? 'info' : ''}>{Math.round(ratio)}%</Pill>
                </div>

                <div className="cc-goal-figures">
                  <span>
                    <b>{money(saved, { decimals: 0 })}</b>
                    <small className="cc-hint"> saved</small>
                  </span>
                  <span className="cc-hint">of {money(target, { decimals: 0 })}</span>
                </div>

                <div
                  className="cc-meter-track"
                  role="progressbar"
                  aria-label={`${goal.name} progress`}
                  aria-valuenow={Math.round(ratio)}
                  aria-valuemin="0"
                  aria-valuemax="100"
                >
                  <i
                    className={`cc-meter-fill ${done ? 'ok' : ratio >= 80 ? 'warn' : ''}`.trim()}
                    style={{ width: `${Math.max(ratio, 1)}%` }}
                  />
                </div>

                <p className={`cc-goal-pace ${pace.tone}`.trim()}>
                  {left > 0 ? `${money(left, { decimals: 0 })} to go · ` : ''}
                  {pace.text}
                </p>

                {!done && (
                  <div className="cc-goal-drop">
                    <input
                      className="cc-input cc-num-input"
                      type="number"
                      inputMode="decimal"
                      min="0.01"
                      step="0.01"
                      placeholder="Amount"
                      aria-label={`Amount to add to ${goal.name}`}
                      value={drafts[goal.id] || ''}
                      onChange={(event) => setDrafts((current) => ({ ...current, [goal.id]: event.target.value }))}
                      onKeyDown={(event) => event.key === 'Enter' && depositInto(goal)}
                    />
                    <button
                      type="button"
                      className="cc-btn cc-btn--primary cc-btn--sm"
                      onClick={() => depositInto(goal)}
                      disabled={busyId === goal.id}
                    >
                      {busyId === goal.id ? 'Adding…' : 'Drop in'}
                    </button>
                  </div>
                )}

                <div className="cc-goal-quick">
                  {[1000, 5000, 10000].map((amount) => (
                    <button
                      key={amount}
                      type="button"
                      className="cc-chip"
                      onClick={() =>
                        setDrafts((current) => ({
                          ...current,
                          [goal.id]: amountFor(current[goal.id], amount),
                        }))
                      }
                    >
                      +{money(amount, { decimals: 0 })}
                    </button>
                  ))}
                </div>

                <div className="cc-goal-actions">
                  <button type="button" className="cc-btn cc-btn--ghost cc-btn--sm" onClick={() => setDialog({ mode: 'edit', item: goal })}>
                    Edit
                  </button>
                  <button type="button" className="cc-btn cc-btn--danger cc-btn--sm" onClick={() => setConfirming(goal)}>
                    Remove
                  </button>
                </div>
              </article>
            )
          })}
        </div>
      )}

      <GoalDialog
        open={Boolean(dialog)}
        initial={dialog?.item || null}
        onClose={() => setDialog(null)}
        onSaved={() => load()}
      />

      <Modal
        open={Boolean(confirming)}
        onClose={() => (busyId ? null : setConfirming(null))}
        eyebrow="Remove jar"
        title="Smash this jar?"
        footer={
          <>
            <button type="button" className="cc-btn cc-btn--ghost" onClick={() => setConfirming(null)} disabled={Boolean(busyId)}>
              Keep it
            </button>
            <button type="button" className="cc-btn cc-btn--danger" onClick={remove} disabled={Boolean(busyId)}>
              {busyId ? 'Removing…' : 'Yes, remove'}
            </button>
          </>
        }
      >
        {confirming && (
          <p className="cc-sub">
            <b>{confirming.name}</b> holds {money(confirming.saved_amount)} of its {money(confirming.target_amount)} target.
            Removing the jar does not move any money — it just forgets the goal.
          </p>
        )}
      </Modal>
    </>
  )
}

function amountFor(current, amount) {
  const base = Number(current) || 0
  return String(base + amount)
}
