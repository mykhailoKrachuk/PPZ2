<?php
declare(strict_types=1);
global $db;

require __DIR__ . '/../backend/require_auth.php';
requireRole('deliver');                 // только курьер
require __DIR__ . '/../backend/config.php';

function s(string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function g(string $k): string { return trim((string)($_GET[$k] ?? '')); }

$ok  = g('ok');
$err = g('err');
$num = g('num');
$to  = g('to');

$search = g('q');
$found = null;
if ($search !== '') {
    $st = $db->prepare("SELECT * FROM parcel WHERE parcel_number=:n LIMIT 1");
    $st->execute([':n'=>$search]);
    $found = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

// активные для списка
$active = $db->query("
  SELECT parcel_number, status, created_at, sent_at, received_at,
         sender_name, phone, user_id, description
  FROM parcel
  WHERE status NOT IN ('delivered','canceled')
  ORDER BY created_at DESC
  LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dostawy · Zmiana statusu</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .notice{padding:10px 12px;border-radius:12px;margin:8px 0}
        .success{background:#ecfdf5;color:#065f46}
        .error{background:#fef2f2;color:#991b1b}
        .table{width:100%;border-collapse:collapse}
        .table th,.table td{padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:14px}
        .badge{padding:2px 8px;border-radius:999px;font-size:12px;background:#eef2ff}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        @media (max-width:800px){.row{grid-template-columns:1fr}}
        .btn-row{display:flex;gap:8px;flex-wrap:wrap}
    </style>
</head>
<body>
<div class="page">
    <header class="topbar right">
        <nav><a class="btn-primary" href="index.html">Wróć</a></nav>
    </header>

    <main class="panel">
        <h1 class="panel-title">Panel dostaw</h1>

        <?php if ($ok==='updated'): ?>
            <div class="notice success">
                Zmieniono status przesyłki <b><?= s($num) ?></b> na <b><?= s($to) ?></b>.
            </div>
        <?php endif; ?>

        <?php if ($err): ?>
            <div class="notice error">
                <?php
                $map = [
                    'empty'=>'Uzupełnij numer i status.',
                    'badmethod'=>'Nieprawidłowa metoda.',
                    'notfound'=>'Nie znaleziono przesyłki.',
                    'illegal'=>'Niedozwolona zmiana statusu.',
                    'server'=>'Błąd serwera.',
                ];
                echo s($map[$err] ?? ('Błąd: '.$err));
                if ($err==='illegal') {
                    echo ' ('.s(g('cur').'→'.g('next')).')';
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Поиск -->
        <form method="get" class="form-narrow centered" style="display:flex;gap:8px;justify-content:flex-start">
            <input class="input" type="text" name="q" placeholder="Numer śledzenia" value="<?= s($search) ?>">
            <button class="btn-primary" type="submit">Szukaj</button>
        </form>

        <?php
        // кнопки в зависимости от текущего статуса
        function statusButtons(array $p): string {
            $cur = $p['status'];
            $n = s($p['parcel_number']);
            $mk = function(string $label, string $next) use ($n): string {
                return '<form style="display:inline" method="post" action="/backend/parcel_update_status.php">'
                    . '<input type="hidden" name="parcel_number" value="'.$n.'">'
                    . '<input type="hidden" name="next_status" value="'.s($next).'">'
                    . '<button class="btn-primary" type="submit">'.$label.'</button>'
                    . '</form>';
            };
            $out = '';
            if ($cur==='created')     $out = $mk('Oznacz jako Wysłana','sent') . ' ' . $mk('Anuluj','canceled');
            if ($cur==='sent')        $out = $mk('W trasie','in_transit') . ' ' . $mk('Anuluj','canceled');
            if ($cur==='in_transit')  $out = $mk('Dostarczona do punktu','received') . ' ' . $mk('Anuluj','canceled');
            // received — дальше выдаёт pracownik (worker -> delivered)
            return $out ?: '<span class="badge">'.s($cur).'</span>';
        }
        ?>

        <!-- Результат поиска -->
        <?php if ($search !== ''): ?>
            <div class="panel" style="margin-top:12px">
                <h3>Wynik</h3>
                <?php if ($found): ?>
                    <div class="row" style="margin:8px 0">
                        <div><b>Numer:</b> <?= s($found['parcel_number']) ?></div>
                        <div><b>Status:</b> <span class="badge"><?= s($found['status']) ?></span></div>
                        <div><b>Klient (user_id):</b> <?= s((string)$found['user_id']) ?></div>
                        <div><b>Telefon:</b> <?= s($found['phone']) ?></div>
                        <div><b>Nadawca:</b> <?= s($found['sender_name']) ?></div>
                        <div><b>Adres:</b> <?= s($found['sender_address']) ?></div>
                        <div style="grid-column:1/-1"><b>Opis:</b> <?= s($found['description']) ?></div>
                    </div>
                    <div class="btn-row"><?= statusButtons($found) ?></div>
                <?php else: ?>
                    Brak przesyłki o numerze <b><?= s($search) ?></b>.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Список активных -->
        <h3 style="margin-top:16px">Aktywne przesyłki</h3>
        <table class="table">
            <thead><tr>
                <th>Numer</th><th>Status</th><th>Utworzono</th><th>Telefon</th><th>Opis</th><th>Akcje</th>
            </tr></thead>
            <tbody>
            <?php foreach ($active as $p): ?>
                <tr>
                    <td><?= s($p['parcel_number']) ?></td>
                    <td><span class="badge"><?= s($p['status']) ?></span></td>
                    <td><?= s((string)$p['created_at']) ?></td>
                    <td><?= s($p['phone']) ?></td>
                    <td><?= s($p['description']) ?></td>
                    <td class="btn-row"><?= statusButtons($p) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="opacity:.7;margin-top:6px">Uwaga: po statusie <b>received</b> paczkę wydaje pracownik w panelu
            <a class="btn-primary" href="worker.php">Pracownik</a>, co oznacza <b>delivered</b>.</p>
    </main>
</div>
</body>
</html>
