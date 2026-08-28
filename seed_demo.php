<?php
if (PHP_SAPI !== "cli") {
    http_response_code(403);
    exit("Ce script doit etre execute depuis la ligne de commande.\n");
}

require "config.php";

function getOrCreateId(mysqli $conn, string $table, string $idColumn, string $name): int
{
    $stmt = $conn->prepare("SELECT {$idColumn} FROM {$table} WHERE nom = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if ($existing) {
        return (int) $existing[$idColumn];
    }
    $stmt = $conn->prepare("INSERT INTO {$table} (nom) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    return $conn->insert_id;
}

function getOrCreateFacture(mysqli $conn, string $type, string $periode, float $montant): int
{
    $stmt = $conn->prepare("SELECT id_facture FROM facture WHERE type = ? AND periode = ? LIMIT 1");
    $stmt->bind_param("ss", $type, $periode);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if ($existing) {
        return (int) $existing["id_facture"];
    }
    $echeance = date("Y-m-d", strtotime($periode . " +30 days"));
    $stmt = $conn->prepare("INSERT INTO facture (type, periode, montant, date_echeance) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssds", $type, $periode, $montant, $echeance);
    $stmt->execute();
    return $conn->insert_id;
}

function addConsumption(mysqli $conn, int $idCompteur, int $idFacture, float $consommation): void
{
    $stmt = $conn->prepare("SELECT 1 FROM concerne WHERE id_compteur = ? AND id_facture = ? LIMIT 1");
    $stmt->bind_param("ii", $idCompteur, $idFacture);
    $stmt->execute();
    if ($stmt->get_result()->fetch_row()) {
        return;
    }
    $stmt = $conn->prepare("INSERT INTO concerne (id_compteur, id_facture, consommation) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $idCompteur, $idFacture, $consommation);
    $stmt->execute();
}

$conn->begin_transaction();
try {
    $groupId = getOrCreateId($conn, "groupe", "id_groupe", "Utilisateur demo");
    $stmt = $conn->prepare("UPDATE groupe SET droits = ? WHERE id_groupe = ?");
    $rights = "dashboard,regions,locaux,compteurs,factures,etats";
    $stmt->bind_param("si", $rights, $groupId);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT id_utilisateur FROM utilisateur WHERE login = ? LIMIT 1");
    $login = "demo@etap.local";
    $stmt->bind_param("s", $login);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO utilisateur (nom, prenom, login, mot_de_passe, id_groupe) VALUES (?, ?, ?, ?, ?)");
        $lastName = "Demo";
        $firstName = "ETAP";
        $password = "demo123";
        $stmt->bind_param("ssssi", $lastName, $firstName, $login, $password, $groupId);
        $stmt->execute();
    }

    $regions = [];
    foreach (["Tunis", "Sfax", "Sousse", "Gabes"] as $name) {
        $regions[$name] = getOrCreateId($conn, "region", "id_region", $name);
    }
    $familles = [];
    foreach (["Administration", "Production", "Logistique"] as $name) {
        $familles[$name] = getOrCreateId($conn, "famille", "id_famille", $name);
    }
    $sousFamilles = [];
    foreach (["Bureaux", "Ateliers", "Entrepots"] as $name) {
        $sousFamilles[$name] = getOrCreateId($conn, "sous_famille", "id_sous_famille", $name);
    }

    $localIds = [];
    $localDefinitions = [
        ["Siege Tunis", "1 avenue de la Republique", "Tunis", "Administration", "Bureaux"],
        ["Centre Tunis", "12 rue du Lac", "Tunis", "Production", "Ateliers"],
        ["Base Sfax", "8 route de l'Aeroport", "Sfax", "Production", "Ateliers"],
        ["Depot Sfax", "4 zone industrielle", "Sfax", "Logistique", "Entrepots"],
        ["Agence Sousse", "22 avenue Hedi Chaker", "Sousse", "Administration", "Bureaux"],
        ["Atelier Sousse", "3 zone industrielle", "Sousse", "Production", "Ateliers"],
        ["Base Gabes", "15 route de Medenine", "Gabes", "Production", "Ateliers"],
        ["Depot Gabes", "6 zone portuaire", "Gabes", "Logistique", "Entrepots"]
    ];
    foreach ($localDefinitions as [$name, $address, $region, $famille, $sousFamille]) {
        $stmt = $conn->prepare("SELECT id_local FROM local WHERE nom = ? LIMIT 1");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            $localIds[$name] = (int) $existing["id_local"];
            continue;
        }
        $stmt = $conn->prepare("INSERT INTO local (nom, adresse, id_region, id_famille, id_sous_famille) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiii", $name, $address, $regions[$region], $familles[$famille], $sousFamilles[$sousFamille]);
        $stmt->execute();
        $localIds[$name] = $conn->insert_id;
    }

    $meters = [];
    $meterDefinitions = [
        ["ELEC-TUN-001", "electricite", "haute", 820, "Siege Tunis"],
        ["ELEC-TUN-002", "electricite", "basse", 560, "Centre Tunis"],
        ["ELEC-SFX-001", "electricite", "haute", 910, "Base Sfax"],
        ["ELEC-SOS-001", "electricite", "basse", 430, "Agence Sousse"],
        ["GAZ-TUN-001", "gaz", null, 380, "Siege Tunis"],
        ["GAZ-SFX-001", "gaz", null, 620, "Base Sfax"],
        ["GAZ-GAB-001", "gaz", null, 740, "Base Gabes"],
        ["GAZ-SOS-001", "gaz", null, 290, "Atelier Sousse"],
        ["EAU-TUN-001", "eau", null, 1250, "Siege Tunis"],
        ["EAU-SFX-001", "eau", null, 1680, "Base Sfax"],
        ["EAU-SOS-001", "eau", null, 980, "Agence Sousse"],
        ["EAU-GAB-001", "eau", null, 1430, "Depot Gabes"]
    ];
    foreach ($meterDefinitions as [$number, $type, $tension, $average, $local]) {
        $stmt = $conn->prepare("SELECT id_compteur FROM compteur WHERE numero = ? LIMIT 1");
        $stmt->bind_param("s", $number);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            $meters[] = [(int) $existing["id_compteur"], $type, $average];
            continue;
        }
        $installed = "2023-01-15";
        $localId = $localIds[$local];
        $stmt = $conn->prepare("INSERT INTO compteur (numero, type, tension, moyenne_consommation, date_installation, id_local) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdsi", $number, $type, $tension, $average, $installed, $localId);
        $stmt->execute();
        $meters[] = [$conn->insert_id, $type, $average];
    }

    $date = new DateTime("first day of this month");
    $date->modify("-35 months");
    for ($month = 0; $month < 36; $month++) {
        $periode = $date->format("Y-m-d");
        foreach (["electricite", "gaz", "eau"] as $type) {
            $total = 0;
            foreach ($meters as [$idCompteur, $meterType, $average]) {
                if ($meterType === $type) {
                    $variation = 0.82 + (($month * 7 + $idCompteur) % 31) / 100;
                    $total += round($average * $variation, 2);
                }
            }
            $idFacture = getOrCreateFacture($conn, $type, $periode, round($total * 0.42, 2));
            foreach ($meters as [$idCompteur, $meterType, $average]) {
                if ($meterType !== $type) {
                    continue;
                }
                $variation = 0.82 + (($month * 7 + $idCompteur) % 31) / 100;
                addConsumption($conn, $idCompteur, $idFacture, round($average * $variation, 2));
            }
        }
        $date->modify("+1 month");
    }

    $conn->commit();
    echo "Donnees de demonstration ajoutees avec succes.\n";
    echo "Connexion: demo@etap.local / demo123\n";
} catch (Throwable $error) {
    $conn->rollback();
    fwrite(STDERR, "Erreur: " . $error->getMessage() . "\n");
    exit(1);
}
