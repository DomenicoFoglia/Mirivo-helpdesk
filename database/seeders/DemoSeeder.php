<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Category;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DemoSeeder
 *
 * Popola un'azienda demo "Acme Corp" con:
 * - 5 utenti (admin, agent L2, agent L1, 2 users) con password "Test@1234567"
 * - 4 categorie
 * - 9 FAQ (delegate a ChatbotFaqsSeeder)
 * - 25 ticket in vari stati con messaggi coerenti
 * - 4 allegati di esempio
 * - 3 inviti pendenti
 *
 * Email su dominio @acme.it (ha MX, passa il validator email:dns dell'app).
 *
 * Idempotente su company/users/categories (firstOrCreate su chiavi naturali).
 * Ticket, messaggi, allegati, inviti vengono aggiunti ogni run: per un
 * reset pulito usare `php artisan migrate:fresh --seed`.
 *
 * Lancio: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $faker = FakerFactory::create('it_IT');

            $company = $this->createCompany();
            $users = $this->createUsers($company);
            $categories = $this->createCategories($company);

            // Le FAQ sono gia' gestite dal ChatbotFaqsSeeder esistente
            $this->call(ChatbotFaqsSeeder::class);

            $tickets = $this->createTickets($company, $users, $categories, $faker);
            $this->createAttachments($tickets);
            $this->createInvitations($company);
        });

        $this->command->info('DemoSeeder completato. Credenziali demo:');
        $this->command->info('  Admin:    mario.rossi@acme.it       / Test@1234567');
        $this->command->info('  Agent L2: sara.neri@acme.it         / Test@1234567');
        $this->command->info('  Agent L1: giovanni.bianchi@acme.it  / Test@1234567');
        $this->command->info('  User 1:   luigi.verdi@acme.it       / Test@1234567');
        $this->command->info('  User 2:   dario.viola@acme.it     / Test@1234567');
    }

    /**
     * Crea l'azienda demo. Idempotente: se esiste gia' non viene ricreata.
     * Prepara anche un logo placeholder se non c'e' un file logo.
     */
    private function createCompany(): Company
    {
        return Company::firstOrCreate(
            ['name' => 'Acme Corp'],
            [
                'slug' => 'acme-corp-demo01',
                'logo' => $this->preparePlaceholderLogo(),
                'theme_color' => 'amber',
            ]
        );
    }

    /**
     * Genera un logo placeholder PNG 200x200 con lettere "AC" su sfondo ambra.
     * Richiede l'estensione GD di PHP (di solito attiva).
     * Ritorna il path relativo tipo "logos/acme-demo.png".
     */
    private function preparePlaceholderLogo(): string
    {
        $filename = 'logos/acme-demo.png';
        $fullPath = storage_path('app/public/' . $filename);

        if (file_exists($fullPath)) {
            return $filename;
        }

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $img = imagecreatetruecolor(200, 200);
        $bg = imagecolorallocate($img, 186, 117, 23);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bg);

        $font = 5;
        $text = 'AC';
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        imagestring($img, $font, (200 - $textWidth) / 2, (200 - $textHeight) / 2, $text, $white);

        imagepng($img, $fullPath);
        imagedestroy($img);

        return $filename;
    }

    /**
     * Crea i 5 utenti demo con password uguale per tutti.
     * Idempotente su email.
     */
    private function createUsers(Company $company): array
    {
        $password = Hash::make('Test@1234567');

        $admin = User::firstOrCreate(
            ['email' => 'mario.rossi@acme.it'],
            [
                'company_id' => $company->id,
                'name' => 'Mario',
                'surname' => 'Rossi',
                'password' => $password,
                'role' => 'admin',
                'level' => null,
                'theme' => 'amber',
            ]
        );

        $agentL2 = User::firstOrCreate(
            ['email' => 'sara.neri@acme.it'],
            [
                'company_id' => $company->id,
                'name' => 'Sara',
                'surname' => 'Neri',
                'password' => $password,
                'role' => 'agent',
                'level' => 2,
                'theme' => 'amber',
            ]
        );

        $agentL1 = User::firstOrCreate(
            ['email' => 'giovanni.bianchi@acme.it'],
            [
                'company_id' => $company->id,
                'name' => 'Giovanni',
                'surname' => 'Bianchi',
                'password' => $password,
                'role' => 'agent',
                'level' => 1,
                'theme' => 'amber',
            ]
        );

        $user1 = User::firstOrCreate(
            ['email' => 'luigi.verdi@acme.it'],
            [
                'company_id' => $company->id,
                'name' => 'Luigi',
                'surname' => 'Verdi',
                'password' => $password,
                'role' => 'user',
                'level' => null,
                'theme' => 'amber',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'dario.viola@acme.it'],
            [
                'company_id' => $company->id,
                'name' => 'Dario',
                'surname' => 'Viola',
                'password' => $password,
                'role' => 'user',
                'level' => null,
                'theme' => 'amber',
            ]
        );

        return [
            'admin' => $admin,
            'agent_l2' => $agentL2,
            'agent_l1' => $agentL1,
            'users' => [$user1, $user2],
        ];
    }

    /**
     * Crea le 4 categorie standard. Coerenti con quelle usate da ChatbotFaqsSeeder.
     * Idempotente su company_id + name.
     */
    private function createCategories(Company $company): array
    {
        $names = ['Account e accesso', 'Fatturazione', 'Supporto tecnico', 'Altro'];

        $map = [];
        foreach ($names as $name) {
            $cat = Category::firstOrCreate(
                ['company_id' => $company->id, 'name' => $name]
            );
            $map[$name] = $cat;
        }

        return $map;
    }

    /**
     * Crea 25 ticket con date sparse negli ultimi 60 giorni.
     * Distribuisce i ticket tra i due utenti (Luigi e Dario) alternandoli.
     * NON idempotente: rilanciare crea nuovi ticket.
     */
    private function createTickets(Company $company, array $users, array $categories, $faker): array
    {
        $templates = [
            ['title' => 'Non riesco a resettare la password', 'category' => 'Account e accesso', 'status' => 'open', 'priority' => 'high'],
            ['title' => 'Errore 500 quando esporto fatture in PDF', 'category' => 'Fatturazione', 'status' => 'working', 'priority' => 'high'],
            ['title' => 'La dashboard e\' molto lenta stamattina', 'category' => 'Supporto tecnico', 'status' => 'open', 'priority' => 'medium'],
            ['title' => 'Impossibile allegare file superiori a 5MB', 'category' => 'Supporto tecnico', 'status' => 'closed', 'priority' => 'low'],
            ['title' => 'Richiesta di fattura in inglese', 'category' => 'Fatturazione', 'status' => 'closed', 'priority' => 'low'],
            ['title' => 'Il logo aziendale non viene salvato', 'category' => 'Supporto tecnico', 'status' => 'working', 'priority' => 'medium'],
            ['title' => 'Come cambio il piano tariffario?', 'category' => 'Fatturazione', 'status' => 'closed', 'priority' => null],
            ['title' => 'Configurazione SSO non funzionante', 'category' => 'Account e accesso', 'status' => 'escalated', 'priority' => 'high'],
            ['title' => 'Notifiche email non arrivano', 'category' => 'Supporto tecnico', 'status' => 'open', 'priority' => 'medium'],
            ['title' => 'Errore durante l\'invito di un nuovo utente', 'category' => 'Account e accesso', 'status' => 'working', 'priority' => 'medium'],
            ['title' => 'Bug: il filtro categoria si resetta al refresh', 'category' => 'Supporto tecnico', 'status' => 'escalated', 'priority' => 'medium'],
            ['title' => 'Come esporto tutti i ticket in CSV?', 'category' => 'Altro', 'status' => 'closed', 'priority' => null],
            ['title' => 'Impossibile creare una nuova categoria', 'category' => 'Supporto tecnico', 'status' => 'open', 'priority' => 'low'],
            ['title' => 'Richiesta di rimborso per fattura duplicata', 'category' => 'Fatturazione', 'status' => 'escalated', 'priority' => 'high'],
            ['title' => 'Il chatbot Mira non risponde in italiano', 'category' => 'Supporto tecnico', 'status' => 'open', 'priority' => 'low'],
            ['title' => 'Accesso negato alla sezione admin', 'category' => 'Account e accesso', 'status' => 'closed', 'priority' => 'medium'],
            ['title' => 'Modifica anagrafica cliente', 'category' => 'Altro', 'status' => 'closed', 'priority' => null],
            ['title' => 'Feedback: aggiungere dark mode al report PDF', 'category' => 'Altro', 'status' => 'working', 'priority' => 'low'],
            ['title' => 'Errore nel calcolo IVA italiana', 'category' => 'Fatturazione', 'status' => 'escalated', 'priority' => 'high'],
            ['title' => 'Come funziona la funzione di escalation?', 'category' => 'Altro', 'status' => 'closed', 'priority' => null],
            ['title' => 'La ricerca globale ignora gli accenti', 'category' => 'Supporto tecnico', 'status' => 'open', 'priority' => 'low'],
            ['title' => 'Ho perso l\'accesso al mio account', 'category' => 'Account e accesso', 'status' => 'closed', 'priority' => 'medium'],
            ['title' => 'Consulenza su integrazione API', 'category' => 'Altro', 'status' => 'working', 'priority' => 'medium'],
            ['title' => 'Errore di sintassi CSV nell\'importazione', 'category' => 'Supporto tecnico', 'status' => 'closed', 'priority' => 'medium'],
            ['title' => 'Richiesta funzionalita\' notifiche mobile', 'category' => 'Altro', 'status' => 'open', 'priority' => 'low'],
        ];

        $tickets = [];
        foreach ($templates as $i => $template) {
            $createdAt = Carbon::now()->subDays(rand(1, 60))->subHours(rand(0, 23));

            // Alterna Luigi (indice 0) e Dario (indice 1) come autore
            $author = $users['users'][$i % 2];

            // Assignee: solo per ticket working o closed. Escalated e open no.
            $assigneeId = null;
            if (in_array($template['status'], ['working', 'closed'])) {
                $assigneeId = rand(0, 1) ? $users['agent_l1']->id : $users['agent_l2']->id;
            }

            $closedAt = $template['status'] === 'closed'
                ? $createdAt->copy()->addHours(rand(2, 72))
                : null;

            $ticket = Ticket::create([
                'company_id' => $company->id,
                'user_id' => $author->id,
                'assignee_id' => $assigneeId,
                'category_id' => $categories[$template['category']]->id,
                'title' => $template['title'],
                'status' => $template['status'],
                'priority' => $template['priority'],
                'created_at' => $createdAt,
                'updated_at' => $closedAt ?? $createdAt,
                'closed_at' => $closedAt,
            ]);

            $this->createMessagesForTicket($ticket, $author, $users, $faker, $template['status']);
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * Popola messaggi coerenti sul ticket:
     * - primo messaggio: sempre dell'utente che ha aperto il ticket
     * - se non open: risposta dell'agente assegnato
     * - se working/escalated/closed: 1-3 messaggi di scambio ulteriori
     * - se working/escalated: aggiunge una nota interna privata dell'agent L2
     */
    private function createMessagesForTicket(Ticket $ticket, User $author, array $users, $faker, string $status): void
    {
        $firstBodies = [
            'Buongiorno, vi scrivo perche\' ho riscontrato questo problema. Potete aiutarmi?',
            'Salve, non riesco a completare l\'operazione. Attendo vostra risposta.',
            'Ciao, mi succede da stamattina. Grazie in anticipo.',
            'Buonasera, ho gia\' provato le soluzioni base ma senza risultato.',
        ];

        Message::create([
            'ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'body' => $firstBodies[array_rand($firstBodies)],
            'type' => 'public',
            'created_at' => $ticket->created_at,
            'updated_at' => $ticket->created_at,
        ]);

        if ($status === 'open') {
            return;
        }

        $agent = $ticket->assignee_id === $users['agent_l1']->id ? $users['agent_l1'] : $users['agent_l2'];

        $agentReplies = [
            'Salve, grazie per aver segnalato. Sto verificando e le rispondo a breve.',
            'Buongiorno, ho preso in carico il ticket. Puo\' fornirmi maggiori dettagli?',
            'Ciao, sto analizzando il problema. La aggiorno appena ho novita\'.',
        ];

        $replyTime = $ticket->created_at->copy()->addHours(rand(1, 6));

        Message::create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => $agentReplies[array_rand($agentReplies)],
            'type' => 'public',
            'created_at' => $replyTime,
            'updated_at' => $replyTime,
        ]);

        if (in_array($status, ['working', 'escalated', 'closed'])) {
            $followupCount = rand(1, 3);
            for ($i = 0; $i < $followupCount; $i++) {
                $participant = rand(0, 1) ? $author : $agent;
                $messageTime = $ticket->created_at->copy()->addHours(rand(6, 48));

                Message::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $participant->id,
                    'body' => $faker->paragraph(rand(1, 3)),
                    'type' => 'public',
                    'created_at' => $messageTime,
                    'updated_at' => $messageTime,
                ]);
            }
        }

        if (in_array($status, ['working', 'escalated'])) {
            $privateMessages = [
                'Nota interna: cliente premium, gestire con priorita\'.',
                'Nota interna: gia\' segnalato in passato, controllare storico.',
                'Nota interna: escalation al team dev in corso.',
            ];

            $messageTime = $ticket->created_at->copy()->addHours(rand(2, 24));

            Message::create([
                'ticket_id' => $ticket->id,
                'user_id' => $users['agent_l2']->id,
                'body' => $privateMessages[array_rand($privateMessages)],
                'type' => 'private',
                'created_at' => $messageTime,
                'updated_at' => $messageTime,
            ]);
        }
    }

    /**
     * Aggiunge un allegato PNG di esempio ai primi 4 ticket.
     * Genera immagini placeholder al volo, cosi' non servono file esterni.
     */
    private function createAttachments(array $tickets): void
    {
        $ticketsWithAttachments = array_slice($tickets, 0, 4);

        foreach ($ticketsWithAttachments as $i => $ticket) {
            $firstMessage = Message::where('ticket_id', $ticket->id)
                ->orderBy('created_at')
                ->first();

            if (!$firstMessage) {
                continue;
            }

            $filename = 'demo-screenshot-' . ($i + 1) . '.png';
            $path = 'attachments/' . $filename;
            $fullPath = storage_path('app/public/' . $path);

            if (!file_exists($fullPath)) {
                $dir = dirname($fullPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                $img = imagecreatetruecolor(600, 400);
                $bg = imagecolorallocate($img, 240, 240, 245);
                $accent = imagecolorallocate($img, 186, 117, 23);
                $textColor = imagecolorallocate($img, 100, 100, 100);
                $white = imagecolorallocate($img, 255, 255, 255);

                imagefill($img, 0, 0, $bg);
                imagefilledrectangle($img, 0, 0, 600, 50, $accent);
                imagestring($img, 5, 20, 20, 'Screenshot demo #' . ($i + 1), $white);
                imagestring($img, 3, 20, 100, 'Contenuto di esempio per allegato', $textColor);
                imagerectangle($img, 40, 140, 560, 360, $textColor);

                imagepng($img, $fullPath);
                imagedestroy($img);
            }

            Attachment::create([
                'message_id' => $firstMessage->id,
                'user_id' => $firstMessage->user_id,
                'filename' => $filename,
                'original_filename' => 'screenshot-problema-' . ($i + 1) . '.png',
                'path' => $path,
                'mime_type' => 'image/png',
                'size' => filesize($fullPath) ?: 12000,
                'created_at' => $firstMessage->created_at,
                'updated_at' => $firstMessage->created_at,
            ]);
        }
    }

    /**
     * Crea 3 inviti pendenti (non ancora accettati) per mostrare
     * la feature invito nell'area admin.
     */
    private function createInvitations(Company $company): void
    {
        $invitations = [
            ['email' => 'francesca.moretti@acme.it', 'role' => 'agent'],
            ['email' => 'davide.conti@acme.it', 'role' => 'user'],
            ['email' => 'chiara.rizzo@acme.it', 'role' => 'user'],
        ];

        foreach ($invitations as $inv) {
            Invitation::create([
                'company_id' => $company->id,
                'email' => $inv['email'],
                'role' => $inv['role'],
                'token' => Str::random(32),
                'expires_at' => Carbon::now()->addDays(rand(1, 7)),
                'created_at' => Carbon::now()->subDays(rand(1, 3)),
                'updated_at' => Carbon::now()->subDays(rand(1, 3)),
            ]);
        }
    }
}