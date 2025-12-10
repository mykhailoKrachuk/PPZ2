<?php
declare(strict_types=1);
global $db;

require __DIR__ . '/../backend/require_auth.php';
requireRole('worker');                 // только сотрудник
require __DIR__ . '/../backend/config.php';

function s(string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function g(string $k): string { return trim((string)($_GET[$k] ?? '')); }

$ok    = g('ok');
$err   = g('err');
$numOk = g('num');

$search = g('q');
$found  = null;
if ($search !== '') {
    $st = $db->prepare("SELECT * FROM parcel WHERE parcel_number = :n LIMIT 1");
    $st->execute([':n'=>$search]);
    $found = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Списки
$limit = 50;
$active = $db->query("
    SELECT parcel_number, status, created_at, sent_at, received_at, issued_at,
           sender_name, sender_address, phone, user_id, size, weight, price, description
    FROM parcel
    WHERE status <> 'delivered'
    ORDER BY created_at DESC
    LIMIT {$limit}
")->fetchAll(PDO::FETCH_ASSOC);

$delivered = $db->query("
    SELECT parcel_number, issued_at, sender_name, phone, user_id
    FROM parcel
    WHERE status = 'delivered'
    ORDER BY issued_at DESC NULLS LAST, created_at DESC
    LIMIT {$limit}
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pracownik · Przesyłki</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .toolbar{display:flex;gap:.5rem;align-items:center;justify-content:space-between;margin-bottom:12px}
        .tabs{display:flex;gap:8px;margin:12px 0}
        .tab{padding:8px 12px;border-radius:10px;background:#f1f5f9;cursor:pointer}
        .tab.active{background:#dbeafe}
        .hidden{display:none}
        .table{width:100%;border-collapse:collapse}
        .table th,.table td{padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:14px}
        .badge{padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff}
        .notice{padding:10px 12px;border-radius:12px}
        .notice.success{background:#ecfdf5;color:#065f46}
        .notice.error{background:#fef2f2;color:#991b1b}
        /* modal */
        .modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;padding:20px}
        .modal{background:#fff;border-radius:16px;max-width:900px;width:100%;padding:18px}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        @media (max-width:800px){.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="page">
    <header class="topbar right">
        <nav><a class="link-btn" href="index.html">Wróć</a></nav>
    </header>

    <main class="panel">
        <h1 class="panel-title">Lista przesyłek</h1>

        <?php if ($ok): ?>
            <div class="notice success">
                <?php if ($ok==='created'): ?>
                    Utworzono paczkę. Numer śledzenia: <b><?= s($numOk) ?></b>.
                <?php elseif ($ok==='delivered'): ?>
                    Paczka <b><?= s($numOk) ?></b> została wydana (delivered).
                <?php else: ?>
                    Operacja zakończona pomyślnie.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($err): ?>
            <div class="notice error">
                <?php
                $map = [
                    'fill' => 'Wypełnij wszystkie pola paczki.',
                    'user' => 'Podaj e-mail lub telefon klienta.',
                    'w'    => 'Waga musi być liczbą.',
                    'pr'   => 'Cena musi być liczbą.',
                    'nouser' => 'Nie znaleziono klienta o podanym e-mailu lub telefonie.',
                    'nonum'  => 'Brak numeru przesyłki.',
                    'notallowed' => 'Nie można wydać tej paczki (już wydana lub anulowana).',
                    'badmethod'  => 'Nieprawidłowa metoda.',
                    'server'     => 'Błąd serwera. Spróbuj ponownie.',
                ];
                $chunks = explode(',', $err);
                foreach ($chunks as $code) {
                    echo '<div>'. s($map[$code] ?? ('Błąd: '.$code)) .'</div>';
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="toolbar">
            <form method="get" class="form-narrow" style="display:flex;gap:8px;">
                <input class="input" type="text" name="q" placeholder="Numer śledzenia" value="<?= s($search) ?>">
                <button class="btn-primary" type="submit">Szukaj</button>
            </form>
            <button id="btnNew" class="btn-primary" type="button">Nowa paczka</button>
        </div>

        <?php if ($search !== ''): ?>
            <div class="panel" style="padding:12px;margin-bottom:12px;">
                <h3>Wynik wyszukiwania</h3>
                <?php if ($found): ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:8px 0;">
                        <div><b>Numer:</b> <?= s($found['parcel_number']) ?></div>
                        <div><b>Status:</b> <span class="badge"><?= s($found['status']) ?></span></div>
                        <div><b>Klient (user_id):</b> <?= s((string)$found['user_id']) ?></div>
                        <div><b>Telefon:</b> <?= s($found['phone']) ?></div>
                        <div><b>Nadawca:</b> <?= s($found['sender_name']) ?></div>
                        <div><b>Adres nadawcy:</b> <?= s($found['sender_address']) ?></div>
                        <div><b>Opis:</b> <?= s($found['description']) ?></div>
                        <div><b>Wymiary:</b> <?= s($found['size']) ?>; <b>Waga:</b> <?= s($found['weight']) ?>; <b>Cena:</b> <?= s($found['price']) ?></div>
                    </div>

                    <?php if ($found['status'] !== 'delivered' && $found['status'] !== 'canceled'): ?>
                        <form method="post" action="/backend/parcel_deliver.php" style="margin-top:8px;">
                            <input type="hidden" name="parcel_number" value="<?= s($found['parcel_number']) ?>">
                            <button class="btn-secondary" type="submit">Wydaj paczkę (oznacz jako delivered)</button>
                        </form>
                    <?php else: ?>
                        <div class="notice" style="background:#f8fafc">Ta paczka jest już <?= s($found['status']) ?>.</div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="notice">Brak paczki o numerze: <b><?= s($search) ?></b>.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <div class="tab active" data-tab="active">Aktywne</div>
            <div class="tab" data-tab="delivered">Wydane</div>
        </div>

        <!-- Akтивные -->
        <section id="tab-active">
            <table class="table">
                <thead>
                <tr>
                    <th>Numer</th><th>Status</th><th>Utworzono</th><th>Klient</th><th>Telefon</th><th>Opis</th><th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($active as $r): ?>
                    <tr>
                        <td><?= s($r['parcel_number']) ?></td>
                        <td><span class="badge"><?= s($r['status']) ?></span></td>
                        <td><?= s((string)$r['created_at']) ?></td>
                        <td><?= s((string)$r['user_id']) ?></td>
                        <td><?= s($r['phone']) ?></td>
                        <td><?= s($r['description']) ?></td>
                        <td>
                            <?php if ($r['status'] !== 'delivered' && $r['status'] !== 'canceled'): ?>
                                <form method="post" action="/backend/parcel_deliver.php" style="display:inline">
                                    <input type="hidden" name="parcel_number" value="<?= s($r['parcel_number']) ?>">
                                    <button class="btn-primary" type="submit">Wydaj</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Выданные -->
        <section id="tab-delivered" class="hidden">
            <table class="table">
                <thead><tr><th>Numer</th><th>Wydano</th><th>Klient</th><th>Telefon</th></tr></thead>
                <tbody>
                <?php foreach ($delivered as $r): ?>
                    <tr>
                        <td><?= s($r['parcel_number']) ?></td>
                        <td><?= s((string)$r['issued_at']) ?></td>
                        <td><?= s((string)$r['user_id']) ?></td>
                        <td><?= s($r['phone']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

    </main>
</div>

<!-- Модалка создания -->
<div id="modal" class="modal-backdrop">
    <div class="modal">
        <h3 style="margin-bottom:10px;">Nowa paczka</h3>
        <form method="post" action="/backend/parcel_create.php" class="grid" autocomplete="off">
            <h4 style="grid-column:1/-1;margin:0;">Klient</h4>
            <input class="input" type="email" name="user_mail"  placeholder="E-mail klienta (opcjonalnie)">
            <input class="input" type="tel"   name="user_phone" placeholder="Telefon klienta (opcjonalnie)">

            <h4 style="grid-column:1/-1;margin:0;">Dane paczki</h4>
            <input class="input" type="text" name="sender_name"    placeholder="Nadawca" required>
            <input class="input" type="text" name="sender_address" placeholder="Adres nadawcy" required>
            <input class="input" type="tel"  name="phone"          placeholder="Telefon kontaktowy" required>
            <input class="input" type="text" name="description"    placeholder="Opis" required>
            <input class="input" type="text" name="size"           placeholder="Rozmiar (np. S/M/L)" required>
            <input class="input" type="text" name="weight"         placeholder="Waga (kg)" required>
            <input class="input" type="text" name="price"          placeholder="Cena (PLN)" required>

            <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end;margin-top:6px;">
                <button type="button" id="btnCancel" class="btn-secondary">Anuluj</button>
                <button type="submit" class="btn-primary">Utwórz</button>
            </div>
        </form>
    </div>
</div>

<script>
    const tabs = document.querySelectorAll('.tab');
    const sections = {
        active: document.getElementById('tab-active'),
        delivered: document.getElementById('tab-delivered')
    };
    tabs.forEach(t => t.addEventListener('click', () => {
        tabs.forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        const key = t.dataset.tab;
        sections.active.classList.toggle('hidden', key!=='active');
        sections.delivered.classList.toggle('hidden', key!=='delivered');
    }));

    const modal = document.getElementById('modal');
    const btnNew = document.getElementById('btnNew');
    const btnCancel = document.getElementById('btnCancel');
    btnNew.addEventListener('click', () => modal.style.display='flex');
    btnCancel.addEventListener('click', () => modal.style.display='none');
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display='none'; });
</script>
</body>
</html>
