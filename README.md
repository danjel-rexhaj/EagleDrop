🦅 EagleDrop

EagleDrop është një platformë web për menaxhimin dhe pagesën e shërbimeve/produkteve online, e ndërtuar me fokus te automatizimi i pagesave, siguria, dhe struktura e pastër e kodit.

Projekti është zhvilluar dhe përmirësuar në disa faza dhe përdor Stripe për procesimin e pagesave.

🚀 Funksionalitete Kryesore

💳 Pagesa online me Stripe

🛒 Checkout për produkte / shërbime (single & cart)

✅ Verifikim i pagesës dhe suksesit të transaksionit

🔐 Menaxhim i sigurt i kredencialeve (pa sekrete në repo)

⚙️ Strukturë e përgatitur për zgjerim dhe integrime të tjera

🔐 Siguria & Best Practices

Ky projekt nuk përmban:

❌ Stripe Secret Keys në kod

❌ .env file në repository

❌ vendor/ folder në GitHub

Pse?

Për arsye sigurie

Për të ndjekur praktikat profesionale të zhvillimit

Për të shmangur rrjedhje të kredencialeve

Të gjitha sekretet menaxhohen me environment variables.

📦 Dependencies (Composer)

Projektit i nevojiten disa librari PHP (p.sh. Stripe SDK), të cilat instalohen me Composer.

vendor/ është i përjashtuar nga GitHub dhe krijohet lokalisht.

🛠️ Instalimi Lokal
1️⃣ Klono repository-n
git clone https://github.com/danjel-rexhaj/EagleDrop.git
cd EagleDrop

2️⃣ Instalo dependencies
composer install

3️⃣ Konfiguro environment variables

Krijo një file .env (lokalisht):

STRIPE_SECRET_KEY=sk_test_your_key_here
STRIPE_PUBLISHABLE_KEY=pk_test_your_key_here


⚠️ Mos e ngarko .env në GitHub

4️⃣ Start serverin lokal

Nëse përdor XAMPP:

http://localhost/EagleDrop

💳 Pagesat me Stripe

Pagesat realizohen përmes Stripe Checkout, duke përdorur:

checkout_single.php

checkout_cart.php

payment_success.php

Stripe inicializohet në kod përmes:

\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));


Kjo siguron që:

API keys nuk ekspozohen

Projekti është i deploy-ueshëm në çdo ambient (local / production)

📁 Struktura e Projektit (shkurt)
EagleDrop/
├── assets/
├── vendor/        (ignored, created by composer)
├── checkout_cart.php
├── checkout_single.php
├── payment_success.php
├── .gitignore
├── README.md

🧠 Për kë është ky projekt?

Studentë që mësojnë pagesa online

Zhvillues PHP që duan shembull real Stripe integration

Projekte akademike / demo / MVP

Bazë për sisteme pagesash më komplekse

📌 Shënim

Ky projekt është ndërtuar për qëllime edukative dhe praktike, por ndjek standarde reale të industrisë për:

sigurinë

strukturën

menaxhimin e sekreteve

👨‍💻 Autor

Zhvilluar nga Danjel Rexhaj
