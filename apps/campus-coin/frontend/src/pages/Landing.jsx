import { Link } from 'react-router-dom'

const MAP = [
  ['Home', '/', 'The front door'],
  ['Sign in', '/login', 'Welcome back'],
  ['Create account', '/register', 'Start a ledger'],
  ['Reset password', '/reset-password', 'Get back in'],
  ['Overview', '/dashboard', 'This month at a glance'],
  ['Transactions', '/transactions', 'Log money in and out'],
  ['Budgets', '/budgets', 'Guardrails per category'],
  ['Reports', '/reports', 'Six-month rewind'],
  ['Smart tips', '/tips', 'Nudges from your habits'],
  ['Categories', '/categories', 'Your own labels'],
  ['Profile', '/profile', 'Allowance and savings goal'],
  ['Admin studio', '/admin', 'Campus-wide numbers'],
]

const TICKER = ['Allowance', 'Canteen', 'Hostel', 'Transport', 'Scholarship', 'Textbooks', 'Data plan', 'Outings', 'Part-time', 'Savings']

export default function Landing() {
  const ticker = [...TICKER, ...TICKER]
  return (
    <div className="landing">
      <div className="aurora one" />
      <div className="aurora two" />
      <div className="aurora three" />
      <header className="landing-nav">
        <Link className="brand" to="/"><span>◒</span> Campus<span>Coin</span></Link>
        <div className="nav-links">
          <a href="#how">How it works</a>
          <a href="#sitemap">Sitemap</a>
          <Link className="quiet-link" to="/login">Sign in</Link>
          <Link className="cta small" to="/register">Get started <b>→</b></Link>
        </div>
      </header>

      <section className="hero">
        <div className="hero-copy">
          <p className="overline">The NextGen BudgetBee</p>
          <h1>
            <span className="line"><span>Your money.</span></span>
            <span className="line"><span><em>In full colour.</em></span></span>
          </h1>
          <p className="lead">A vivid ledger for allowance, gigs, canteen runs and the quiet art of keeping some naira for yourself.</p>
          <div className="hero-actions">
            <Link className="cta" to="/register">Start your money story <b>→</b></Link>
            <Link className="ghost" to="/login">I already have an account</Link>
          </div>
          <div className="trust">
            <span>✦ No bank connection</span>
            <span>✦ Built for students</span>
            <span>✦ You stay in control</span>
          </div>
        </div>
        <div className="hero-art">
          <img className="hero-photo" src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=1000&auto=format&fit=crop" alt="University student smiling on campus" loading="eager" />
          <div className="orbit" aria-hidden="true">
            <span className="satellite one">₦</span>
            <span className="satellite two">✦</span>
          </div>
          <div className="coin coin-a">₦</div>
          <div className="coin coin-b">✦</div>
          <div className="dashboard-preview">
            <p>This month</p>
            <h3>₦ 42,500.00</h3>
            <div className="wave"><i /><i /><i /><i /><i /><i /><i /></div>
            <div className="preview-row"><span>Food fuel</span><b>− ₦4,250</b></div>
            <div className="preview-row"><span>Allowance</span><b className="green">+ ₦30,000</b></div>
          </div>
        </div>
      </section>

      <div className="marquee" aria-hidden="true">
        <div className="marquee-track">
          {ticker.map((item, index) => <span key={`${item}-${index}`}>{item} <b>✦</b></span>)}
        </div>
      </div>

      <section className="bento" id="how">
        <article className="bento-wide">
          <p className="overline">01 — Log it</p>
          <h2>Ten seconds from pocket to page.</h2>
          <p>Income and expenses use the categories students actually live with: canteen, hostel, data, textbooks, gigs.</p>
        </article>
        <article>
          <p className="overline">02 — See it</p>
          <h2>Charts that talk back.</h2>
          <p>Category bars, a six-month swing, and a budget line that turns warm before you overshoot.</p>
        </article>
        <article className="bento-clay">
          <p className="overline">03 — Grow it</p>
          <h2>Tips from your own trail.</h2>
          <p>Campus Coin compares this month with your habits and offers a nudge you can pin, dismiss, or ignore.</p>
        </article>
        <article className="bento-iris">
          <p className="overline">Suggestions, not orders</p>
          <h2>Type “campus cafe”. Watch Food appear.</h2>
          <p>Category hints learn when you correct them. You can always pick a different label.</p>
        </article>
      </section>

      <section className="steps">
        <p className="overline">A short path</p>
        <ol>
          <li><strong>Create a free account</strong><span>Name, username, and a password you remember.</span></li>
          <li><strong>Drop in this month</strong><span>Allowance in, canteen out. Repeat until it feels honest.</span></li>
          <li><strong>Set one bright line</strong><span>A food budget is enough to start. The bell rings near the edge.</span></li>
        </ol>
      </section>

      <section className="sitemap" id="sitemap">
        <div className="sitemap-head">
          <p className="overline">Sitemap</p>
          <h2>Every door in the house.</h2>
        </div>
        <div className="sitemap-grid">
          {MAP.map(([label, to, note]) => (
            <Link key={to} to={to}>
              <b>{label}</b>
              <span>{note}</span>
            </Link>
          ))}
        </div>
      </section>

      <footer className="landing-foot">
        <p>Campus Coin · student money, kept by hand.</p>
        <a href="#sitemap">Jump to the sitemap</a>
      </footer>
    </div>
  )
}
