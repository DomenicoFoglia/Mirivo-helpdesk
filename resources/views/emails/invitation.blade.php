<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Invito Mirivo</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f5; color: #1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color: #d97706; padding: 24px 32px; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 22px; font-weight: 600;">Mirivo</h1>
                            <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;">Visto. Risolto.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 32px;">
                            <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 600; color: #111827;">
                                Sei stato invitato in {{ $invitation->company->name }}
                            </h2>

                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6;">
                                Ciao,
                            </p>

                            <p style="margin: 0 0 16px 0; font-size: 15px; line-height: 1.6;">
                                Sei stato invitato a unirti a <strong>{{ $invitation->company->name }}</strong> su Mirivo
                                come <strong>{{ $invitation->role === 'agent' ? 'agente' : 'utente' }}</strong>.
                            </p>

                            <p style="margin: 0 0 24px 0; font-size: 15px; line-height: 1.6;">
                                Clicca sul pulsante qui sotto per completare la registrazione. Il link è valido fino al
                                <strong>{{ \Carbon\Carbon::parse($invitation->expires_at)->format('d/m/Y H:i') }}</strong>.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 0 24px 0;">
                                <tr>
                                    <td style="background-color: #d97706; border-radius: 8px;">
                                        <a href="{{ config('app.frontend_url') }}/invite/{{ $invitation->token }}"
                                           style="display: inline-block; padding: 12px 24px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 15px;">
                                            Accetta invito
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 8px 0; font-size: 13px; color: #6b7280;">
                                Se il pulsante non funziona, copia e incolla questo link nel browser:
                            </p>
                            <p style="margin: 0 0 16px 0; font-size: 13px; word-break: break-all;">
                                <a href="{{ config('app.frontend_url') }}/invite/{{ $invitation->token }}" style="color: #d97706;">
                                    {{ config('app.frontend_url') }}/invite/{{ $invitation->token }}
                                </a>
                            </p>

                            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">

                            <p style="margin: 0; font-size: 12px; color: #9ca3af; line-height: 1.5;">
                                Se non aspettavi questo invito, puoi ignorare questa email senza problemi.
                                Nessuna azione verrà intrapresa.
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin: 16px 0 0 0; font-size: 12px; color: #9ca3af;">
                    &copy; {{ date('Y') }} Mirivo
                </p>
            </td>
        </tr>
    </table>
</body>
</html>