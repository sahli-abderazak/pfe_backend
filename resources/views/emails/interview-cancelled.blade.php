<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Annulation d'entretien</title>
</head>
<body>
    <h2>Bonjour {{ $interview->candidat_prenom }} {{ $interview->candidat_nom }},</h2>

    <p>
        Nous sommes désolés de vous informer que votre entretien pour le poste de <strong>{{ $interview->poste }}</strong> a été annulé.
    </p>

    <p>
        <strong>Date et heure initialement prévues :</strong> {{ \Carbon\Carbon::parse($interview->date_heure)->format('d/m/Y à H:i') }}<br>
        <strong>Recruteur :</strong> {{ $recruteur->nom_societe }}<br>
    </p>

    <p>
        Nous vous contacterons prochainement pour vous proposer un nouveau créneau si nécessaire.
    </p>

    <p>Nous vous prions de nous excuser pour ce désagrément.</p>

    <p>Cordialement,<br>L'équipe RH</p>
</body>
</html>