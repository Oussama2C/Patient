<?php
session_start();
ini_set('memory_limit', '256M');

class PatientTracker {
    private $patients = [];
    private $dailyCount = [];
    
    
    public function addPatient($patientId, $dateEntree, $dateSortie) {
        $this->patients[] = [
            'id' => $patientId,
            'entree' => new DateTime($dateEntree),
            'sortie' => new DateTime($dateSortie)
        ];
    }
    
    public function calculateDailyPatients() {
        $dailyCount = [];

        if (empty($this->patients)) {
            return [];
        }

        // Y9aleb lya 3la maxdate w min date
        $minDate = clone $this->patients[0]['entree'];
        $maxDate = clone $this->patients[0]['sortie'];

        foreach ($this->patients as $patient) {
            if ($patient['entree'] < $minDate) {
                $minDate = clone $patient['entree'];
            }

            if ($patient['sortie'] > $maxDate) {
                $maxDate = clone $patient['sortie'];
            }
        }

        // Créer une période pour chaque jour
        $period = new DatePeriod(
            $minDate,
            new DateInterval('P1D'),
            $maxDate->modify('+1 day')
        );

        // Initialiser le compteur de jours
        $jourCounter = 1;

        // Pour chaque jour, compter les patients présents
        foreach ($period as $date) {
            $count = 0;
            $jourSemaine = [
                'Sunday' => 'Dimanche',
                'Monday' => 'Lundi',
                'Tuesday' => 'Mardi',
                'Wednesday' => 'Mercredi',
                'Thursday' => 'Jeudi',
                'Friday' => 'Vendredi',
                'Saturday' => 'Samedi'
            ];
            
            foreach ($this->patients as $patient) {
                if ($date >= $patient['entree'] && $date < $patient['sortie']) {
                    $count++;
                }
            }
            
            $dailyCount[] = [
                'jour_numero' => 'J' . $jourCounter ,
                'date' => $date->format('Y-m-d'),
                'jour' => $jourSemaine[$date->format('l')],
                'count' => $count
            ];
            
            $jourCounter++;
        }

        return $dailyCount;
    }

    public function getPatients() {
        return $this->patients;
    }
    
}

// Initialiser ou récupérer le tracker de la session
if (!isset($_SESSION['tracker'])) {
    $_SESSION['tracker'] = new PatientTracker();
}
$tracker = $_SESSION['tracker'];

// Traiter le formulaire si soumis
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['dateEntree']) && isset($_POST['dateSortie'])) {
        $dateEntree = $_POST['dateEntree'];
        $dateSortie = $_POST['dateSortie'];
        
        if (strtotime($dateSortie) >= strtotime($dateEntree)) {
            $tracker->addPatient(uniqid(), $dateEntree, $dateSortie);
            $message = 'Patient ajouté avec succès!';
        } else {
            $message = 'Erreur: La date de sortie doit être après la date d\'entrée!';
        }
    }
}

$dailyCount = $tracker->calculateDailyPatients();


?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Patients</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-container {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="date"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 200px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
    </style>
</head>
<body>
    <h1>Gestion des Patients</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Erreur') === 0 ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="form-container">
        <h2>Ajouter un nouveau patient</h2>
        <form method="POST">
            <div class="form-group">
                <label for="dateEntree">Date d'entrée:</label>
                <input type="date" id="dateEntree" name="dateEntree" autofocus required>
                
            </div>
            
            <div class="form-group">
                <label for="dateSortie">Date de sortie:</label>
                <input type="date" id="dateSortie" name="dateSortie" required>
            </div>
            
            <button type="submit">Ajouter le patient</button>
        </form>
    </div>
    
    <h2>Occupation journalière</h2>
    <table>
        <thead>
            <tr>
                <th>Jour</th>
                <th>Date</th>
                <th>Jour semaine</th>
                <th>Nombre de patients</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dailyCount as $day): ?>
            <tr>
                <td><?php echo htmlspecialchars($day['jour_numero']); ?></td>
                <td><?php echo htmlspecialchars($day['date']); ?></td>
                <td><?php echo htmlspecialchars($day['jour']); ?></td>
                <td><?php echo htmlspecialchars($day['count']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>