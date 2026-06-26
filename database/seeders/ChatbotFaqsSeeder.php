<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class ChatbotFaqsSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = 1; // Acme Corp

        $categories = [
            'Account e accesso',
            'Fatturazione',
            'Supporto tecnico',
            'Altro',
        ];

        $catMap = [];
        foreach ($categories as $name) {
            $cat = Category::firstOrCreate(
                ['company_id' => $companyId, 'name' => $name]
            );
            $catMap[$name] = $cat->id;
        }

        $faqs = [
            [
                'category' => 'Account e accesso',
                'question' => 'Come faccio a resettare la password?',
                'answer' => 'Per resettare la password, vai sulla pagina di login e clicca su "Password dimenticata?". Inserisci la tua email aziendale e riceverai un link per impostare una nuova password. Il link è valido per 60 minuti.',
            ],
            [
                'category' => 'Account e accesso',
                'question' => 'Il mio account è stato bloccato, cosa devo fare?',
                'answer' => 'L\'account viene bloccato dopo 5 tentativi di login falliti consecutivi. Il blocco si rimuove automaticamente dopo 15 minuti. Se hai urgenza, apri un ticket al supporto tecnico e ti sbloccheremo manualmente.',
            ],
            [
                'category' => 'Account e accesso',
                'question' => 'Posso cambiare la mia email associata all\'account?',
                'answer' => 'Per motivi di sicurezza, l\'email associata all\'account non può essere modificata autonomamente. Apri un ticket al supporto specificando vecchia email e nuova email: la modifica avverrà entro 24 ore lavorative.',
            ],
            [
                'category' => 'Fatturazione',
                'question' => 'Dove posso scaricare le mie fatture?',
                'answer' => 'Le fatture sono disponibili nella sezione "Documenti" del tuo profilo aziendale. Sono in formato PDF e archiviate per 10 anni come da normativa. Per fatture più vecchie di 10 anni, contatta l\'amministrazione.',
            ],
            [
                'category' => 'Fatturazione',
                'question' => 'Come modifico il metodo di pagamento?',
                'answer' => 'Il metodo di pagamento si modifica dal pannello amministrativo, sezione "Pagamenti". Sono accettati: bonifico SEPA, carta di credito (Visa, Mastercard, Amex) e RID. La modifica diventa effettiva dal ciclo di fatturazione successivo.',
            ],
            [
                'category' => 'Supporto tecnico',
                'question' => 'Quali browser sono supportati?',
                'answer' => 'L\'applicazione supporta le ultime due versioni di Chrome, Firefox, Safari ed Edge. Internet Explorer non è supportato. Consigliamo Chrome aggiornato per la migliore esperienza.',
            ],
            [
                'category' => 'Supporto tecnico',
                'question' => 'L\'applicazione è lenta o non si carica, cosa posso fare?',
                'answer' => 'Prova in ordine: 1) ricarica la pagina con CTRL+F5 (cache hard refresh), 2) svuota cache e cookie del browser, 3) prova in modalità incognito, 4) prova un altro browser. Se persiste, apri un ticket specificando browser, versione e orario del problema.',
            ],
            [
                'category' => 'Supporto tecnico',
                'question' => 'Non riesco a fare login pur avendo le credenziali giuste',
                'answer' => 'Verifica innanzitutto che il tasto Caps Lock sia disattivato. Poi controlla di essere sull\'URL corretto (https, non http). Se l\'account è bloccato vedrai un messaggio specifico: in quel caso attendi 15 minuti. Se nessuna di queste opzioni funziona, usa "Password dimenticata?" per reimpostarla.',
            ],
            [
                'category' => 'Altro',
                'question' => 'Quali sono gli orari del supporto?',
                'answer' => 'Il supporto è attivo dal lunedì al venerdì, 9:00-18:00 (orario italiano). I ticket possono essere aperti 24/7 ma verranno presi in carico negli orari lavorativi. Per emergenze fuori orario è disponibile il numero verde indicato nel tuo contratto.',
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create([
                'company_id' => $companyId,
                'category_id' => $catMap[$faq['category']],
                'question' => $faq['question'],
                'answer' => $faq['answer'],
            ]);
        }

        $this->command->info('Created ' . count($faqs) . ' FAQs across ' . count($categories) . ' categories for company ' . $companyId);
    }
}