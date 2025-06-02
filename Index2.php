<?php
session_start();

$host = 'localhost';  
$dbname = 'moja_baza_danych';  
$username = 'root';  
$password = '';  
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Błąd połączenia: " . $e->getMessage());
}

$sql = "SELECT id, imie, nazwisko, rezerwacja, data_rezerwacji FROM goscie";
$stmt = $pdo->query($sql);
$goscie = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rezerwuj_id'])) {
    $rezerwuj_id = (int)$_POST['rezerwuj_id'];
    $data_rezerwacji = $_POST['data_rezerwacji'];

    if (strtotime($data_rezerwacji) <= time()) {
        $_SESSION['error'] = 'Data rezerwacji musi być w przyszłości.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $sql = "UPDATE goscie SET rezerwacja = 1, data_rezerwacji = :data_rezerwacji WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $rezerwuj_id,
        ':data_rezerwacji' => $data_rezerwacji
    ]);

    $_SESSION['success'] = 'Rezerwacja została pomyślnie dokonana!';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj_goscia'])) {
    $imie = trim($_POST['imie']);
    $nazwisko = trim($_POST['nazwisko']);
    $rezerwacja = isset($_POST['rezerwacja']) ? 1 : 0;
    $data_rezerwacji = $_POST['data_rezerwacji'] ?? null;

    if ($rezerwacja && strtotime($data_rezerwacji) <= time()) {
        $_SESSION['error'] = 'Data rezerwacji musi być w przyszłości.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $sql = "INSERT INTO goscie (imie, nazwisko, rezerwacja, data_rezerwacji)
            VALUES (:imie, :nazwisko, :rezerwacja, :data_rezerwacji)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':imie' => $imie,
        ':nazwisko' => $nazwisko,
        ':rezerwacja' => $rezerwacja,
        ':data_rezerwacji' => $rezerwacja ? $data_rezerwacji : null
    ]);

    $_SESSION['success'] = 'Gość został dodany!';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usun_id'])) {
    $usun_id = (int)$_POST['usun_id'];

    $sql = "DELETE FROM goscie WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $usun_id]);

    $_SESSION['success'] = 'Gość został usunięty.';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usun_id'])) {
    $usun_id = (int)$_POST['usun_id'];

    $sql = "DELETE FROM goscie WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $usun_id]);

    $_SESSION['success'] = 'Gość został usunięty.';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Rezerwacja Wizyty</title>
    <link rel="stylesheet" href="style1.css">
    <script>
        function FormularzRezerwacji() {
            const checkbox = document.querySelector('input[name="rezerwacja"]');
            const dataInput = document.querySelector('input[name="data_rezerwacji"]');

            if (checkbox.checked) {
                const selectedDate = new Date(dataInput.value);
                const now = new Date();

                if (selectedDate <= now) {
                    alert("Data rezerwacji musi być w przyszłości.");
                    return false;
                }
            }
            return true;
        }

        function SprawdzPoleRezerwacji() {
            const checkbox = document.querySelector('input[name="rezerwacja"]');
            const dataField = document.querySelector('input[name="data_rezerwacji"]');

            dataField.disabled = !checkbox.checked;
            dataField.required = checkbox.checked;
        }

        window.onload = function () {
            SprawdzPoleRezerwacji();
            document.querySelector('input[name="rezerwacja"]').addEventListener('change', SprawdzPoleRezerwacji);
        };
        document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('.guest-table tbody tr');
    rows.forEach(row => {
        const imie = row.cells[0].textContent.toLowerCase();
        const nazwisko = row.cells[1].textContent.toLowerCase();
        if (imie.includes(filter) || nazwisko.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
function ustawMinimalnaDate() {
    const now = new Date();
    const year = now.getFullYear();
    const month = (now.getMonth() + 1).toString().padStart(2, '0');
    const day = now.getDate().toString().padStart(2, '0');
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const minDate = `${year}-${month}-${day}T${hours}:${minutes}`;

    document.querySelectorAll('input[type="datetime-local"]').forEach(input => {
        input.min = minDate;
    });
}

window.addEventListener('load', ustawMinimalnaDate);
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (form.querySelector('input[name="rezerwuj_id"]')) { 
            const dateInput = form.querySelector('input[name="data_rezerwacji"]');
            if (dateInput && dateInput.value) {
                if (!confirm(`Czy na pewno chcesz zarezerwować wizytę na ${dateInput.value.replace('T', ' ')}?`)) {
                    e.preventDefault();
                }
            }
        }
    });
});

    </script>
