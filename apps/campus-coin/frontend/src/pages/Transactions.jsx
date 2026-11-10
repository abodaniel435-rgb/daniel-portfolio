import { useEffect, useMemo, useState } from 'react'
import { api } from '../api'
import {
  asList,
  Card,
  CardHead,
  EmptyState,
  ErrorState,
  Icon,
  Modal,
  PageHead,
  Pill,
  Skeleton,
  TransactionRow,
  money,
  useToast,
} from '../components'
import { TransactionDialog } from '../forms'

const PAGE_SIZE = 10
const EMPTY_FILTERS = { type: 'all', category: 'all', start: '', end: '', q: '' }

export default function Transactions() {
  const { toast } = useToast()
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [items, setItems] = useState([])
  const [categories, setCategories] = useState([])
  const [filters, setFilters] = useState(EMPTY_FILTERS)
  const [page, setPage] = useState(1)
  const [dialog, setDialog] = useState(null)
  const [confirming, setConfirming] = useState(null)
  const [busyId, setBusyId] = useState(null)
  const [csvNote, setCsvNote] = useState('')
  const [csvBusy, setCsvBusy] = useState(false)

  const load = () => {
    setStatus((current) => (current === 'ready' ? 'ready' : 'loading'))
    setError('')
    Promise.all([api('/transactions/'), api('/categories/')])
      .then(([transactions, cats]) => {
        setItems(asList(transactions))
        setCategories(asList(cats))
        setStatus('ready')
      })
      .catch((err) => {
        setError(err.message)
        setStatus('error')
      })
  }

  useEffect(load, [])

  useEffect(() => {
    setPage(1)
  }, [filters])

  const filtered = useMemo(() => {
    const needle = filters.q.trim().toLowerCase()
    return items
      .filter((item) => filters.type === 'all' || item.type === filters.type)
      .filter((item) => filters.category === 'all' || String(item.category) === String(filters.category))
      .filter((item) => !filters.start || String(item.transaction_date).slice(0, 10) >= filters.start)
      .filter((item) => !filters.end || String(item.transaction_date).slice(0, 10) <= filters.end)
      .filter((item) => {
        if (!needle) return true
        const haystack = `${item.description || ''} ${item.category_name || ''} ${item.transaction_date || ''}`.toLowerCase()
        return haystack.includes(needle)
      })
      .sort((a, b) => String(b.transaction_date).localeCompare(String(a.transaction_date)) || b.id - a.id)
  }, [items, filters])

  const totals = useMemo(
    () =>
      filtered.reduce(
        (acc, item) => {
          const value = Number(item.amount) || 0
          if (item.type === 'income') acc.income += value
          else acc.expense += value
          return acc
        },
        { income: 0, expense: 0 },
      ),
    [filtered],
  )

  const pageCount = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE))
  const current = Math.min(page, pageCount)
  const rows = filtered.slice((current - 1) * PAGE_SIZE, current * PAGE_SIZE)
  const isFiltered =
    filters.type !== 'all' || filters.category !== 'all' || filters.start || filters.end || filters.q.trim()

  const setFilter = (key) => (event) => {
    const value = event?.target ? event.target.value : event
    setFilters((current) => ({ ...current, [key]: value }))
  }

  const clearFilters = () => setFilters(EMPTY_FILTERS)

  const startEdit = (item) => setDialog({ mode: 'edit', item })

  const remove = async () => {
    if (!confirming) return
    const target = confirming
    setBusyId(target.id)
    try {
      await api(`/transactions/${target.id}/`, { method: 'DELETE' })
      setItems((current) => current.filter((row) => row.id !== target.id))
      toast({ title: 'Transaction removed', message: `${target.category_name} · ${money(target.amount)}` })
      setConfirming(null)
    } catch (err) {
      toast({ tone: 'error', title: 'Could not remove it', message: err.message })
    } finally {
      setBusyId(null)
    }
  }

  const importCsv = async (event) => {
    const file = event.target.files?.[0]
    event.target.value = ''
    if (!file) return
    const body = new FormData()
    body.append('file', file)
    setCsvNote('')
    setCsvBusy(true)
    try {
      const result = await api('/transactions/import-csv/', { method: 'POST', body })
      const problems = (result.errors || []).length
      const message = `Imported ${result.created} row${result.created === 1 ? '' : 's'}${problems ? ` · ${problems} need a second look` : ''}.`
      setCsvNote(message)
      toast({ title: 'CSV processed', message })
      load()
    } catch (err) {
      setCsvNote(err.message)
      toast({ tone: 'error', title: 'Import failed', message: err.message })
    } finally {
      setCsvBusy(false)
    }
  }

  if (status === 'loading' && items.length === 0) {
    return (
      <>
        <PageHead eyebrow="The daily details" title="Transactions" />
        <Card style={{ padding: 0 }}>
          <Skeleton lines={6} />
        </Card>
      </>
    )
  }

  if (status === 'error' && items.length === 0) {
    return <ErrorState message={error} onRetry={load} />
  }

  return (
    <>
      <PageHead
        eyebrow="The daily details"
        title="Transactions"
        subtitle="Filter, search and fix any line in your ledger."
        action={
          <button type="button" className="cc-btn cc-btn--primary" onClick={() => setDialog({ mode: 'new' })}>
            <Icon name="plus" size={16} /> Log money
          </button>
        }
      />

      <Card style={{ padding: 0 }}>
        <div className="cc-tx-filters">
          <div className="cc-search grow">
            <Icon name="search" size={16} />
            <input
              className="cc-input"
              value={filters.q}
              onChange={setFilter('q')}
              placeholder="Search notes, categories or dates"
              aria-label="Search transactions"
              type="search"
            />
          </div>
          <div className="cc-typechips" role="group" aria-label="Filter by type">
            {[
              ['all', 'All'],
              ['expense', 'Spent'],
              ['income', 'Received'],
            ].map(([key, label]) => (
              <button
                key={key}
                type="button"
                className="cc-chip"
                aria-pressed={filters.type === key}
                onClick={() => setFilter('type')(key)}
              >
                {label}
              </button>
            ))}
          </div>
        </div>

        <div className="cc-tx-filtergrid">
          <label className="cc-field">
            <span className="cc-label">Category</span>
            <select className="cc-select" value={filters.category} onChange={setFilter('category')}>
              <option value="all">Every category</option>
              {categories
                .filter((category) => filters.type === 'all' || category.type === filters.type)
                .map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
            </select>
          </label>
          <label className="cc-field">
            <span className="cc-label">From</span>
            <input className="cc-input cc-num-input" type="date" value={filters.start} onChange={setFilter('start')} />
          </label>
          <label className="cc-field">
            <span className="cc-label">To</span>
            <input className="cc-input cc-num-input" type="date" value={filters.end} onChange={setFilter('end')} />
          </label>
          <div className="cc-field cc-filter-reset">
            <span className="cc-label">&nbsp;</span>
            <button
              type="button"
              className="cc-btn cc-btn--ghost"
              onClick={clearFilters}
              disabled={!isFiltered}
            >
              Reset filters
            </button>
          </div>
        </div>

        <div className="cc-list-summary">
          <p>
            <b>{filtered.length}</b> {filtered.length === 1 ? 'entry' : 'entries'}{' '}
            {isFiltered ? (filtered.length === 1 ? 'matches' : 'match') : 'in total'}
          </p>
          <div className="cc-list-summary-money">
            <span className="in">In {money(totals.income)}</span>
            <span className="out">Out {money(totals.expense)}</span>
          </div>
        </div>

        {rows.length > 0 ? (
          <div className="cc-txlist">
            {rows.map((item) => (
              <TransactionRow
                key={item.id}
                item={item}
                manage
                busy={busyId}
                onEdit={startEdit}
                onDelete={(row) => setConfirming(row)}
              />
            ))}
          </div>
        ) : items.length === 0 ? (
          <EmptyState
            icon="list"
            title="Nothing logged yet"
            text="Your ledger is wide open. Add the first transaction and the dashboard starts telling the story."
            actionLabel="Log your first transaction"
            onAction={() => setDialog({ mode: 'new' })}
          />
        ) : (
          <EmptyState
            icon="filter"
            title="No lines match those filters"
            text="Loosen the dates, switch the type, or clear the search to see everything again."
            actionLabel="Reset filters"
            onAction={clearFilters}
          />
        )}

        {filtered.length > 0 && (
          <div className="cc-pager">
            <span>
              Showing <b>{(current - 1) * PAGE_SIZE + 1}</b>–<b>{Math.min(current * PAGE_SIZE, filtered.length)}</b> of{' '}
              <b>{filtered.length}</b>
            </span>
            <div className="cc-pager-btns">
              <button
                type="button"
                className="cc-btn cc-btn--ghost cc-btn--sm"
                onClick={() => setPage(Math.max(1, current - 1))}
                disabled={current <= 1}
                aria-label="Previous page"
              >
                <Icon name="chevronLeft" size={15} /> Prev
              </button>
              <span className="cc-pill cc-pill--ok" aria-live="polite">
                {current} / {pageCount}
              </span>
              <button
                type="button"
                className="cc-btn cc-btn--ghost cc-btn--sm"
                onClick={() => setPage(Math.min(pageCount, current + 1))}
                disabled={current >= pageCount}
                aria-label="Next page"
              >
                Next <Icon name="chevronRight" size={15} />
              </button>
            </div>
          </div>
        )}
      </Card>

      <div className="cc-split-gap" />

      <Card>
        <CardHead
          eyebrow="Bring your own data"
          title="Import a bank or spreadsheet export"
          action={<Pill tone="info">CSV</Pill>}
        />
        <p className="cc-sub" style={{ marginBottom: 14 }}>
          Columns: <code>type, category, amount, description, transaction_date</code> — dates as YYYY-MM-DD.
        </p>
        <div className="cc-csv-row">
          <label className={`cc-btn cc-btn--ghost ${csvBusy ? 'is-busy' : ''}`.trim()}>
            {csvBusy ? 'Uploading…' : 'Choose a CSV file'}
            <input type="file" accept=".csv,text/csv" onChange={importCsv} disabled={csvBusy} hidden />
          </label>
          {csvNote && <p className="cc-hint">{csvNote}</p>}
        </div>
      </Card>

      <TransactionDialog
        open={Boolean(dialog)}
        initial={dialog?.item || null}
        categories={categories}
        onClose={() => setDialog(null)}
        onSaved={() => load()}
      />

      <Modal
        open={Boolean(confirming)}
        onClose={() => (busyId ? null : setConfirming(null))}
        eyebrow="Remove entry"
        title="Delete this transaction?"
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
            <b>{confirming.description || confirming.category_name}</b> — {money(confirming.amount)} on{' '}
            {String(confirming.transaction_date).slice(0, 10)}. It leaves your totals but stays in the history as deleted.
          </p>
        )}
      </Modal>
    </>
  )
}
