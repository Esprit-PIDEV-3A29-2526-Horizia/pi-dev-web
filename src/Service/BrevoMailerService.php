<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BrevoMailerService
{
    public function __construct(
        private HttpClientInterface $client,
        private string $brevoApiKey
    ) {
    }

    public function sendReservationConfirmation(
        string $toEmail,
        string $toName,
        string $voyageTitre,
        string $destination,
        string $dateDepart,
        string $dateRetour,
        int $nbPersonnes,
        ?int $reservationId = null,
        ?string $paymentUrl = null
    ): void {
        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
        $safeVoyage = htmlspecialchars($voyageTitre, ENT_QUOTES, 'UTF-8');
        $safeDestination = htmlspecialchars($destination, ENT_QUOTES, 'UTF-8');
        $safeDateDepart = htmlspecialchars($dateDepart, ENT_QUOTES, 'UTF-8');
        $safeDateRetour = htmlspecialchars($dateRetour, ENT_QUOTES, 'UTF-8');
        $safeNbPersonnes = htmlspecialchars((string) $nbPersonnes, ENT_QUOTES, 'UTF-8');
        $safeReservationId = $reservationId ? htmlspecialchars((string) $reservationId, ENT_QUOTES, 'UTF-8') : null;
        $safePaymentUrl = $paymentUrl ? htmlspecialchars($paymentUrl, ENT_QUOTES, 'UTF-8') : null;

        $paymentButtonHtml = $safePaymentUrl ? '
            <tr>
                <td style="padding:10px 40px 10px 40px;" align="center">
                    <a href="'.$safePaymentUrl.'" 
                       style="display:inline-block;padding:14px 28px;background:linear-gradient(135deg,#16a34a 0%, #22c55e 100%);color:#ffffff;text-decoration:none;border-radius:12px;font-size:16px;font-weight:800;box-shadow:0 10px 24px rgba(34,197,94,0.25);">
                        Payer maintenant
                    </a>
                </td>
            </tr>
        ' : '';

        $html = '
        <div style="margin:0;padding:0;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f7fb;padding:30px 0;">
                <tr>
                    <td align="center">
                        <table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,0.08);">
                            
                            <tr>
                                <td style="background:linear-gradient(135deg,#0f4c81 0%, #23779c 100%);padding:34px 40px;color:#ffffff;">
                                    <div style="font-size:28px;font-weight:800;letter-spacing:0.4px;">Horozia</div>
                                    <div style="margin-top:8px;font-size:15px;opacity:0.92;">
                                        Confirmation de votre réservation
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:36px 40px 18px 40px;">
                                    <h2 style="margin:0 0 16px 0;color:#1f2937;font-size:28px;line-height:1.2;">
                                        Bonjour '.$safeName.',
                                    </h2>

                                    <p style="margin:0 0 14px 0;color:#4b5563;font-size:16px;line-height:1.7;">
                                        Nous avons le plaisir de vous informer que votre réservation a bien été
                                        <strong style="color:#198754;">confirmée</strong>.
                                    </p>

                                    <p style="margin:0;color:#4b5563;font-size:16px;line-height:1.7;">
                                        Vous trouverez ci-dessous le récapitulatif de votre voyage.
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:18px 40px 12px 40px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;">
                                        <tr>
                                            <td style="padding:24px;">
                                                <div style="font-size:20px;font-weight:800;color:#1f2937;margin-bottom:18px;">
                                                    '.$safeVoyage.'
                                                </div>

                                                '.($safeReservationId ? '
                                                <div style="margin-bottom:14px;font-size:14px;color:#23779c;font-weight:700;">
                                                    Référence de réservation : #'.$safeReservationId.'
                                                </div>
                                                ' : '').'

                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;width:42%;">
                                                            Destination
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDestination.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;">
                                                            Date de départ
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDateDepart.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;">
                                                            Date de retour
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDateRetour.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;color:#6b7280;font-size:15px;">
                                                            Nombre de personnes
                                                        </td>
                                                        <td style="padding:10px 0;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeNbPersonnes.'
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            '.$paymentButtonHtml.'

                            <tr>
                                <td style="padding:18px 40px 8px 40px;">
                                    <div style="background:#eef6fb;border-left:4px solid #23779c;padding:16px 18px;border-radius:12px;color:#334155;font-size:14px;line-height:1.7;">
                                        Merci pour votre confiance. Nous vous souhaitons un excellent voyage avec Horozia.
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:24px 40px 36px 40px;">
                                    <p style="margin:0 0 8px 0;color:#4b5563;font-size:15px;">
                                        Cordialement,
                                    </p>
                                    <p style="margin:0;color:#1f2937;font-size:16px;font-weight:800;">
                                        Horozia Travel
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="background:#f8fafc;padding:18px 40px;color:#94a3b8;font-size:12px;text-align:center;border-top:1px solid #e5e7eb;">
                                    Cet email a été envoyé automatiquement par Horozia.
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </div>';

        $this->sendEmail(
            $toEmail,
            $toName,
            'Confirmation de votre réservation - Horozia',
            $html
        );
    }

    public function sendReservationCancellation(
        string $toEmail,
        string $toName,
        string $voyageTitre,
        string $destination,
        string $dateDepart,
        string $dateRetour,
        int $nbPersonnes,
        ?int $reservationId = null
    ): void {
        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
        $safeVoyage = htmlspecialchars($voyageTitre, ENT_QUOTES, 'UTF-8');
        $safeDestination = htmlspecialchars($destination, ENT_QUOTES, 'UTF-8');
        $safeDateDepart = htmlspecialchars($dateDepart, ENT_QUOTES, 'UTF-8');
        $safeDateRetour = htmlspecialchars($dateRetour, ENT_QUOTES, 'UTF-8');
        $safeNbPersonnes = htmlspecialchars((string) $nbPersonnes, ENT_QUOTES, 'UTF-8');
        $safeReservationId = $reservationId ? htmlspecialchars((string) $reservationId, ENT_QUOTES, 'UTF-8') : null;

        $html = '
        <div style="margin:0;padding:0;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f7fb;padding:30px 0;">
                <tr>
                    <td align="center">
                        <table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,0.08);">
                            
                            <tr>
                                <td style="background:linear-gradient(135deg,#7f1d1d 0%, #dc2626 100%);padding:34px 40px;color:#ffffff;">
                                    <div style="font-size:28px;font-weight:800;letter-spacing:0.4px;">Horozia</div>
                                    <div style="margin-top:8px;font-size:15px;opacity:0.92;">
                                        Annulation de votre réservation
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:36px 40px 18px 40px;">
                                    <h2 style="margin:0 0 16px 0;color:#1f2937;font-size:28px;line-height:1.2;">
                                        Bonjour '.$safeName.',
                                    </h2>

                                    <p style="margin:0 0 14px 0;color:#4b5563;font-size:16px;line-height:1.7;">
                                        Nous vous informons que votre réservation a été
                                        <strong style="color:#dc2626;">annulée</strong>.
                                    </p>

                                    <p style="margin:0;color:#4b5563;font-size:16px;line-height:1.7;">
                                        Voici le récapitulatif de la réservation concernée.
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:18px 40px 12px 40px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;">
                                        <tr>
                                            <td style="padding:24px;">
                                                <div style="font-size:20px;font-weight:800;color:#1f2937;margin-bottom:18px;">
                                                    '.$safeVoyage.'
                                                </div>

                                                '.($safeReservationId ? '
                                                <div style="margin-bottom:14px;font-size:14px;color:#dc2626;font-weight:700;">
                                                    Référence de réservation : #'.$safeReservationId.'
                                                </div>
                                                ' : '').'

                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;width:42%;">
                                                            Destination
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDestination.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;">
                                                            Date de départ
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDateDepart.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;">
                                                            Date de retour
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDateRetour.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;color:#6b7280;font-size:15px;">
                                                            Nombre de personnes
                                                        </td>
                                                        <td style="padding:10px 0;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeNbPersonnes.'
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:18px 40px 8px 40px;">
                                    <div style="background:#fef2f2;border-left:4px solid #dc2626;padding:16px 18px;border-radius:12px;color:#7f1d1d;font-size:14px;line-height:1.7;">
                                        Pour toute question, merci de contacter l’équipe Horozia.
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:24px 40px 36px 40px;">
                                    <p style="margin:0 0 8px 0;color:#4b5563;font-size:15px;">
                                        Cordialement,
                                    </p>
                                    <p style="margin:0;color:#1f2937;font-size:16px;font-weight:800;">
                                        Horozia Travel
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="background:#f8fafc;padding:18px 40px;color:#94a3b8;font-size:12px;text-align:center;border-top:1px solid #e5e7eb;">
                                    Cet email a été envoyé automatiquement par Horozia.
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </div>';

        $this->sendEmail(
            $toEmail,
            $toName,
            'Annulation de votre réservation - Horozia',
            $html
        );
    }

    public function sendReservationPending(
        string $toEmail,
        string $toName,
        string $voyageTitre,
        string $destination,
        string $dateDepart,
        string $dateRetour,
        int $nbPersonnes,
        ?int $reservationId = null
    ): void {
        $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
        $safeVoyage = htmlspecialchars($voyageTitre, ENT_QUOTES, 'UTF-8');
        $safeDestination = htmlspecialchars($destination, ENT_QUOTES, 'UTF-8');
        $safeDateDepart = htmlspecialchars($dateDepart, ENT_QUOTES, 'UTF-8');
        $safeDateRetour = htmlspecialchars($dateRetour, ENT_QUOTES, 'UTF-8');
        $safeNbPersonnes = htmlspecialchars((string) $nbPersonnes, ENT_QUOTES, 'UTF-8');
        $safeReservationId = $reservationId ? htmlspecialchars((string) $reservationId, ENT_QUOTES, 'UTF-8') : null;

        $html = '
        <div style="margin:0;padding:0;background-color:#f4f7fb;font-family:Arial,Helvetica,sans-serif;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f7fb;padding:30px 0;">
                <tr>
                    <td align="center">
                        <table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 12px 35px rgba(15,23,42,0.08);">
                            
                            <tr>
                                <td style="background:linear-gradient(135deg,#92400e 0%, #f59e0b 100%);padding:34px 40px;color:#ffffff;">
                                    <div style="font-size:28px;font-weight:800;letter-spacing:0.4px;">Horozia</div>
                                    <div style="margin-top:8px;font-size:15px;opacity:0.92;">
                                        Votre réservation est en attente
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:36px 40px 18px 40px;">
                                    <h2 style="margin:0 0 16px 0;color:#1f2937;font-size:28px;line-height:1.2;">
                                        Bonjour '.$safeName.',
                                    </h2>

                                    <p style="margin:0 0 14px 0;color:#4b5563;font-size:16px;line-height:1.7;">
                                        Votre réservation a bien été enregistrée et elle est actuellement
                                        <strong style="color:#d97706;">en attente de confirmation</strong>.
                                    </p>

                                    <p style="margin:0;color:#4b5563;font-size:16px;line-height:1.7;">
                                        Nous vous informerons dès qu’elle sera validée.
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:18px 40px 12px 40px;">
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;">
                                        <tr>
                                            <td style="padding:24px;">
                                                <div style="font-size:20px;font-weight:800;color:#1f2937;margin-bottom:18px;">
                                                    '.$safeVoyage.'
                                                </div>

                                                '.($safeReservationId ? '
                                                <div style="margin-bottom:14px;font-size:14px;color:#d97706;font-weight:700;">
                                                    Référence de réservation : #'.$safeReservationId.'
                                                </div>
                                                ' : '').'

                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;width:42%;">
                                                            Destination
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDestination.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;">
                                                            Date de départ
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDateDepart.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:15px;">
                                                            Date de retour
                                                        </td>
                                                        <td style="padding:10px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeDateRetour.'
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:10px 0;color:#6b7280;font-size:15px;">
                                                            Nombre de personnes
                                                        </td>
                                                        <td style="padding:10px 0;color:#111827;font-size:15px;font-weight:700;">
                                                            '.$safeNbPersonnes.'
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:18px 40px 8px 40px;">
                                    <div style="background:#fffbeb;border-left:4px solid #f59e0b;padding:16px 18px;border-radius:12px;color:#92400e;font-size:14px;line-height:1.7;">
                                        Statut actuel : <strong>EN ATTENTE</strong>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding:24px 40px 36px 40px;">
                                    <p style="margin:0 0 8px 0;color:#4b5563;font-size:15px;">
                                        Cordialement,
                                    </p>
                                    <p style="margin:0;color:#1f2937;font-size:16px;font-weight:800;">
                                        Horozia Travel
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="background:#f8fafc;padding:18px 40px;color:#94a3b8;font-size:12px;text-align:center;border-top:1px solid #e5e7eb;">
                                    Cet email a été envoyé automatiquement par Horozia.
                                </td>
                            </tr>

                        </table>
                    </td>
                </tr>
            </table>
        </div>';

        $this->sendEmail(
            $toEmail,
            $toName,
            'Votre réservation est en attente - Horozia',
            $html
        );
    }

    private function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $html
    ): void {
        $response = $this->client->request('POST', 'https://api.brevo.com/v3/smtp/email', [
            'headers' => [
                'accept' => 'application/json',
                'api-key' => $this->brevoApiKey,
                'content-type' => 'application/json',
            ],
            'json' => [
                'sender' => [
                    'name' => 'Horozia Travel',
                    'email' => 'ibtissemsghaier12@gmail.com',
                ],
                'to' => [[
                    'email' => $toEmail,
                    'name' => $toName !== '' ? $toName : $toEmail,
                ]],
                'subject' => $subject,
                'htmlContent' => $html,
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $data['message'] ?? 'Erreur inconnue Brevo.';
            throw new \RuntimeException($message);
        }
    }
}