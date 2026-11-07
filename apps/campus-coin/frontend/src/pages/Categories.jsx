import { useEffect, useState } from 'react'
import { api } from '../api'
import { asList, EmptyState, ErrorState, Field, PageHead, Skeleton } from '../components'

export default function Categories() {
  const [status, setStatus] = useState('loading')
  const [error, setError] = useState('')
  const [categories, setCategories] = useState([])
  const [form, setForm] = useState({ name: '', type: 'expense' })
  const [editing, setEditing] = useState(null)
  const [note, setNote] = useState('')

  const load = () => {
    setStatus('loading')
    api('/categories/')
      .then((rows) => {
        setCategories(asList(rows))
        setStatus('ready')
      })
      .catch((err) => {
        setError(err.message)
        setStatus('error')
      })
  }

  useEffect(load, [])

  const save = async (event) => {
    event.preventDefault()
    setNote('')
    try {
      if (editing) await api(`/categories/${editing}/`, { method: 'PATCH', body: form })
      else await api('/categories/', { method: 'POST', body: form })
      setForm({ name: '', type: 'expense' })
      setEditing(null)
      setNote(editing ? 'Category updated.' : 'Category added to your ledger.')
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  const remove = async (category) => {
    if (!window.confirm(`Delete “${category.name}”? Transactions already using it stay put.`)) return
    try {
      await api(`/categories/${category.id}/`, { method: 'DELETE' })
      load()
    } catch (err) {
      setNote(err.message)
    }
  }

  if (status === 'loading') return <Skeleton lines={4} />
  if (status === 'error') return <ErrorState message={error} onRetry={load} />

  const groups = [
    ['income', 'Money in'],
    ['expense', 'Money out'],
  ]

  return (
    <>
      <PageHead eyebrow="Make the labels yours" title="Categories with a student accent." />
      <section className="two-col">
        <form className="glass rise" onSubmit={save}>
          <h2>{editing ? 'Rename your category' : 'Add a personal category'}</h2>
          <Field label="Name">
            <input required maxLength="100" value={form.name} placeholder="e.g. Data & airtime" onChange={(event) => setForm({ ...form, name: event.target.value })} />
          </Field>
          <Field label="Type">
            <select value={form.type} onChange={(event) => setForm({ ...form, type: event.target.value })}>
              <option value="expense">Expense</option>
              <option value="income">Income</option>
            </select>
          </Field>
          {note && <p className={note.toLowerCase().includes('category') ? 'form-note ok' : 'form-note'}>{note}</p>}
          <button className="cta full">{editing ? 'Save changes' : 'Add category'} <b>→</b></button>
          {editing && <button className="text-btn" type="button" onClick={() => { setEditing(null); setForm({ name: '', type: 'expense' }) }}>Cancel</button>}
        </form>
        <div className="category-groups">
          {groups.map(([type, title]) => {
            const rows = categories.filter((category) => category.type === type)
            return (
              <section key={type} className="glass rise">
                <p className="overline">{title}</p>
                {rows.map((category) => (
                  <article className="category-row" key={category.id}>
                    <div>
                      <b>{category.name}</b>
                      <p>{category.is_default ? 'Campus default' : 'Yours'}</p>
                    </div>
                    {!category.is_default && (
                      <div className="row-actions">
                        <button type="button" onClick={() => { setEditing(category.id); setForm({ name: category.name, type: category.type }) }}>Edit</button>
                        <button type="button" onClick={() => remove(category)}>Delete</button>
                      </div>
                    )}
                  </article>
                ))}
                {rows.length === 0 && <EmptyState icon="▦" title="None yet" text="Add a label on the left." />}
              </section>
            )
          })}
        </div>
      </section>
    </>
  )
}
