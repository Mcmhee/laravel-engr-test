# Claims Management System

A modern Laravel-based web application for managing, batching, and optimizing healthcare claims between providers and insurers. The system streamlines claim submission, processing, and cost analysis, making it easy for both healthcare providers and insurance companies to collaborate efficiently.

---

## 🚀 Quick Start

Follow these steps to set up and run the application locally:

1. **Clone the repository**
   ```bash
   git clone <your-repo-url>
   cd <project-directory>
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Run database migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

5. **Start the development servers**
   ```bash
   npm run dev
   php artisan serve
   ```

6. **Access the app**
   - Open your browser and go to [http://localhost:8000](http://localhost:8000)
   - The UI is displayed on the root page

---

## ✨ Features

- **Healthcare Provider Portal**: Submit and manage medical claims for patients
- **Insurer Dashboard**: Review, batch, and optimize claims for cost efficiency
- **Automated Claim Batching**: Group claims based on insurer preferences and constraints
- **Cost Analysis**: Detailed breakdown of claim processing costs by specialty, priority, provider, and more
- **Optimization Recommendations**: Get actionable suggestions to reduce costs and improve efficiency
- **Modern UI**: Clean, responsive interface built with Vue 3, Inertia.js, and Tailwind CSS
- **RESTful API**: Endpoints for claim submission, batch management, cost analysis, and optimization

---

## 🧩 How the App Works (In Simple Terms)

- **Claims**: Healthcare providers submit claims for medical services rendered. Each claim includes details like provider, insurer, specialty, priority, and items/services billed.
- **Insurers**: Insurance companies receive claims and have preferences for how claims should be grouped (batched) and processed.
- **Batches**: Claims are grouped into batches based on insurer rules (e.g., minimum/maximum batch size, daily capacity, date preferences). Batching helps streamline processing and reduce costs.
- **Optimization**: The system analyzes unbatched claims and recommends the most cost-effective way to batch and process them, considering factors like specialty efficiency, claim priority, and insurer constraints.
- **Cost Analysis**: Insurers can view detailed analytics on claim processing costs, identify high-cost areas, and discover opportunities for savings.

---

## 🧠 How the Batching Algorithm Works (Layman's Explanation)

When a healthcare provider submits a claim, the system needs to decide how to group ("batch") it with other claims so that the insurance company can process them together in the most cost-effective way. Here's how it works in simple terms:

- **Grouping Claims:** The system looks at all the claims and tries to group them into batches based on the insurance company's rules. For example, some insurers want claims grouped by the date of the medical visit, while others prefer the date the claim was submitted.

- **Batch Size and Capacity:** Each insurer has rules about how many claims can be in a batch (minimum and maximum), and how many claims they can process in a day. The system makes sure not to put too many or too few claims in a batch.

- **Specialty and Priority:** Some insurers are better at handling certain types of medical claims (like heart or bone treatments), and urgent claims cost more to process. The system takes these into account to keep costs low.

- **Claim Value:** Expensive claims cost more to process, so the system tries to balance batches to avoid making any one batch too costly.

- **Timing:** Processing costs go up as the month goes on. The system tries to batch claims earlier in the month when it's cheaper, if possible.

- **Optimization:** The system uses a smart algorithm to look at all these factors and find the best way to group claims so the insurer spends the least amount of money overall. If it can't find a perfect solution, it uses a simple backup method to make sure every claim still gets processed.

- **Cost Calculation:** For each batch, the system calculates the total cost by looking at:
  - The day of the month (later = more expensive)
  - The type of medical specialty (some are cheaper for certain insurers)
  - The urgency (priority) of the claim
  - The total value of the claim

- **Notification:** Once a batch is created, the insurer gets an email letting them know a new batch is ready for processing.

In short, the system is always trying to save money for insurers by grouping claims in the smartest way possible, while following all the rules and limits set by each insurer.

---

## 🖥️ User Interaction

### Web UI
- **Home Page**: Choose your role (Provider or Insurer)
- **Provider**: Fill out and submit claim forms
- **Insurer**: Access dashboard to view claims, run optimizations, and analyze costs

### API Endpoints (Key Examples)
- `POST /api/claims` — Submit a new claim
- `GET /api/insurers/{id}/claims` — List claims for an insurer
- `GET /api/insurers/{id}/optimization-recommendations` — Get batching and cost-saving suggestions
- `POST /api/insurers/{id}/optimize-batching` — Run claim batching optimization
- `GET /api/insurers/{id}/cost-analysis` — View detailed cost analytics

---

## 📁 Project Structure (Key Folders)

- `app/` — Laravel backend logic (actions, controllers, models)
- `resources/js/` — Frontend code (Vue components, pages, layouts)
- `routes/` — API and web route definitions
- `database/` — Migrations, seeders, and factories


---

## 📝 Extra Notes

- The app uses Laravel 11, Vue 3, Inertia.js, and Tailwind CSS
- Make sure your `.env` file is configured for your local database
- Run `php artisan migrate --seed` to populate the database with sample insurers and data
- For production, use `npm run build` and configure a proper web server

---

For any questions or contributions, please open an issue or submit a pull request!



