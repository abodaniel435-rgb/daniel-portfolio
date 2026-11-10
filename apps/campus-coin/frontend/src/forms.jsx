import { useEffect, useMemo, useState } from 'react'
import { api } from './api'
import { amountForInput, getCurrency, money } from './currency'
import { Field, Modal, Segmented, useToast } from './components'
import { rememberCategory, suggestCategory } from './suggest'

const today = () => new Date().toISOString().slice(0, 10)
const firstOfMonth = () => `${today().slice(0, 7)}-01`

function useForm(initial) {
  const [values, setValues] = useState(initial)
  const [errors, setErrors] = useState({})
  const [pending, setPending] = useState(false)
  const [formError, setFormError] = useState('')

  useEffect(() => {
    setValues(initial)
    setErrors({})
    setFormError('')
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [JSON.stringify(initial)])

  const set = (key) => (event) => {
    const value = event?.target ? event.target.value : event
    setValues((current) => ({ ...current, [key]: value }))
    setErrors((current) => ({ ...current, [key]: undefined }))
  }

  return { values, setValues, set, errors, setErrors, pending, setPending, formError, setFormError }
}

function messageOf(error) {
  return error?.message || 'Something went wrong. Try again.'
}

/* ------------------------------------------------------- transaction dialog */

const blankTransaction = () => ({
  type: 'expense',
  category: '',
  amount: '',
  description: '',
  transaction_date: today(),
  is_recurring: false,
})

export function TransactionDialog({ open, onClose, categories = [], initial = null, onSaved }) {
  const { toast } = useToast()
  const start = useMemo(
    () =>
      initial
        ? {
            type: initial.type,
            category: String(initial.category),
            amount: amountForInput(initial.amount),
            description: initial.description || '',
            transaction_date: String(initial.transaction_date).slice(0, 10),
            is_recurring: Boolean(initial.is_recurring),
          }
        : blankTransaction(),
    [initial],
  )
  const form = useForm(start)
  const [manual, setManual] = useState(Boolean(initial))
  const [hint, setHint] = useState('')

  const pool = categories.filter((category) => category.type === form.values.type)

  const onDescription = (event) => {
    const value = event.target.value
    form.set('description')(value)
    if (manual) return
    const guess = suggestCategory(value, categories, form.values.type)
    if (guess) {
      form.setValues((current) => ({ ...current, category: String(guess.id) }))
      setHint(`Suggested ${guess.name} — change it if that is off.`)
    } else {
      setHint('')
    }
  }

  const switchType = (type) => {
    form.setValues((current) => ({ ...current, type, category: '' }))
    setHint('')
    setManual(false)
  }

  const submit = async (event) => {
    event.preventDefault()
    const found = {}
    if (!form.values.category) found.category = 'Pick a category.'
    if (!form.values.amount || Number(form.values.amount) <= 0) found.amount = 'Enter an amount above zero.'
    if (!form.values.transaction_date) found.transaction_date = 'Pick a date.'
    if (Object.keys(found).length) {
      form.setErrors(found)
      return
    }
    form.setPending(true)
    form.setFormError('')
    try {
      const payload = {
        type: form.values.type,
        category: Number(form.values.category),
        amount: String(form.values.amount),
        description: form.values.description,
        transaction_date: form.values.transaction_date,
        is_recurring: Boolean(form.values.is_recurring),
      }
      const saved = initial
        ? await api(`/transactions/${initial.id}/`, { method: 'PATCH', body: payload })
        : await api('/transactions/', { method: 'POST', body: payload })
      rememberCategory(form.values.type, form.values.description || '', form.values.category)
      toast({
        title: initial ? 'Transaction updated' : 'Transaction logged',
        message: `${money(payload.amount)} · ${saved.category_name || 'saved'}`,
      })
      onSaved?.(saved)
      if (!initial) {
        form.setValues(blankTransaction())
        setHint('')
      }
      onClose?.()
    } catch (error) {
      if (error.fields) form.setErrors(error.fields)
      form.setFormError(messageOf(error))
    } finally {
      form.setPending(false)
    }
  }

  return (
    <Modal
      open={open}
      onClose={form.pending ? () => {} : onClose}
      eyebrow={initial ? 'Edit entry' : 'New entry'}
      title={initial ? 'Update this transaction' : 'Log a transaction'}
      footer={
        <>
          <button type="button" className="cc-btn cc-btn--ghost" onClick={onClose} disabled={form.pending}>
            Cancel
          </button>
          <button type="submit" form="tx-form" className="cc-btn cc-btn--primary" disabled={form.pending}>
            {form.pending ? 'Saving…' : initial ? 'Save changes' : 'Save transaction'}
          </button>
        </>
      }
    >
      <form id="tx-form" onSubmit={submit} className="cc-auth-form" noValidate>
        <Segmented
          label="Transaction type"
          value={form.values.type}
          onChange={switchType}
          options={[
            ['expense', 'I spent'],
            ['income', 'I received'],
          ]}
        />
        <Field label="What was it?" htmlFor="tx-desc" hint={hint || 'A short note helps you remember.'}>
          <input
            id="tx-desc"
            className="cc-input"
            value={form.values.description}
            onChange={onDescription}
            placeholder="e.g. Lunch at the canteen"
            maxLength={255}
          />
        </Field>
        <div className="cc-form-grid">
          <Field label="Category" htmlFor="tx-cat" error={form.errors.category}>
            <select
              id="tx-cat"
              className="cc-select"
              value={form.values.category}
              aria-invalid={Boolean(form.errors.category)}
              onChange={(event) => {
                setManual(true)
                form.set('category')(event.target.value)
              }}
            >
              <option value="">{pool.length ? 'Choose a category' : 'No categories yet'}</option>
              {pool.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </Field>
          <Field label="Amount" htmlFor="tx-amount" error={form.errors.amount}>
            <input
              id="tx-amount"
              className="cc-input cc-num-input"
              type="number"
              inputMode="decimal"
              step="0.01"
              min="0.01"
              placeholder="0.00"
              value={form.values.amount}
              aria-invalid={Boolean(form.errors.amount)}
              onChange={form.set('amount')}
            />
          </Field>
          <Field label="Date" htmlFor="tx-date" error={form.errors.transaction_date}>
            <input
              id="tx-date"
              className="cc-input cc-num-input"
              type="date"
              value={form.values.transaction_date}
              aria-invalid={Boolean(form.errors.transaction_date)}
              onChange={form.set('transaction_date')}
            />
          </Field>
          <div className="cc-field" style={{ justifyContent: 'flex-end' }}>
            <span className="cc-label">Repeat</span>
            <label className="cc-check" style={{ minHeight: 44 }}>
              <input
                type="checkbox"
                checked={form.values.is_recurring}
                onChange={(event) => form.set('is_recurring')(event.target.checked)}
              />
              Monthly bill
            </label>
          </div>
        </div>
        {form.formError && (
          <p className="cc-inline-error" role="alert">
            {form.formError}
          </p>
        )}
      </form>
    </Modal>
  )
}

/* ------------------------------------------------------------ budget dialog */

export function BudgetDialog({ open, onClose, categories = [], initial = null, defaultMonth = '', onSaved }) {
  const { toast } = useToast()
  const start = useMemo(
    () => ({
      category: initial ? String(initial.category) : '',
      limit_amount: initial ? amountForInput(initial.limit_amount) : '',
      month: initial ? String(initial.month).slice(0, 10) : defaultMonth || firstOfMonth(),
    }),
    [initial, defaultMonth],
  )
  const form = useForm(start)
  const expenseCategories = categories.filter((category) => category.type === 'expense')

  const submit = async (event) => {
    event.preventDefault()
    const found = {}
    if (!form.values.category) found.category = 'Choose the category this budget guards.'
    if (!form.values.limit_amount || Number(form.values.limit_amount) <= 0)
      found.limit_amount = 'Set a limit above zero.'
    if (!form.values.month) found.month = 'Pick the month.'
    if (Object.keys(found).length) {
      form.setErrors(found)
      return
    }
    form.setPending(true)
    form.setFormError('')
    try {
      const payload = {
        category: Number(form.values.category),
        limit_amount: String(form.values.limit_amount),
        month: form.values.month.length === 7 ? `${form.values.month}-01` : form.values.month,
      }
      const saved = initial
        ? await api(`/budgets/${initial.id}/`, { method: 'PATCH', body: payload })
        : await api('/budgets/', { method: 'POST', body: payload })
      toast({
        title: initial ? 'Budget updated' : 'Budget locked in',
        message: `${saved.category_name} · ${money(payload.limit_amount)} for the month`,
      })
      onSaved?.(saved)
      onClose?.()
    } catch (error) {
      if (error.fields) form.setErrors(error.fields)
      form.setFormError(messageOf(error))
    } finally {
      form.setPending(false)
    }
  }

  return (
    <Modal
      open={open}
      onClose={form.pending ? () => {} : onClose}
      eyebrow={initial ? 'Edit budget' : 'New budget'}
      title={initial ? 'Adjust this guardrail' : 'Give a category a limit'}
      footer={
        <>
          <button type="button" className="cc-btn cc-btn--ghost" onClick={onClose} disabled={form.pending}>
            Cancel
          </button>
          <button type="submit" form="budget-form" className="cc-btn cc-btn--primary" disabled={form.pending}>
            {form.pending ? 'Saving…' : initial ? 'Save changes' : 'Lock in budget'}
          </button>
        </>
      }
    >
      <form id="budget-form" onSubmit={submit} className="cc-auth-form" noValidate>
        <Field label="Expense category" htmlFor="budget-cat" error={form.errors.category}>
          <select
            id="budget-cat"
            className="cc-select"
            value={form.values.category}
            disabled={Boolean(initial)}
            aria-invalid={Boolean(form.errors.category)}
            onChange={form.set('category')}
          >
            <option value="">Choose a category</option>
            {expenseCategories.map((category) => (
              <option key={category.id} value={category.id}>
                {category.name}
              </option>
            ))}
          </select>
        </Field>
        <div className="cc-form-grid">
          <Field
            label={`Monthly limit (${getCurrency().symbol})`}
            htmlFor="budget-limit"
            error={form.errors.limit_amount}
          >
            <input
              id="budget-limit"
              className="cc-input cc-num-input"
              type="number"
              inputMode="decimal"
              step="0.01"
              min="0.01"
              placeholder="25000.00"
              value={form.values.limit_amount}
              aria-invalid={Boolean(form.errors.limit_amount)}
              onChange={form.set('limit_amount')}
            />
          </Field>
          <Field label="Month" htmlFor="budget-month" error={form.errors.month}>
            <input
              id="budget-month"
              className="cc-input cc-num-input"
              type="month"
              value={form.values.month.slice(0, 7)}
              aria-invalid={Boolean(form.errors.month)}
              onChange={(event) => form.setValues((current) => ({ ...current, month: `${event.target.value}-01` }))}
            />
          </Field>
        </div>
        {form.formError && (
          <p className="cc-inline-error" role="alert">
            {form.formError}
          </p>
        )}
      </form>
    </Modal>
  )
}

/* -------------------------------------------------------------- goal dialog */

export function GoalDialog({ open, onClose, initial = null, onSaved }) {
  const { toast } = useToast()
  const start = useMemo(
    () => ({
      name: initial?.name || '',
      emoji: initial?.emoji || '🎯',
      target_amount: initial ? amountForInput(initial.target_amount) : '',
      saved_amount: initial ? amountForInput(initial.saved_amount) : '0',
      deadline: initial?.deadline ? String(initial.deadline).slice(0, 10) : '',
    }),
    [initial],
  )
  const form = useForm(start)

  const submit = async (event) => {
    event.preventDefault()
    const found = {}
    if (!form.values.name.trim()) found.name = 'Give the jar a name.'
    if (!form.values.target_amount || Number(form.values.target_amount) <= 0)
      found.target_amount = 'Target must be above zero.'
    if (Number(form.values.saved_amount) < 0) found.saved_amount = 'Saved amount cannot be negative.'
    if (Number(form.values.saved_amount) > Number(form.values.target_amount) && Number(form.values.target_amount) > 0)
      found.saved_amount = 'Saved cannot be more than the target.'
    if (Object.keys(found).length) {
      form.setErrors(found)
      return
    }
    form.setPending(true)
    form.setFormError('')
    try {
      const payload = {
        name: form.values.name.trim(),
        emoji: form.values.emoji || '🎯',
        target_amount: String(form.values.target_amount),
        deadline: form.values.deadline || null,
      }
      if (initial) payload.saved_amount = String(form.values.saved_amount || 0)
      const saved = initial
        ? await api(`/goals/${initial.id}/`, { method: 'PATCH', body: payload })
        : await api('/goals/', { method: 'POST', body: payload })
      toast({
        title: initial ? 'Jar updated' : 'Jar created',
        message: `${saved.name} · ${money(saved.target_amount)} target`,
      })
      onSaved?.(saved)
      onClose?.()
    } catch (error) {
      if (error.fields) form.setErrors(error.fields)
      form.setFormError(messageOf(error))
    } finally {
      form.setPending(false)
    }
  }

  return (
    <Modal
      open={open}
      onClose={form.pending ? () => {} : onClose}
      eyebrow={initial ? 'Edit jar' : 'New jar'}
      title={initial ? 'Update this goal' : 'Start a goal jar'}
      footer={
        <>
          <button type="button" className="cc-btn cc-btn--ghost" onClick={onClose} disabled={form.pending}>
            Cancel
          </button>
          <button type="submit" form="goal-form" className="cc-btn cc-btn--primary" disabled={form.pending}>
            {form.pending ? 'Saving…' : initial ? 'Save changes' : 'Create jar'}
          </button>
        </>
      }
    >
      <form id="goal-form" onSubmit={submit} className="cc-auth-form" noValidate>
        <div className="cc-form-grid">
          <Field label="Jar name" htmlFor="goal-name" error={form.errors.name} className="span-2">
            <input
              id="goal-name"
              className="cc-input"
              value={form.values.name}
              maxLength={120}
              placeholder="New laptop"
              aria-invalid={Boolean(form.errors.name)}
              onChange={form.set('name')}
            />
          </Field>
          <Field label="Emoji" htmlFor="goal-emoji">
            <input
              id="goal-emoji"
              className="cc-input"
              value={form.values.emoji}
              maxLength={8}
              onChange={form.set('emoji')}
            />
          </Field>
          <Field label="Deadline" htmlFor="goal-deadline" hint="Optional, but it drives the daily drip.">
            <input
              id="goal-deadline"
              className="cc-input cc-num-input"
              type="date"
              value={form.values.deadline}
              onChange={form.set('deadline')}
            />
          </Field>
          <Field label="Target amount" htmlFor="goal-target" error={form.errors.target_amount}>
            <input
              id="goal-target"
              className="cc-input cc-num-input"
              type="number"
              inputMode="decimal"
              step="0.01"
              min="0.01"
              placeholder="450000"
              value={form.values.target_amount}
              aria-invalid={Boolean(form.errors.target_amount)}
              onChange={form.set('target_amount')}
            />
          </Field>
          {initial && (
            <Field label="Already saved" htmlFor="goal-saved" error={form.errors.saved_amount}>
              <input
                id="goal-saved"
                className="cc-input cc-num-input"
                type="number"
                inputMode="decimal"
                step="0.01"
                min="0"
                value={form.values.saved_amount}
                aria-invalid={Boolean(form.errors.saved_amount)}
                onChange={form.set('saved_amount')}
              />
            </Field>
          )}
        </div>
        {form.formError && (
          <p className="cc-inline-error" role="alert">
            {form.formError}
          </p>
        )}
      </form>
    </Modal>
  )
}
