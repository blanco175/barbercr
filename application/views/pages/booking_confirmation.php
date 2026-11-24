<?php extend('layouts/message_layout'); ?>

<?php section('content'); ?>

<div class="d-flex align-items-center justify-content-center min-vh-100-">
    <div class="text-center py-4 px-3" style="max-width: 520px; width: 100%;">
        <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mx-auto mb-4" style="width: 100px; height: 100px;">
            <i class="fas fa-calendar-check fa-3x text-success"></i>
        </div>

        <h3 class="text-success fw-semibold mb-4"><?= lang('appointment_registered') ?></h3>

        <p class="fs-5 text-muted mb-1">
            <?= lang('appointment_details_was_sent_to_you') ?>
        </p>

        <p class="text-muted small mb-4">
            <?= lang('check_spam_folder') ?>
        </p>

        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center mt-4">
            <a href="<?= site_url() ?>" class="btn btn-primary px-4 py-2">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= lang('go_to_booking_page') ?>
            </a>

            <?php if (vars('display_add_to_google_calendar') === '1'): ?>
            <a href="<?= vars('add_to_google_url') ?>" id="add-to-google-calendar" class="btn btn-outline-primary px-4 py-2" target="_blank">
                <i class="fab fa-google me-2"></i>
                <?= lang('add_to_google_calendar') ?>
            </a>
            <?php endif; ?>
        </div>

        <?php
        $sinpe_numero   = '6329-9879';
        $whatsapp_local = '50663299879';
        $monto_adelanto = '₡2.000';

        $cust = function_exists('vars') ? (vars('customer_data') ?? []) : ($customer_data ?? []);
        $prov = function_exists('vars') ? (vars('provider_data') ?? []) : ($provider_data ?? []);
        $appt = function_exists('vars') ? (vars('appointment_data') ?? []) : ($appointment_data ?? []);

        $cliente_nombre = htmlspecialchars($cust['first_name'] ?? 'Cliente', ENT_QUOTES, 'UTF-8');
        $barbero_nombre = htmlspecialchars($prov['first_name'] ?? 'Barbero', ENT_QUOTES, 'UTF-8');
        $fecha_hora     = htmlspecialchars($appt['start_datetime'] ?? '', ENT_QUOTES, 'UTF-8');

        $mensaje_wa = rawurlencode(
            "Hola *BarberCR*, soy {$cliente_nombre}. Acabo de agendar mi cita con *{$barbero_nombre}* ({$fecha_hora}). Adjunto mi comprobante SINPE de adelanto:"
        );
        ?>

        <div class="mt-4 p-4 text-center" style="background-color: #141418; border: 2px solid #D90429; border-radius: 12px;">
            <h5 style="color: #ffffff; font-weight: bold; margin-bottom: 10px;">
                Confirmación por SINPE Móvil
            </h5>
            <p style="color: #e5e7eb; font-size: 0.95rem; margin-bottom: 14px;">
                Para asegurar tu espacio en horario pico (viernes tarde y sábados), realiza un adelanto de <strong><?= $monto_adelanto ?></strong> al SINPE <strong><?= $sinpe_numero ?></strong>.
            </p>
            <a href="https://wa.me/<?= $whatsapp_local ?>?text=<?= $mensaje_wa ?>"
               target="_blank"
               class="btn btn-success w-100"
               style="font-weight: bold; padding: 12px; font-size: 1rem;">
                Enviar comprobante por WhatsApp
            </a>
        </div>
    </div>
</div>

<?php end_section('content'); ?>

<?php section('scripts'); ?>

<?php component('google_analytics_script', ['google_analytics_code' => vars('google_analytics_code')]); ?>
<?php component('matomo_analytics_script', [
    'matomo_analytics_url' => vars('matomo_analytics_url'),
    'matomo_analytics_site_id' => vars('matomo_analytics_site_id'),
]); ?>

<?php end_section('scripts'); ?>