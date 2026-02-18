# 🏦 Procesator - Professional SEC Insights Dashboard

**Procesator** is a high-performance, AI-driven financial analysis platform that transforms raw SEC EDGAR filings into actionable investment intelligence. It combines real-time data ingestion, local LLM processing, and professional financial metrics into a sleek, modern interface.

---

## 🚀 Key Features

### 🧠 AI-Powered Intelligence
- **Automated Summarization:** Instantly grasp the core message of complex filings analysis.
- **Sentiment Classification:** Real-time market mood tracking (Positive/Negative/Neutral).
- **Smart Categorization:** Filings are automatically sorted into intuitive groups like Earnings, Insider Trading, or Legal.

### 📊 Professional Financial Analysis
- **Deep-Dive Metrics:** Real-time enrichment with 8 critical indicators: P/E Ratio, Profit Margin, ROE, Debt-to-Equity, and more.
- **Earnings Surprise Tracking:** Interactive charts comparing Actual vs. Estimated EPS history.
- **Master-Detail Interface:** Seamless transition from global feed to specific company deep-dives.

### 🔍 Advanced Discovery & Personalization
- **Persistent Watchlist:** Follow your favorite tickers and filter the feed to stay focused on your portfolio.
- **Smart Search Engine:** Industrial-grade autocomplete with keyboard navigation (Arrows/Enter) and support for both Tickers and Names.
- **Real-Time Polling:** The dashboard scans for new filings every 60 seconds without requiring page refreshes.

---

## 🛠 Tech Stack

- **Backend:** Laravel 12 (Sail / Docker)
- **Frontend:** Vue.js 3 (Composition API) & Inertia.js
- **Styling:** Tailwind CSS (Glassmorphism design)
- **AI Engine:** Ollama
- **Data APIs:** SEC EDGAR (RSS), Finnhub API
- **Visualization:** Chart.js

---

## ⚙️ Installation & Setup

### 1. Requirements
- Docker & Laravel Sail
- Ollama (running locally or use an cloud model)
- Finnhub API Key

### 2. Environment Configuration
Clone the project and set up your `.env`:
```bash
cp .env.example .env
```
Ensure you configure:
- `FINNHUB_API_KEY`
- `OLLAMA_KEY`
- `OLLAMA_HOST=http://172.17.0.1:11434` (for Docker-to-Host communication)

### 3. Launching the App
```bash
# Start containers
./vendor/bin/sail up -d

# Install dependencies & migrate
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail php artisan migrate

# Start the frontend
npm run dev
```

### ⚙️ 4. Automation & Background Tasks

#### Manual Scan
To trigger a data fetch immediately:
```bash
./vendor/bin/sail php artisan news:fetch
```

#### Full Automation
To keep the dashboard updated automatically in the background, run these in separate terminal windows:

```bash
# Start the background scheduler (Runs news:fetch every minute)
./vendor/bin/sail php artisan schedule:work

# Start the queue worker (Processes AI analysis & financial enrichment)
./vendor/bin/sail php artisan queue:work
```

---

## 🏛 Architecture Highlights

- **Clean Layering:** Strict separation between Ingestion (Services), Transformation (Resources), and Delivery (Controllers).
- **Async Pipeline:** Heavy AI and API tasks are handled by background Jobs to ensure zero UI lag.
- **Security First:** Implemented API Rate Limiting, strict input sanitization, and route protection via Middleware.
- **Performance:** Optimized Eloquent queries and debounced frontend search for minimal server load.

---

## 📄 License
This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
