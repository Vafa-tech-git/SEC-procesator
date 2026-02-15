# Procesator: AI-Driven SEC insights dashboard

Procesator is a high-performance, automated intelligence platform that monitors, cleans, and analyzes SEC (U.S. Securities and Exchange Commission) filings in real-time. By combining a resilient 3-tier data fetching engine with local LLM-based sentiment analysis, it transforms raw legal documents into actionable financial insights.

## 🚀 Key features

*   **Resilient 3-Tier data fetching**: 
    *   **Tier 1**: Structured JSON/Atom API for maximum speed.
    *   **Tier 2**: HTML Scraping (DomCrawler) as a primary fallback.
    *   **Tier 3**: Headless Browser (Browsershot/Puppeteer) to bypass strict bot protection.
*   **AI sentiment analysis**: Automated summarization and sentiment classification (Positive/Negative/Neutral) using local LLMs (Ollama/Llama3) with a seamless Cloud fallback.
*   **Real-time dashboard**: Interactive UI built with Vue.js and Inertia.js, featuring a 60-second "Heartbeat" countdown and instant data synchronization.
*   **Market analytics**: Dynamic visual breakdown of market sentiment using interactive Chart.js components.
*   **Enterprise security**: Implements custom Content Security Policy (CSP), X-Frame-Options, Rate Limiting, and Mass Assignment protection.
*   **Professional architecture**: Adheres to SOLID principles, utilizing Service Layers, Dependency Injection, and Model Observers for a clean, decoupled backend.

## 🛠 Tech stack

*   **Backend**: Laravel 12 (PHP 8.3)
*   **Frontend**: Vue.js 3, Inertia.js, Tailwind CSS
*   **Database**: MySQL
*   **AI Engine**: Ollama
*   **Automation**: Laravel Scheduler & Artisan Commands
*   **Infrastructure**: Docker (Laravel Sail)

## 📦 Installation & Setup

### 1. Clone & Environment
```bash
git clone https://github.com/yourusername/procesator.git
cd procesator
cp .env.example .env
```

### 2. Start infrastructure (Docker)
Ensure you have Docker and Docker Compose installed.
```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install --force
```

### 3. Database & assets
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run build
```

### 4. AI configuration
Ensure **Ollama** is running on your host machine and listening to Docker:
```bash
export OLLAMA_HOST=0.0.0.0
ollama serve
```
Update your `.env` with the correct model name (e.g., `llama3`).

### 5. Start the Engine
To start the real-time background scanner:
```bash
./vendor/bin/sail artisan schedule:work
```

## 🏗 System Architecture

The project follows a decoupled service-based architecture:
*   **`SecFetcher`**: Orchestrates the multi-tier retrieval of SEC documents.
*   **`AiProcessor`**: Handles HTML sanitization and LLM communication.
*   **`FilingObserver`**: Decouples the fetcher from the processor, ensuring that every new filing is analyzed the moment it hits the database.
*   **`FilingController`**: Serves as the high-performance bridge between data models and the reactive UI.

## 📄 License
This project is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
