<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Entretien Planifié</title>
</head>
<body>
    <h2>Bonjour {{ $interview->candidat_prenom }} {{ $interview->candidat_nom }},</h2>

    <p>
        Vous êtes invité(e) à un entretien pour le poste de <strong>{{ $interview->poste }}</strong>.
    </p>

    <p>
    <strong>Date et heure :</strong> {{ \Carbon\Carbon::parse($interview->date_heure)->format('d/m/Y à H:i') }}<br>
    <strong>Recruteur :</strong> {{ $recruteur->nom_societe }}<br>
    <strong>Email :</strong> {{ $recruteur->email }}<br>
    <strong>Type de l'entretien :</strong> {{ ucfirst($interview->type) }}<br>
    @if($interview->type === 'en ligne')
        <strong>Lien Meet :</strong> <a href="{{ $interview->lien_ou_adresse }}">{{ $interview->lien_ou_adresse }}</a>
    @else
        <strong>Adresse :</strong> {{ $interview->lien_ou_adresse }}
    @endif
</p>


    <p>
        N'hésitez pas à répondre à cet email pour confirmer ou proposer un autre créneau si besoin.
    </p>

    <p>Merci,<br>L'équipe RH</p>
</body>
</html>
