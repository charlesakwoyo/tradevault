<p align="center">
  <img src="public/favicon.svg" width="72" alt="TradeVault logo">
</p>

<h1 align="center">TradeVault</h1>

<p align="center">
  A transparent crypto-market platform: live prices, verified accounts, and balances backed by a double-entry ledger.
</p>

<p align="center">
  <img alt="PHP 8.4" src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white">
  <img alt="Laravel 13" src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white">
  <img alt="Tailwind CSS 4" src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white">
  <img alt="Tests: Pest" src="https://img.shields.io/badge/tests-Pest-F28D1A">
</p>

---

## Overview

TradeVault is a Laravel web application where customers can open a verified account, follow live crypto-asset prices, and see exactly where their money stands. Every balance comes from a double-entry ledger, so each credit and debit is a balanced, auditable record.

> **Project status:** accounts, verification, live market data, the ledger, dashboards and the AI help assistant are working. **Deposits, withdrawals and trading are not live yet.** Handling real customer funds also requires the appropriate licence (or a licensed broker partner) in each jurisdiction you operate in.

## Features

### For customers
- **Live market data:** 12 crypto markets (BTC, ETH, SOL, XRP and more) with price, 24-hour change, high, low and volume, refreshed every minute from Binance public data. Stale prices are flagged as *Delayed*, never shown as live.
- **Sign-up with email or Google,** with minimum-age and country checks, and consent to the Terms, Privacy Policy and Risk Disclosure.
- **Verification:** email and phone verification, with identity (KYC) status tracked on every account.
- **Account security:** strong password rules, optional two-factor authentication (TOTP) with recovery codes, and rate-limited sign-in.
- **Dashboard and transactions:** balances, totals, open positions and a filterable transaction history drawn from the ledger.
- **AI help assistant:** a chat widget that answers questions about prices, the platform and the customer's own account (read-only).

### For administrators
- **Back office** with a dashboard of real totals and a searchable user overview.
- **Roles and permissions:** admin, support, finance and trading roles, with per-route permission checks.
- **Two-factor authentication required** for all staff accounts.
- **Append-only audit log** of sign-ins, security changes and administrative actions.

### Under the hood
- **Double-entry ledger:** balanced, row-locked, idempotent postings. Customer wallets can never go negative. `ledger:verify` checks integrity every hour.
- **Swappable market-data driver:** `binance` for live prices, `sandbox` for offline development.
- **Sanctum-based JSON API** for authentication, profile and wallet data.
- **Extensive Pest test suite** covering auth, authorization, the ledger, market data and the assistant.

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 13 |
| Authentication | Laravel Fortify (web), Sanctum (API), Socialite (Google) |
| Authorization | spatie/laravel-permission |
| Frontend | Blade, Tailwind CSS 4, Alpine.js, Vite |
| Market data | Binance public REST API |
| AI assistant | Anthropic Claude API (`anthropic-ai/sdk`) |
| Database | SQLite by default (MySQL / PostgreSQL supported by Laravel) |
| Testing | Pest 4 |

## Getting started

### Requirements
- PHP 8.3+ with the `bcmath`, `pdo_sqlite` and `openssl` extensions
- Composer 2
- Node.js 20.19+ and npm

### Installation

```bash
git clone https://github.com/charlesakwoyo/tradevault.git
cd tradevault

# Installs dependencies, creates .env, generates the app key, runs migrations and builds assets
composer run setup

# Seed roles, permissions and the crypto markets
php artisan db:seed

# Create your administrator account (you will be prompted for the details)
php artisan app:create-admin you@example.com
```

### Running locally

```bash
composer run dev
```

This starts everything you need together:

| Process | Purpose |
|---|---|
| `server` | The web app at http://localhost:8000 |
| `queue` | Background jobs |
| `scheduler` | Refreshes market prices every minute, plus hourly ledger checks and daily clean-up |
| `vite` | Compiles CSS/JS with hot reload |

Keep this terminal open while you work, and stop it with **Ctrl+C**.

> **Pages look unstyled?** The Vite dev server probably stopped without cleaning up. Run `npm run build` and refresh.

## Configuration

All settings live in `.env` (see `.env.example`). The most important ones:

