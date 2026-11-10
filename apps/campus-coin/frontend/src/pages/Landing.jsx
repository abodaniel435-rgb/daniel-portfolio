import { Link } from 'react-router-dom'
import { Icon } from '../components'

const FEATURES = [
  {
    mark: '₦',
    title: 'Every naira, logged',
    body: 'Allowance, canteen runs, data top-ups, gig money. One ledger that knows the difference between a want and a bill.',
  },
  {
    mark: '72',
    title: 'A money-health score',
    body: 'One number built from your savings rate, how many budgets you kept and how often you log. With the maths shown.',
  },
  {
    mark: '◎',
    title: 'Budgets that warn you',
    body: 'Set a line per category. The bar turns amber at 80% and coral when you cross it — before the money is gone.',
  },
  {
    mark: '🫙',
    title: 'Goal jars with a daily drip',
    body: 'Laptop, rent, Detty December. Campus Coin tells you exactly how much to drop in per day to land the date.',
  },
  {
    mark: '⌁',
    title: 'Split & settle',
    body: 'Shared hostel cleaning, group data, that one roommate. Log the bill, split it, tick off who has paid.',
  },
  {
    mark: '?',
    title: 'Ask your own ledger',
    body: '“Can 15k last 12 days?” The money roommate answers with your real numbers, not generic advice.',
  },
]

export default function Landing() {
  return (
    <div className="cc-mk">
      <header className="cc-mk-nav">
        <Link to="/" className="cc-brand" aria-label="Campus Coin home">
          <span className="cc-brand-mark">C</span>
          Campus<em>Coin</em>
        </Link>
        <nav className="cc-mk-nav-links" aria-label="Marketing">
          <a href="#how">How it works</a>
          <a href="#features">Features</a>
          <Link to="/login">Sign in</Link>
          <Link to="/register" className="cc-btn cc-btn--primary cc-btn--sm">
            Start free
          </Link>
        </nav>
      </header>

      <div className="cc-mk-wrap">
        <section className="cc-hero-mk">
          <div className="cc-hero-mk-copy">
            <p className="cc-eyebrow">Student money, without the guesswork</p>
            <h1>
              Money that
              <br />
              behaves on <em>campus.</em>
            </h1>
            <p className="cc-lead">
              Campus Coin turns your allowance into a plan you can actually keep. Log what you spend, see where it goes,
              get a money-health score you can explain, and save toward the thing you are actually saving for.
            </p>
            <div className="cc-hero-actions">
              <Link to="/register" className="cc-btn cc-btn--primary">
                Create a free account <Icon name="arrow" size={16} />
              </Link>
              <Link to="/login" className="cc-btn cc-btn--ghost">
                I already have an account
              </Link>
            </div>
            <div className="cc-trust">
              <span>
                <i /> No bank connection
              </span>
              <span>
                <i /> Built for Naira budgets
              </span>
              <span>
                <i /> Free while you study
              </span>
            </div>
          </div>

          <div className="cc-mock" aria-hidden="true">
            <div className="cc-mock-top">
              <span className="cc-pill cc-pill--ok">November</span>
              <span className="cc-pill">Money health 92</span>
            </div>
            <div>
              <p className="cc-eyebrow">Left this month</p>
              <p className="cc-mock-balance">₦67,250.00</p>
            </div>
            <div className="cc-spark">
              {[42, 58, 35, 74, 51, 66, 88].map((height, index) => (
                <i key={index} style={{ height: `${height}%` }} />
              ))}
            </div>
            <div>
              <div className="cc-meter">
                <div className="cc-meter-head">
                  <b>Food</b>
                  <span>₦17,250 / ₦25,000</span>
                </div>
                <div className="cc-meter-track">
                  <i className="cc-meter-fill" style={{ width: '69%' }} />
                </div>
              </div>
            </div>
            <div>
              <div className="cc-mock-row">
                <span>F</span>
                <b style={{ fontWeight: 600 }}>Canteen lunch</b>
                <b style={{ color: 'var(--text)' }}>− ₦3,300.00</b>
              </div>
              <div className="cc-mock-row">
                <span>A</span>
                <b style={{ fontWeight: 600 }}>Allowance</b>
                <b style={{ color: 'var(--lime)' }}>+ ₦85,000.00</b>
              </div>
            </div>
          </div>
        </section>

        <section className="cc-mk-section" id="how">
          <div className="cc-mk-section-head">
            <p className="cc-eyebrow">Three steps, one evening</p>
            <h2>From “where did my money go?” to “I know exactly.”</h2>
            <p className="cc-sub">
              Campus Coin is not a bank and never touches your account. It is the honest ledger between your allowance
              and your month.
            </p>
          </div>
          <div className="cc-steps">
            <article className="cc-step">
              <h3>Sign up in a minute</h3>
              <p>Name, username, country — that sets your currency. No card, no bank login, no waiting list.</p>
            </article>
            <article className="cc-step">
              <h3>Log the week honestly</h3>
              <p>Allowance in, canteen out. Type “jollof” and the food category is suggested for you.</p>
            </article>
            <article className="cc-step">
              <h3>Give every naira a job</h3>
              <p>One budget line and one goal jar is enough to start. The score explains itself from there.</p>
            </article>
          </div>
        </section>

        <section className="cc-mk-section" id="features">
          <div className="cc-mk-section-head">
            <p className="cc-eyebrow">What is inside</p>
            <h2>A full money desk, sized for a student.</h2>
          </div>
          <div className="cc-features">
            {FEATURES.map((feature) => (
              <article className="cc-feature" key={feature.title}>
                <span className="cc-feature-mark" aria-hidden="true">
                  {feature.mark}
                </span>
                <h3>{feature.title}</h3>
                <p>{feature.body}</p>
              </article>
            ))}
          </div>
        </section>

        <section className="cc-cta-band">
          <div>
            <p className="cc-eyebrow">Ready when your allowance lands</p>
            <h2>Start the semester with a ledger that tells the truth.</h2>
          </div>
          <Link to="/register" className="cc-btn cc-btn--primary">
            Create my account <Icon name="arrow" size={16} />
          </Link>
        </section>

        <footer className="cc-mk-foot">
          <p>© {new Date().getFullYear()} Campus Coin · student money, sorted.</p>
          <nav aria-label="Footer">
            <Link to="/login">Sign in</Link>
            <Link to="/register">Create account</Link>
            <Link to="/reset-password">Reset password</Link>
          </nav>
        </footer>
      </div>
    </div>
  )
}