</head>
<body>
<div class="container">
    <h1>Lista Gości</h1>
    <?php if (isset($_SESSION['success'])): ?>
        <p class="message success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <p class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>
<label for="searchInput">Szukaj gościa:</label>
<input type="text" id="searchInput" placeholder="Wpisz imię lub nazwisko...">
    <table class="guest-table">
    <thead>
    <tr>
        <th>Imię</th>
        <th>Nazwisko</th>
        <th>Rezerwacja</th>
        <th>Rezerwuj</th>
        <th>Usuń</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($goscie as $gosc): ?>
        <tr class="<?php echo $gosc['rezerwacja'] ? 'reserved' : ''; ?>">
            <td><?php echo htmlspecialchars($gosc['imie']); ?></td>
            <td><?php echo htmlspecialchars($gosc['nazwisko']); ?></td>
            <td>
                <?php echo $gosc['rezerwacja'] ? date('d-m-Y H:i', strtotime($gosc['data_rezerwacji'])) : 'Brak rezerwacji'; ?>
            </td>
            <td>
                <?php if (!$gosc['rezerwacja']): ?>
                    <form method="POST" style="display:flex; flex-direction: column; gap: 5px;">
                        <input type="hidden" name="rezerwuj_id" value="<?php echo $gosc['id']; ?>">
                        <label for="data_rezerwacji_<?php echo $gosc['id']; ?>">Data rezerwacji:</label>
                        <input type="datetime-local" name="data_rezerwacji" id="data_rezerwacji_<?php echo $gosc['id']; ?>" required>
                        <button type="submit" class="reserve-btn">Zarezerwuj wizytę</button>
                    </form>
                <?php else: ?>
                    <button class="reserved-btn" disabled>Wizyta zarezerwowana</button>
                <?php endif; ?>
            </td>
            <td>
                <form method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć tego gościa?');">
                    <input type="hidden" name="usun_id" value="<?php echo $gosc['id']; ?>">
                    <button type="submit" class="delete-btn">Usuń</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div id="summary" style="margin-bottom: 20px; font-weight: bold;"></div>

    <h2>Dodaj Nowego Gościa</h2>
    <form method="POST" onsubmit="return FormularzRezerwacji()">
        <label for="imie">Imię:</label>
        <input type="text" name="imie" required><br><br>

        <label for="nazwisko">Nazwisko:</label>
        <input type="text" name="nazwisko" required><br><br>

        <label>
            <input type="checkbox" name="rezerwacja"> Rezerwacja?
        </label><br><br>

        <label for="data_rezerwacji">Data rezerwacji:</label>
        <input type="datetime-local" name="data_rezerwacji"><br><br>

        <button type="submit" name="dodaj_goscia">Dodaj Gościa</button>
    </form>
</div>

<script>
    function updateSummary() {
  const rows = document.querySelectorAll('.guest-table tbody tr');
  let totalGuests = 0;
  let reservedGuests = 0;

  rows.forEach(row => {
    if (row.style.display !== 'none') {
      totalGuests++;
      if (row.classList.contains('reserved')) {
        reservedGuests++;
      }
    }
  });

  const nonReservedGuests = totalGuests - reservedGuests;
  document.getElementById('summary').innerHTML =
    `Liczba gości: ${totalGuests} | ` +
    `Z rezerwacją: ${reservedGuests} | ` +
    `Bez rezerwacji: ${nonReservedGuests}`;
}
updateSummary();
document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('.guest-table tbody tr');

    rows.forEach(row => {
        const imie = row.cells[0].textContent.toLowerCase();
        const nazwisko = row.cells[1].textContent.toLowerCase();

        if (imie.includes(filter) || nazwisko.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