| Variable | Purpose |
|---|---|
| `APP_URL` | Public URL of the app |
| `MARKET_DATA_DRIVER` | `binance` for live prices, `sandbox` for simulated offline prices |
| `BINANCE_BASE_URL` | Use `https://data-api.binance.vision` where `api.binance.com` is geo-blocked |
| `MARKET_DATA_STALE_AFTER_SECONDS` | Age after which a price is shown as *Delayed* (default 300) |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | Enable "Continue with Google". The button appears once both are set. |
| `ANTHROPIC_API_KEY` | Enables the AI assistant. The chat button appears once it is set. |
| `ASSISTANT_MODEL` | Claude model for the assistant (default `claude-opus-5`) |
| `MAIL_MAILER` | Set to a real mail provider so verification emails are delivered (`log` only writes them to a file) |
| `SMS_DRIVER` | Phone-verification SMS driver (`log` is for development and is blocked in production) |
| `SUPPORT_EMAIL` | Shown to customers on the site and by the assistant |
| `REGISTRATION_ENABLED`, `REGISTRATION_MINIMUM_AGE` | Registration rules |
| `GEO_ALLOWED_COUNTRIES`, `GEO_BLOCKED_COUNTRIES` | Country restrictions (ISO codes, comma-separated) |
| `LIMIT_*` | Deposit and withdrawal limits shown to customers |

### Google sign-in
1. Create an OAuth client (type **Web application**) in [Google Cloud Console → Credentials](https://console.cloud.google.com/apis/credentials).
2. Add `{APP_URL}/auth/google/callback` as an authorised redirect URI.
3. Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`.

New Google users complete a short form (phone, country, date of birth, consent) before their account is created. Existing accounts are linked only when both Google and TradeVault have verified the email.

### AI assistant
1. Create an API key at [console.anthropic.com](https://console.anthropic.com/settings/keys).
2. Set `ANTHROPIC_API_KEY` in `.env`.

The assistant can read live prices for everyone and, for customers, their own balances, verification status and recent transactions. It cannot move money or place orders, will not give investment advice, and is rate-limited to 10 messages per minute and 200 per day per user.

## Useful commands

| Command | Description |
|---|---|
| `composer run dev` | Start the full local stack |
| `php artisan app:create-admin {email}` | Create an admin account, or promote an existing one |
| `php artisan markets:refresh-prices` | Fetch the latest market prices now |
| `php artisan ledger:verify` | Check that every journal balances and every wallet matches its entries |
| `php artisan test --compact` | Run the test suite |
| `vendor/bin/pint` | Format PHP code |
| `npm run build` | Build production assets |

## API

JSON endpoints under `/api`, authenticated with Sanctum bearer tokens:

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/auth/register` | Register a customer |
| `POST` | `/api/auth/login` | Obtain a token (two-factor aware) |
| `POST` | `/api/auth/logout` | Revoke the current token |
| `POST` | `/api/auth/forgot-password` | Send a password-reset link |
| `GET` | `/api/user` | Current user |
| `GET` / `PUT` | `/api/user/profile` | View or update the profile |
| `GET` | `/api/wallet` | Wallet balances |
| `GET` | `/api/wallet/transactions` | Ledger transactions |

## Project structure

```
app/
├── Actions/Fortify/        Registration, profile and password actions
├── Console/Commands/       app:create-admin, markets:refresh-prices, ledger:verify
├── Http/Controllers/       Web, admin, API, Google sign-in and assistant controllers
├── Models/                 Users, wallets, ledger entries, markets, prices, orders, KYC…
├── Services/
│   ├── Assistant/          AI assistant: model client, read-only tools, conversation loop
│   ├── Dashboard/          Customer and admin dashboard figures
│   ├── MarketData/         Binance and sandbox price providers, price refresher
│   └── Wallet/             Double-entry ledger and wallet services
config/tradevault.php       Platform rules: registration, KYC, limits, legal documents
resources/legal/            Terms, Privacy, Risk, AML and Withdrawal policy texts
resources/views/            Blade templates (landing page, dashboards, auth, admin)
routes/                     web.php, api.php, console.php (scheduled tasks)
tests/                      Pest feature and unit tests
```

## Testing

```bash
php artisan test --compact
```

Tests run against an in-memory SQLite database. External services are faked: no request reaches Binance, Google or the Claude API during tests.

## Deployment checklist

Before accepting real customers:

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`, and serve over HTTPS
- [ ] Use a production database (MySQL or PostgreSQL) and a real mail provider
- [ ] Configure a production SMS driver for phone verification
- [ ] Run a scheduler (`php artisan schedule:run` every minute) and a queue worker
- [ ] Replace the placeholder legal texts in `resources/legal/` with counsel-reviewed versions
- [ ] Set a real `SUPPORT_EMAIL`
- [ ] Obtain the required licence, or partner with a licensed broker, before handling client funds

## Roadmap

- [x] Accounts, roles, two-factor authentication and audit log
- [x] Double-entry ledger and dashboards
- [x] Live crypto market data
- [x] Google sign-in
- [x] AI help assistant
- [ ] Email and SMS providers for production
- [ ] Deposits and withdrawals (M-Pesa, cards)
- [ ] Order placement via a licensed broker or exchange
- [ ] Support tickets and notifications

## Risk warning

Trading and investing involve significant risk, including the possible loss of all capital invested. Crypto-asset prices are highly volatile. Nothing in this project is investment advice.
